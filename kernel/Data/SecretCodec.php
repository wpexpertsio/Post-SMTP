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
