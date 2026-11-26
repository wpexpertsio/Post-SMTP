<?php
/**
 * postman_options keys for email logging (per-site log data; settings copied on multisite sync).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogOptionKeys {

	public const SAVE_MESSAGE_OPTION = 'mail_log_save_message';

	/**
	 * @return string[]
	 */
	public static function keys(): array {
		return array(
			\PostmanOptions::MAIL_LOG_ENABLED_OPTION,
			\PostmanOptions::MAIL_LOG_MAX_ENTRIES,
			\PostmanOptions::TRANSCRIPT_SIZE,
			self::SAVE_MESSAGE_OPTION,
		);
	}

	/**
	 * @param array<string,mixed> $source postman_options from main site.
	 * @return array<string,mixed>
	 */
	public static function extractValues( array $source ): array {
		$patch = array();
		foreach ( self::keys() as $key ) {
			if ( array_key_exists( $key, $source ) ) {
				$patch[ $key ] = $source[ $key ];
			}
		}

		return $patch;
	}
}