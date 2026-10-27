<?php
/**
 * Control-plane fields stored in postman_options after migration.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsSchema {

	/**
	 * Fields that remain in postman_options after modern migration.
	 *
	 * @return string[]
	 */
	public static function controlPlaneFields(): array {
		return array(
			'transport_type',
			'primary_connection',
			'selected_primary',
			'selected_fallback',
			'sender_email',
			'sender_name',
			'envelope_sender',
			'fallback_smtp_enabled',
			'run_mode',
			'logging_enabled',
			'log_level',
			'notification_service',
		);
	}

	/**
	 * Strip credential keys from options, keeping control-plane fields.
	 *
	 * @param array<string,mixed> $options Full options array.
	 * @return array<string,mixed>
	 */
	public static function stripCredentials( array $options ): array {
		foreach ( SensitiveKeys::all() as $key ) {
			unset( $options[ $key ] );
		}
		unset( $options['fallback_smtp_password'], $options['fallback_smtp_hostname'] );
		return $options;
	}
}
