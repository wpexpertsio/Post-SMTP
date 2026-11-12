<?php
/**
 * Sync modern connection rows into legacy PostmanOptions for OAuth grant admin-post flow.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\SecretCodec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionOAuthGrantBridge {

	private const OAUTH_PROVIDERS = array(
		'gmail_api',
		'office365_api',
		'zohomail_api',
	);

	public static function init(): void {
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'stripLegacyGrantHandlerForOffice365' ), 1 );
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'onBeforeGrant' ), 5 );
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'preventLegacyOffice365GrantHandler' ), 99 );
	}

	/**
	 * Gmail-style admin-post grant must not run for Entra manual Office 365 (would redirect to admin.php?page=postman).
	 */
	public static function stripLegacyGrantHandlerForOffice365(): void {
		if ( ! self::isOffice365GrantRequest() ) {
			return;
		}

		global $wp_filter;

		$hook = 'admin_post_postman/requestOauthGrant';
		if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
			return;
		}

		$hook_obj = $wp_filter[ $hook ];
		foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
			if ( (int) $priority <= 5 ) {
				continue;
			}
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if (
					is_array( $function )
					&& isset( $function[1] )
					&& 'handleOAuthPermissionRequestAction' === $function[1]
				) {
					remove_action( $hook, $function, (int) $priority );
				}
			}
		}
	}

	private static function isOffice365GrantRequest(): bool {
		$mailer = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( (string) $_GET['mailer'] ) ) : '';

		return 'office365_api' === $mailer;
	}

	public static function onBeforeGrant(): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$requested_id = isset( $_GET['connection_id'] ) ? absint( $_GET['connection_id'] ) : null;
		$mailer_hint  = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( (string) $_GET['mailer'] ) ) : '';

		$connection_id = self::resolveGrantConnectionId( $requested_id, $mailer_hint );

		$result = self::prepareForGrant( $connection_id, $mailer_hint );
		if ( ! $result['success'] ) {
			wp_die(
				esc_html( $result['message'] ?? __( 'Could not start OAuth for this connection.', 'post-smtp' ) ),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 400 )
			);
		}

		\PostmanOptions::getInstance()->reload();

		$prepared_transport = (string) \PostmanOptions::getInstance()->getTransportType();
		$is_office365       = 'office365_api' === $mailer_hint || 'office365_api' === $prepared_transport;

		if ( $is_office365 ) {
			if ( class_exists( 'PostmanSession', false ) ) {
				\PostmanSession::getInstance()->unsetOauthInProgress();
			}
			if ( null !== $connection_id && $connection_id >= 0 ) {
				set_transient( 'post_smtp_office365_oauth_connection_id', (int) $connection_id, 15 * MINUTE_IN_SECONDS );
			}
			if ( self::redirectOffice365EntraOAuth() ) {
				exit;
			}

			wp_die(
				esc_html__(
					'Microsoft 365 OAuth could not start. Ensure Post SMTP Pro and the Microsoft 365 mailer are active, then save your app credentials and try again.',
					'post-smtp'
				),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 400 )
			);
		}
	}

	/**
	 * Gmail-style grant handler must not run for Microsoft 365 (Entra app credentials).
	 */
	public static function preventLegacyOffice365GrantHandler(): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$transport = (string) \PostmanOptions::getInstance()->getTransportType();
		if ( 'office365_api' !== $transport ) {
			return;
		}

		wp_die(
			esc_html__(
				'Microsoft 365 OAuth could not start. Save your Application ID and client secret, then try Connect to Microsoft API again.',
				'post-smtp'
			),
			esc_html__( 'Post SMTP', 'post-smtp' ),
			array( 'response' => 400 )
		);
	}

	/**
	 * @return array{success:bool,message?:string}
	 */
	public static function prepareForGrant( int $connection_id, string $mailer_hint = '' ): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return array( 'success' => true );
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();

		if ( $connection_id < 0 || ! isset( $connections[ $connection_id ] ) || ! is_array( $connections[ $connection_id ] ) ) {
			$message = 'office365_api' === $mailer_hint
				? __( 'Save this connection first, then open it again to connect to the Microsoft API.', 'post-smtp' )
				: __( 'Connection not found.', 'post-smtp' );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$row = SecretCodec::decodeConnectionRow( $connections[ $connection_id ] );

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			$provider = $mailer_hint;
		} else {
			$provider = self::resolveProviderFromRow( $row, $mailer_hint );
			if ( ! in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
				return array(
					'success' => false,
					'message' => __( 'This mailer does not use OAuth.', 'post-smtp' ),
				);
			}
		}

		$client_id     = (string) ( $row['oauth_client_id'] ?? '' );
		$client_secret = (string) ( $row['oauth_client_secret'] ?? '' );
		$sender_email  = (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' );
		$sender_name   = (string) ( $row['sender_name'] ?? $row['from_name'] ?? '' );

		if ( 'office365_api' === $provider ) {
			$client_id     = (string) ( $row['office365_app_id'] ?? $client_id );
			$client_secret = (string) ( $row['office365_app_password'] ?? $client_secret );
			$client_id     = SecretCodec::decode( $client_id );
			$client_secret = SecretCodec::decode( $client_secret );
		}

		if ( 'zohomail_api' === $provider ) {
			$client_id     = (string) ( $row['zohomail_client_id'] ?? $client_id );
			$client_secret = (string) ( $row['zohomail_client_secret'] ?? $client_secret );
		}

		if ( '' === $client_id || '' === $client_secret ) {
			if ( 'zohomail_api' === $provider ) {
				$message = __( 'Save Region, Client ID, and Client Secret before connecting to the Zoho API.', 'post-smtp' );
			} elseif ( 'office365_api' === $provider ) {
				$message = __( 'Save Application (client) ID and client secret before connecting to the Microsoft API.', 'post-smtp' );
			} else {
				$message = __( 'Save Client ID and Client secret before connecting to the Gmail API.', 'post-smtp' );
			}
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$options = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options['transport_type']      = $provider;
		$options['auth_type']           = \PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;
		$options['oauth_client_id']     = $client_id;
		$options['oauth_client_secret'] = $client_secret;
		$options['sender_email']        = sanitize_email( $sender_email );
		$options['sender_name']         = sanitize_text_field( $sender_name );

		if ( 'gmail_api' === $provider ) {
			$options['hostname'] = 'www.googleapis.com';
			$options['port']     = 443;
			$options['enc_type'] = \PostmanOptions::SECURITY_TYPE_SMTPS;
		}

		if ( 'zohomail_api' === $provider ) {
			$options['zohomail_client_id']     = $client_id;
			$options['zohomail_client_secret'] = $client_secret;
			$options['zohomail_region']       = sanitize_key( (string) ( $row['zohomail_region'] ?? 'com' ) );
		}

		if ( 'office365_api' === $provider ) {
			// Legacy Microsoft 365 OAuth expects single-layer base64 in postman_options (see Pro Transport).
			$options['office365_app_id']       = SecretCodec::encodeSingleBase64( $client_id );
			$options['office365_app_password'] = SecretCodec::encodeSingleBase64( $client_secret );
			$options['hostname']               = 'smtp.office365.com';
			$options['port']                   = 443;
			$options['enc_type']               = \PostmanOptions::SECURITY_TYPE_SMTPS;
		}

		LiveLinkOptionGuard::updateOption( 'postman_options', $options, false );

		/**
		 * Fires after a connection row is copied into legacy options for an OAuth grant request.
		 *
		 * @param int                 $connection_id Connection index.
		 * @param array<string,mixed> $row           Connection row.
		 * @param array<string,mixed> $options       Updated postman_options.
		 */
		do_action( 'post_smtp_connection_oauth_grant_prepared', $connection_id, $row, $options );

		return array( 'success' => true );
	}

	/**
	 * @param int|null $requested_id Connection index from the request, if any.
	 * @param string   $mailer_hint  OAuth mailer slug from the request (e.g. gmail_api).
	 */
	private static function resolveGrantConnectionId( ?int $requested_id, string $mailer_hint ): int {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();

		if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
			$provider = self::resolveProviderFromRow( $connections[ $requested_id ], $mailer_hint );
			if ( in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
				return $requested_id;
			}
		}

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
					return (int) $idx;
				}
				if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
					return (int) $idx;
				}
				if ( self::rowHasOAuthFieldDraft( $row, $mailer_hint ) ) {
					return (int) $idx;
				}
				$provider = self::resolveProviderFromRow( $row, $mailer_hint );
				if ( $provider === $mailer_hint ) {
					return (int) $idx;
				}
			}

			if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
				$candidate = $connections[ $requested_id ];
				if (
					$mailer_hint === (string) ( $candidate['provider'] ?? '' )
					|| self::rowHasOAuthCredentials( $candidate, $mailer_hint )
					|| self::rowHasOAuthFieldDraft( $candidate, $mailer_hint )
				) {
					return $requested_id;
				}
			}

			return -1;
		}

		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return 0;
		}

		$control = get_option( 'postman_options', array() );
		if ( is_array( $control ) && isset( $control['primary_connection'] ) ) {
			$primary = (int) $control['primary_connection'];
			if ( isset( $connections[ $primary ] ) && is_array( $connections[ $primary ] ) ) {
				$primary_provider = self::resolveProviderFromRow( $connections[ $primary ], $mailer_hint );
				if ( '' === $mailer_hint || in_array( $primary_provider, self::OAUTH_PROVIDERS, true ) ) {
					return $primary;
				}
			}
		}

		return isset( $connections[0] ) ? 0 : (int) array_key_first( $connections );
	}

	/**
	 * @param array<string,mixed> $row Connection row from postman_connections.
	 */
	private static function resolveProviderFromRow( array $row, string $mailer_hint = '' ): string {
		$provider = (string) ( $row['provider'] ?? '' );
		if ( '' === $provider || 'default' === $provider ) {
			$provider = (string) ( $row['transport_type'] ?? '' );
		}

		if ( in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
			return $provider;
		}

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
				return $mailer_hint;
			}
			if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
				return $mailer_hint;
			}
		}

		if ( self::rowHasOAuthCredentials( $row, 'gmail_api' ) && ( '' === $provider || 'default' === $provider || 'smtp' === $provider ) ) {
			return 'gmail_api';
		}

		return $provider;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowHasOAuthCredentials( array $row, string $provider ): bool {
		switch ( $provider ) {
			case 'gmail_api':
				return '' !== (string) ( $row['oauth_client_id'] ?? '' )
					&& '' !== (string) ( $row['oauth_client_secret'] ?? '' );
			case 'office365_api':
				$app_id = (string) ( $row['office365_app_id'] ?? $row['oauth_client_id'] ?? '' );
				$secret = (string) ( $row['office365_app_password'] ?? $row['oauth_client_secret'] ?? '' );
				return '' !== $app_id && '' !== $secret;
			case 'zohomail_api':
				return '' !== (string) ( $row['zohomail_client_id'] ?? $row['oauth_client_id'] ?? '' )
					&& '' !== (string) ( $row['zohomail_client_secret'] ?? $row['oauth_client_secret'] ?? '' );
		}

		return false;
	}

	/**
	 * Detect saved OAuth credential fields even when client id + secret are not both present yet.
	 *
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowHasOAuthFieldDraft( array $row, string $provider ): bool {
		switch ( $provider ) {
			case 'office365_api':
				return '' !== (string) ( $row['office365_app_id'] ?? '' )
					|| '' !== (string) ( $row['office365_app_password'] ?? '' );
			case 'gmail_api':
				return '' !== (string) ( $row['oauth_client_id'] ?? '' )
					|| '' !== (string) ( $row['oauth_client_secret'] ?? '' );
			case 'zohomail_api':
				return '' !== (string) ( $row['zohomail_client_id'] ?? '' )
					|| '' !== (string) ( $row['zohomail_client_secret'] ?? '' );
		}

		return false;
	}

	/**
	 * Read App ID / secret from the revamp connection row during an in-flight grant (Local Live Link safe).
	 *
	 * @return array{client_id:string,client_secret:string}|null
	 */
	public static function getOffice365GrantCredentialsForOAuth(): ?array {
		$connection_id = get_transient( 'post_smtp_office365_oauth_connection_id' );
		if ( ! is_numeric( $connection_id ) || (int) $connection_id < 0 ) {
			return null;
		}

		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return null;
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		$index       = (int) $connection_id;

		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			return null;
		}

		$row = SecretCodec::decodeConnectionRow( $connections[ $index ] );

		$client_id = SecretCodec::decode( (string) ( $row['office365_app_id'] ?? $row['oauth_client_id'] ?? '' ) );
		$secret    = SecretCodec::decode( (string) ( $row['office365_app_password'] ?? $row['oauth_client_secret'] ?? '' ) );

		if ( '' === $client_id || '' === $secret ) {
			return null;
		}

		return array(
			'client_id'     => $client_id,
			'client_secret' => $secret,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function buildOffice365EntraClientConfig( string $client_id, string $client_secret ): array {
		$redirect_uri = Office365OAuthRedirectUri::resolve();
		$authority    = 'https://login.microsoftonline.com/common';

		return array(
			'clientId'                => $client_id,
			'clientSecret'            => $client_secret,
			'redirectUri'             => $redirect_uri,
			'urlAuthorize'            => $authority . '/oauth2/v2.0/authorize',
			'urlAccessToken'          => $authority . '/oauth2/v2.0/token',
			'urlResourceOwnerDetails' => '',
			'scopes'                  => 'openid profile offline_access Mail.Send Mail.Send.Shared',
		);
	}

	/**
	 * Microsoft 365 manual connect uses Entra OAuth (Pro), not the Gmail-style grant handler.
	 */
	private static function redirectOffice365EntraOAuth(): bool {
		if ( ! defined( 'POST_SMTP_PRO_PATH' ) ) {
			return false;
		}

		if ( function_exists( 'psp_load_microsoft_365' ) ) {
			psp_load_microsoft_365();
		} else {
			$bootstrap = POST_SMTP_PRO_PATH . 'extensions/microsoft-365/microsoft-365.php';
			if ( ! is_readable( $bootstrap ) ) {
				return false;
			}
			require_once $bootstrap;
			if ( function_exists( 'psp_load_microsoft_365' ) ) {
				psp_load_microsoft_365();
			}
		}

		if ( ! class_exists( 'OAuthOffice365', false ) || ! class_exists( 'Post_Smtp_Office365', false ) ) {
			return false;
		}

		Office365OAuthRedirectUri::syncOAuthOption();

		$config = get_option( 'postman_office365_oauth', array() );
		if ( ! is_array( $config ) || empty( $config ) ) {
			if ( function_exists( 'post_smtp_pffice365_activate' ) ) {
				post_smtp_pffice365_activate();
			}
		}

		$grant_creds = self::getOffice365GrantCredentialsForOAuth();
		if ( is_array( $grant_creds ) ) {
			$oauth_client = self::buildOffice365EntraClientConfig(
				$grant_creds['client_id'],
				$grant_creds['client_secret']
			);
		} else {
			$office365    = new \Post_Smtp_Office365();
			$oauth_client = $office365->get_office365_configuration();
		}

		if ( empty( $oauth_client['clientId'] ) || empty( $oauth_client['clientSecret'] ) ) {
			return false;
		}

		if ( empty( $oauth_client['urlAuthorize'] ) ) {
			$oauth_client = self::buildOffice365EntraClientConfig(
				(string) $oauth_client['clientId'],
				(string) $oauth_client['clientSecret']
			);
		}

		$client = new \OAuthOffice365( $oauth_client );
		$state  = get_transient( \Post_Smtp_Office365::STATE );
		if ( false === $state ) {
			$state = bin2hex( random_bytes( 16 ) );
			set_transient( \Post_Smtp_Office365::STATE, $state, 5 * MINUTE_IN_SECONDS );
		}

		$url = $client->getOffice365AuthURL(
			array(
				'state' => $state,
			)
		);

		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		if ( false === strpos( $url, 'login.microsoftonline.com' ) ) {
			return false;
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * @param int    $connection_id Connection index in postman_connections.
	 * @param string $mailer        OAuth provider slug.
	 */
	public static function manualAuthorizeUrl( int $connection_id, string $mailer = 'gmail_api' ): string {
		$args = array(
			'action'        => 'postman/requestOauthGrant',
			'connection_id' => max( 0, $connection_id ),
		);

		if ( in_array( $mailer, self::OAUTH_PROVIDERS, true ) ) {
			$args['mailer'] = $mailer;
		}

		return add_query_arg( $args, admin_url( 'admin-post.php' ) );
	}
}
