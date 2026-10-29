<?php
/**
 * WordPress admin submenu items for the revamp (Help, Mobile App).
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReactAdminSubmenu {

	public const SLUG_HELP      = 'post_smtp_help_docs';
	public const SLUG_MOBILE_APP  = 'post_smtp_mobile_app';

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'trimAddonsSubmenu' ), PHP_INT_MAX );

		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'registerSubmenus' ), 90 );
		add_action( 'admin_menu', array( self::class, 'trimUnwantedSubmenus' ), PHP_INT_MAX );
		add_filter( 'submenu_file', array( self::class, 'highlightSubmenu' ) );
	}

	public static function registerSubmenus(): void {
		$cap = \Postman::MANAGE_POSTMAN_CAPABILITY_NAME;

		add_submenu_page(
			'postman',
			__( 'Help and Docs', 'post-smtp' ),
			__( 'Help and Docs', 'post-smtp' ),
			$cap,
			self::SLUG_HELP,
			array( self::class, 'renderRedirectPlaceholder' )
		);

		add_submenu_page(
			'postman',
			__( 'Mobile App', 'post-smtp' ),
			__( 'Mobile App', 'post-smtp' ),
			$cap,
			self::SLUG_MOBILE_APP,
			array( self::class, 'renderRedirectPlaceholder' )
		);
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

	public static function trimUnwantedSubmenus(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		global $submenu;

		if ( ! isset( $submenu['postman'] ) || ! is_array( $submenu['postman'] ) ) {
			return;
		}

		$remove_slugs = array(
			'postman/configuration',
			'postman_email_log',
			'post_smtp_extensions',
			'contact',
			'wp-support-forum',
			'pricing',
			'addons',
		);

		$remove_labels = array(
			'Contact Us',
			'Support Forum',
			'Extension',
			'Extensions',
			'Add-Ons',
			'Upgrade to Pro',
			'Pricing',
		);

		foreach ( $submenu['postman'] as $index => $item ) {
			if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
				continue;
			}

			$slug = (string) $item[2];

			if ( in_array( $slug, $remove_slugs, true ) ) {
				unset( $submenu['postman'][ $index ] );
				continue;
			}

			if ( false !== strpos( $slug, '#mobile-app' ) ) {
				unset( $submenu['postman'][ $index ] );
				continue;
			}

			if ( false !== stripos( $slug, 'postmansmtp.com/pricing' ) ) {
				unset( $submenu['postman'][ $index ] );
				continue;
			}

			$label = isset( $item[0] ) ? wp_strip_all_tags( (string) $item[0] ) : '';
			if ( in_array( $label, $remove_labels, true ) ) {
				unset( $submenu['postman'][ $index ] );
			}
		}
	}

	/**
	 * @param string|null $submenu_file Current submenu file.
	 * @return string|null
	 */
	public static function highlightSubmenu( $submenu_file ) {
		if ( ! isset( $_GET['page'] ) || 'postman' !== $_GET['page'] ) {
			return $submenu_file;
		}

		$route = isset( $_GET['ps-route'] ) ? sanitize_key( wp_unslash( $_GET['ps-route'] ) ) : '';
		if ( 'mobile-app' === $route ) {
			return self::SLUG_MOBILE_APP;
		}

		return $submenu_file;
	}

	public static function renderRedirectPlaceholder(): void {
		// Redirects run in AdminExperienceGuard::admin_init.
		echo '<div class="wrap"><p>' . esc_html__( 'Redirecting…', 'post-smtp' ) . '</p></div>';
	}

	public static function helpDocsUrl(): string {
		return 'https://postmansmtp.com/documentation/?utm_source=plugin&utm_medium=admin_menu';
	}

	public static function mobileAppDashboardUrl(): string {
		return admin_url( 'admin.php?page=postman&ps-route=mobile-app' );
	}
}
