<?php
/**
 * Single gate for legacy vs modern connection storage.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionRepositoryFactory {

	/**
	 * True when site uses legacy postman_options-only schema.
	 */
	public static function isLegacyMode(): bool {
		$version = get_option( ConnectionSchema::OPTION_DB_VERSION, ConnectionSchema::LEGACY_DB_VERSION );
		return (string) $version !== (string) ConnectionSchema::MODERN_DB_VERSION;
	}

	public static function make(): ConnectionRepositoryInterface {
		if ( self::isLegacyMode() ) {
			return new LegacyConnectionRepository();
		}
		return new ModernConnectionRepository();
	}

	/**
	 * Initialize modern storage for new installs.
	 */
	public static function bootstrapNewInstall(): void {
		if ( false !== get_option( ConnectionSchema::OPTION_DB_VERSION, false ) ) {
			return;
		}

		update_option( ConnectionSchema::OPTION_DB_VERSION, ConnectionSchema::MODERN_DB_VERSION );
		update_option( ConnectionSchema::OPTION_CONNECTIONS, array() );
	}
}
