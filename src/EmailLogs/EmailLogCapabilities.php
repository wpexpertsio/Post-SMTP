<?php
/**
 * Email log capability checks.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogCapabilities {

	public static function can_manage(): bool {
		if ( ! class_exists( '\Postman' ) ) {
			return current_user_can( 'manage_options' );
		}

		return current_user_can( \Postman::MANAGE_POSTMAN_CAPABILITY_LOGS );
	}
}
