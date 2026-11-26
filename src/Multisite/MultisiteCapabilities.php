<?php
/**
 * Ensure Post SMTP capabilities exist on every network site (new subsites, per-blog roles).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteCapabilities {

	public static function init(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'plugins_loaded', array( self::class, 'ensureForCurrentSite' ), 25 );
		add_action( 'init', array( self::class, 'ensureForCurrentSite' ), 2 );
		add_action( 'wpmu_new_blog', array( self::class, 'ensureForNewBlog' ), 10, 1 );
	}

	public static function ensureForNewBlog( int $blog_id ): void {
		if ( $blog_id <= 0 ) {
			return;
		}

		switch_to_blog( $blog_id );

		try {
			self::grantIfNeeded();
		} finally {
			restore_current_blog();
		}
	}

	public static function ensureForCurrentSite(): void {
		self::grantIfNeeded();
	}

	public static function grantIfNeeded(): void {
		if ( ! class_exists( '\Postman' ) || ! class_exists( '\PostmanInstaller' ) ) {
			return;
		}

		if ( ! self::siteNeedsCapabilityGrant() ) {
			return;
		}

		$installer = new \PostmanInstaller();
		$installer->addCapability();
	}

	private static function siteNeedsCapabilityGrant(): bool {
		$administrator = get_role( 'administrator' );
		if ( $administrator && ! $administrator->has_cap( \Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			return true;
		}

		$wp_roles = wp_roles();
		if ( ! $wp_roles ) {
			return false;
		}

		foreach ( array_keys( $wp_roles->roles ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role || ! $role->has_cap( 'activate_plugins' ) ) {
				continue;
			}

			if ( ! $role->has_cap( \Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
				return true;
			}
		}

		return false;
	}
}