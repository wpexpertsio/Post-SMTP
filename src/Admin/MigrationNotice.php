<?php
/**
 * Legacy admin notice for migration promo on non-dashboard Post SMTP screens.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MigrationNotice {

	public static function init(): void {
		add_action( 'admin_notices', array( self::class, 'render' ) );
	}

	public static function render(): void {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'postman' !== $page && ! str_starts_with( $page, 'postman/' ) ) {
			return;
		}

		MigrationPromoBanner::renderAdminNotice();
	}
}
