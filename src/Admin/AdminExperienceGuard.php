<?php
/**
 * Blocks cross-cohort admin URLs (legacy vs revamp).
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminExperienceGuard {

	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'handleAdminInitRedirects' ), 1 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueLegacyHashGuard' ) );
	}

	public static function handleAdminInitRedirects(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( CohortResolver::usesReactAdmin() ) {
			self::guardMigratedExperience( $page );
			return;
		}

		self::guardLegacyExperience( $page );
	}

	private static function guardMigratedExperience( string $page ): void {
		if ( ReactAdminLoader::MIGRATION_PAGE_SLUG === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=postman' ) );
			exit;
		}

		if ( ReactAdminSubmenu::SLUG_HELP === $page ) {
			wp_safe_redirect( ReactAdminSubmenu::helpDocsUrl() );
			exit;
		}

		if ( ReactAdminSubmenu::SLUG_MOBILE_APP === $page ) {
			wp_safe_redirect( ReactAdminSubmenu::mobileAppDashboardUrl() );
			exit;
		}

		$legacy_pages = array(
			'postman/configuration'        => 'settings/general',
			'postman/configuration_wizard' => 'setup-wizard',
		);

		if ( isset( $legacy_pages[ $page ] ) ) {
			wp_safe_redirect(
				admin_url( 'admin.php?page=postman#/' . $legacy_pages[ $page ] )
			);
			exit;
		}
	}

	private static function guardLegacyExperience( string $page ): void {
		if ( 'postman' === $page && ! empty( $_GET['ps-route'] ) ) {
			$route = sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) );
			if ( self::shouldAllowPsRouteOnPostman( $route ) ) {
				return;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=postman' ) );
			exit;
		}

		if ( ReactAdminSubmenu::SLUG_MOBILE_APP === $page
			|| ReactAdminSubmenu::SLUG_HELP === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=postman' ) );
			exit;
		}
	}

	/**
	 * Revamp Connections OAuth returns use ps-route (and hash routes under connections/*).
	 */
	private static function shouldAllowPsRouteOnPostman( string $route ): bool {
		if ( str_starts_with( $route, 'connections/' ) || str_starts_with( $route, 'setup-wizard' ) ) {
			return true;
		}

		if ( isset( $_GET['success'] ) || isset( $_GET['msg'] ) ) {
			return true;
		}

		if ( isset( $_GET['office365_connect_tab'] ) || isset( $_GET['gmail_connect_tab'] ) ) {
			return true;
		}

		return false;
	}

	public static function enqueueLegacyHashGuard( string $hook ): void {
		if ( CohortResolver::usesReactAdmin() ) {
			return;
		}

		if ( 'toplevel_page_postman' !== $hook ) {
			return;
		}

		wp_add_inline_script(
			'post-smtp-dashboard',
			"(function(){var h=window.location.hash||'';if(/^#\\/(connections|email-logs|notifications|tools|mobile-app|help-docs|reports|settings)/.test(h)){window.location.replace(window.location.pathname+window.location.search);}})();",
			'before'
		);
	}
}
