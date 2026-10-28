<?php
/**
 * Connection list, ordering, fallback toggles, and CRUD.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Providers\ProviderRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionService {

	/**
	 * @return array<string,mixed>
	 */
	public function listForAdmin(): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $this->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );
		$limits      = ConnectionLimits::get();
		$enriched    = array();

		foreach ( $connections as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$masked     = ConnectionSchema::maskSecrets( $row );
			$role_meta  = self::deriveRole( (int) $index, $connections );
			$can_toggle = self::isFallbackSlotIndex( (int) $index, $limits );
			$enriched[] = array_merge(
				$masked,
				$role_meta,
				array(
					'id'                  => (int) $index,
					'can_toggle_fallback' => $can_toggle,
				)
			);
		}

		return array(
			'connections' => $enriched,
			'options'     => $repo->getOptions(),
			'is_legacy'   => ConnectionRepositoryFactory::isLegacyMode(),
			'limits'      => ConnectionLimits::get(),
		);
	}

	/**
	 * Ordered fallback connection indices (index > 0, fallback_enabled), after BC normalization.
	 *
	 * @param array<int,array<string,mixed>> $connections Raw rows.
	 * @param array<string,mixed>            $options     postman_options control plane.
	 * @return array<int,int>
	 */
	public static function getEnabledFallbackIndices( array $connections, array $options ): array {
		$service    = new self();
		$normalized = $service->normalizeOnRead( $connections, $options );
		$indices    = array();
		foreach ( $normalized as $index => $row ) {
			if ( (int) $index > 0 && ! empty( $row['fallback_enabled'] ) ) {
				$indices[] = (int) $index;
			}
		}
		return $indices;
	}

	/**
	 * @param array<int,array<string,mixed>> $connections Raw rows.
	 * @param array<string,mixed>            $options     postman_options control plane.
	 * @return array<int,array<string,mixed>>
	 */
	public function normalizeOnRead( array $connections, array $options ): array {
		$legacy_fallback_on = ( $options['fallback_smtp_enabled'] ?? 'no' ) === 'yes';
		$selected_fallback  = isset( $options['selected_fallback'] ) ? (int) $options['selected_fallback'] : 1;

		foreach ( $connections as $index => &$row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( $index === 0 ) {
				unset( $row['fallback_enabled'] );
				continue;
			}
			if ( array_key_exists( 'fallback_enabled', $row ) ) {
				$row['fallback_enabled'] = self::toBool( $row['fallback_enabled'] );
				continue;
			}
			if ( (int) $index === $selected_fallback && $legacy_fallback_on ) {
				$row['fallback_enabled'] = true;
			} else {
				$row['fallback_enabled'] = false;
			}
		}
		unset( $row );

		return self::enforceFallbackSlots( $connections, ConnectionLimits::get() );
	}

	/**
	 * Rows below the fallback slot band stay inactive until dragged up (Figma manage list).
	 *
	 * @param array<int,array<string,mixed>> $connections Rows.
	 * @param array<string,mixed>            $limits      From ConnectionLimits::get().
	 * @return array<int,array<string,mixed>>
	 */
	private static function enforceFallbackSlots( array $connections, array $limits ): array {
		$max_slot = (int) ( $limits['max_fallback_slot_index'] ?? $limits['max_enabled_fallbacks'] ?? 1 );
		foreach ( $connections as $index => &$row ) {
			if ( ! is_array( $row ) || $index <= 0 ) {
				continue;
			}
			if ( $index > $max_slot ) {
				$row['fallback_enabled'] = false;
			}
		}
		unset( $row );
		return $connections;
	}

	/**
	 * @param int $index Connection index.
	 */
	public static function isFallbackSlotIndex( int $index, ?array $limits = null ): bool {
		if ( $index <= 0 ) {
			return false;
		}
		$limits   = $limits ?? ConnectionLimits::get();
		$max_slot = (int) ( $limits['max_fallback_slot_index'] ?? $limits['max_enabled_fallbacks'] ?? 1 );
		return $index <= $max_slot;
	}

	/**
	 * @param array<int,int> $order Connection indices in new order.
	 * @return array{success:bool,message?:string,connections?:array}
	 */
	public function reorder( array $order ): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		$count       = count( $connections );

		if ( count( $order ) !== $count ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid reorder: connection count mismatch.', 'post-smtp' ),
			);
		}

		$seen = array();
		$new  = array();
		foreach ( $order as $idx ) {
			$idx = (int) $idx;
			if ( $idx < 0 || $idx >= $count || isset( $seen[ $idx ] ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid reorder: unknown connection index.', 'post-smtp' ),
				);
			}
			$seen[ $idx ] = true;
			$new[]        = $connections[ $idx ];
		}

		$options = $repo->getOptions();
		$options['primary_connection'] = 0;
		$options['selected_primary']   = 0;

		$repo->saveConnections( $this->normalizeOnRead( $new, $options ) );
		$repo->saveOptions( $options );

		return array(
			'success'     => true,
			'connections' => $this->listForAdmin()['connections'],
		);
	}

	/**
	 * @return array{success:bool,message?:string,connections?:array}
	 */
	public function setFallbackEnabled( int $index, bool $enabled ): array {
		if ( $index <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Primary connection cannot be toggled as fallback.', 'post-smtp' ),
			);
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $this->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );

		if ( ! isset( $connections[ $index ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Connection not found.', 'post-smtp' ),
			);
		}

		if ( $enabled ) {
			$limits = ConnectionLimits::get();
			if ( ! self::isFallbackSlotIndex( $index, $limits ) ) {
				return array(
					'success' => false,
					'message' => __( 'Drag this connection up in the list before enabling it as a fallback.', 'post-smtp' ),
				);
			}
			$active = self::countEnabledFallbacks( $connections );
			if ( $active >= $limits['max_enabled_fallbacks'] ) {
				return array(
					'success' => false,
					'message' => __( 'Fallback limit reached for your plan.', 'post-smtp' ),
				);
			}
		}

		$connections[ $index ]['fallback_enabled'] = $enabled;
		$connections = self::enforceFallbackSlots( $connections, ConnectionLimits::get() );
		$repo->saveConnections( $connections );

		return array(
			'success'     => true,
			'connections' => $this->listForAdmin()['connections'],
		);
	}

	/**
	 * @param array<string,mixed> $row Connection payload.
	 * @return array{success:bool,message?:string,connections?:array}
	 */
	public function create( array $row ): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $this->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );
		$limits      = ConnectionLimits::get();

		if ( count( $connections ) >= $limits['max_connections'] ) {
			return array(
				'success' => false,
				'message' => __( 'Connection limit reached for your plan.', 'post-smtp' ),
			);
		}

		$sanitized = ConnectionSchema::sanitizeRow( $row );
		$validate  = $this->validateRow( $sanitized, $connections, null );
		if ( ! $validate['success'] ) {
			return $validate;
		}

		if ( count( $connections ) > 0 ) {
			$new_index = count( $connections );
			$in_slot   = self::isFallbackSlotIndex( $new_index, $limits );
			$sanitized['fallback_enabled'] = $in_slot
				&& self::countEnabledFallbacks( $connections ) < $limits['max_enabled_fallbacks'];
		}

		$auth = $this->authenticateRow( $sanitized );
		if ( ! $auth['success'] ) {
			return $auth;
		}

		$connections[] = $sanitized;
		$connections   = self::enforceFallbackSlots( $connections, $limits );
		$repo->saveConnections( $connections );

		if ( count( $connections ) === 1 ) {
			$options = $repo->getOptions();
			$options['primary_connection'] = 0;
			$options['selected_primary']   = 0;
			$repo->saveOptions( $options );
		}

		return array(
			'success'     => true,
			'message'     => __( 'Connection saved.', 'post-smtp' ),
			'connections' => $this->listForAdmin()['connections'],
		);
	}

	/**
	 * @param array<string,mixed> $row Connection payload.
	 * @return array{success:bool,message?:string,connections?:array}
	 */
	public function update( int $index, array $row ): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $this->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );

		if ( ! isset( $connections[ $index ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Connection not found.', 'post-smtp' ),
			);
		}

		$merged = array_merge( $connections[ $index ], $row );
		$merged = $this->preserveMaskedSecrets( $connections[ $index ], $merged );
		$sanitized = ConnectionSchema::sanitizeRow( $merged );
		$validate  = $this->validateRow( $sanitized, $connections, $index );
		if ( ! $validate['success'] ) {
			return $validate;
		}

		if ( 0 === $index ) {
			unset( $sanitized['fallback_enabled'] );
		}

		$auth = $this->authenticateRow( $sanitized );
		if ( ! $auth['success'] ) {
			return $auth;
		}

		$connections[ $index ] = $sanitized;
		$repo->saveConnections( $connections );

		return array(
			'success'     => true,
			'message'     => __( 'Connection saved.', 'post-smtp' ),
			'connections' => $this->listForAdmin()['connections'],
		);
	}

	/**
	 * @return array{success:bool,message?:string,connections?:array}
	 */
	public function delete( int $index ): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();

		if ( ! isset( $connections[ $index ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Connection not found.', 'post-smtp' ),
			);
		}

		array_splice( $connections, $index, 1 );
		$options = $repo->getOptions();
		$options['primary_connection'] = 0;
		$options['selected_primary']   = 0;

		$repo->saveConnections( $this->normalizeOnRead( $connections, $options ) );
		$repo->saveOptions( $options );

		return array(
			'success'     => true,
			'connections' => $this->listForAdmin()['connections'],
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $connections All rows.
	 * @return array{role:string,fallback_priority:?int,role_label:string}
	 */
	public static function deriveRole( int $index, array $connections ): array {
		if ( 0 === $index ) {
			return array(
				'role'              => 'primary',
				'fallback_priority' => null,
				'role_label'        => __( 'Primary', 'post-smtp' ),
			);
		}

		$row = $connections[ $index ] ?? array();
		if ( empty( $row['fallback_enabled'] ) ) {
			return array(
				'role'              => 'inactive',
				'fallback_priority' => null,
				'role_label'        => __( 'Inactive', 'post-smtp' ),
			);
		}

		$priority = 0;
		for ( $i = 1; $i <= $index; $i++ ) {
			$other = $connections[ $i ] ?? array();
			if ( ! empty( $other['fallback_enabled'] ) ) {
				++$priority;
			}
		}

		return array(
			'role'              => 'fallback',
			'fallback_priority' => $priority,
			'role_label'        => sprintf(
				/* translators: %d: fallback priority number */
				__( 'Fallback %d', 'post-smtp' ),
				$priority
			),
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $connections Rows.
	 */
	private static function countEnabledFallbacks( array $connections ): int {
		$count = 0;
		foreach ( $connections as $index => $row ) {
			if ( $index > 0 && ! empty( $row['fallback_enabled'] ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * @param array<int,array<string,mixed>> $connections Existing.
	 * @param int|null                       $skip_index    Index being updated.
	 * @return array{success:bool,message?:string}
	 */
	private function validateRow( array $row, array $connections, ?int $skip_index ): array {
		$title = trim( (string) ( $row['title'] ?? $row['connection_title'] ?? '' ) );
		if ( '' === $title ) {
			return array(
				'success' => false,
				'message' => __( 'Connection title is required.', 'post-smtp' ),
			);
		}

		foreach ( $connections as $idx => $existing ) {
			if ( null !== $skip_index && (int) $idx === $skip_index ) {
				continue;
			}
			$existing_title = trim( (string) ( $existing['title'] ?? $existing['connection_title'] ?? '' ) );
			if ( $existing_title !== '' && strcasecmp( $existing_title, $title ) === 0 ) {
				return array(
					'success' => false,
					'message' => __( 'Connection title must be unique.', 'post-smtp' ),
				);
			}
		}

		$email = (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' );
		if ( '' !== $email && ! is_email( $email ) ) {
			return array(
				'success' => false,
				'message' => __( 'From email address is invalid.', 'post-smtp' ),
			);
		}

		return array( 'success' => true );
	}

	/**
	 * @param array<string,mixed> $row Sanitized row.
	 * @return array{success:bool,message?:string}
	 */
	private function authenticateRow( array $row ): array {
		$slug     = (string) ( $row['provider'] ?? '' );
		$registry = new ProviderRegistry();
		$provider = $registry->create( $slug );
		$result   = $provider->authenticate( $row );
		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => $result['message'] ?? __( 'Provider validation failed.', 'post-smtp' ),
			);
		}
		return array( 'success' => true );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	/**
	 * @param array<string,mixed> $existing Stored row.
	 * @param array<string,mixed> $incoming   Request payload.
	 * @return array<string,mixed>
	 */
	private function preserveMaskedSecrets( array $existing, array $incoming ): array {
		foreach ( $incoming as $key => $value ) {
			if ( is_string( $value ) && preg_match( '/^•+$/u', $value ) ) {
				$incoming[ $key ] = $existing[ $key ] ?? '';
			}
		}
		return $incoming;
	}

	/**
	 * @param mixed $value Raw value.
	 */
	private static function toBool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
		}
		return (bool) $value;
	}
}
