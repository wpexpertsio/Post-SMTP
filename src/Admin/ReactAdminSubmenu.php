<?php
/**
 * WordPress admin submenu items for the revamp (Help, Mobile App only).
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReactAdminSubmenu {

	public const SLUG_HELP       = 'post_smtp_help_docs';
	public const SLUG_MOBILE_APP = 'post_smtp_mobile_app';

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'prepareAdminMenu' ), 1 );
		add_action( 'admin_menu', array( self::class, 'trimAddonsSubmenu' ), PHP_INT_MAX );
		// After LegacyRevertPage (99); before late legacy registrars.
		add_action( 'admin_menu', array( self::class, 'registerSubmenus' ), 101 );
		add_action( 'admin_menu', array( self::class, 'trimUnwantedSubmenus' ), PHP_INT_MAX );
		// After Freemius / upgrade hooks (e.g. PostmanSuggestProSocket @ 10000000000).
		add_action( 'admin_menu', array( self::class, 'finalizeRevampSubmenu' ), 10000000001 );
		add_filter( 'submenu_file', array( self::class, 'highlightSubmenu' ) );
	}

	/**
	 * Re-check cohort/caps at menu build time (multisite subsites often differ from init).
	 */
	public static function prepareAdminMenu(): void {
		if ( is_multisite() ) {
			if ( class_exists( '\PostSMTP\Multisite\MultisiteAdminExperience' ) ) {
				\PostSMTP\Multisite\MultisiteAdminExperience::alignChildCohortForCurrentBlog();
				CohortResolver::syncCohortFromStorage();
			}
			if ( class_exists( '\PostSMTP\Multisite\MultisiteCapabilities' ) ) {
				\PostSMTP\Multisite\MultisiteCapabilities::grantIfNeeded();
			}
		}
	}

	/**
	 * Revamp wp-admin flyout: Dashboard (core) + Help + Mobile + optional Revert to Legacy.
	 *
	 * @return array<int,array{slug:string,label:string,route:string,cap:string}>
	 */
	public static function revampMenuItems(): array {
		$cap = \Postman::MANAGE_POSTMAN_CAPABILITY_NAME;

		return array(
			array(
				'slug'  => self::SLUG_HELP,
				'label' => __( 'Help and Docs', 'post-smtp' ),
				'route' => 'help-docs',
				'cap'   => $cap,
			),
			array(
				'slug'  => self::SLUG_MOBILE_APP,
				'label' => __( 'Mobile App', 'post-smtp' ),
				'route' => 'mobile-app',
				'cap'   => $cap,
			),
		);
	}

	/**
	 * @return string[]
	 */
	/**
	 * Slugs that load the React dashboard shell (not Revert to Legacy).
	 *
	 * @return string[]
	 */
	public static function reactShellPageSlugs(): array {
		$slugs = array( 'postman' );
		foreach ( self::revampMenuItems() as $item ) {
			$slugs[] = $item['slug'];
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Allowed Post SMTP wp-admin submenu slugs on revamp (whitelist for trim/finalize).
	 *
	 * @return string[]
	 */
	public static function protectedSubmenuSlugs(): array {
		$slugs = self::reactShellPageSlugs();

		if ( CohortResolver::current() === CohortResolver::COHORT_MIGRATED ) {
			$slugs[] = LegacyRevertPage::PAGE_SLUG;
		}

		return array_values( array_unique( $slugs ) );
	}

	public static function routeForPageSlug( string $page_slug ): ?string {
		foreach ( self::revampMenuItems() as $item ) {
			if ( $item['slug'] === $page_slug ) {
				return $item['route'];
			}
		}

		return null;
	}

	public static function pageSlugForRoute( string $route ): ?string {
		$route = trim( $route, '/' );
		if ( '' === $route ) {
			return 'postman';
		}

		foreach ( self::revampMenuItems() as $item ) {
			if ( trim( (string) $item['route'], '/' ) === $route ) {
				return $item['slug'];
			}
		}

		return null;
	}

	public static function dashboardUrlForRoute( string $route ): string {
		return add_query_arg(
			array(
				'page'     => 'postman',
				'ps-route' => $route,
			),
			admin_url( 'admin.php' )
		);
	}

	public static function registerSubmenus(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		self::ensureRevampSubmenuSlugsRegistered();
	}

	private static function ensureRevampSubmenuSlugsRegistered(): void {
		self::ensureDashboardSubmenu();

		global $submenu;

		$present = array();
		if ( isset( $submenu['postman'] ) && is_array( $submenu['postman'] ) ) {
			foreach ( $submenu['postman'] as $item ) {
				if ( is_array( $item ) && isset( $item[2] ) ) {
					$present[ (string) $item[2] ] = true;
				}
			}
		}

		foreach ( self::revampMenuItems() as $item ) {
			if ( isset( $present[ $item['slug'] ] ) ) {
				continue;
			}

			add_submenu_page(
				'postman',
				$item['label'],
				$item['label'],
				$item['cap'],
				$item['slug'],
				array( self::class, 'renderRedirectPlaceholder' )
			);
		}
	}

	/**
	 * WordPress may drop the duplicate parent slug from the flyout when other submenus exist.
	 */
	private static function ensureDashboardSubmenu(): void {
		global $submenu;

		if ( isset( $submenu['postman'] ) && is_array( $submenu['postman'] ) ) {
			foreach ( $submenu['postman'] as $item ) {
				if ( is_array( $item ) && isset( $item[2] ) && 'postman' === (string) $item[2] ) {
					return;
				}
			}
		}

		$page_title = sprintf(
			/* translators: %s: plugin name */
			__( '%s Setup', 'post-smtp' ),
			__( 'Post SMTP', 'post-smtp' )
		);

		add_submenu_page(
			'postman',
			$page_title,
			__( 'Dashboard', 'post-smtp' ),
			\Postman::MANAGE_POSTMAN_CAPABILITY_NAME,
			'postman',
			array( self::class, 'renderDashboardSubmenu' )
		);
	}

	private static function reorderDashboardFirst(): void {
		global $submenu;

		if ( ! isset( $submenu['postman'] ) || ! is_array( $submenu['postman'] ) ) {
			return;
		}

		$dashboard = null;
		$rest      = array();

		foreach ( $submenu['postman'] as $item ) {
			if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
				$rest[] = $item;
				continue;
			}
			if ( 'postman' === (string) $item[2] ) {
				$dashboard = $item;
				continue;
			}
			$rest[] = $item;
		}

		if ( null !== $dashboard ) {
			$submenu['postman'] = array_merge( array( $dashboard ), $rest );
		}
	}

	/**
	 * Whitelist-only: drop every Post SMTP submenu except allowed revamp items.
	 */
	public static function applyRevampSubmenuWhitelist(): void {
		global $submenu;

		if ( ! isset( $submenu['postman'] ) || ! is_array( $submenu['postman'] ) ) {
			return;
		}

		$keep = array_flip( self::protectedSubmenuSlugs() );

		foreach ( $submenu['postman'] as $index => $item ) {
			if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
				unset( $submenu['postman'][ $index ] );
				continue;
			}

			$slug = (string) $item[2];
			if ( ! isset( $keep[ $slug ] ) ) {
				unset( $submenu['postman'][ $index ] );
			}
		}

		$submenu['postman'] = array_values( $submenu['postman'] );
	}

	public static function trimUnwantedSubmenus(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		self::applyRevampSubmenuWhitelist();
		self::reorderDashboardFirst();
	}

	public static function finalizeRevampSubmenu(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		self::ensureRevampSubmenuSlugsRegistered();
		self::applyRevampSubmenuWhitelist();
		self::reorderDashboardFirst();
	}

	/**
	 * Remove Freemius Add-Ons submenu on legacy and revamp.
	 */
	public static function trimAddonsSubmenu(): void {
		global $submenu;

		if ( ! isset( $submenu['postman'] ) || ! is_array( $submenu['postman'] ) ) {
			return;
		}

		foreach ( $submenu['postman'] as $index => $item ) {
			if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
				continue;
			}

			$slug  = (string) $item[2];
			$label = isset( $item[0] ) ? wp_strip_all_tags( (string) $item[0] ) : '';

			if ( 'addons' === $slug || 'Add-Ons' === $label ) {
				unset( $submenu['postman'][ $index ] );
			}
		}
	}

	/**
	 * @param string|null $submenu_file Current submenu file.
	 * @return string|null
	 */
	public static function highlightSubmenu( $submenu_file ) {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return $submenu_file;
		}

		if ( ! isset( $_GET['page'] ) || 'postman' !== $_GET['page'] ) {
			return $submenu_file;
		}

		$route = isset( $_GET['ps-route'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) ) : '';
		if ( '' === $route ) {
			return $submenu_file;
		}

		$slug = self::pageSlugForRoute( $route );

		return $slug ? $slug : $submenu_file;
	}

	public static function renderDashboardSubmenu(): void {
		// React root renders in in_admin_header; legacy wrap is hidden on revamp.
	}

	public static function renderRedirectPlaceholder(): void {
		// Redirects run in AdminExperienceGuard::admin_init.
		echo '<div class="wrap"><p>' . esc_html__( 'Redirecting…', 'post-smtp' ) . '</p></div>';
	}

	public static function helpDocsDashboardUrl(): string {
		return self::dashboardUrlForRoute( 'help-docs' );
	}

	public static function helpDocsUrl(): string {
		return self::helpDocsDashboardUrl();
	}

	public static function mobileAppDashboardUrl(): string {
		return self::dashboardUrlForRoute( 'mobile-app' );
	}

	public static function reportsDashboardUrl(): string {
		return self::dashboardUrlForRoute( 'reports' );
	}

	public static function settingsDashboardUrl(): string {
		return self::dashboardUrlForRoute( 'settings/general' );
	}
}