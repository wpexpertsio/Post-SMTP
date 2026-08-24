<?php
/**
 * Legacy admin path — frozen Postman wizard.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyAdminLoader {

	public static function init(): void {
		// Legacy Postman bootstrap handles wizard UI; no new components added here.
		add_action(
			'admin_enqueue_scripts',
			static function ( $hook ) {
				if ( false === strpos( (string) $hook, 'postman' ) ) {
					return;
				}
				wp_enqueue_style(
					'post-smtp-legacy-badge',
					POST_SMTP_URL . '/style/post-smtp-legacy-badge.css',
					array(),
					POST_SMTP_VER
				);
			}
		);
	}
}
