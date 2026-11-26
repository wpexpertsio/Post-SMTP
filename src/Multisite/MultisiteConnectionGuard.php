<?php
/**
 * Block mailer option writes on child sites when global settings are enabled.
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

use PostSMTP\Kernel\Data\ConnectionSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteConnectionGuard {

	public static function init(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_filter( 'pre_update_option_' . ConnectionSchema::OPTION_CONNECTIONS, array( self::class, 'blockConnections' ), 10, 3 );
		add_filter( 'pre_update_option_' . ConnectionSchema::OPTION_OPTIONS, array( self::class, 'blockLegacyOptions' ), 10, 3 );
		add_filter( 'pre_update_option_' . ConnectionSchema::OPTION_DB_VERSION, array( self::class, 'blockSimpleOption' ), 10, 3 );

		foreach ( array( 'postman_auth_token', 'postman_office365_oauth' ) as $option_name ) {
			add_filter( 'pre_update_option_' . $option_name, array( self::class, 'blockSimpleOption' ), 10, 3 );
		}
	}

	/**
	 * @param mixed $value New value.
	 * @param mixed $old_value Old value.
	 */
	public static function blockConnections( $value, $old_value, $option ) {
		unset( $option );
		if ( ! MultisitePolicy::shouldBlockChildMailerWrites() ) {
			return $value;
		}

		return $old_value;
	}

	/**
	 * @param mixed $value New value.
	 * @param mixed $old_value Old value.
	 */
	public static function blockLegacyOptions( $value, $old_value, $option ) {
		unset( $option );
		if ( ! MultisitePolicy::shouldBlockChildMailerWrites() ) {
			return $value;
		}

		if ( ! MailerOptionKeys::patchTouchesMailerKeys( $old_value, $value ) ) {
			return $value;
		}

		return $old_value;
	}

	/**
	 * @param mixed $value New value.
	 * @param mixed $old_value Old value.
	 */
	public static function blockSimpleOption( $value, $old_value, $option ) {
		unset( $option );
		if ( ! MultisitePolicy::shouldBlockChildMailerWrites() ) {
			return $value;
		}

		return $old_value;
	}
}