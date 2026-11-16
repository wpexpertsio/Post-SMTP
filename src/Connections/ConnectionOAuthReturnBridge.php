<?php
/**
 * Unified OAuth return handling for revamp Connections (Gmail + Microsoft 365).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionOAuthReturnBridge {

	public static function init(): void {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;

		self::captureGmailOneClickTokensEarly();
		add_action( 'plugins_loaded', array( self::class, 'captureGmailOneClickTokensEarly' ), 1 );
		add_action( 'init', array( self::class, 'captureGmailOneClickTokensEarly' ), -99999 );
		add_action( 'init', array( self::class, 'processGmailOneClickReturnEarly' ), -99998 );
		add_action( 'init', array( self::class, 'processOAuthReturns' ), 1 );
		add_action( 'init', array( self::class, 'processOAuthReturns' ), 5 );
		add_action( 'admin_init', array( self::class, 'processCapturedOneClickIfPending' ), -100000 );
		add_action( 'admin_init', array( self::class, 'processOAuthReturns' ), -99999 );
		add_action( 'admin_init', array( self::class, 'maybeRedirectStaleOAuthLanding' ), 2 );
		add_action( 'set_current_user', array( self::class, 'processGmailOneClickAfterAuth' ), 1, 1 );
	}

	/**
	 * Exchange tokens saved by the mu-plugin guard once the admin user session is ready.
	 */
	public static function processCapturedOneClickIfPending(): void {
		if ( defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
			return;
		}

		$has_incoming = function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' )
			&& post_smtp_gmail_oneclick_guard_has_incoming();

		if ( ! $has_incoming
			&& ! ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::hasCapturedOneClickPayload() )
			&& ! self::isGmailOneClickMiddlewareCallback() ) {
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! class_exists( GmailRevampOAuth::class, false ) ) {
			return;
		}

		GmailRevampOAuth::captureOneClickMiddlewarePayloadEarly();
		GmailRevampOAuth::processOneClickMiddlewareReturn();
	}

	/**
	 * Strip large token query args before Postman admin / object cache runs (same request, plugins_loaded).
	 */
	public static function captureGmailOneClickTokensEarly(): void {
		if ( ! self::isGmailOneClickMiddlewareCallback() ) {
			return;
		}

		if ( class_exists( GmailRevampOAuth::class, false ) ) {
			GmailRevampOAuth::captureOneClickMiddlewarePayloadEarly();
		}
	}

	/**
	 * Exchange one-click tokens before legacy Postman admin/React bootstrap (avoids OOM with tokens in the URL).
	 */
	public static function processGmailOneClickReturnEarly(): void {
		if ( ! self::isGmailOneClickMiddlewareCallback() ) {
			return;
		}

		if ( defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
			return;
		}

		if ( function_exists( 'wp_get_current_user' ) ) {
			wp_get_current_user();
		}

		if ( ! self::shouldProcessGmailOneClickReturn() || ! class_exists( GmailRevampOAuth::class, false ) ) {
			return;
		}

		GmailRevampOAuth::processOneClickMiddlewareReturn();
	}

	/**
	 * Run one-click return as soon as the admin user context exists (before heavy admin bootstrap).
	 *
	 * @param int $user_id Current user ID.
	 */
	public static function processGmailOneClickAfterAuth( $user_id ): void {
		if ( $user_id < 1 || ! is_admin() ) {
			return;
		}

		if ( ! self::isGmailOneClickMiddlewareCallback() || defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
			return;
		}

		if ( ! self::shouldProcessGmailOneClickReturn() || ! class_exists( GmailRevampOAuth::class, false ) ) {
			return;
		}

		GmailRevampOAuth::processOneClickMiddlewareReturn();
	}

	/**
	 * Exchange authorization codes before heavy admin / React bootstrap (mirrors Office 365 init 5 flow).
	 */
	public static function processOAuthReturns(): void {
		if ( self::isGmailOneClickMiddlewareCallback() ) {
			if ( defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
				return;
			}

			if ( is_admin() && ! is_user_logged_in() && function_exists( 'wp_get_current_user' ) ) {
				wp_get_current_user();
			}

			if ( self::shouldProcessGmailOneClickReturn() && class_exists( GmailRevampOAuth::class, false ) ) {
				GmailRevampOAuth::processOneClickMiddlewareReturn();
			}

			if ( defined( 'POST_SMTP_GMAIL_ONECLICK_HANDLED' ) ) {
				return;
			}

			return;
		}

		if ( ! is_admin() && ! self::isWpAdminOAuthCallback() ) {
			return;
		}

		if ( self::shouldProcessOffice365Return() ) {
			self::runOffice365ReturnHandler();
			return;
		}

		if ( self::shouldProcessGmailReturn() ) {
			GmailRevampOAuth::processRevampOAuthReturn();
		}
	}

	/**
	 * Do not load the React SPA while provider OAuth query args are still present.
	 */
	public static function shouldDeferReactAdminBootstrap(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		if ( self::isGmailOneClickMiddlewareCallback() ) {
			return true;
		}

		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['state'] ) && ! isset( $_GET['error'] ) && ! isset( $_GET['scope'] ) ) {
			return false;
		}

		if ( class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', false )
			&& class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', false )
			&& \PostSMTP\Pro\Connections\Office365RevampOAuth::isActive()
			&& \PostSMTP\Pro\Connections\Office365OAuthGrantContext::shouldProcessOAuthReturn() ) {
			return true;
		}

		if ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::shouldDeferReactAdminBootstrap() ) {
			return true;
		}

		return self::looksLikeGoogleOAuthScope();
	}

	/**
	 * OAuth finished but the browser landed on admin.php?page=postman without ps-route (query stripped by redirect).
	 */
	public static function maybeRedirectStaleOAuthLanding(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( isset( $_GET['code'] ) || isset( $_GET['state'] ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' !== $page ) {
			return;
		}

		if ( isset( $_GET['ps-route'] ) && '' !== (string) $_GET['ps-route'] ) {
			return;
		}

		if ( self::maybeRedirectStaleGmailLanding() ) {
			return;
		}

		self::maybeRedirectStaleOffice365Landing();
	}

	private static function maybeRedirectStaleGmailLanding(): bool {
		if ( ! class_exists( GmailRevampOAuth::class, false )
			|| ! GmailRevampOAuth::shouldUseRevampOAuthReturn() ) {
			return false;
		}

		if ( ! isset( $_GET['success'] ) && ! isset( $_GET['msg'] ) && ! isset( $_GET['gmail_connect_tab'] ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) && ! GmailRevampOAuth::currentUserCanCompleteGmailOAuth() ) {
			return false;
		}

		$extra = array();
		if ( isset( $_GET['success'] ) ) {
			$extra['success'] = sanitize_text_field( wp_unslash( (string) $_GET['success'] ) );
		}
		if ( isset( $_GET['msg'] ) ) {
			$extra['msg'] = sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) );
		}
		if ( isset( $_GET['gmail_connect_tab'] ) ) {
			$tab = sanitize_key( wp_unslash( (string) $_GET['gmail_connect_tab'] ) );
			if ( in_array( $tab, array( 'manual', 'one_click' ), true ) ) {
				$extra['gmail_connect_tab'] = $tab;
			}
		}

		$target = GmailRevampOAuth::afterOAuthReturnUrl( $extra );
		wp_safe_redirect( $target );
		exit;
	}

	private static function maybeRedirectStaleOffice365Landing(): void {
		if ( ! class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', false )
			|| ! \PostSMTP\Pro\Connections\Office365RevampOAuth::isActive() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		\PostSMTP\Pro\Connections\Office365RevampOAuth::maybeRedirectStaleEntraCallbackOnPostmanPage();
	}

	private static function shouldProcessGmailReturn(): bool {
		if ( ! isset( $_GET['code'] ) || '' === (string) $_GET['code'] ) {
			return false;
		}

		if ( ! class_exists( GmailRevampOAuth::class, false )
			|| ! GmailRevampOAuth::shouldUseRevampOAuthReturn() ) {
			return false;
		}

		if ( self::shouldProcessOffice365Return() ) {
			return false;
		}

		if ( GmailRevampOAuth::isRevampGmailOAuthReturnRequest() ) {
			return true;
		}

		return self::looksLikeGoogleOAuthScope();
	}

	private static function shouldProcessOffice365Return(): bool {
		if ( ! isset( $_GET['code'] ) && ! isset( $_GET['error'] ) ) {
			return false;
		}

		if ( self::looksLikeGoogleOAuthScope() ) {
			return false;
		}

		if ( ! class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', true )
			|| ! class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', false )
			|| ! \PostSMTP\Pro\Connections\Office365RevampOAuth::isActive() ) {
			return false;
		}

		return \PostSMTP\Pro\Connections\Office365OAuthGrantContext::shouldProcessOAuthReturn();
	}

	private static function looksLikeGoogleOAuthScope(): bool {
		$scope = isset( $_GET['scope'] ) ? wp_unslash( (string) $_GET['scope'] ) : '';
		if ( '' === $scope ) {
			return false;
		}

		return str_contains( $scope, 'googleapis.com' ) || str_contains( $scope, 'gmail.' );
	}

	private static function isWpAdminOAuthCallback(): bool {
		return isset( $_GET['code'] ) || isset( $_GET['error'] ) || isset( $_GET['state'] );
	}

	public static function isGmailOneClickMiddlewareCallback(): bool {
		if ( ! isset( $_GET['action'] ) ) {
			return false;
		}

		$action = sanitize_key( wp_unslash( (string) $_GET['action'] ) );

		if ( 'gmail_oauth_redirect' !== $action ) {
			return false;
		}

		if ( function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' ) && post_smtp_gmail_oneclick_guard_has_incoming() ) {
			return true;
		}

		return isset( $_GET['access_token'] )
			|| isset( $_GET['error'] )
			|| ( class_exists( GmailRevampOAuth::class, false ) && GmailRevampOAuth::hasCapturedOneClickPayload() );
	}

	private static function shouldProcessGmailOneClickReturn(): bool {
		if ( ! self::isGmailOneClickMiddlewareCallback() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( class_exists( GmailRevampOAuth::class, false ) ) {
			return GmailRevampOAuth::currentUserCanCompleteGmailOAuth()
				|| current_user_can( 'manage_options' );
		}

		return current_user_can( 'manage_options' );
	}

	private static function runOffice365ReturnHandler(): void {
		if ( ! class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', false ) ) {
			return;
		}

		\PostSMTP\Pro\Connections\Office365RevampOAuth::processEntraCallback();
	}
}