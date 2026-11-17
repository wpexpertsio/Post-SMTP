<?php
/**
 * Default Microsoft 365 configure payload for the React admin app.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Connections\ConnectionOAuthGrantBridge;
use PostSMTP\Pro\Connections\Office365OAuthRedirectUri;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Office365ConfigureBootstrap {

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$redirect_uri = self::resolveRedirectUriForEntra();
		$redirect_scheme = wp_parse_url( $redirect_uri, PHP_URL_SCHEME );
		$js_origin        = esc_url_raw( set_url_scheme( site_url(), is_string( $redirect_scheme ) ? $redirect_scheme : 'https' ) );

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
		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return array(
				'connected'      => false,
				'connectedEmail' => '',
				'connectedLabel' => __( 'Connected to Microsoft 365 API', 'post-smtp' ),
			);
		}

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

	/**
	 * Entra Web redirect URIs must not include query strings (unlike the legacy grant kickoff URL).
	 */
	private static function resolveRedirectUriForEntra(): string {
		if ( class_exists( Office365OAuthRedirectUri::class, true ) ) {
			return Office365OAuthRedirectUri::resolve();
		}

		$scheme = 'http';
		if ( is_ssl() ) {
			$scheme = 'https';
		} elseif ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
			$scheme = 'https';
		} else {
			$home = (string) get_option( 'home', '' );
			$site = (string) get_option( 'siteurl', '' );
			if ( 0 === strpos( $home, 'https://' ) || 0 === strpos( $site, 'https://' ) ) {
				$scheme = 'https';
			} else {
				$host = (string) wp_parse_url( $home ? $home : $site, PHP_URL_HOST );
				if ( '' !== $host ) {
					foreach ( array( '.localsite.io', '.local', '.loca.lt', '.test' ) as $suffix ) {
						$len = strlen( $suffix );
						if ( $len <= strlen( $host ) && substr( $host, -$len ) === $suffix ) {
							$scheme = 'https';
							break;
						}
					}
				}
			}
		}

		$raw      = site_url( '/wp-admin/', $scheme );
		$filtered = apply_filters( 'post_smtp_office365_oauth_redirect_uri', $raw );
		$url      = is_string( $filtered ) && '' !== $filtered ? $filtered : $raw;

		return trailingslashit( esc_url_raw( untrailingslashit( $url ) ) );
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
