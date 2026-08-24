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
			} else {
				$sanitized[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Mask sensitive values for migration preview API responses.
	 *
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	public static function maskSecrets( array $row ): array {
		$masked = $row;
		foreach ( SensitiveKeys::all() as $key ) {
			if ( ! empty( $masked[ $key ] ) ) {
				$masked[ $key ] = '••••••••';
			}
		}
		return $masked;
	}
}
