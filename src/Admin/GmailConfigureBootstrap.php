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
		return array(
			'redirectUri'          => esc_url_raw( admin_url( 'options-general.php?page=postman' ) ),
			'jsOrigin'             => esc_url_raw( site_url() ),
			'manualAuthorizeUrl'   => esc_url_raw( admin_url( 'admin-post.php?action=postman/requestOauthGrant' ) ),
			'manualSectionTitle'   => __( 'Connect it yourself', 'post-smtp' ),
			'manualSectionHint'    => __( 'Create an app in your Google account, then paste the values below.', 'post-smtp' ),
			'redirectLabel'          => __( 'Redirect URL', 'post-smtp' ),
			'redirectHint'           => __( 'Copy this into your Google app settings.', 'post-smtp' ),
			'jsOriginLabel'          => __( 'JavaScript origin', 'post-smtp' ),
			'jsOriginHint'           => __( 'Copy this into your app\'s allowed origins.', 'post-smtp' ),
			'oneClick'               => array(
				'title'         => __( 'Connect Gmail in one click', 'post-smtp' ),
				'description'   => __( 'Sign in with Google and Post SMTP does the rest.', 'post-smtp' ),
				'upgradeLabel'  => __( 'Upgrade to Pro', 'post-smtp' ),
				'signInLabel'   => __( 'Sign in with Google', 'post-smtp' ),
				'removeLabel'   => __( 'Remove authorization', 'post-smtp' ),
				'hasPro'        => false,
				'extensionEnabled' => false,
			),
		);
	}
}
