<?php
/**
 * Loads consolidated kernel libraries (vendor, Zend SMTP, HTMLPurifier).
 *
 * @package PostSMTP\Kernel\Libraries
 */

namespace PostSMTP\Kernel\Libraries;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LibraryLoader {

	private static bool $loaded = false;

	public static function init(): void {
		if ( self::$loaded ) {
			return;
		}

		$kernel = defined( 'POST_SMTP_PATH' ) ? POST_SMTP_PATH : dirname( __DIR__, 2 );

		$paths = array(
			$kernel . '/kernel/Libraries/vendor/autoload.php',
			$kernel . '/kernel/Libraries/vendor/vendor/autoload.php',
			$kernel . '/Postman/Postman-Mail/libs/vendor/autoload.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				break;
			}
		}

		if ( ! class_exists( 'HTMLPurifier_Bootstrap', false ) ) {
			$htmlpurifier        = $kernel . '/kernel/Libraries/htmlpurifier/HTMLPurifier.auto.php';
			$legacy_htmlpurifier = $kernel . '/includes/libs/HTMLPurifier/HTMLPurifier.auto.php';

			if ( file_exists( $htmlpurifier ) ) {
				require_once $htmlpurifier;
			} elseif ( file_exists( $legacy_htmlpurifier ) ) {
				require_once $legacy_htmlpurifier;
			}
		}

		self::$loaded = true;
	}

	/**
	 * Path to Zend mail library used by legacy SMTP transport.
	 */
	public static function zendPath(): string {
		$kernel = defined( 'POST_SMTP_PATH' ) ? POST_SMTP_PATH : dirname( __DIR__, 2 );
		$modern = $kernel . '/kernel/Libraries/zend-smtp';
		if ( is_dir( $modern ) ) {
			return $modern;
		}
		return $kernel . '/Postman/Postman-Mail/Zend-1.12.10';
	}
}
