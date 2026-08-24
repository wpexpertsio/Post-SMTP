<?php
/**
 * Loads Postman-Connection resolver for migration engine compatibility.
 *
 * @package PostSMTP\Bootstrap
 */

namespace PostSMTP\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyBridge {

	public static function init(): void {
		$resolver = POST_SMTP_PATH . '/Postman/Postman-Connection/PostmanConnectionResolver.php';
		if ( file_exists( $resolver ) && ! class_exists( 'Postman_Connection_Resolver', false ) ) {
			require_once $resolver;
		}
	}
}
