<?php
/**
 * Gmail OAuth — revamp connections (manual grant + one-click return URLs).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GmailRevampOAuth {

	/** Connection index to use for post-grant redirect (avoids repeated option reads). */
	private static int $grant_redirect_connection_id = -1;

	/**
	 * OAuth tokens captured before admin bootstrap (large query args exhaust PHP memory in object cache).
	 *
	 * @var array<string, string>|null
	 */
	private static ?array $captured_oneclick_payload = null;

	public static function isActive(): bool {
		if ( class_exists( CohortResolver::class, false ) && CohortResolver::usesReactAdmin() ) {
			return true;
		}

		return ! ConnectionRepositoryFactory::isLegacyMode();
	}

	public static function usesReactConnectionsAdmin(): bool {
		if ( class_exists( CohortResolver::class, false ) && CohortResolver::usesReactAdmin() ) {
			return true;
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return true;
		}

		return count( ConnectionSchema::readConnectionsOption() ) > 0;
	}

	/**
	 * Whether post-grant redirects should land on the revamp Connections UI.
	 */
	public static function shouldUseRevampOAuthReturn(): bool {
		if ( class_exists( CohortResolver::class, false ) && CohortResolver::usesReactAdmin() ) {
			return true;
		}

		return self::usesReactConnectionsAdmin();
	}

	/**
	 * Google returned an auth code on admin.php?page=postman for a revamp connection grant.
	 */
	public static function shouldHandleRevampGoogleCallback(): bool {
		return self::isRevampGmailOAuthReturnRequest();
	}

	/**
	 * Early bootstrap: legacy Google redirect URI hits Settings → forward before WP drops query args.
	 */
	public static function boot(): void {
		self::captureOneClickMiddlewarePayloadEarly();
		self::init();
		self::forwardLegacyOAuthCallbackToAdmin();
	}

	/**
	 * Google still redirects to options-general.php?page=postman; the menu lives on admin.php?page=postman.
	 * WordPress often canonicalizes that and strips ?code= — forward first while params remain.
	 */
	public static function forwardLegacyOAuthCallbackToAdmin(): void {
		if ( ! is_admin() ) {
			return;
		}

		$on_options_general = false;
		global $pagenow;
		if ( is_string( $pagenow ) && 'options-general.php' === $pagenow ) {
			$on_options_general = true;
		} elseif ( isset( $_SERVER['PHP_SELF'] ) ) {
			$on_options_general = ( false !== strpos( wp_unslash( (string) $_SERVER['PHP_SELF'] ), 'options-general.php' ) );
		} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$on_options_general = ( false !== strpos( wp_unslash( (string) $_SERVER['REQUEST_URI'] ), 'options-general.php' ) );
		}

		if ( ! $on_options_general ) {
			return;
		}

		if ( ! self::usesReactConnectionsAdmin() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' !== $page ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['error'] ) ) {
			return;
		}

		$forward_args = array();
		foreach ( $_GET as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				continue;
			}
			$forward_args[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
		}

		$redirect_target = add_query_arg( $forward_args, admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_target );
		exit;
	}

	/**
	 * Revamp manual OAuth should register admin.php?page=postman as an authorized redirect URI in Google Cloud.
	 */
	public static function manualOAuthCallbackUri(): string {
		if ( self::usesReactConnectionsAdmin() ) {
			return admin_url( 'admin.php?page=postman' );
		}

		return admin_url( 'options-general.php?page=postman' );
	}

	public static function init(): void {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;

		add_action( 'plugins_loaded', array( self::class, 'prepareCallbackContext' ), 1 );
		add_action( 'init', array( self::class, 'prepareCallbackContext' ), -9999 );
		add_action( 'init', array( self::class, 'normalizeGoogleCallbackAdminPage' ), -9998 );
		add_filter( 'post_smtp_oauth_in_progress_ttl', array( self::class, 'oauthInProgressTtl' ), 10, 1 );
		add_filter( 'post_smtp_google_oauth_callback_uri', array( self::class, 'filterGoogleOAuthCallbackUri' ), 10, 1 );
		add_filter( 'post_smtp_google_oauth_return_url', array( self::class, 'filterGoogleOAuthReturnUrl' ), 20, 1 );
		add_action( 'post_smtp_gmail_oauth_transaction_started', array( self::class, 'onGmailOAuthTransactionStarted' ), 10, 1 );
		add_action( 'init', array( self::class, 'forwardLegacyOAuthCallbackToAdmin' ), 0 );
		add_action( 'admin_init', array( self::class, 'redirectIfOAuthQueryStillPresent' ), 999 );
		add_action( 'admin_init', array( self::class, 'maybeRepairOrphanGmailAuthTokens' ), 20 );
		self::registerConfigurePanelHooks();
	}

	/**
	 * Entry point for {@see ConnectionOAuthReturnBridge} (init + admin_init).
	 */
	public static function processRevampOAuthReturn(): void {
		self::maybeExchangeGoogleGrant();
	}

	/**
	 * Migrate legacy postman_auth_token onto connection rows (revamp does not keep that option).
	 */
	public static function maybeRepairOrphanGmailAuthTokens(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! GmailConnectionTokens::shouldStoreTokensOnConnections() ) {
			return;
		}

		if ( isset( $_GET['code'] ) || isset( $_GET['state'] ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' !== $page ) {
			return;
		}

		if ( ! class_exists( GmailConnectionTokens::class, false ) ) {
			return;
		}

		GmailConnectionTokens::migrateLegacyGlobalAuthOnce();
	}

	/**
	 * Skip the React SPA shell while Google OAuth query args are still on admin.php?page=postman.
	 */
	public static function shouldDeferReactAdminBootstrap(): bool {
		if ( ! is_admin() || ! self::shouldUseRevampOAuthReturn() ) {
			return false;
		}

		if ( self::isOneClickMiddlewareReturnRequest() ) {
			return true;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['state'] ) && ! isset( $_GET['scope'] ) ) {
			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( '' !== $page && 'postman' !== $page ) {
			return false;
		}

		return true;
	}

	private static function maybeExchangeGoogleGrant(): void {
		if ( defined( 'POST_SMTP_GMAIL_GRANT_HANDLED' ) ) {
			self::redirectIfOAuthQueryStillPresent();
			return;
		}

		if ( ! self::shouldProcessRevampGoogleGrant() ) {
			return;
		}

		self::exchangeGrantAndRedirect();
	}

	/**
	 * Google redirected to admin.php?page=postman with an authorization code.
	 */
	public static function isRevampGmailOAuthReturnRequest(): bool {
		return self::shouldProcessRevampGoogleGrant();
	}

	/**
	 * Whether this request should exchange a Google OAuth code for revamp Connections.
	 */
	public static function shouldProcessRevampGoogleGrant(): bool {
		if ( ! self::shouldUseRevampOAuthReturn() ) {
			return false;
		}

		if ( ! is_admin() ) {
			return false;
		}

		if ( ! isset( $_GET['code'] ) || '' === (string) $_GET['code'] ) {
			return false;
		}

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' === $state ) {
			return false;
		}

		self::bindStateFromActiveGrant( $state );

		if ( ! self::isKnownIncomingState( $state ) ) {
			if ( self::looksLikeGoogleOAuthScopeParam() && self::hasActiveGrantContext() ) {
				$connection_id = self::resolveActiveGrantConnectionId();
				if ( $connection_id >= 0 ) {
					self::rememberStateBinding( $state, $connection_id, self::resolveConnectTab() );
				}
			}
			if ( ! self::isKnownIncomingState( $state ) ) {
				return false;
			}
		}

		if ( ! is_user_logged_in() || ! self::currentUserCanCompleteGmailOAuth() ) {
			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( '' !== $page && 'postman' !== $page ) {
			return false;
		}

		return true;
	}

	public static function currentUserCanCompleteGmailOAuth(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( class_exists( '\Postman', false ) && current_user_can( \Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Strip Google OAuth query args when grant already finished (prevents reload loops / OOM).
	 */
	public static function redirectIfOAuthQueryStillPresent(): void {
		if ( ! is_admin() || ! self::shouldUseRevampOAuthReturn() ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['state'] ) ) {
			return;
		}

		$auth_code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['code'] ) ) : '';
		if ( ! defined( 'POST_SMTP_GMAIL_GRANT_HANDLED' ) ) {
			if ( '' === $auth_code || ! get_transient( 'post_smtp_gmail_oauth_code_' . md5( $auth_code ) ) ) {
				return;
			}
		}

		self::redirectAfterManualGrant(
			true,
			__( 'The OAuth 2.0 authorization was successful. Ready to send e-mail.', 'post-smtp' )
		);
	}

	/**
	 * Exchange Google auth code, persist tokens on the connection row, redirect to connection edit.
	 */
	public static function exchangeGrantAndRedirect(): void {
		static $in_flight = false;

		if ( $in_flight || defined( 'POST_SMTP_GMAIL_GRANT_HANDLED' ) ) {
			self::redirectIfOAuthQueryStillPresent();
			return;
		}

		$auth_code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['code'] ) ) : '';
		if ( '' !== $auth_code ) {
			$done_key = 'post_smtp_gmail_oauth_code_' . md5( $auth_code );
			if ( get_transient( $done_key ) ) {
				self::redirectAfterManualGrant(
					true,
					__( 'The OAuth 2.0 authorization was successful. Ready to send e-mail.', 'post-smtp' )
				);
				return;
			}
		}

		$in_flight = true;

		self::bootstrapGrantDependencies();

		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		self::restoreInFlightConnectionFromState( $incoming_state );

		$connection_id = self::resolveConnectionId();
		if ( $connection_id >= 0 ) {
			self::$grant_redirect_connection_id = $connection_id;
			if ( class_exists( ConnectionOAuthGrantBridge::class, false ) ) {
				ConnectionOAuthGrantBridge::prepareForGrant( $connection_id, 'gmail_api' );
				if ( class_exists( '\PostmanOptions', false ) ) {
					\PostmanOptions::getInstance()->reload();
				}
			}
		}

		$session = \PostmanSession::getInstance();
		if ( ! $session->isSetOauthInProgress() && '' !== $incoming_state ) {
			$session->setOauthInProgress( $incoming_state );
		}

		$transaction_id = $session->isSetOauthInProgress() ? (string) $session->getOauthInProgress() : $incoming_state;

		\PostmanUtils::lock();

		try {
			$authentication_manager = \PostmanAuthenticationManagerFactory::getInstance()->createAuthenticationManager();
			if ( $authentication_manager->processAuthorizationGrantCode( $transaction_id ) ) {
				$store_index = self::$grant_redirect_connection_id >= 0
					? self::$grant_redirect_connection_id
					: self::resolveConnectionId();
				if ( $store_index >= 0 ) {
					self::$grant_redirect_connection_id = $store_index;
				}

				if ( class_exists( GmailConnectionTokens::class, false )
					&& GmailConnectionTokens::shouldSkipGlobalAuthOption() ) {
					GmailConnectionTokens::storeTokensAfterOAuthGrant( $store_index );
				} else {
					\PostmanOAuthToken::getInstance()->save();
					if ( class_exists( GmailConnectionTokens::class, false ) ) {
						GmailConnectionTokens::storeTokensAfterOAuthGrant( $store_index );
					}
				}
				if ( '' !== $auth_code ) {
					set_transient( 'post_smtp_gmail_oauth_code_' . md5( $auth_code ), 1, 15 * MINUTE_IN_SECONDS );
				}
				self::redirectAfterManualGrant(
					true,
					__( 'The OAuth 2.0 authorization was successful. Ready to send e-mail.', 'post-smtp' )
				);
			}

			self::redirectAfterManualGrant(
				false,
				__( 'Your email provider did not grant Postman permission. Try again.', 'post-smtp' )
			);
		} catch ( \PostmanStateIdMissingException $e ) {
			self::redirectAfterManualGrant(
				false,
				__( 'The grant code from Google had no accompanying state and may be a forgery', 'post-smtp' )
			);
		} catch ( \Exception $e ) {
			$message = sprintf(
				/* translators: %s is the error message */
				__( 'Error authenticating with this Client ID. [%s]', 'post-smtp' ),
				$e->getMessage()
			);
			self::redirectAfterManualGrant( false, $message );
		} finally {
			if ( class_exists( 'PostmanUtils', false ) ) {
				\PostmanUtils::unlock();
			}
		}
	}

	private static function looksLikeGoogleOAuthScopeParam(): bool {
		$scope = isset( $_GET['scope'] ) ? wp_unslash( (string) $_GET['scope'] ) : '';
		if ( '' === $scope ) {
			return false;
		}

		return str_contains( $scope, 'googleapis.com' ) || str_contains( $scope, 'gmail.' );
	}

	private static function bootstrapGrantDependencies(): void {
		if ( ! defined( 'POST_SMTP_PATH' ) ) {
			return;
		}

		$base = POST_SMTP_PATH . '/Postman/';
		$files = array(
			'PostmanSession.php',
			'PostmanOptions.php',
			'PostmanOAuthToken.php',
			'PostmanUtils.php',
			'Postman-Auth/PostmanAuthenticationManagerFactory.php',
		);

		foreach ( $files as $file ) {
			$path = $base . $file;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	}

	/**
	 * Restore grant transients as early as possible (Office 365 parity).
	 */
	public static function prepareCallbackContext(): void {
		if ( ! self::usesReactConnectionsAdmin() ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['error'] ) ) {
			return;
		}

		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' === $incoming_state ) {
			return;
		}

		self::bindStateFromActiveGrant( $incoming_state );

		if ( ! self::isKnownIncomingState( $incoming_state ) ) {
			return;
		}

		self::restoreInFlightConnectionFromState( $incoming_state );

		if ( class_exists( 'PostmanSession', false ) ) {
			$session = \PostmanSession::getInstance();
			if ( ! $session->isSetOauthInProgress() ) {
				$session->setOauthInProgress( $incoming_state );
			}
		}
	}

	/**
	 * Google may return to /wp-admin/?code=…; Postman grant handling expects page=postman.
	 */
	public static function normalizeGoogleCallbackAdminPage(): void {
		if ( ! is_admin() || ! self::usesReactConnectionsAdmin() ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['error'] ) ) {
			return;
		}

		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' === $incoming_state || ! self::isKnownIncomingState( $incoming_state ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' === $page ) {
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$forward_args = array( 'page' => 'postman' );
		foreach ( $_GET as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key || is_array( $value ) ) {
				continue;
			}
			$forward_args[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
		}

		$redirect_target = add_query_arg( $forward_args, admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_target );
		exit;
	}

	/**
	 * @param string $url Default return URL after Gmail manual OAuth (wizard / dashboard).
	 */
	public static function filterGoogleOAuthReturnUrl( string $url ): string {
		if ( ! self::usesReactConnectionsAdmin() ) {
			return $url;
		}

		$success = isset( $_GET['success'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['success'] ) ) : '';
		$extra   = array();
		if ( '' !== $success ) {
			$extra['success'] = '1' === $success ? '1' : '0';
		}
		if ( isset( $_GET['msg'] ) ) {
			$extra['msg'] = sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) );
		}

		return self::afterOAuthReturnUrl( $extra );
	}

	/**
	 * @param string $uri Default legacy Settings → Post SMTP callback.
	 */
	public static function filterGoogleOAuthCallbackUri( string $uri ): string {
		if ( ! self::usesReactConnectionsAdmin() ) {
			return $uri;
		}

		return self::manualOAuthCallbackUri();
	}

	/**
	 * @param int $ttl Seconds.
	 */
	public static function oauthInProgressTtl( int $ttl ): int {
		if ( ! self::usesReactConnectionsAdmin() ) {
			return $ttl;
		}

		return max( $ttl, 30 * MINUTE_IN_SECONDS );
	}

	/**
	 * @param string $transaction_id OAuth state sent to Google.
	 */
	public static function onGmailOAuthTransactionStarted( string $transaction_id ): void {
		if ( '' === $transaction_id ) {
			return;
		}

		$connection_id = self::resolveConnectionId();
		if ( $connection_id < 0 ) {
			$connection_id = self::resolveRedirectConnectionId();
		}
		$tab = self::resolveConnectTab();
		if ( $connection_id >= 0 ) {
			self::rememberGrant( $connection_id, $tab );
		}
		self::rememberStateBinding( $transaction_id, max( 0, $connection_id ), $tab );
	}

	/**
	 * Resolve connection index for post-grant redirect (never rely on URL hash — wp_safe_redirect strips it).
	 */
	public static function resolveRedirectConnectionId(): int {
		$connection_id = self::resolveConnectionId();
		if ( $connection_id >= 0 ) {
			$rows = GmailConnectionTokens::getConnectionRows();
			if ( isset( $rows[ $connection_id ] ) && is_array( $rows[ $connection_id ] ) ) {
				return $connection_id;
			}
		}

		foreach ( GmailConnectionTokens::getConnectionRows() as $index => $row ) {
			if ( ! is_array( $row ) || 'gmail_api' !== (string) ( $row['provider'] ?? '' ) ) {
				continue;
			}
			return (int) $index;
		}

		$rows = GmailConnectionTokens::getConnectionRows();
		if ( isset( $rows[0] ) && is_array( $rows[0] ) ) {
			return 0;
		}

		$first = is_array( $rows ) ? array_key_first( $rows ) : null;

		return null !== $first ? (int) $first : -1;
	}

	/**
	 * Persist tokens (when applicable) and redirect to the connection edit UI.
	 */
	public static function redirectAfterManualGrant( bool $success, string $message ): void {
		if ( ! defined( 'POST_SMTP_GMAIL_GRANT_HANDLED' ) ) {
			define( 'POST_SMTP_GMAIL_GRANT_HANDLED', true );
		}

		if ( ! self::shouldUseRevampOAuthReturn() ) {
			$fallback = remove_query_arg(
				array( 'code', 'state', 'scope', 'iss', 'error' ),
				admin_url( 'admin.php?page=postman' )
			);
			wp_safe_redirect( $fallback );
			exit;
		}

		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' !== $incoming_state ) {
			self::restoreInFlightConnectionFromState( $incoming_state );
		}

		if ( $success && class_exists( GmailConnectionTokens::class, false )
			&& ! GmailConnectionTokens::shouldSkipGlobalAuthOption() ) {
			GmailConnectionTokens::persistFromGlobalAuthOption();
		}

		$connection_id = self::$grant_redirect_connection_id;
		if ( $connection_id < 0 ) {
			$connection_id = self::resolveRedirectConnectionId();
		}

		if ( class_exists( 'PostmanUtils', false ) ) {
			\PostmanUtils::unlock();
		}
		if ( class_exists( 'PostmanSession', false ) ) {
			\PostmanSession::getInstance()->unsetOauthInProgress();
		}

		self::clearGrantTransients();

		$extra = array(
			'success' => $success ? '1' : '0',
		);
		if ( '' !== $message ) {
			$extra['msg'] = wp_strip_all_tags( $message );
		}
		$extra['gmail_connect_tab'] = self::resolveConnectTab();

		$redirect_target = self::connectionEditRedirectUrl( $connection_id, $extra );
		$redirect_target = remove_query_arg(
			array( 'code', 'state', 'scope', 'iss', 'error' ),
			$redirect_target
		);

		$redirected = wp_safe_redirect( $redirect_target );
		if ( ! $redirected ) {
			wp_redirect( $redirect_target );
		}
		exit;
	}

	public static function hasCapturedOneClickPayload(): bool {
		return null !== self::$captured_oneclick_payload && array() !== self::$captured_oneclick_payload;
	}

	/**
	 * Read middleware callback params from memory after stripping them from $_GET / $_REQUEST.
	 */
	private static function oneClickRequestValue( string $key ): string {
		if ( null !== self::$captured_oneclick_payload && isset( self::$captured_oneclick_payload[ $key ] ) ) {
			return (string) self::$captured_oneclick_payload[ $key ];
		}

		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) );
	}

	/**
	 * Copy token query args off the request superglobals before Postman / React admin loads.
	 */
	public static function captureOneClickMiddlewarePayloadEarly(): void {
		if ( null !== self::$captured_oneclick_payload ) {
			return;
		}

		if ( function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' ) && post_smtp_gmail_oneclick_guard_has_incoming() ) {
			self::captureOneClickPayloadFromArray( $GLOBALS['post_smtp_gmail_oneclick_incoming'] );
			unset( $GLOBALS['post_smtp_gmail_oneclick_incoming'] );
			self::stripOneClickTokenQueryArgsFromSuperglobals();
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : '';
		if ( 'gmail_oauth_redirect' !== $action ) {
			return;
		}

		if ( ! isset( $_GET['access_token'] ) && ! isset( $_GET['error'] ) ) {
			return;
		}

		$incoming = array();
		foreach ( $_GET as $key => $value ) {
			if ( ! is_string( $key ) || is_array( $value ) ) {
				continue;
			}
			$incoming[ $key ] = (string) wp_unslash( $value );
		}

		self::captureOneClickPayloadFromArray( $incoming );
		self::stripOneClickTokenQueryArgsFromSuperglobals();
	}

	/**
	 * @param array<string, string> $incoming Raw callback query values (tokens are not run through sanitize_text_field).
	 */
	private static function captureOneClickPayloadFromArray( array $incoming ): void {
		$keys = array(
			'access_token',
			'refresh_token',
			'expires_in',
			'msg',
			'user_email',
			'error',
			'success',
			'_wpnonce',
			'connection_id',
			'ps-route',
		);

		$payload = array();
		foreach ( $keys as $key ) {
			if ( ! isset( $incoming[ $key ] ) || '' === (string) $incoming[ $key ] ) {
				continue;
			}
			$value = (string) $incoming[ $key ];
			if ( in_array( $key, array( 'access_token', 'refresh_token' ), true ) ) {
				$payload[ $key ] = $value;
				continue;
			}
			if ( 'user_email' === $key ) {
				$payload[ $key ] = sanitize_email( $value );
			} elseif ( 'expires_in' === $key || 'connection_id' === $key ) {
				$payload[ $key ] = (string) (int) $value;
			} else {
				$payload[ $key ] = sanitize_text_field( $value );
			}
		}

		self::$captured_oneclick_payload = $payload;
	}

	/**
	 * Remove large token query args so the rest of admin bootstrap does not copy them into cache/logs.
	 */
	public static function stripOneClickTokenQueryArgsFromSuperglobals(): void {
		foreach ( array( 'access_token', 'refresh_token' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) {
				unset( $_GET[ $key ] );
			}
			if ( isset( $_REQUEST[ $key ] ) ) {
				unset( $_REQUEST[ $key ] );
			}
		}
	}

	public static function isOneClickMiddlewareReturnRequest(): bool {
		if ( self::hasCapturedOneClickPayload() ) {
			return true;
		}

		if ( function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' ) && post_smtp_gmail_oneclick_guard_has_incoming() ) {
			return true;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : '';
		if ( 'gmail_oauth_redirect' !== $action ) {
			return false;
		}

		return isset( $_GET['access_token'] ) || isset( $_GET['error'] );
	}

	/**
	 * OAuth callback URL sent to Gmail one-click middleware (stored as redirect_url).
	 * Includes hash route for the React admin; tokens are appended by middleware on return.
	 *
	 * @param int $connection_id Connection index, or -1 for connections/add.
	 */
	public static function oneClickMiddlewareClientSiteUrl( int $connection_id = -1 ): string {
		$parts = self::oneClickMiddlewareClientSiteUrlParts( $connection_id );

		return $parts['url'] . $parts['hash'];
	}

	/**
	 * Split query URL and React hash — hash must not ride on auth-url HTTP requests (GET fragments truncate).
	 *
	 * @return array{url:string,hash:string}
	 */
	public static function oneClickMiddlewareClientSiteUrlParts( int $connection_id = -1 ): array {
		$query = array(
			'page'              => 'postman',
			'action'            => 'gmail_oauth_redirect',
			'gmail_connect_tab' => 'one_click',
		);

		if ( $connection_id >= 0 ) {
			$query['connection_id'] = $connection_id;
			$query['ps-route']      = 'connections/' . $connection_id . '/edit';
			$hash                   = '#/connections/' . $connection_id . '/edit';
		} else {
			$query['ps-route'] = 'connections/add';
			$hash              = '#/connections/add';
		}

		return array(
			'url'  => add_query_arg( $query, admin_url( 'admin.php' ) ),
			'hash' => $hash,
		);
	}

	/**
	 * Legacy configuration wizard vs revamp Connections editor (same middleware callback).
	 *
	 * @return 'legacy'|'revamp'
	 */
	public static function resolveOneClickReturnMode(): string {
		$connection_arg = self::oneClickRequestValue( 'connection_id' );
		if ( '' !== $connection_arg && is_numeric( $connection_arg ) && (int) $connection_arg >= 0 ) {
			return 'revamp';
		}

		if ( self::hasActiveGrantContext() || self::hasRevampOneClickIntent() ) {
			return 'revamp';
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';

		if ( 'postman' === $page && isset( $_GET['gmail_connect_tab'] ) ) {
			$tab = sanitize_key( wp_unslash( (string) $_GET['gmail_connect_tab'] ) );
			if ( in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
				return 'revamp';
			}
		}

		if ( 'postman' === $page ) {
			$ps_route = isset( $_GET['ps-route'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) ) : '';
			if ( str_starts_with( $ps_route, 'connections/' ) ) {
				return 'revamp';
			}
		}

		if ( 'postman/configuration_wizard' === $page ) {
			return 'legacy';
		}

		if ( isset( $_GET['socket'] ) && 'gmail_api' === sanitize_key( wp_unslash( (string) $_GET['socket'] ) ) ) {
			if ( 'postman' !== $page ) {
				return 'legacy';
			}
		}

		if ( self::shouldUseRevampOAuthReturn() && ( 'postman' === $page || '' === $page ) ) {
			return 'revamp';
		}

		return 'legacy';
	}

	/**
	 * @return array<string,string>
	 */
	private static function oneClickQueryArgsToStrip(): array {
		return array(
			'action',
			'access_token',
			'refresh_token',
			'expires_in',
			'user_email',
			'_wpnonce',
			'connection_id',
			'success',
			'msg',
			'code',
			'state',
			'scope',
			'iss',
			'error',
			'socket',
			'step',
		);
	}

	/**
	 * Revamp one-click started (even if middleware still redirects to the legacy wizard slug).
	 */
	private static function hasRevampOneClickIntent(): bool {
		if ( ! self::shouldUseRevampOAuthReturn() ) {
			return false;
		}

		$tab = get_transient( 'post_smtp_gmail_oauth_connect_tab' );
		if ( 'one_click' === $tab ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta_tab = get_user_meta( $user_id, 'post_smtp_gmail_oauth_connect_tab', true );
			if ( 'one_click' === $meta_tab ) {
				return true;
			}
		}

		return false;
	}

	private static function oauthAdminMessage( string $text ): string {
		return did_action( 'init' ) ? __( $text, 'post-smtp' ) : $text;
	}

	private static function verifyOneClickReturnNonce(): bool {
		$nonce = self::oneClickRequestValue( '_wpnonce' );
		if ( '' === $nonce ) {
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				$stored = get_transient( 'post_smtp_gmail_oneclick_expected_nonce_' . $user_id );
				if ( is_string( $stored ) && '' !== $stored ) {
					$nonce = $stored;
				}
			}
		}

		if ( '' !== $nonce ) {
			$valid = (bool) wp_verify_nonce( $nonce, 'gmail_oauth_redirect' );
			if ( $valid ) {
				$user_id = get_current_user_id();
				if ( $user_id > 0 ) {
					delete_transient( 'post_smtp_gmail_oneclick_expected_nonce_' . $user_id );
				}

				return true;
			}
		}

		if (
			current_user_can( 'manage_options' )
			&& self::hasRevampOneClickIntent()
			&& '' !== self::oneClickRequestValue( 'access_token' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Drop token query args when verification fails (avoid admin OOM / leaked tokens in the bar).
	 *
	 * @param 'legacy'|'revamp' $mode
	 */
	private static function abortOneClickReturnWithoutTokens( string $mode ): void {
		define( 'POST_SMTP_GMAIL_ONECLICK_HANDLED', true );

		$target = 'legacy' === $mode
			? admin_url( 'admin.php?page=postman/configuration_wizard&socket=gmail_api&step=2' )
			: self::connectionEditRedirectUrl( 0, array( 'success' => '0' ) );

		$target = remove_query_arg( self::oneClickQueryArgsToStrip(), $target );
		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * Persist middleware tokens and redirect (legacy wizard or revamp connection edit).
	 */
	public static function processOneClickMiddlewareReturn(): void {
		if ( ! self::isOneClickMiddlewareReturnRequest() ) {
			return;
		}

		if ( defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) && ! self::currentUserCanCompleteGmailOAuth() ) {
			wp_die( esc_html( self::oauthAdminMessage( 'You do not have sufficient permissions to access this page.' ) ) );
		}

		$mode = self::resolveOneClickReturnMode();

		if ( ! self::verifyOneClickReturnNonce() ) {
			self::abortOneClickReturnWithoutTokens( $mode );
		}

		define( 'POST_SMTP_GMAIL_ONECLICK_HANDLED', true );
		if ( 'revamp' === $mode ) {
			self::prepareOneClickGrantContext();
		}

		if ( '' !== self::oneClickRequestValue( 'error' ) ) {
			$msg = self::oneClickRequestValue( 'msg' );
			if ( '' === $msg ) {
				$msg = self::oauthAdminMessage( 'Gmail authorization failed.' );
			}
			if ( 'legacy' === $mode ) {
				self::redirectAfterLegacyOneClickGrant( false, $msg );
			}
			self::redirectAfterOneClickGrant( false, $msg );
		}

		$access_token  = self::oneClickRequestValue( 'access_token' );
		$refresh_token = self::oneClickRequestValue( 'refresh_token' );
		if ( '' === $access_token ) {
			return;
		}

		self::stripOneClickTokenQueryArgsFromSuperglobals();

		$expires_in = (int) self::oneClickRequestValue( 'expires_in' );
		$msg        = self::oneClickRequestValue( 'msg' );
		$user_email = self::oneClickRequestValue( 'user_email' );
		$expires_at = time() + max( 0, $expires_in );

		if ( 'legacy' === $mode ) {
			self::completeLegacyOneClickGrant( $access_token, $refresh_token, $expires_at, $user_email, $msg );
			return;
		}

		$connection_id = self::resolveConnectionIdForOneClick();
		$stored        = false;

		if ( '' !== $refresh_token && class_exists( GmailConnectionTokens::class, false ) ) {
			$index = GmailConnectionTokens::resolveIndexForOneClickCallback( $connection_id );
			if ( $index >= 0 ) {
				$stored = GmailConnectionTokens::storeOnConnection(
					$index,
					$access_token,
					$refresh_token,
					$expires_at,
					$user_email
				);
				if ( $stored ) {
					self::$grant_redirect_connection_id = $index;
					if ( GmailConnectionTokens::shouldSkipGlobalAuthOption() ) {
						GmailConnectionTokens::scheduleGlobalAuthCleanup();
					}
				}
			}
		}

		if ( ! $stored && '' !== $refresh_token && ! GmailConnectionTokens::shouldSkipGlobalAuthOption() ) {
			update_option(
				'postman_auth_token',
				array(
					'access_token'       => $access_token,
					'refresh_token'      => $refresh_token,
					'auth_token_expires' => $expires_at,
					'vendor_name'        => 'google',
					'user_email'         => $user_email,
				),
				false
			);
			$stored = true;
		}

		$success_msg = '' !== $msg ? $msg : __( 'Gmail connected successfully.', 'post-smtp' );
		self::redirectAfterOneClickGrant( $stored || '' !== $access_token, $success_msg );
	}

	/**
	 * Legacy wizard: global postman_auth_token and redirect to configuration wizard (no tokens in URL).
	 */
	private static function completeLegacyOneClickGrant(
		string $access_token,
		string $refresh_token,
		int $expires_at,
		string $user_email,
		string $msg
	): void {
		if ( '' !== $access_token ) {
			update_option(
				'postman_auth_token',
				array(
					'access_token'       => $access_token,
					'refresh_token'      => $refresh_token,
					'auth_token_expires' => $expires_at,
					'vendor_name'        => 'google',
					'user_email'         => $user_email,
				),
				false
			);
		}

		$success_msg = '' !== $msg
			? $msg
			: __( 'The OAuth 2.0 authorization was successful. Ready to send e-mail.', 'post-smtp' );
		self::redirectAfterLegacyOneClickGrant( '' !== $access_token, $success_msg );
	}

	public static function redirectAfterLegacyOneClickGrant( bool $success, string $message ): void {
		$target = admin_url( 'admin.php?page=postman/configuration_wizard&socket=gmail_api&step=2' );
		if ( $success ) {
			$target = add_query_arg( 'success', '1', $target );
		}
		if ( '' !== $message ) {
			$target = add_query_arg( 'msg', rawurlencode( wp_strip_all_tags( $message ) ), $target );
		}

		$target = remove_query_arg( self::oneClickQueryArgsToStrip(), $target );
		wp_safe_redirect( $target );
		exit;
	}

	private static function prepareOneClickGrantContext(): void {
		set_transient( 'post_smtp_gmail_oauth_connect_tab', 'one_click', self::GRANT_CONTEXT_TTL );

		$connection_id = self::resolveConnectionIdForOneClick();
		if ( $connection_id < 0 ) {
			return;
		}

		set_transient( 'post_smtp_gmail_oauth_connection_id', $connection_id, self::GRANT_CONTEXT_TTL );

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id', (int) $connection_id );
			update_user_meta( $user_id, 'post_smtp_gmail_oauth_connect_tab', 'one_click' );
		}
	}

	/**
	 * Connection index from ps-route (connections/3/edit) or connections/add.
	 */
	public static function parseConnectionIndexFromPsRoute(): int {
		$route = self::oneClickRequestValue( 'ps-route' );
		if ( '' === $route && isset( $_GET['ps-route'] ) ) {
			$route = sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) );
		}
		if ( '' === $route ) {
			return -1;
		}

		if ( preg_match( '#^connections/(\d+)/edit$#', $route, $matches ) ) {
			return (int) $matches[1];
		}

		if ( str_starts_with( $route, 'connections/add' ) ) {
			return -1;
		}

		return -1;
	}

	public static function clientUrlIsAddRoute(): bool {
		$route = self::oneClickRequestValue( 'ps-route' );
		if ( '' !== $route ) {
			return str_starts_with( $route, 'connections/add' );
		}

		if ( isset( $_GET['ps-route'] ) ) {
			$route = sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) );

			return str_starts_with( $route, 'connections/add' );
		}

		return false;
	}

	private static function resolveConnectionIdForOneClick(): int {
		$from_grant = self::resolveConnectionId();
		if ( $from_grant >= 0 ) {
			return $from_grant;
		}

		$from_route = self::parseConnectionIndexFromPsRoute();
		if ( $from_route >= 0 ) {
			if ( class_exists( ConnectionOAuthGrantBridge::class, false ) ) {
				return ConnectionOAuthGrantBridge::resolveGmailGrantConnectionId( $from_route );
			}

			return $from_route;
		}

		if ( self::clientUrlIsAddRoute() ) {
			return -1;
		}

		$connection_arg = self::oneClickRequestValue( 'connection_id' );
		if ( '' !== $connection_arg && is_numeric( $connection_arg ) ) {
			$from_url = (int) $connection_arg;
			if ( class_exists( ConnectionOAuthGrantBridge::class, false ) ) {
				return ConnectionOAuthGrantBridge::resolveGmailGrantConnectionId( $from_url );
			}

			return $from_url;
		}

		if ( class_exists( ConnectionOAuthGrantBridge::class, false ) ) {
			$resolved = ConnectionOAuthGrantBridge::resolveGmailGrantConnectionId( null );
			if ( $resolved >= 0 ) {
				return $resolved;
			}
		}

		return self::resolveRedirectConnectionId();
	}

	public static function redirectAfterOneClickGrant( bool $success, string $message ): void {
		if ( ! defined( 'POST_SMTP_GMAIL_GRANT_HANDLED' ) ) {
			define( 'POST_SMTP_GMAIL_GRANT_HANDLED', true );
		}

		$connection_id = self::$grant_redirect_connection_id;
		if ( $connection_id < 0 ) {
			$connection_id = self::resolveConnectionIdForOneClick();
		}
		if ( $connection_id < 0 && class_exists( GmailConnectionTokens::class, false ) ) {
			$connection_id = GmailConnectionTokens::resolveIndexForOneClickCallback( -1 );
		}
		self::$grant_redirect_connection_id = max( 0, $connection_id );

		if ( class_exists( 'PostmanUtils', false ) ) {
			\PostmanUtils::unlock();
		}

		self::clearGrantTransients();

		$extra = array(
			'success'           => $success ? '1' : '0',
			'gmail_connect_tab' => 'one_click',
		);
		if ( '' !== $message ) {
			$extra['msg'] = wp_strip_all_tags( $message );
		}

		$redirect_target = self::connectionEditRedirectUrl( self::$grant_redirect_connection_id, $extra );
		$redirect_target = remove_query_arg( self::oneClickQueryArgsToStrip(), $redirect_target );

		wp_safe_redirect( $redirect_target );
		exit;
	}

	/**
	 * Redirect target for PHP header redirects (no hash fragment).
	 *
	 * @param int                 $connection_id Connection index in postman_connections.
	 * @param array<string,mixed> $query_args    success, msg, gmail_connect_tab, ….
	 */
	public static function connectionEditRedirectUrl( int $connection_id, array $query_args = array() ): string {
		$route = $connection_id >= 0
			? 'connections/' . (int) $connection_id . '/edit'
			: 'connections';

		$query_args = array_merge(
			array(
				'page'     => 'postman',
				'ps-route' => $route,
			),
			$query_args
		);

		return add_query_arg( $query_args, admin_url( 'admin.php' ) );
	}

	/**
	 * After OAuth, land on the connection edit screen (HashRouter path).
	 *
	 * @param int                 $connection_id Connection index in postman_connections.
	 * @param array<string,mixed> $query_args    success, msg, gmail_connect_tab, ….
	 */
	public static function connectionEditUrl( int $connection_id, array $query_args = array() ): string {
		$route = $connection_id >= 0
			? 'connections/' . (int) $connection_id . '/edit'
			: 'connections';

		$query_args = array_merge(
			array(
				'page'     => 'postman',
				'ps-route' => $route,
			),
			$query_args
		);

		$url  = add_query_arg( $query_args, admin_url( 'admin.php' ) );
		$hash = $connection_id >= 0
			? '#/connections/' . (int) $connection_id . '/edit'
			: '#/connections';

		// Hash for copy/paste links; server redirects must use ps-route (fragments are stripped).
		return $url . $hash;
	}

	/**
	 * @param array<string,mixed> $extra success, msg, etc.
	 */
	public static function afterOAuthReturnUrl( array $extra = array() ): string {
		$connection_id = self::$grant_redirect_connection_id;
		if ( $connection_id < 0 ) {
			$connection_id = self::resolveRedirectConnectionId();
		}
		if ( $connection_id < 0 ) {
			$connection_id = self::resolveConnectionId();
		}

		$extra['gmail_connect_tab'] = self::resolveConnectTab();

		return self::connectionEditRedirectUrl( $connection_id, $extra );
	}

	// --- OAuth grant context (connection index survives Google redirect) ---

	private const GRANT_CONTEXT_TTL = 30 * MINUTE_IN_SECONDS;

	private static function grantBindingKey( string $state ): string {
		return 'post_smtp_gmail_oauth_bind_' . md5( $state );
	}

	public static function isKnownIncomingState( string $state ): bool {
		$state = sanitize_text_field( $state );
		if ( '' === $state ) {
			return false;
		}

		$bound = get_transient( self::grantBindingKey( $state ) );
		if ( is_array( $bound ) && isset( $bound['connection_id'] ) && is_numeric( $bound['connection_id'] ) ) {
			return (int) $bound['connection_id'] >= 0;
		}

		if ( class_exists( 'PostmanSession', false ) ) {
			$session = \PostmanSession::getInstance();
			if ( $session->isSetOauthInProgress() ) {
				$expected = (string) $session->getOauthInProgress();
				if ( '' !== $expected && hash_equals( $expected, $state ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function hasActiveGrantContext(): bool {
		$id = get_transient( 'post_smtp_gmail_oauth_connection_id' );
		if ( is_numeric( $id ) && (int) $id >= 0 ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta = get_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id', true );
			if ( is_numeric( $meta ) && (int) $meta >= 0 ) {
				return true;
			}
		}

		return false;
	}

	public static function bindStateFromActiveGrant( string $state ): void {
		$state = sanitize_text_field( $state );
		if ( '' === $state ) {
			return;
		}

		$bound = get_transient( self::grantBindingKey( $state ) );
		if ( is_array( $bound ) && isset( $bound['connection_id'] ) && is_numeric( $bound['connection_id'] ) ) {
			return;
		}

		if ( class_exists( 'PostmanSession', false ) ) {
			$session = \PostmanSession::getInstance();
			if ( $session->isSetOauthInProgress() ) {
				$expected = (string) $session->getOauthInProgress();
				if ( '' !== $expected && hash_equals( $expected, $state ) ) {
					$connection_id = self::resolveActiveGrantConnectionId();
					if ( $connection_id >= 0 ) {
						self::rememberStateBinding( $state, $connection_id, self::resolveConnectTab() );
					}
					return;
				}
			}
		}

		if ( ! self::hasActiveGrantContext() ) {
			return;
		}

		$connection_id = self::resolveActiveGrantConnectionId();
		if ( $connection_id < 0 ) {
			return;
		}

		self::rememberStateBinding( $state, $connection_id, self::resolveConnectTab() );
	}

	private static function resolveActiveGrantConnectionId(): int {
		$id = get_transient( 'post_smtp_gmail_oauth_connection_id' );
		if ( is_numeric( $id ) && (int) $id >= 0 ) {
			return (int) $id;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta = get_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id', true );
			if ( is_numeric( $meta ) && (int) $meta >= 0 ) {
				return (int) $meta;
			}
		}

		return -1;
	}

	public static function restoreInFlightConnectionFromState( string $state ): void {
		$state = sanitize_text_field( $state );
		if ( '' === $state ) {
			return;
		}

		$bound = get_transient( self::grantBindingKey( $state ) );
		if ( ! is_array( $bound ) || ! isset( $bound['connection_id'] ) || ! is_numeric( $bound['connection_id'] ) ) {
			return;
		}

		$tab = isset( $bound['tab'] ) ? sanitize_key( (string) $bound['tab'] ) : 'manual';
		if ( ! in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
			$tab = 'manual';
		}

		self::rememberGrant( (int) $bound['connection_id'], $tab );
	}

	public static function rememberStateBinding( string $state, int $connection_id, string $tab = 'manual' ): void {
		if ( '' === $state || $connection_id < 0 ) {
			return;
		}

		if ( ! in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
			$tab = 'manual';
		}

		if ( class_exists( 'PostmanSession', false ) ) {
			$session = \PostmanSession::getInstance();
			if ( $session->isSetOauthInProgress() ) {
				$previous_state = (string) $session->getOauthInProgress();
				if ( '' !== $previous_state && ! hash_equals( $previous_state, $state ) ) {
					delete_transient( self::grantBindingKey( $previous_state ) );
				}
			}
		}

		set_transient(
			self::grantBindingKey( $state ),
			array(
				'connection_id' => (int) $connection_id,
				'tab'           => $tab,
			),
			self::GRANT_CONTEXT_TTL
		);
	}

	public static function rememberGrant( int $connection_id, string $tab = 'manual' ): void {
		if ( $connection_id < 0 ) {
			return;
		}

		if ( ! in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
			$tab = 'manual';
		}

		set_transient( 'post_smtp_gmail_oauth_connection_id', $connection_id, self::GRANT_CONTEXT_TTL );
		set_transient( 'post_smtp_gmail_oauth_connect_tab', $tab, self::GRANT_CONTEXT_TTL );

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id', (int) $connection_id );
			update_user_meta( $user_id, 'post_smtp_gmail_oauth_connect_tab', $tab );
		}
	}

	public static function resolveConnectionId(): int {
		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' !== $incoming_state ) {
			$bound = get_transient( self::grantBindingKey( $incoming_state ) );
			if ( is_array( $bound ) && isset( $bound['connection_id'] ) && is_numeric( $bound['connection_id'] ) ) {
				$from_state = (int) $bound['connection_id'];
				if ( $from_state >= 0 ) {
					return $from_state;
				}
			}
		}

		$id = get_transient( 'post_smtp_gmail_oauth_connection_id' );
		if ( is_numeric( $id ) && (int) $id >= 0 ) {
			return (int) $id;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta = get_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id', true );
			if ( is_numeric( $meta ) && (int) $meta >= 0 ) {
				return (int) $meta;
			}
		}

		if ( isset( $_GET['connection_id'] ) && is_numeric( $_GET['connection_id'] ) ) {
			return (int) $_GET['connection_id'];
		}

		if ( class_exists( GmailConnectionTokens::class, false ) ) {
			return GmailConnectionTokens::resolveIndexForOAuthCallback( -1 );
		}

		return -1;
	}

	public static function resolveConnectTab(): string {
		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' !== $incoming_state ) {
			$bound = get_transient( self::grantBindingKey( $incoming_state ) );
			if ( is_array( $bound ) && isset( $bound['tab'] ) ) {
				$tab_candidate = sanitize_key( (string) $bound['tab'] );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					return $tab_candidate;
				}
			}
		}

		$tab = get_transient( 'post_smtp_gmail_oauth_connect_tab' );
		if ( is_string( $tab ) && in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
			return $tab;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$meta_tab = get_user_meta( $user_id, 'post_smtp_gmail_oauth_connect_tab', true );
			if ( is_string( $meta_tab ) && in_array( $meta_tab, array( 'manual', 'one_click' ), true ) ) {
				return $meta_tab;
			}
		}

		if ( isset( $_GET['gmail_connect_tab'] ) ) {
			$candidate = sanitize_key( wp_unslash( (string) $_GET['gmail_connect_tab'] ) );
			if ( in_array( $candidate, array( 'manual', 'one_click' ), true ) ) {
				return $candidate;
			}
		}

		return 'manual';
	}

	public static function clearGrantTransients(): void {
		$incoming_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		if ( '' !== $incoming_state ) {
			delete_transient( self::grantBindingKey( $incoming_state ) );
		}

		delete_transient( 'post_smtp_gmail_oauth_connection_id' );
		delete_transient( 'post_smtp_gmail_oauth_connect_tab' );

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			delete_user_meta( $user_id, 'post_smtp_gmail_oauth_connection_id' );
			delete_user_meta( $user_id, 'post_smtp_gmail_oauth_connect_tab' );
		}
	}

	// --- Gmail configure panel (React admin defaults) ---

	private static function registerConfigurePanelHooks(): void {
		add_filter( 'post_smtp_gmail_configure_one_click', array( self::class, 'filterGmailDefaultOneClick' ), 5, 2 );
		add_filter( 'post_smtp_gmail_configure_defaults', array( self::class, 'filterGmailDefaultConnectTab' ), 5 );

		if ( self::usesReactConnectionsAdmin() || self::isActive() ) {
			add_filter( 'post_smtp_gmail_configure_manual_oauth', array( self::class, 'filterGmailRevampManualOAuthState' ), 20, 2 );
		}

		if ( self::usesReactConnectionsAdmin() ) {
			add_filter( 'post_smtp_legacy_wizard', array( self::class, 'filterUseRevampOAuthReturn' ), 20 );
		}
	}

	public static function filterUseRevampOAuthReturn( bool $legacy ): bool {
		return self::usesReactConnectionsAdmin() ? false : $legacy;
	}

	/**
	 * @param array<string,mixed> $manual
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function filterGmailRevampManualOAuthState( array $manual, array $defaults ): array {
		unset( $defaults );

		return array(
			'connected'      => false,
			'connectedEmail' => '',
			'connectedLabel' => __( 'Connected to Gmail API', 'post-smtp' ),
		);
	}

	/**
	 * @param array<string,mixed> $one_click
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function filterGmailDefaultOneClick( array $one_click, array $defaults ): array {
		unset( $defaults );

		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		return array_merge(
			array(
				'toggleDisabled'      => ! $has_pro,
				'toggleChecked'       => false,
				'openUpgradeOnToggle' => ! $has_pro,
				'showProTag'          => ! $has_pro,
				'googleSignInLocked'  => ! $has_pro,
			),
			$one_click
		);
	}

	/**
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function filterGmailDefaultConnectTab( array $defaults ): array {
		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		$defaults['defaultConnectTab'] = $has_pro ? 'one_click' : 'manual';

		return $defaults;
	}

	/**
	 * @param array<string,mixed> $payload Toggle attempt context from the React UI.
	 */
	public static function fireGmailOneClickToggleAttempt( array $payload ): void {
		do_action( 'post_smtp_gmail_configure_one_click_toggle_attempt', $payload );
	}
}
