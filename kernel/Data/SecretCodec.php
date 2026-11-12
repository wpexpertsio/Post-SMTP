<?php
/**
 * Secret encoding/decoding for legacy postman_options storage.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SecretCodec {

	/**
	 * Decode a secret read from postman_options (unwrap multi-layer base64).
	 *
	 * @param mixed $stored Raw DB value.
	 * @return string
	 */
	public static function decode( $stored ): string {
		$current = (string) $stored;
		if ( '' === $current ) {
			return '';
		}

		while ( true ) {
			$decoded = base64_decode( $current, true );
			if ( false === $decoded || '' === $decoded ) {
				break;
			}
			if ( base64_encode( $decoded ) !== $current ) {
				break;
			}
			$current = $decoded;
		}

		return $current;
	}

	/**
	 * Normalize a secret to exactly one base64 layer for postman_options persistence.
	 *
	 * @param mixed $stored Plaintext or multiply-wrapped base64.
	 * @return string
	 */
	public static function encodeSingleBase64( $stored ): string {
		$plain = self::decode( $stored );
		return '' === $plain ? '' : base64_encode( $plain );
	}

	/**
	 * Repair known secret keys in a postman_options array.
	 *
	 * @param array<string,mixed> $options Options array (modified by reference).
	 */
	/**
	 * Connection row keys stored as single-layer base64 in postman_connections.
	 *
	 * @return string[]
	 */
	public static function connectionSecretKeys(): array {
		return array_merge(
			SensitiveKeys::base64OptionKeys(),
			array(
				'oauth_client_secret',
				'office365_app_password',
				'zohomail_client_secret',
			)
		);
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	public static function encodeConnectionRow( array $row ): array {
		foreach ( self::connectionSecretKeys() as $key ) {
			if ( ! isset( $row[ $key ] ) || '' === (string) $row[ $key ] ) {
				continue;
			}
			$value = (string) $row[ $key ];
			if ( preg_match( '/^•+$/u', $value ) ) {
				continue;
			}
			$row[ $key ] = self::encodeSingleBase64( $value );
		}

		return $row;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	public static function decodeConnectionRow( array $row ): array {
		foreach ( self::connectionSecretKeys() as $key ) {
			if ( ! isset( $row[ $key ] ) || '' === (string) $row[ $key ] ) {
				continue;
			}
			$row[ $key ] = self::decode( $row[ $key ] );
		}

		return $row;
	}

	public static function repairOptionsEncoding( array &$options ): void {
		foreach ( SensitiveKeys::base64OptionKeys() as $key ) {
			if ( ! isset( $options[ $key ] ) || '' === (string) $options[ $key ] ) {
				continue;
			}
			$options[ $key ] = self::encodeSingleBase64( $options[ $key ] );
		}

		foreach ( SensitiveKeys::plaintextOptionKeys() as $key ) {
			if ( ! isset( $options[ $key ] ) || '' === (string) $options[ $key ] ) {
				continue;
			}
			$options[ $key ] = self::decode( $options[ $key ] );
		}
	}
}
