<?php
/**
 * Default Zoho configure payload for the React admin app.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ZohoConfigureBootstrap {

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$redirect_uri = admin_url( 'admin.php?page=postman/' );

		$defaults = array(
			'redirectUri'          => esc_url_raw( $redirect_uri ),
			'redirectLabel'        => __( 'Redirect URL', 'post-smtp' ),
			'redirectHint'         => __( 'Please copy this URL into the “Redirect URL” field of your Zoho account settings.', 'post-smtp' ),
			'configureFigmaNode'   => '1339:17259',
			'connectLabel'         => __( 'Connect to Zoho API', 'post-smtp' ),
			'connectedLabel'       => __( 'Connected to Zoho Mail API', 'post-smtp' ),
			'oauthConnectBaseUrl'  => '',
			'hasPro'               => false,
			'oauth'                => array(
				'connected' => false,
			),
		);

		/**
		 * Filter Zoho configure payload exposed as `postSmtpAdmin.zohoConfigure`.
		 *
		 * @param array<string,mixed> $defaults Zoho configure defaults.
		 */
		return apply_filters( 'post_smtp_zoho_configure_defaults', $defaults );
	}

	/**
	 * @param int $connection_id Connection index in postman_connections.
	 */
	public static function oauthConnectUrl( int $connection_id ): string {
		if ( class_exists( 'Post_SMTP_Pro_Zoho_Configure_Hooks', false ) ) {
			return Post_SMTP_Pro_Zoho_Configure_Hooks::oauthConnectUrl( $connection_id );
		}

		return '';
	}
}
