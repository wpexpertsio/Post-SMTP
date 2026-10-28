<?php
/**
 * Default Gmail configure payload for the React admin app.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GmailConfigureBootstrap {

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$redirect_uri = admin_url( 'options-general.php?page=postman' );
		$js_origin    = site_url();

		if ( class_exists( 'PostmanGoogleOAuthScribe', false ) ) {
			$scribe       = new \PostmanGoogleOAuthScribe();
			$redirect_uri = $scribe->getCallbackUrl();
			$js_origin    = $scribe->getCallbackDomain();
		}

		$defaults = array(
			'redirectUri'          => esc_url_raw( $redirect_uri ),
			'jsOrigin'             => esc_url_raw( $js_origin ),
			'manualAuthorizeUrl'   => esc_url_raw( admin_url( 'admin-post.php?action=postman/requestOauthGrant' ) ),
			'manualSectionTitle'   => __( 'Connect it yourself', 'post-smtp' ),
			'manualSectionHint'    => __( 'Create an app in your Google account, then paste the values below.', 'post-smtp' ),
			'redirectLabel'          => __( 'Redirect URL', 'post-smtp' ),
			'redirectHint'           => __( 'Copy this into your Google app settings.', 'post-smtp' ),
			'jsOriginLabel'          => __( 'JavaScript origin', 'post-smtp' ),
			'jsOriginHint'           => __( 'Copy this into your app\'s allowed origins.', 'post-smtp' ),
			'configureFigmaNode'     => '1329:15620',
			'oneClick'               => array(
				'title'         => __( 'Connect Gmail in one click', 'post-smtp' ),
				'description'   => __( 'Sign in with Gmail and Post SMTP does the rest.', 'post-smtp' ),
				'upgradeLabel'  => __( 'Upgrade to Pro', 'post-smtp' ),
				'signInLabel'   => __( 'Sign in with Google', 'post-smtp' ),
				'removeLabel'   => __( 'Remove authorization', 'post-smtp' ),
				'connectApiLabel' => __( 'Connect to Gmail API', 'post-smtp' ),
				'hasPro'        => false,
				'extensionEnabled' => false,
			),
		);

		$defaults['oneClick'] = apply_filters( 'post_smtp_gmail_configure_one_click', $defaults['oneClick'], $defaults );

		$defaults['manualOAuth'] = self::manualOAuthState();
		$defaults['manualOAuth'] = apply_filters( 'post_smtp_gmail_configure_manual_oauth', $defaults['manualOAuth'], $defaults );

		/**
		 * Filter Gmail configure payload exposed as `postSmtpAdmin.gmailConfigure`.
		 *
		 * @param array<string,mixed> $defaults Gmail configure defaults.
		 */
		return apply_filters( 'post_smtp_gmail_configure_defaults', $defaults );
	}

	/**
	 * Manual OAuth connection state for the "Connect to Gmail API" CTA (Figma 1329:15734).
	 *
	 * @return array<string,mixed>
	 */
	private static function manualOAuthState(): array {
		$one_click_enabled = class_exists( '\Postman_Connection_Resolver', false )
			&& \Postman_Connection_Resolver::is_gmail_oneclick_extension_enabled();

		$token = get_option( 'postman_auth_token', array() );
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
					/* translators: %s: Google account email */
					__( 'Connected with: %s', 'post-smtp' ),
					$email
				)
				: __( 'Connected to Gmail API', 'post-smtp' ),
		);
	}
}
