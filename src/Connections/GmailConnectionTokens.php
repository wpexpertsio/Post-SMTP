<?php
/**
 * Gmail OAuth tokens on postman_connections rows (revamp).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Data\SecretCodec;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GmailConnectionTokens {

	public const ACCESS_TOKEN  = 'access_token';
	public const REFRESH_TOKEN   = 'refresh_token';
	public const TOKEN_EXPIRES   = 'auth_token_expires';
	public const USER_EMAIL      = 'user_email';

	private static $skip_global_cache = null;

	private static $persisting_incoming = false;

	/** @var array<string,mixed> In-flight send context (replaces global postman_auth_token on revamp). */
	private static $send_config = array();

	private static $send_connection_index = -1;

	private static $persisting_refresh = false;

	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'migrateLegacyGlobalAuthOnce' ), 5 );
		add_filter( 'pre_option_postman_auth_token', array( self::class, 'filterPreOptionRead' ), 10, 1 );
		add_filter( 'option_postman_auth_token', array( self::class, 'filterOptionRead' ), 10, 1 );
		add_filter( 'pre_update_option_postman_auth_token', array( self::class, 'filterOptionWrite' ), 10, 2 );
	}

	/**
	 * Revamp sites do not keep Gmail bearer tokens in postman_auth_token.
	 */
	public static function shouldSkipGlobalAuthOption(): bool {
		if ( null !== self::$skip_global_cache ) {
			return self::$skip_global_cache;
		}

		self::$skip_global_cache = self::usesPerConnectionTokenStore();

		return self::$skip_global_cache;
	}

	/**
	 * One-time: move legacy global tokens onto a connection row, then remove the option.
	 */
	public static function migrateLegacyGlobalAuthOnce(): void {
		if ( ! is_user_logged_in() || ! self::shouldStoreTokensOnConnections() ) {
			return;
		}

		if ( isset( $_GET['code'] ) || isset( $_GET['state'] ) ) {
			return;
		}

		if ( isset( $_GET['action'] )
			&& 'gmail_oauth_redirect' === sanitize_key( wp_unslash( (string) $_GET['action'] ) ) ) {
			if ( isset( $_GET['access_token'] ) ) {
				return;
			}
			if ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::hasCapturedOneClickPayload() ) {
				return;
			}
			if ( function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' ) && post_smtp_gmail_oneclick_guard_has_incoming() ) {
				return;
			}
		}

		if ( ! self::shouldSkipGlobalAuthOption() ) {
			return;
		}

		$raw = self::readRawGlobalAuthOptionFromDb();
		if ( ! is_array( $raw ) || 'google' !== (string) ( $raw['vendor_name'] ?? '' ) ) {
			if ( null !== $raw ) {
				self::clearGlobalAuthOption();
			}
			return;
		}

		if ( '' === (string) ( $raw['access_token'] ?? '' ) || '' === (string) ( $raw['refresh_token'] ?? '' ) ) {
			self::clearGlobalAuthOption();
			return;
		}

		$needs_sync = false;
		foreach ( self::getConnectionRows() as $row ) {
			if ( ! is_array( $row ) || 'gmail_api' !== (string) ( $row['provider'] ?? '' ) ) {
				continue;
			}
			if ( ! self::connectionRowHasOAuthSecrets( $row ) ) {
				$needs_sync = true;
				break;
			}
		}

		if ( $needs_sync ) {
			self::persistFromIncomingAuthPayload( $raw );
		}

		self::clearGlobalAuthOption();
	}

	public static function clearGlobalAuthOption(): void {
		if ( ! self::shouldSkipGlobalAuthOption() ) {
			return;
		}

		delete_option( 'postman_auth_token' );
		wp_cache_delete( 'postman_auth_token', 'options' );
	}

	/**
	 * Remove postman_auth_token after OAuth (never during pre_update_option on that same option).
	 */
	public static function scheduleGlobalAuthCleanup(): void {
		static $scheduled = false;
		if ( $scheduled || ! self::shouldSkipGlobalAuthOption() ) {
			return;
		}
		$scheduled = true;
		add_action( 'shutdown', array( self::class, 'clearGlobalAuthOption' ), 0 );
	}

	/**
	 * Bypass option filters (revamp masks postman_auth_token reads).
	 *
	 * @return array<string,mixed>|null Null when the option row is missing.
	 */
	public static function readRawGlobalAuthOptionFromDb(): ?array {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			$fallback = get_option( 'postman_auth_token', array() );
			return is_array( $fallback ) ? $fallback : null;
		}

		$serialized = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				'postman_auth_token'
			)
		);

		if ( null === $serialized ) {
			return null;
		}

		$auth = maybe_unserialize( $serialized );

		return is_array( $auth ) ? $auth : null;
	}

	public static function shouldStoreTokensOnConnections(): bool {
		if ( count( ConnectionSchema::readConnectionsOption() ) > 0 ) {
			return true;
		}

		if ( class_exists( '\PostSMTP\Rollout\CohortResolver', false )
			&& \PostSMTP\Rollout\CohortResolver::usesReactAdmin() ) {
			return true;
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return true;
		}

		return class_exists( GmailRevampOAuth::class, false )
			&& GmailRevampOAuth::usesReactConnectionsAdmin();
	}

	public static function usesPerConnectionTokenStore(): bool {
		if ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::shouldUseRevampOAuthReturn() ) {
			return true;
		}

		if ( ! class_exists( '\Postman_Connection_Resolver', false ) ) {
			return false;
		}

		foreach ( self::getConnectionRows() as $row ) {
			if ( is_array( $row ) && 'gmail_api' === (string) ( $row['provider'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function getConnectionRows(): array {
		$stored = self::readConnectionRowsFromOption();
		if ( count( $stored ) > 0 ) {
			return $stored;
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		if ( is_array( $connections ) && count( $connections ) > 0 ) {
			return $connections;
		}

		if ( class_exists( '\Postman_Connection_Resolver', false ) ) {
			$resolved = \Postman_Connection_Resolver::get_connections();
			if ( is_array( $resolved ) && count( $resolved ) > 0 ) {
				return $resolved;
			}
		}

		return array();
	}

	/**
	 * Raw postman_connections option (tolerates corrupted / serialized strings).
	 *
	 * @return array<int|string,array<string,mixed>>
	 */
	private static function readConnectionRowsFromOption(): array {
		return ConnectionSchema::readConnectionsOption();
	}

	/**
	 * Ensure a connection row exists before writing OAuth tokens (grant may run when the row was never saved).
	 *
	 * @return int Connection index used (may differ from requested when the list is sparse).
	 */
	public static function ensureConnectionRowExists( int $index, string $provider = 'gmail_api' ): int {
		if ( $index < 0 ) {
			$index = 0;
		}

		$connections = self::readConnectionRowsFromOption();
		foreach ( array( $index, (string) $index ) as $key ) {
			if ( isset( $connections[ $key ] ) && is_array( $connections[ $key ] ) ) {
				return (int) $index;
			}
		}

		if ( '' !== $provider ) {
			foreach ( $connections as $idx => $row ) {
				if ( is_array( $row ) && $provider === (string) ( $row['provider'] ?? '' ) ) {
					return (int) $idx;
				}
			}
		}

		$options = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$new_row = array(
			'provider'       => $provider,
			'transport_type' => $provider,
			'sender_email'   => (string) ( $options['sender_email'] ?? '' ),
			'sender_name'    => (string) ( $options['sender_name'] ?? '' ),
			'title'          => (string) ( $options['sender_name'] ?? __( 'Gmail', 'post-smtp' ) ),
		);

		if ( 'gmail_api' === $provider ) {
			$new_row['oauth_client_id']     = SecretCodec::decode( (string) ( $options['oauth_client_id'] ?? '' ) );
			$new_row['oauth_client_secret'] = SecretCodec::decode( (string) ( $options['oauth_client_secret'] ?? '' ) );
		}

		if ( ! isset( $connections[ $index ] ) ) {
			while ( count( $connections ) < $index ) {
				$connections[] = array( 'provider' => 'smtp' );
			}
			$connections[ $index ] = $new_row;
		}

		ConnectionSchema::writeConnectionsOption( $connections );

		return (int) $index;
	}

	public static function resolveIndexForOAuthCallback( int $from_request = -1 ): int {
		if ( $from_request >= 0 ) {
			return $from_request;
		}

		$resolved = GmailRevampOAuth::resolveConnectionId();
		if ( $resolved >= 0 ) {
			return $resolved;
		}

		foreach ( self::getConnectionRows() as $index => $row ) {
			if ( is_array( $row ) && 'gmail_api' === (string) ( $row['provider'] ?? '' ) ) {
				return (int) $index;
			}
		}

		return -1;
	}

	/**
	 * Middleware one-click callback: bind tokens to a Gmail connection row (create row when missing).
	 */
	public static function resolveIndexForOneClickCallback( int $from_request = -1 ): int {
		if ( $from_request >= 0 && self::shouldStoreTokensOnConnections() ) {
			return self::ensureConnectionRowExists( $from_request, 'gmail_api' );
		}

		if ( $from_request >= 0 ) {
			return $from_request;
		}

		$resolved = GmailRevampOAuth::resolveConnectionId();
		if ( $resolved >= 0 && self::shouldStoreTokensOnConnections() ) {
			return self::ensureConnectionRowExists( $resolved, 'gmail_api' );
		}
		if ( $resolved >= 0 ) {
			return $resolved;
		}

		$fallback = self::resolveIndexForOAuthCallback( -1 );
		if ( $fallback >= 0 ) {
			return $fallback;
		}

		if ( ! self::shouldStoreTokensOnConnections() ) {
			return -1;
		}

		if ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::clientUrlIsAddRoute() ) {
			return self::appendGmailConnectionForOneClickGrant();
		}

		return self::ensureConnectionRowExists( 0, 'gmail_api' );
	}

	/**
	 * OAuth return on connections/add: append a new Gmail row instead of overwriting index 0.
	 */
	public static function appendGmailConnectionForOneClickGrant(): int {
		$connections = self::readConnectionRowsFromOption();
		$options       = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$new_row = array(
			'provider'       => 'gmail_api',
			'transport_type' => 'gmail_api',
			'sender_email'   => (string) ( $options['sender_email'] ?? '' ),
			'sender_name'    => (string) ( $options['sender_name'] ?? '' ),
			'title'          => (string) ( $options['sender_name'] ?? __( 'Gmail', 'post-smtp' ) ),
		);

		$new_row['oauth_client_id']     = SecretCodec::decode( (string) ( $options['oauth_client_id'] ?? '' ) );
		$new_row['oauth_client_secret'] = SecretCodec::decode( (string) ( $options['oauth_client_secret'] ?? '' ) );

		$connections[] = $new_row;
		$index         = count( $connections ) - 1;
		ConnectionSchema::writeConnectionsOption( $connections );

		return (int) $index;
	}

	/**
	 * @param array<string,mixed> $row Connection row (encoded or decoded).
	 */
	public static function isConnectedStored( array $row ): bool {
		if ( '' !== sanitize_email( (string) ( $row[ self::USER_EMAIL ] ?? '' ) ) ) {
			return true;
		}

		$refresh = (string) ( $row[ self::REFRESH_TOKEN ] ?? '' );
		if ( '' === $refresh ) {
			return false;
		}

		if ( preg_match( '/^•+$/u', $refresh ) ) {
			return true;
		}

		return '' !== SecretCodec::decode( $refresh );
	}

	/**
	 * @param array<string,mixed> $row Connection row (stored form).
	 * @return array{access_token:string,refresh_token:string,auth_token_expires:int,user_email:string}
	 */
	public static function readTokensFromRow( array $row ): array {
		$decoded = SecretCodec::decodeConnectionRow( $row );

		return array(
			'access_token'       => (string) ( $decoded[ self::ACCESS_TOKEN ] ?? '' ),
			'refresh_token'      => (string) ( $decoded[ self::REFRESH_TOKEN ] ?? '' ),
			'auth_token_expires' => (int) ( $decoded[ self::TOKEN_EXPIRES ] ?? 0 ),
			'user_email'         => sanitize_email( (string) ( $decoded[ self::USER_EMAIL ] ?? '' ) ),
		);
	}

	public static function storeOnConnection(
		int $index,
		string $access_token,
		string $refresh_token,
		int $expires_at,
		string $user_email = ''
	): bool {
		if ( '' === $access_token || '' === $refresh_token ) {
			return false;
		}

		if ( ! self::shouldStoreTokensOnConnections() ) {
			return false;
		}

		$index = self::ensureConnectionRowExists( $index, 'gmail_api' );

		$connections = self::readConnectionRowsFromOption();
		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			return false;
		}

		$connections[ $index ][ self::ACCESS_TOKEN ]  = $access_token;
		$connections[ $index ][ self::REFRESH_TOKEN ] = $refresh_token;
		$connections[ $index ][ self::TOKEN_EXPIRES ] = (string) max( 0, $expires_at );

		if ( '' !== $user_email ) {
			$connections[ $index ][ self::USER_EMAIL ] = sanitize_email( $user_email );
		} elseif ( empty( $connections[ $index ][ self::USER_EMAIL ] ) ) {
			$fallback = sanitize_email( (string) ( $connections[ $index ]['sender_email'] ?? $connections[ $index ]['from_email'] ?? '' ) );
			if ( '' !== $fallback ) {
				$connections[ $index ][ self::USER_EMAIL ] = $fallback;
			}
		}

		$tab = GmailRevampOAuth::resolveConnectTab();
		if ( in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
			$connections[ $index ]['gmail_connect_tab'] = $tab;
		}

		$connections[ $index ] = SecretCodec::encodeConnectionRow( $connections[ $index ] );
		ConnectionSchema::writeConnectionsOption( $connections );

		return self::connectionRowHasOAuthSecrets( $connections[ $index ] );
	}

	/**
	 * Legacy-only: copy tokens from postman_auth_token (pre-revamp wizard).
	 */
	public static function persistFromGlobalAuthOption(): bool {
		if ( self::shouldSkipGlobalAuthOption() ) {
			return false;
		}

		$auth = get_option( 'postman_auth_token', array() );
		if ( ! is_array( $auth ) ) {
			return false;
		}

		return self::persistFromIncomingAuthPayload( $auth );
	}

	/**
	 * @param array<string,mixed> $auth Token payload from OAuth (postman_auth_token shape).
	 */
	public static function persistFromIncomingAuthPayload( array $auth, int $index = -1 ): bool {
		if ( self::$persisting_incoming ) {
			return false;
		}

		if ( ! self::shouldStoreTokensOnConnections() ) {
			return false;
		}

		self::$persisting_incoming = true;

		try {
			return self::persistFromIncomingAuthPayloadInternal( $auth, $index );
		} finally {
			self::$persisting_incoming = false;
		}
	}

	/**
	 * @param array<string,mixed> $auth Token payload from OAuth (postman_auth_token shape).
	 */
	private static function persistFromIncomingAuthPayloadInternal( array $auth, int $index ): bool {
		$access  = (string) ( $auth['access_token'] ?? '' );
		$refresh = (string) ( $auth['refresh_token'] ?? '' );
		$expires = (int) ( $auth['auth_token_expires'] ?? 0 );
		$email   = sanitize_email( (string) ( $auth['user_email'] ?? '' ) );

		if ( '' === $access || '' === $refresh ) {
			return false;
		}

		if ( '' === $email ) {
			$email = self::fetchGoogleAccountEmail( $access );
		}

		if ( $index < 0 && isset( $auth['oauth_connection_id'] ) && is_numeric( $auth['oauth_connection_id'] ) ) {
			$index = (int) $auth['oauth_connection_id'];
		}
		if ( $index < 0 ) {
			$index = self::resolveIndexForOAuthCallback( -1 );
		}
		if ( $index < 0 ) {
			$index = 0;
		}

		$index  = self::ensureConnectionRowExists( $index, 'gmail_api' );
		$stored = self::storeOnConnection( $index, $access, $refresh, $expires, $email );

		if ( $stored && self::shouldSkipGlobalAuthOption() ) {
			self::scheduleGlobalAuthCleanup();
		}

		return $stored;
	}

	/**
	 * After Google returns tokens, write them onto the connection row (source of truth for revamp).
	 */
	public static function storeTokensAfterOAuthGrant( int $index = -1 ): bool {
		if ( ! class_exists( '\PostmanOAuthToken', false ) ) {
			return false;
		}

		$token   = \PostmanOAuthToken::getInstance();
		$access  = (string) $token->getAccessToken();
		$refresh = (string) $token->getRefreshToken();
		if ( '' === $access || '' === $refresh ) {
			return false;
		}

		$payload = array(
			'access_token'       => $access,
			'refresh_token'      => $refresh,
			'auth_token_expires' => (int) $token->getExpiryTime(),
			'vendor_name'        => 'google',
		);

		if ( $index >= 0 ) {
			$payload['oauth_connection_id'] = $index;
		}

		return self::persistFromIncomingAuthPayload( $payload, $index );
	}

	/**
	 * Write refreshed in-memory tokens onto the active connection row (after access-token refresh).
	 *
	 * @param array<string,mixed> $auth postman_auth_token-shaped payload.
	 */
	public static function persistFromSendContext( array $auth ): void {
		if ( ! self::usesPerConnectionTokenStore() && ! GmailRevampOAuth::shouldUseRevampOAuthReturn() ) {
			return;
		}

		$index = isset( $auth['oauth_connection_id'] ) && is_numeric( $auth['oauth_connection_id'] )
			? (int) $auth['oauth_connection_id']
			: self::getSendConnectionIndex();

		if ( $index < 0 ) {
			$index = self::resolveIndexForOAuthCallback( -1 );
		}
		if ( $index < 0 ) {
			return;
		}

		$access  = (string) ( $auth['access_token'] ?? '' );
		$refresh = (string) ( $auth['refresh_token'] ?? '' );
		if ( '' === $access || '' === $refresh ) {
			return;
		}

		$expires = isset( $auth['auth_token_expires'] ) ? (int) $auth['auth_token_expires'] : 0;
		$email   = isset( $auth['user_email'] ) ? sanitize_email( (string) $auth['user_email'] ) : '';

		self::storeOnConnection( $index, $access, $refresh, $expires, $email );
	}

	/**
	 * Resolve the authorized Google account email for display after manual OAuth.
	 */
	public static function fetchGoogleAccountEmail( string $access_token ): string {
		$access_token = trim( $access_token );
		if ( '' === $access_token ) {
			return '';
		}

		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v2/userinfo',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return '';
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( is_string( $body ) ? $body : '', true );
		if ( ! is_array( $data ) || empty( $data['email'] ) ) {
			return '';
		}

		return sanitize_email( (string) $data['email'] );
	}

	/**
	 * Copy row tokens into postman_auth_token before legacy Gmail API send (singleton reads option once per request).
	 */
	public static function clearOnConnection( int $index ): bool {
		if ( $index < 0 || ( ! self::usesPerConnectionTokenStore() && ! GmailRevampOAuth::usesReactConnectionsAdmin() ) ) {
			return false;
		}

		$connections = self::getConnectionRows();
		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			return false;
		}

		unset(
			$connections[ $index ][ self::ACCESS_TOKEN ],
			$connections[ $index ][ self::REFRESH_TOKEN ],
			$connections[ $index ][ self::TOKEN_EXPIRES ],
			$connections[ $index ][ self::USER_EMAIL ],
			$connections[ $index ]['gmail_connect_tab']
		);

		ConnectionSchema::writeConnectionsOption( $connections );

		if ( self::shouldSkipGlobalAuthOption() ) {
			self::clearGlobalAuthOption();
		}

		return true;
	}

	public static function hydrateLegacyOAuthOption( int $index ): void {
		if ( $index < 0 ) {
			return;
		}

		if ( ! self::usesPerConnectionTokenStore() && ! GmailRevampOAuth::usesReactConnectionsAdmin() ) {
			return;
		}

		$connections = self::getConnectionRows();
		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			return;
		}

		$tokens = self::readTokensFromRow( $connections[ $index ] );
		if ( '' === $tokens['access_token'] || '' === $tokens['refresh_token'] ) {
			if ( ! self::shouldSkipGlobalAuthOption() ) {
				self::persistFromGlobalAuthOption();
				$connections = self::getConnectionRows();
				if ( ! isset( $connections[ $index ] ) ) {
					return;
				}
				$tokens = self::readTokensFromRow( $connections[ $index ] );
			}
			if ( '' === $tokens['access_token'] || '' === $tokens['refresh_token'] ) {
				return;
			}
		}

		if ( self::usesPerConnectionTokenStore() ) {
			self::primeSendFromConnection( $index );
			if ( class_exists( '\PostmanOAuthToken', false ) && method_exists( '\PostmanOAuthToken', 'reloadFromOption' ) ) {
				\PostmanOAuthToken::getInstance()->reloadFromOption();
			}
			return;
		}

		$legacy = array(
			'access_token'        => $tokens['access_token'],
			'refresh_token'       => $tokens['refresh_token'],
			'auth_token_expires'  => $tokens['auth_token_expires'] > 0 ? $tokens['auth_token_expires'] : time() + HOUR_IN_SECONDS,
			'user_email'          => $tokens['user_email'],
			'vendor_name'         => 'google',
			'oauth_connection_id' => $index,
		);

		update_option( 'postman_auth_token', $legacy, false );

		if ( class_exists( '\PostmanOAuthToken', false ) && method_exists( '\PostmanOAuthToken', 'reloadFromOption' ) ) {
			\PostmanOAuthToken::getInstance()->reloadFromOption();
		}
	}

	/**
	 * @param array<string,mixed> $row Encoded connection row.
	 */
	private static function connectionRowHasOAuthSecrets( array $row ): bool {
		if ( ! is_array( $row ) || count( $row ) === 0 ) {
			return false;
		}

		$decoded = SecretCodec::decodeConnectionRow( $row );

		return '' !== (string) ( $decoded[ self::ACCESS_TOKEN ] ?? '' )
			&& '' !== (string) ( $decoded[ self::REFRESH_TOKEN ] ?? '' );
	}

	public static function primeSendFromConnection( int $index ): void {
		if ( $index < 0 ) {
			return;
		}

		$connections = self::getConnectionRows();
		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			return;
		}

		$tokens = self::readTokensFromRow( $connections[ $index ] );
		if ( '' === $tokens['access_token'] || '' === $tokens['refresh_token'] ) {
			self::clearSendContext();
			return;
		}

		self::$send_connection_index = $index;
		self::$send_config           = array(
			'access_token'        => $tokens['access_token'],
			'refresh_token'       => $tokens['refresh_token'],
			'auth_token_expires'  => $tokens['auth_token_expires'] > 0 ? $tokens['auth_token_expires'] : time() + HOUR_IN_SECONDS,
			'user_email'          => $tokens['user_email'],
			'vendor_name'         => 'google',
			'oauth_connection_id' => $index,
		);
	}

	public static function setSendInMemoryConfig( array $config ): void {
		self::$send_config = $config;
		if ( isset( $config['oauth_connection_id'] ) && is_numeric( $config['oauth_connection_id'] ) ) {
			self::$send_connection_index = (int) $config['oauth_connection_id'];
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function getSendConfig(): array {
		return self::$send_config;
	}

	public static function getSendConnectionIndex(): int {
		return self::$send_connection_index;
	}

	public static function isSendContextPrimed(): bool {
		return self::$send_connection_index >= 0
			&& '' !== (string) ( self::$send_config['access_token'] ?? '' )
			&& '' !== (string) ( self::$send_config['refresh_token'] ?? '' );
	}

	public static function clearSendContext(): void {
		self::$send_config           = array();
		self::$send_connection_index = -1;
	}

	/**
	 * @param mixed $pre Value from pre_option filter.
	 * @return mixed
	 */
	public static function filterPreOptionRead( $pre ) {
		if ( false !== $pre ) {
			return $pre;
		}

		if ( ! self::shouldSkipGlobalAuthOption() ) {
			return $pre;
		}

		if ( self::isSendContextPrimed() ) {
			return false;
		}

		return array();
	}

	/**
	 * @param mixed $value Stored option value.
	 * @return mixed
	 */
	public static function filterOptionRead( $value ) {
		if ( self::isSendContextPrimed() ) {
			$config = self::getSendConfig();
			if ( is_array( $config ) && '' !== (string) ( $config['access_token'] ?? '' ) ) {
				return $config;
			}
		}

		if ( self::shouldSkipGlobalAuthOption() ) {
			return is_array( $value ) ? $value : array();
		}

		return $value;
	}

	/**
	 * @param mixed $value     New value about to be saved.
	 * @param mixed $old_value Previous value.
	 * @return mixed
	 */
	public static function filterOptionWrite( $value, $old_value ) {
		if ( self::shouldSkipGlobalAuthOption() ) {
			return $old_value;
		}

		if ( self::isSendContextPrimed() && is_array( $value ) && ! self::$persisting_refresh ) {
			self::$persisting_refresh = true;
			self::persistFromSendContext( $value );
			self::setSendInMemoryConfig( array_merge( self::getSendConfig(), $value ) );
			self::$persisting_refresh = false;
			return $old_value;
		}

		return $value;
	}

	/**
	 * Gmail API delivery on the revamp mail pipeline via legacy Zend/Gmail transport.
	 *
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param int|null            $log_id         Email log ID (unused).
	 * @param array<string,mixed> $connection     Active connection row.
	 * @param array<string,mixed> $processed_data Normalized email data.
	 * @return array{success:bool,message:string,send:bool,transcript?:string}
	 */
	public static function sendViaLegacyTransport( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		unset( $log_id, $processed_data );

		$connection_index = self::resolveSendConnectionIndex( $connection );
		if (
			$connection_index >= 0
			&& 'gmail_api' === (string) ( $connection['provider'] ?? $connection['transport_type'] ?? '' )
		) {
			self::hydrateLegacyOAuthOption( $connection_index );
			if ( class_exists( ConnectionLegacySendBridge::class, false ) ) {
				ConnectionLegacySendBridge::apply_runtime_patch_for_connection( $connection, false );
			}
		}

		if ( ! class_exists( '\PostmanWpMail', false ) ) {
			if ( defined( 'POST_SMTP_PATH' ) && is_readable( POST_SMTP_PATH . 'Postman/PostmanWpMail.php' ) ) {
				require_once POST_SMTP_PATH . 'Postman/PostmanWpMail.php';
			} elseif ( function_exists( 'post_setupPostman' ) ) {
				post_setupPostman();
			}
		}

		if ( ! class_exists( '\PostmanWpMail', false ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post SMTP mail engine is not available.', 'post-smtp' ),
				'send'    => false,
			);
		}

		$skip_fallback = static function () {
			return true;
		};
		add_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );

		$headers     = $atts['headers'] ?? '';
		$attachments = $atts['attachments'] ?? array();

		$mailer  = new \PostmanWpMail();
		$success = $mailer->send(
			$atts['to'] ?? '',
			$atts['subject'] ?? '',
			$atts['message'] ?? '',
			$headers,
			$attachments
		);

		remove_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );

		$result = apply_filters( 'postman_wp_mail_result', null );
		if ( ! is_array( $result ) ) {
			$result = array();
		}

		$message = $success
			? __( 'Email sent successfully.', 'post-smtp' )
			: __( 'The email could not be sent. Check your Gmail API connection and OAuth tokens.', 'post-smtp' );

		if ( ! $success && isset( $result['exception'] ) && $result['exception'] instanceof \Exception ) {
			$message = sanitize_text_field( $result['exception']->getMessage() );
		}

		$payload = array(
			'success' => (bool) $success,
			'message' => $message,
			'send'    => (bool) $success,
		);

		if ( isset( $result['transcript'] ) && '' !== (string) $result['transcript'] ) {
			$payload['transcript'] = (string) $result['transcript'];
		}

		return $payload;
	}

	/**
	 * @param array<string,mixed> $connection Active connection row.
	 */
	private static function resolveSendConnectionIndex( array $connection ): int {
		if ( class_exists( '\Postman_Connection_Resolver', false ) ) {
			$routed = get_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT );
			if ( is_numeric( $routed ) && (int) $routed >= 0 ) {
				return (int) $routed;
			}
		}

		$email = sanitize_email( (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ) );
		foreach ( self::getConnectionRows() as $index => $row ) {
			if ( ! is_array( $row ) || 'gmail_api' !== (string) ( $row['provider'] ?? '' ) ) {
				continue;
			}
			if ( '' !== $email ) {
				$row_email = sanitize_email( (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ) );
				if ( $row_email !== $email ) {
					continue;
				}
			}
			return (int) $index;
		}

		return 0;
	}
}
