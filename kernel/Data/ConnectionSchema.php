<?php
/**
 * Modern postman_connections schema constants and helpers.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionSchema {

	public const OPTION_CONNECTIONS = 'postman_connections';
	public const OPTION_OPTIONS     = 'postman_options';
	public const OPTION_DB_VERSION  = 'postman_db_version';
	public const OPTION_ROLLBACK    = 'deleted_email_settings';
	public const MODERN_DB_VERSION  = '1.0.2';
	public const LEGACY_DB_VERSION  = '1.0.1';

	/**
	 * Base fields present on every connection row.
	 *
	 * @return string[]
	 */
	public static function baseFields(): array {
		return array(
			'provider',
			'title',
			'sender_email',
			'sender_name',
			'envelope_sender',
			'priority',
			'fallback_enabled',
		);
	}

	/**
	 * Sanitize a connection row for storage.
	 *
	 * @param array<string,mixed> $row Raw connection row.
	 * @return array<string,mixed>
	 */
	public static function sanitizeRow( array $row ): array {
		$sanitized = array();

		foreach ( $row as $key => $value ) {
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $value;
				continue;
			}

			if ( in_array( $key, array( 'sender_email', 'envelope_sender', 'from_email' ), true ) ) {
				$sanitized[ $key ] = sanitize_email( (string) $value );
			} elseif ( in_array( $key, array( 'port', 'priority' ), true ) ) {
				$sanitized[ $key ] = absint( $value );
			} elseif ( 'fallback_enabled' === $key || str_contains( $key, 'prevent_sender' ) ) {
				$sanitized[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} else {
				$sanitized[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		self::syncCanonicalFromConfigureFormFields( $sanitized, $row );

		if ( isset( $sanitized['office365_connect_tab'] ) ) {
			$tab = sanitize_key( (string) $sanitized['office365_connect_tab'] );
			$sanitized['office365_connect_tab'] = in_array( $tab, array( 'one_click', 'manual' ), true ) ? $tab : '';
		}

		if ( isset( $sanitized['gmail_connect_tab'] ) ) {
			$tab = sanitize_key( (string) $sanitized['gmail_connect_tab'] );
			$sanitized['gmail_connect_tab'] = in_array( $tab, array( 'one_click', 'manual' ), true ) ? $tab : '';
		}

		return $sanitized;
	}

	/**
	 * Revamp Connections UI edits connection_title / from_email / from_name; storage uses title / sender_*.
	 * When those form keys are present on save, they win over stale canonical fields in the payload.
	 *
	 * @param array<string,mixed> $sanitized Output of field sanitization.
	 * @param array<string,mixed> $raw       Raw request row (before sanitize).
	 */
	private static function syncCanonicalFromConfigureFormFields( array &$sanitized, array $raw ): void {
		if ( array_key_exists( 'connection_title', $raw ) ) {
			$connection_title = trim( (string) ( $sanitized['connection_title'] ?? '' ) );
			if ( '' !== $connection_title ) {
				$sanitized['title'] = $connection_title;
			}
		} elseif ( ! empty( $sanitized['connection_title'] ) && empty( $sanitized['title'] ) ) {
			$sanitized['title'] = $sanitized['connection_title'];
		}

		if ( array_key_exists( 'from_email', $raw ) ) {
			$from_email = trim( (string) ( $sanitized['from_email'] ?? '' ) );
			if ( '' !== $from_email ) {
				$sanitized['sender_email'] = $from_email;
			}
		} elseif ( ! empty( $sanitized['from_email'] ) && empty( $sanitized['sender_email'] ) ) {
			$sanitized['sender_email'] = $sanitized['from_email'];
		}

		if ( array_key_exists( 'from_name', $raw ) ) {
			$from_name = trim( (string) ( $sanitized['from_name'] ?? '' ) );
			if ( '' !== $from_name ) {
				$sanitized['sender_name'] = $from_name;
			}
		} elseif ( ! empty( $sanitized['from_name'] ) && empty( $sanitized['sender_name'] ) ) {
			$sanitized['sender_name'] = $sanitized['from_name'];
		}
	}

	/**
	 * Mask sensitive values for migration preview API responses.
	 *
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	public static function maskSecrets( array $row ): array {
		$masked = $row;
		foreach ( SensitiveKeys::maskKeys() as $key ) {
			if ( ! empty( $masked[ $key ] ) ) {
				$masked[ $key ] = '••••••••';
			}
		}
		return $masked;
	}

	/**
	 * Read postman_connections tolerating corrupted serialized options (Local Live Link).
	 *
	 * @return array<int|string,array<string,mixed>>
	 */
	public static function readConnectionsOption(): array {
		$stored = get_option( self::OPTION_CONNECTIONS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = is_string( $stored ) ? maybe_unserialize( $stored ) : array();
		}
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$rows = array();
		foreach ( $stored as $index => $row ) {
			if ( is_array( $row ) ) {
				$rows[ $index ] = $row;
			}
		}

		return $rows;
	}

	/**
	 * @param array<int|string,array<string,mixed>> $connections Connection rows.
	 */
	public static function writeConnectionsOption( array $connections ): bool {
		$saved = update_option( self::OPTION_CONNECTIONS, $connections, false );

		if ( $saved && self::connectionsPayloadMatchesOption( $connections, self::readConnectionsOption() ) ) {
			return true;
		}

		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return (bool) $saved;
		}

		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
				self::OPTION_CONNECTIONS
			)
		);

		$serialized = maybe_serialize( $connections );
		if ( $exists > 0 ) {
			$wpdb->update(
				$wpdb->options,
				array( 'option_value' => $serialized ),
				array( 'option_name' => self::OPTION_CONNECTIONS ),
				array( '%s' ),
				array( '%s' )
			);
		} else {
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => self::OPTION_CONNECTIONS,
					'option_value' => $serialized,
					'autoload'     => 'no',
				),
				array( '%s', '%s', '%s' )
			);
		}

		wp_cache_delete( self::OPTION_CONNECTIONS, 'options' );

		return true;
	}

	/**
	 * @param array<int|string,array<string,mixed>> $expected Intended payload.
	 * @param array<int|string,array<string,mixed>> $actual   Value read back from the DB.
	 */
	private static function connectionsPayloadMatchesOption( array $expected, array $actual ): bool {
		if ( count( $expected ) !== count( $actual ) ) {
			return false;
		}

		foreach ( $expected as $index => $row ) {
			if ( ! isset( $actual[ $index ] ) || ! is_array( $actual[ $index ] ) ) {
				return false;
			}
			if ( wp_json_encode( $row ) !== wp_json_encode( $actual[ $index ] ) ) {
				return false;
			}
		}

		return true;
	}
}
