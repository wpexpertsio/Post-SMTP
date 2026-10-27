<?php
/**
 * Modern migration API — wraps legacy MigrationEngine without admin UI hooks.
 *
 * @package PostSMTP\Kernel\Migration
 */

namespace PostSMTP\Kernel\Migration;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Data\SecretCodec;
use PostSMTP\Kernel\Data\SensitiveKeys;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/MigrationEngine.php';

/**
 * Service subclass that exposes migration operations for REST v2.
 */
final class ConnectionMigratorService extends \PostmanFallbackMigration {

	public function __construct() {
		// Skip admin hooks from parent constructor.
	}

	/**
	 * Build a masked preview of connections that migration would create.
	 *
	 * @return array<string,mixed>
	 */
	public function preview(): array {
		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return array(
				'already_migrated' => true,
				'connections'        => ConnectionSchema::maskSecrets(
					(array) get_option( ConnectionSchema::OPTION_CONNECTIONS, array() )
				),
			);
		}

		$options = get_option( ConnectionSchema::OPTION_OPTIONS, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$transport = $options['transport_type'] ?? 'default';
		$preview   = array(
			'already_migrated' => false,
			'transport_type'   => $transport,
			'connections'      => array(),
			'can_migrate'        => (bool) $this->invokePrivateGetter( 'can_update_fallback', array() ),
			'pro_error'          => $this->invokePrivateGetter( 'get_pro_version_error', array() ),
		);

		$primary = array(
			'provider'     => $transport,
			'title'        => ucwords( str_replace( '_', ' ', (string) $transport ) ),
			'sender_email' => $options['sender_email'] ?? '',
			'sender_name'  => $options['sender_name'] ?? '',
		);

		foreach ( SensitiveKeys::all() as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$primary[ $key ] = '••••••••';
			}
		}

		$preview['connections'][] = ConnectionSchema::maskSecrets( $primary );

		if ( ( $options['fallback_smtp_enabled'] ?? 'no' ) === 'yes' && ! empty( $options['fallback_smtp_hostname'] ) ) {
			$preview['connections'][] = array(
				'provider' => 'smtp',
				'title'    => 'Fallback SMTP',
				'hostname' => $options['fallback_smtp_hostname'] ?? '',
				'port'     => $options['fallback_smtp_port'] ?? '',
			);
		}

		return $preview;
	}

	/**
	 * Execute schema migration: options → connections.
	 *
	 * @return array<string,mixed>
	 */
	public function migrate(): array {
		if ( ! $this->invokePrivateGetter( 'can_update_fallback', array() ) ) {
			return array(
				'success' => false,
				'message' => $this->invokePrivateGetter( 'get_pro_version_error', array() ) ?: __( 'Migration requirements not met.', 'post-smtp' ),
			);
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return array(
				'success' => true,
				'message' => __( 'Already on modern storage.', 'post-smtp' ),
			);
		}

		$this->invokePrivate( 'save_mail_connections' );
		$this->invokePrivate( 'update_db_version' );
		$this->invokePrivate( 'store_email_settings' );

		if ( class_exists( '\PostmanOptions' ) ) {
			\PostmanOptions::getInstance()->reload();
		}

		CohortResolver::markMigrated();

		return array(
			'success'     => true,
			'message'     => __( 'Migration completed successfully.', 'post-smtp' ),
			'connections' => get_option( ConnectionSchema::OPTION_CONNECTIONS, array() ),
		);
	}

	/**
	 * Roll back migration within the recovery window.
	 *
	 * @return array<string,mixed>
	 */
	public function rollback(): array {
		return $this->restoreFromBackup();
	}

	/**
	 * Whether a post-migration rollback snapshot is still available.
	 *
	 * @return array<string,mixed>
	 */
	public function rollbackStatus(): array {
		$raw = get_option( ConnectionSchema::OPTION_ROLLBACK, false );

		if ( ! is_array( $raw ) || empty( $raw['data'] ) ) {
			return array(
				'has_backup'       => false,
				'expires_at'       => null,
				'can_full_revert'  => false,
				'cohort'           => CohortResolver::current(),
				'is_legacy_storage' => ConnectionRepositoryFactory::isLegacyMode(),
			);
		}

		$expires = isset( $raw['expires'] ) ? (int) $raw['expires'] : 0;
		$valid   = $expires > time();

		return array(
			'has_backup'        => $valid,
			'expires_at'        => $valid ? gmdate( 'Y-m-d H:i:s', $expires ) : null,
			'can_full_revert'   => $valid,
			'cohort'            => CohortResolver::current(),
			'is_legacy_storage' => ConnectionRepositoryFactory::isLegacyMode(),
		);
	}

	/**
	 * Return to legacy admin. Restores settings when backup exists.
	 *
	 * @param bool $force_ui_only Revert UI/storage even without a backup snapshot.
	 * @return array<string,mixed>
	 */
	public function revertToLegacy( bool $force_ui_only = false ): array {
		$status = $this->rollbackStatus();

		if ( $status['can_full_revert'] ) {
			return $this->restoreFromBackup();
		}

		if ( ! $force_ui_only ) {
			return array(
				'success' => false,
				'message' => __(
					'No rollback snapshot is available. Confirm UI-only revert to switch back without restoring deleted credentials.',
					'post-smtp'
				),
				'code'    => 'no_backup',
			);
		}

		delete_option( ConnectionSchema::OPTION_CONNECTIONS );
		update_option( ConnectionSchema::OPTION_DB_VERSION, ConnectionSchema::LEGACY_DB_VERSION );
		update_option( 'post_smtp_cohort', CohortResolver::COHORT_LEGACY );

		if ( class_exists( '\PostmanOptions' ) ) {
			\PostmanOptions::getInstance()->reload();
		}

		return array(
			'success' => true,
			'message' => __(
				'Switched back to the legacy admin. Re-check your mailer settings in the setup wizard if email stops working.',
				'post-smtp'
			),
			'mode'    => 'ui_only',
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function restoreFromBackup(): array {
		$backup = $this->invokePrivateGetter( 'get_expiring_option', array( ConnectionSchema::OPTION_ROLLBACK ) );

		if ( false === $backup || ! is_array( $backup ) ) {
			return array(
				'success' => false,
				'message' => __( 'No rollback snapshot available.', 'post-smtp' ),
				'code'    => 'no_backup',
			);
		}

		if ( class_exists( '\Postman_Connection_Resolver' ) ) {
			\Postman_Connection_Resolver::prepare_postman_options_for_legacy_restore( $backup );
		} else {
			SecretCodec::repairOptionsEncoding( $backup );
		}

		update_option( ConnectionSchema::OPTION_OPTIONS, $backup );
		delete_option( ConnectionSchema::OPTION_CONNECTIONS );
		delete_option( ConnectionSchema::OPTION_ROLLBACK );
		update_option( ConnectionSchema::OPTION_DB_VERSION, ConnectionSchema::LEGACY_DB_VERSION );
		update_option( 'post_smtp_cohort', CohortResolver::COHORT_LEGACY );

		if ( class_exists( '\PostmanOptions' ) ) {
			\PostmanOptions::getInstance()->reload();
		}

		return array(
			'success' => true,
			'message' => __( 'Settings restored to legacy storage.', 'post-smtp' ),
			'mode'    => 'full',
		);
	}

	/**
	 * @param string $method Method name.
	 */
	private function invokePrivate( string $method ): void {
		$ref = new \ReflectionMethod( \PostmanFallbackMigration::class, $method );
		$ref->setAccessible( true );
		$ref->invoke( $this );
	}

	/**
	 * @param string        $method Method name.
	 * @param array<int,mixed> $args  Arguments.
	 * @return mixed
	 */
	private function invokePrivateGetter( string $method, array $args ) {
		$ref = new \ReflectionMethod( \PostmanFallbackMigration::class, $method );
		$ref->setAccessible( true );
		return $ref->invoke( $this, ...$args );
	}
}

/**
 * Facade for REST controllers.
 */
final class ConnectionMigrator {

	private ConnectionMigratorService $service;

	public function __construct() {
		$this->service = new ConnectionMigratorService();
	}

	public function preview(): array {
		return $this->service->preview();
	}

	public function migrate(): array {
		return $this->service->migrate();
	}

	public function rollback(): array {
		return $this->service->rollback();
	}

	public function rollbackStatus(): array {
		return $this->service->rollbackStatus();
	}

	public function revertToLegacy( bool $force_ui_only = false ): array {
		return $this->service->revertToLegacy( $force_ui_only );
	}
}
