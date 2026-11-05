<?php
/**
 * Default Microsoft 365 configure payload for the React admin app.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Connections\ConnectionOAuthGrantBridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Office365ConfigureBootstrap {

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$redirect_uri = esc_url_raw( site_url( '/wp-admin/' ) );
		$js_origin    = esc_url_raw( site_url() );

		$defaults = array(
			'redirectUri'            => $redirect_uri,
			'jsOrigin'               => $js_origin,
			'manualAuthorizeUrl'     => esc_url_raw( ConnectionOAuthGrantBridge::manualAuthorizeUrl( 0, 'office365_api' ) ),
			'manualSectionTitle'     => __( 'Connect it yourself', 'post-smtp' ),
			'manualSectionHint'      => __( 'Create an app in the Microsoft Entra admin center, then paste the values below.', 'post-smtp' ),
			'redirectLabel'          => __( 'Redirect URL', 'post-smtp' ),
			'redirectHint'           => __( 'Copy this into your Microsoft app registration (Redirect URI).', 'post-smtp' ),
			'jsOriginLabel'          => __( 'Authorized JavaScript origin', 'post-smtp' ),
			'jsOriginHint'           => __( 'Copy this into your app\'s allowed JavaScript origins.', 'post-smtp' ),
			'configureFigmaNode'     => '1329:16097',
			'tabOneClickLabel'       => __( 'Connect in just one click', 'post-smtp' ),
			'tabManualLabel'         => __( 'Connect manually', 'post-smtp' ),
			'oneClickSectionTitle'   => __( 'Microsoft 365 / Outlook one click', 'post-smtp' ),
			'oneClickSectionHint'    => __( 'Post SMTP offers two ways to connect Microsoft 365 with WordPress: One-Click Setup (Business plan) or manual app registration.', 'post-smtp' ),
			'defaultConnectTab'      => 'manual',
			'connectApiLabel'        => __( 'Connect to Microsoft API', 'post-smtp' ),
			'oneClick'               => array(
				'title'               => __( 'Connect Microsoft 365 in one click', 'post-smtp' ),
				'description'         => __( 'Sign in with Microsoft and Post SMTP handles the app registration for you.', 'post-smtp' ),
				'businessUpgradeLabel' => __( 'Upgrade to Business', 'post-smtp' ),
				'signInLabel'         => __( 'Sign in with Microsoft', 'post-smtp' ),
				'removeLabel'         => __( 'Remove authorization', 'post-smtp' ),
				'connectApiLabel'     => __( 'Connect to Microsoft API', 'post-smtp' ),
				'hasPro'              => false,
				'isBusinessPlan'      => false,
				'extensionEnabled'    => false,
				'showBusinessTag'     => false,
				'microsoftSignInLocked' => true,
				'toggleAjax'          => array(
					'url'    => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
					'action' => 'save_office365_oneclick_toggle',
					'nonce'  => wp_create_nonce( 'office365_oneclick_toggle' ),
				),
			),
		);

		$defaults['oneClick'] = apply_filters( 'post_smtp_office365_configure_one_click', $defaults['oneClick'], $defaults );

		$defaults['manualOAuth'] = self::manualOAuthState();
		$defaults['manualOAuth'] = apply_filters( 'post_smtp_office365_configure_manual_oauth', $defaults['manualOAuth'], $defaults );

		/**
		 * Filter Microsoft 365 configure payload exposed as `postSmtpAdmin.office365Configure`.
		 *
		 * @param array<string,mixed> $defaults Office 365 configure defaults.
		 */
		return apply_filters( 'post_smtp_office365_configure_defaults', $defaults );
	}

	/**
	 * Manual OAuth connection state for the "Connect to Microsoft API" CTA.
	 *
	 * @return array<string,mixed>
	 */
	private static function manualOAuthState(): array {
		$one_click_enabled = self::isOneClickExtensionEnabled();

		$token = get_option( 'postman_office365_oauth', array() );
		$email = is_array( $token ) && ! empty( $token['user_email'] )
			? sanitize_email( (string) $token['user_email'] )
			: '';
		$has_refresh = is_array( $token ) && ! empty( $token['refresh_token'] );
		$connected   = ( $has_refresh || '' !== $email ) && ! $one_click_enabled;

		return array(
			'connected'      => $connected,
			'connectedEmail' => $email,
			'connectedLabel' => $email
				? sprintf(
					/* translators: %s: Microsoft account email */
					__( 'Connected with: %s', 'post-smtp' ),
					$email
				)
				: __( 'Connected to Microsoft 365 API', 'post-smtp' ),
		);
	}

	private static function isOneClickExtensionEnabled(): bool {
		$pro_options = get_option( 'post_smtp_pro', array() );
		if ( ! is_array( $pro_options ) ) {
			return false;
		}
		$extensions = isset( $pro_options['extensions'] ) && is_array( $pro_options['extensions'] )
			? $pro_options['extensions']
			: array();

		return in_array( 'microsoft-one-click', $extensions, true );
	}
}
