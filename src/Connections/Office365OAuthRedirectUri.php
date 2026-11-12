<?php
/**
 * Microsoft Entra OAuth redirect URI for manual Office 365 connections (revamp UI).
 *
 * Must match the URI registered in the Azure app — including https vs http.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Office365OAuthRedirectUri {

	/**
	 * Redirect URI shown in the UI and sent in the authorize request.
	 */
	public static function resolve(): string {
		$scheme = self::preferredScheme();
		$raw    = site_url( '/wp-admin/', $scheme );

		/**
		 * Filter the Microsoft 365 OAuth redirect URI before authorize / token exchange.
		 *
		 * @param string $url Absolute redirect URI (typically .../wp-admin/).
		 */
		$filtered = apply_filters( 'post_smtp_office365_oauth_redirect_uri', $raw );
		$url      = is_string( $filtered ) && '' !== $filtered ? $filtered : $raw;

		return trailingslashit( esc_url_raw( untrailingslashit( $url ) ) );
	}

	/**
	 * Keep postman_office365_oauth in sync with {@see resolve()}.
	 */
	public static function syncOAuthOption(): void {
		if ( ! class_exists( 'Post_Smtp_Office365', false ) ) {
			return;
		}

		$config = get_option( \Post_Smtp_Office365::OAUTH_OPTIONS, array() );
		if ( ! is_array( $config ) ) {
			if ( function_exists( 'post_smtp_pffice365_activate' ) ) {
				post_smtp_pffice365_activate();
				$config = get_option( \Post_Smtp_Office365::OAUTH_OPTIONS, array() );
			}
			if ( ! is_array( $config ) ) {
				$config = array(
					'OAUTH_SCOPES'             => 'openid profile offline_access Mail.Send Mail.Send.Shared',
					'OAUTH_AUTHORITY'          => 'https://login.microsoftonline.com/common',
					'OAUTH_AUTHORIZE_ENDPOINT' => '/oauth2/v2.0/authorize',
					'OAUTH_TOKEN_ENDPOINT'     => '/oauth2/v2.0/token',
				);
			}
		}

		$config['OAUTH_REDIRECT_URI'] = self::resolve();
		LiveLinkOptionGuard::updateOption( \Post_Smtp_Office365::OAUTH_OPTIONS, $config, false );
	}

	private static function preferredScheme(): string {
		if ( is_ssl() ) {
			return 'https';
		}

		if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
			return 'https';
		}

		$home = (string) get_option( 'home', '' );
		$site = (string) get_option( 'siteurl', '' );

		if ( self::starts_with( $home, 'https://' ) || self::starts_with( $site, 'https://' ) ) {
			return 'https';
		}

		$host = (string) wp_parse_url( $home ?: $site, PHP_URL_HOST );
		if ( '' !== $host ) {
			$local_hosts = array( '.localsite.io', '.local', '.loca.lt', '.test' );
			foreach ( $local_hosts as $suffix ) {
				if ( self::ends_with( $host, $suffix ) ) {
					return 'https';
				}
			}
		}

		return 'http';
	}

	private static function starts_with( string $haystack, string $needle ): bool {
		return '' === $needle || 0 === strpos( $haystack, $needle );
	}

	private static function ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}
		$len = strlen( $needle );

		return $len <= strlen( $haystack ) && substr( $haystack, -$len ) === $needle;
	}
}
