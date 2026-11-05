<?php
/**
 * Normalize stored log headers for wp_mail() resend.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogResendHeaders {

	/**
	 * Headers handled outside custom headers (connection From, wp_mail to/subject/body).
	 *
	 * @var list<string>
	 */
	private const SKIP_NAMES = array(
		'from',
		'to',
		'subject',
		'mime-version',
		'date',
		'message-id',
		'content-type',
		'content-transfer-encoding',
	);

	/**
	 * @param mixed $raw Stored original_headers (string, serialized array, or header lines).
	 * @return list<string> Header lines for wp_mail().
	 */
	public static function for_wp_mail( $raw ): array {
		if ( empty( $raw ) ) {
			return array();
		}

		if ( is_string( $raw ) && is_serialized( $raw ) ) {
			$raw = unserialize( $raw, array( 'allowed_classes' => false ) );
		}

		$lines = self::expand_to_lines( $raw );

		return self::filter_lines( $lines );
	}

	/**
	 * @param mixed $raw Raw value.
	 * @return list<string>
	 */
	private static function expand_to_lines( $raw ): array {
		if ( is_string( $raw ) ) {
			return self::split_header_block( $raw );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$lines = array();
		foreach ( $raw as $header ) {
			if ( is_array( $header ) && isset( $header['name'], $header['content'] ) ) {
				$lines[] = trim( (string) $header['name'] ) . ': ' . trim( (string) $header['content'] );
				continue;
			}
			if ( is_string( $header ) ) {
				foreach ( self::split_header_block( $header ) as $line ) {
					$lines[] = $line;
				}
			}
		}

		return $lines;
	}

	/**
	 * @return list<string>
	 */
	private static function split_header_block( string $block ): array {
		$block = str_replace( array( "\r\n", "\r" ), "\n", $block );
		$parts = explode( "\n", $block );
		$lines = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( '' !== $part ) {
				$lines[] = $part;
			}
		}
		return $lines;
	}

	/**
	 * @param list<string> $lines Header lines.
	 * @return list<string>
	 */
	private static function filter_lines( array $lines ): array {
		$out = array();
		foreach ( $lines as $line ) {
			if ( false === strpos( $line, ':' ) ) {
				continue;
			}

			list( $name, $value ) = explode( ':', $line, 2 );
			$name  = trim( $name );
			$value = trim( preg_replace( '/[\r\n]+/', ' ', (string) $value ) );

			if ( '' === $name || '' === $value ) {
				continue;
			}

			if ( in_array( strtolower( $name ), self::SKIP_NAMES, true ) ) {
				continue;
			}

			$out[] = $name . ': ' . $value;
		}

		return $out;
	}
}
