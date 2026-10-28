<?php
/**
 * WordPress hooks for the Gmail Connections configure panel (free UI; Pro extends).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers default Gmail configure bootstrap data and documents extension points for Post SMTP Pro.
 *
 * Filters:
 * - post_smtp_gmail_configure_defaults — full `gmailConfigure` array for React admin bootstrap
 * - post_smtp_gmail_configure_one_click — `gmailConfigure.oneClick` block (second arg: full defaults)
 * - post_smtp_gmail_configure_manual_oauth — manual OAuth connected state / labels (second arg: full defaults)
 * - post_smtp_gmail_provider_schema — Gmail connection form schema (GmailProvider)
 * - post_smtp_gmail_authenticate — short-circuit connection save validation
 * - post_smtp_admin_config — entire React bootstrap (includes gmailConfigure)
 *
 * Actions:
 * - post_smtp_gmail_configure_one_click_toggle_attempt — fired when a disabled one-click toggle is activated (free upsell)
 */
final class GmailConfigurePanelHooks {

	public static function init(): void {
		add_filter( 'post_smtp_gmail_configure_one_click', array( self::class, 'defaultOneClick' ), 5, 2 );
		ConnectionOAuthGrantBridge::init();
	}

	/**
	 * @param array<string,mixed> $one_click
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function defaultOneClick( array $one_click, array $defaults ): array {
		unset( $defaults );

		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		return array_merge(
			array(
				'toggleDisabled'      => ! $has_pro,
				'toggleChecked'       => false,
				'openUpgradeOnToggle' => ! $has_pro,
			),
			$one_click
		);
	}

	/**
	 * @param array<string,mixed> $payload Toggle attempt context from the React UI.
	 */
	public static function doOneClickToggleAttempt( array $payload ): void {
		/**
		 * Fires when the user tries to enable Gmail one-click while the toggle is locked (typically free tier).
		 *
		 * @param array<string,mixed> $payload Context including upgradeUrl when available.
		 */
		do_action( 'post_smtp_gmail_configure_one_click_toggle_attempt', $payload );
	}
}
