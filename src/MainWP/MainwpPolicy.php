<?php
/**
 * MainWP child/parent detection for revamp admin and mail routing.
 *
 * @package PostSMTP\MainWP
 */

namespace PostSMTP\MainWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MainwpPolicy {

	/**
	 * Child site sends mail via the MainWP dashboard parent.
	 */
	public static function isChildUsingParentMailer(): bool {
		if ( ! self::isMainwpChildPluginActive() ) {
			return false;
		}

		return (bool) get_option( 'post_smtp_use_from_main_site', false );
	}

	/**
	 * Whether the child may not edit mailer / connection storage locally.
	 */
	public static function shouldBlockChildMailerWrites(): bool {
		if ( ! self::isChildUsingParentMailer() ) {
			return false;
		}

		/**
		 * When the MainWP extension allows per-child SMTP ("Individual Settings"),
		 * local mailer writes may be permitted even if Post SMTP is active on the child.
		 *
		 * @param bool $allows_local_mailer Default false.
		 */
		return ! (bool) apply_filters( 'post_smtp_mainwp_child_allows_local_mailer', false );
	}

	public static function isParentConfiguredForChild(): bool {
		return (bool) get_option( 'post_smtp_parent_configured', false );
	}

	/**
	 * MainWP dashboard (parent) with Post SMTP MainWP extension context for aggregated logs UI.
	 */
	public static function isMainwpDashboard(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'mainwp/mainwp.php' ) ) {
			return false;
		}

		return is_plugin_active( 'post-smtp-for-mainwp/mainwp-post-smtp-extension.php' )
			|| is_plugin_active( 'mainwp-post-smtp-extension/mainwp-post-smtp-extension.php' );
	}

	public static function isMainwpChildPluginActive(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'mainwp-child/mainwp-child.php' );
	}
}