<?php
/**
 * Check Connection tool (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\SensitiveKeys;
use PostSMTP\Kernel\Providers\ProviderRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CheckConnectionService {

	private const HISTORY_OPTION = 'post_smtp_connection_check_history';

	private ConnectionCheckRunner $runner;

	public function __construct() {
		$this->runner = new ConnectionCheckRunner();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		$connections = $this->listConnectionsForUi();
		$history     = $this->loadHistory();

		$rows = array();
		foreach ( $connections as $conn ) {
			$id   = (int) $conn['id'];
			$last = isset( $history[ (string) $id ] ) ? $history[ (string) $id ] : null;
			$rows[] = array_merge(
				$conn,
				array(
					'last_check' => $last ? $this->formatHistoricalCheck( $last ) : null,
				)
			);
		}

		return array(
			'connections'      => $rows,
			'has_connections'  => count( $rows ) > 0,
			'has_configured'   => (bool) array_filter(
				$rows,
				static function ( $row ) {
					return ! empty( $row['is_configured'] );
				}
			),
			'links'            => array(
				'connections' => admin_url( 'admin.php?page=postman#/connections' ),
			),
			'copy'             => array(
				'disclaimer' => __( 'A successful connection check does not guarantee inbox delivery.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @return array{success:bool,message?:string,result?:array<string,mixed>,code?:string}
	 */
	public function runCheck( int $connection_id ): array {
		if ( $connection_id < 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid connection.', 'post-smtp' ),
				'code'    => 'invalid_connection',
			);
		}

		$lock_key = 'post_smtp_conn_check_' . $connection_id;
		if ( get_transient( $lock_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'A connection check is already running for this mailer.', 'post-smtp' ),
				'code'    => 'already_running',
			);
		}

		set_transient( $lock_key, 1, 120 );

		try {
			$row = $this->resolveConnectionRow( $connection_id );
			if ( empty( $row ) ) {
				return array(
					'success' => false,
					'message' => __( 'Connection not found.', 'post-smtp' ),
					'code'    => 'invalid_connection',
				);
			}

			if ( ConnectionRepositoryFactory::isLegacyMode() ) {
				$result = $this->runLegacyCheck( $connection_id );
			} else {
				$slug = (string) ( $row['provider'] ?? '' );
				if ( '' === $slug ) {
					return array(
						'success' => false,
						'message' => __( 'Configure this connection before running a check.', 'post-smtp' ),
						'code'    => 'not_configured',
					);
				}
				$result = $this->runner->run( $connection_id, $row );
			}
			$this->saveHistoryEntry( $connection_id, $result );

			return array(
				'success' => true,
				'result'  => $this->formatHistoricalCheck( $result ),
			);
		} finally {
			delete_transient( $lock_key );
		}
	}

	/**
	 * @param array<string,mixed> $result Raw check result.
	 * @return array<string,mixed>
	 */
	private function formatHistoricalCheck( array $result ): array {
		$checked_at = (string) ( $result['checked_at'] ?? '' );
		$timestamp  = $checked_at ? strtotime( $checked_at ) : false;
		$display    = $timestamp
			? wp_date(
				__( 'M j, Y g:i a', 'post-smtp' ),
				$timestamp,
				wp_timezone()
			)
			: '';

		return array(
			'connection_id'       => (int) ( $result['connection_id'] ?? 0 ),
			'checked_at'          => $checked_at,
			'checked_at_display'  => $display,
			'checked_at_label'    => $display
				? sprintf(
					/* translators: %s: formatted date/time */
					__( 'Last checked %s', 'post-smtp' ),
					$display
				)
				: '',
			'is_historical'       => true,
			'overall_status'      => (string) ( $result['overall_status'] ?? 'none' ),
			'checks'              => $result['checks'] ?? array(),
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function listConnectionsForUi(): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $this->legacyConnections();
		}

		$service     = new ConnectionService();
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $service->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );
		$catalog     = MailerCatalog::all();
		$labels      = array();
		foreach ( $catalog as $item ) {
			if ( ! empty( $item['slug'] ) ) {
				$labels[ (string) $item['slug'] ] = (string) ( $item['label'] ?? $item['slug'] );
			}
		}

		$out = array();
		foreach ( $connections as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$provider = (string) ( $row['provider'] ?? '' );
			if ( '' === $provider ) {
				continue;
			}

			$role_meta = ConnectionService::deriveRole( (int) $index, $connections );
			$title     = trim( (string) ( $row['title'] ?? $row['connection_title'] ?? '' ) );
			$mailer    = $labels[ $provider ] ?? ucwords( str_replace( '_', ' ', $provider ) );
			$label     = $title !== '' ? $title : $mailer;
			$ready     = $this->validateConnectionRow( $row );

			$out[] = array(
				'id'                 => (int) $index,
				'provider'           => $provider,
				'label'              => $label,
				'mailer_label'       => $mailer,
				'role'               => (string) ( $role_meta['role'] ?? 'inactive' ),
				'role_label'         => (string) ( $role_meta['role_label'] ?? '' ),
				'is_primary'         => 'primary' === ( $role_meta['role'] ?? '' ),
				'is_configured'      => $ready['success'],
				'is_usable'          => $ready['success'],
				'account_identifier' => $this->deriveAccountIdentifier( $row ),
				'transport_label'    => $this->runner->transportLabel( $provider, $row ),
			);
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function legacyConnections(): array {
		$configured = false;
		$transport  = 'default';
		$label      = __( 'Primary connection', 'post-smtp' );

		if ( class_exists( '\PostmanTransportRegistry' ) && class_exists( '\PostmanOptions' ) ) {
			$active     = \PostmanTransportRegistry::getInstance()->getActiveTransport();
			$configured = $active && $active->isConfiguredAndReady();
			$transport  = (string) \PostmanOptions::getInstance()->getTransportType();
			$label      = ucwords( str_replace( '_', ' ', $transport ) );
		}

		return array(
			array(
				'id'                 => 0,
				'provider'           => $transport,
				'label'              => $label,
				'mailer_label'       => $label,
				'role'               => 'primary',
				'role_label'         => __( 'Primary', 'post-smtp' ),
				'is_primary'         => true,
				'is_configured'      => $configured,
				'is_usable'          => $configured,
				'account_identifier' => $this->legacyAccountIdentifier(),
				'transport_label'    => 'API' === strtoupper( $transport ) ? 'API' : 'SMTP',
			),
		);
	}

	private function legacyAccountIdentifier(): string {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return '';
		}
		$email = sanitize_email( (string) \PostmanOptions::getInstance()->getMessageSenderEmail() );
		return $email;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private function deriveAccountIdentifier( array $row ): string {
		$email = sanitize_email( (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ) );
		if ( $email ) {
			return $email;
		}

		foreach ( SensitiveKeys::all() as $key ) {
			if ( empty( $row[ $key ] ) || '••••••••' === $row[ $key ] ) {
				continue;
			}
			$value = (string) $row[ $key ];
			$suffix = strlen( $value ) >= 4 ? substr( $value, -4 ) : $value;

			return sprintf(
				/* translators: %s: last four characters of an API key */
				__( 'key ••••%s', 'post-smtp' ),
				$suffix
			);
		}

		$user = (string) ( $row['basic_auth_username'] ?? '' );
		if ( '' !== $user ) {
			return sanitize_text_field( $user );
		}

		return '';
	}

	/**
	 * @return array<string,mixed>
	 */
	private function resolveConnectionRow( int $connection_id ): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $connection_id === 0 ? array( 'provider' => 'legacy' ) : array();
		}

		$repo = ConnectionRepositoryFactory::make();
		$row  = $repo->getConnection( $connection_id );
		return is_array( $row ) ? $row : array();
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array{success:bool,message:string}
	 */
	private function validateConnectionRow( array $row ): array {
		$slug = (string) ( $row['provider'] ?? '' );
		if ( '' === $slug ) {
			return array(
				'success' => false,
				'message' => __( 'Missing mailer type.', 'post-smtp' ),
			);
		}

		$registry = new ProviderRegistry();
		$provider = $registry->create( $slug );
		$result   = $provider->authenticate( $row );

		return array(
			'success' => ! empty( $result['success'] ),
			'message' => (string) ( $result['message'] ?? '' ),
		);
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function loadHistory(): array {
		$stored = get_option( self::HISTORY_OPTION, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * @param array<string,mixed> $result Check result.
	 */
	/**
	 * @return array<string,mixed>
	 */
	private function runLegacyCheck( int $connection_id ): array {
		$checks = array();
		$ready  = false;

		if ( class_exists( '\PostmanTransportRegistry' ) ) {
			$active = \PostmanTransportRegistry::getInstance()->getActiveTransport();
			$ready  = $active && $active->isConfiguredAndReady();
		}

		$checks[] = array(
			'id'     => 'configuration',
			'title'  => __( 'Connection configuration', 'post-smtp' ),
			'status' => $ready ? 'passed' : 'failed',
			'detail' => $ready
				? __( 'Legacy mailer settings are saved.', 'post-smtp' )
				: __( 'Configure your mailer before running a check.', 'post-smtp' ),
		);

		$checks[] = array(
			'id'     => 'ready',
			'title'  => __( 'Ready to send', 'post-smtp' ),
			'status' => $ready ? 'passed' : 'failed',
			'detail' => $ready
				? __( 'No blockers found. Inbox delivery is not guaranteed.', 'post-smtp' )
				: __( 'Fix configuration before sending.', 'post-smtp' ),
		);

		$overall = $ready ? 'passed' : 'failed';

		return array(
			'connection_id'  => $connection_id,
			'checked_at'     => gmdate( 'c' ),
			'overall_status' => $overall,
			'checks'         => $checks,
		);
	}

	private function saveHistoryEntry( int $connection_id, array $result ): void {
		$history                     = $this->loadHistory();
		$history[ (string) $connection_id ] = $result;
		update_option( self::HISTORY_OPTION, $history, false );
	}
}
