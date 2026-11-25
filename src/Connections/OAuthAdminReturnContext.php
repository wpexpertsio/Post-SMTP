<?php
/**
 * Remember where OAuth started (setup wizard vs connections) for post-grant redirects.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OAuthAdminReturnContext {

	public const ROUTE_SETUP_WIZARD = 'setup-wizard';

	/** Explicit grant from Connections edit/add (clear stale wizard return). */
	public const ROUTE_CONNECTION_EDIT = 'connection-edit';

	private const TRANSIENT_PREFIX = 'post_smtp_oauth_ps_route_';

	private const TRANSIENT_CONN_PREFIX = 'post_smtp_oauth_ps_route_conn_';

	private const USER_META_KEY = 'post_smtp_oauth_ps_route';

	private const TTL = 30 * MINUTE_IN_SECONDS;

	private static function connectionTransientKey( int $user_id, int $connection_id ): string {
		return self::TRANSIENT_CONN_PREFIX . $user_id . '_' . max( 0, (int) $connection_id );
	}

	/**
	 * Capture `ps_return_route` on grant-start requests (admin-post, ajax, etc.).
	 */
	public static function captureFromGrantRequest(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$raw = self::readReturnRouteFromSuperglobals();
		if ( self::ROUTE_SETUP_WIZARD !== $raw && self::isGrantRequestFromSetupWizardReferer() ) {
			$raw = self::ROUTE_SETUP_WIZARD;
		}

		if ( self::ROUTE_CONNECTION_EDIT === $raw ) {
			$connection_id = self::readConnectionIdFromSuperglobals();
			self::clearForCurrentUser( $connection_id );
			self::traceGrantCapture( $raw, 'cleared_connection_edit' );

			return;
		}

		if ( self::ROUTE_SETUP_WIZARD === $raw ) {
			self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
			$connection_id = self::readConnectionIdFromSuperglobals();
			if ( null !== $connection_id ) {
				self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );
			}
			self::traceGrantCapture( $raw, 'remembered_setup_wizard' );

			return;
		}

		// Grant without ps_return_route: keep wizard return from saveDraft / UI unless the client
		// sends ps_return_route=connection-edit (Connections screen). Referer cannot include # fragments.

		self::traceGrantCapture( $raw, 'no_ps_return_route' );
	}

	private static function traceGrantCapture( string $ps_return_route, string $action ): void {
		$connection_id = self::readConnectionIdFromSuperglobals();
		OAuthAdminReturnTrace::record(
			'grant_capture',
			array(
				'ps_return_route'   => $ps_return_route,
				'action'            => $action,
				'connection_id'     => $connection_id,
				'stored_user'       => self::readStoredPsRouteForUser(),
				'stored_connection' => null !== $connection_id
					? self::readStoredPsRouteForConnection( $connection_id )
					: '',
				'request_uri'       => isset( $_SERVER['REQUEST_URI'] )
					? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
					: '',
			)
		);
	}

	/**
	 * Connection index on grant-start (admin-post query or ajax body).
	 */
	public static function readConnectionIdFromSuperglobals(): ?int {
		$raw = null;
		if ( isset( $_GET['connection_id'] ) && is_numeric( $_GET['connection_id'] ) ) {
			$raw = (int) $_GET['connection_id'];
		} elseif ( isset( $_POST['connection_id'] ) && is_numeric( $_POST['connection_id'] ) ) {
			$raw = (int) $_POST['connection_id'];
		}

		if ( null === $raw || $raw < 0 ) {
			return null;
		}

		return $raw;
	}

	/**
	 * `ps_return_route` from the current grant-start request (admin-post / ajax).
	 */
	private static function isGrantRequestFromSetupWizardReferer(): bool {
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( (string) $_SERVER['HTTP_REFERER'] ) : '';
		$ref = is_string( $ref ) ? $ref : '';
		if ( '' === $ref ) {
			return false;
		}

		return false !== stripos( $ref, 'setup-wizard' )
			|| false !== stripos( $ref, 'ps-route=setup-wizard' )
			|| false !== stripos( $ref, '#/setup-wizard' );
	}

	public static function readReturnRouteFromSuperglobals(): string {
		$raw = '';
		if ( isset( $_GET['ps_return_route'] ) ) {
			$raw = sanitize_text_field( wp_unslash( (string) $_GET['ps_return_route'] ) );
		} elseif ( isset( $_POST['ps_return_route'] ) ) {
			$raw = sanitize_text_field( wp_unslash( (string) $_POST['ps_return_route'] ) );
		}

		return $raw;
	}

	/**
	 * Stored wizard vs connections return route for the current (or given) user.
	 */
	public static function readStoredPsRouteForUser( ?int $user_id = null ): string {
		$user_id = null !== $user_id ? $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return '';
		}

		$cached = get_transient( self::TRANSIENT_PREFIX . $user_id );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$meta = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( is_string( $meta ) && '' !== $meta ) {
			return $meta;
		}

		return '';
	}

	/**
	 * Request param first, then user-scoped storage from an earlier grant-start.
	 */
	public static function resolveIncomingReturnRoute(): string {
		$from_request = self::readReturnRouteFromSuperglobals();
		if ( self::ROUTE_SETUP_WIZARD === $from_request ) {
			self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );

			return self::ROUTE_SETUP_WIZARD;
		}

		return self::readStoredPsRouteForUser();
	}

	/**
	 * Tie setup-wizard return to the connection row used for this grant (index 0 in wizard).
	 */
	public static function rememberGrantReturnForConnection( int $connection_id ): void {
		if ( $connection_id < 0 || ! is_user_logged_in() ) {
			return;
		}

		$from_request = self::readReturnRouteFromSuperglobals();
		if ( self::ROUTE_SETUP_WIZARD === $from_request ) {
			self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
			self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );

			return;
		}

		$stored = self::readStoredPsRouteForUser();
		if ( self::ROUTE_SETUP_WIZARD === $stored ) {
			self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );
		}
	}

	public static function rememberPsRouteForConnection( int $connection_id, string $route ): void {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || $connection_id < 0 ) {
			return;
		}

		$route = sanitize_text_field( $route );
		if ( self::ROUTE_SETUP_WIZARD !== $route ) {
			return;
		}

		set_transient( self::connectionTransientKey( $user_id, $connection_id ), $route, self::TTL );
	}

	public static function readStoredPsRouteForConnection( int $connection_id ): string {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || $connection_id < 0 ) {
			return '';
		}

		$cached = get_transient( self::connectionTransientKey( $user_id, $connection_id ) );
		if ( is_string( $cached ) && self::ROUTE_SETUP_WIZARD === $cached ) {
			return $cached;
		}

		return '';
	}

	public static function rememberPsRoute( string $route ): void {
		$route = sanitize_text_field( $route );
		if ( self::ROUTE_SETUP_WIZARD !== $route ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		set_transient( self::TRANSIENT_PREFIX . $user_id, $route, self::TTL );
		update_user_meta( $user_id, self::USER_META_KEY, $route );
	}

	/**
	 * Entra manual OAuth state embeds setup wizard when segment after tab flag is `w` (ps365.{id}.m.w.{nonce}).
	 */
	private static function office365StateEmbedsSetupWizard( string $state ): bool {
		$state = sanitize_text_field( $state );

		return '' !== $state && 1 === preg_match( '#^ps365\.\d+\.[mo]\.w\.#i', $state );
	}

	/**
	 * Resolve React admin ps-route after OAuth (wizard vs connection edit).
	 */
	public static function resolvePsRoute( int $connection_id, ?string $incoming_state = null ): string {
		$state = null !== $incoming_state
			? sanitize_text_field( $incoming_state )
			: ( isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '' );

		$incoming_ps_route = isset( $_GET['ps-route'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ps-route'] ) ) : '';
		if ( self::ROUTE_SETUP_WIZARD === $incoming_ps_route ) {
			return self::ROUTE_SETUP_WIZARD;
		}

		$stored = self::readStoredPsRouteForConnection( $connection_id );
		if ( self::ROUTE_SETUP_WIZARD === $stored ) {
			return self::ROUTE_SETUP_WIZARD;
		}

		if ( '' !== $state && self::office365StateEmbedsSetupWizard( $state ) ) {
			self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
			if ( $connection_id >= 0 ) {
				self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );
			}

			return self::ROUTE_SETUP_WIZARD;
		}

		if ( '' !== $state && class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', true ) ) {
			$from_embed = \PostSMTP\Pro\Connections\Office365OAuthGrantContext::parsePsRouteFromState( $state );
			if ( self::ROUTE_SETUP_WIZARD === $from_embed ) {
				self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
				if ( $connection_id >= 0 ) {
					self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );
				}

				return self::ROUTE_SETUP_WIZARD;
			}

			$from_bound = \PostSMTP\Pro\Connections\Office365OAuthGrantContext::boundPsRouteForOAuthState( $state );
			if ( self::ROUTE_SETUP_WIZARD === $from_bound ) {
				self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
				if ( $connection_id >= 0 ) {
					self::rememberPsRouteForConnection( $connection_id, self::ROUTE_SETUP_WIZARD );
				}

				return self::ROUTE_SETUP_WIZARD;
			}
		}

		$stored = self::readStoredPsRouteForUser();

		if ( '' === $stored && '' !== $state && class_exists( GmailRevampOAuth::class, false ) ) {
			$from_state = GmailRevampOAuth::boundPsRouteForOAuthState( $state );
			if ( self::ROUTE_SETUP_WIZARD === $from_state ) {
				self::rememberPsRoute( self::ROUTE_SETUP_WIZARD );
				$stored = self::ROUTE_SETUP_WIZARD;
			}
		}

		if ( self::ROUTE_SETUP_WIZARD === $stored ) {
			return self::ROUTE_SETUP_WIZARD;
		}

		if ( $connection_id >= 0 ) {
			return 'connections/' . (int) $connection_id . '/edit';
		}

		return 'connections';
	}

	/**
	 * @param array<string,mixed> $query_args success, msg, *_connect_tab, ….
	 */
	public static function buildAdminRedirect( string $ps_route, array $query_args = array() ): string {
		$ps_route = sanitize_text_field( $ps_route );
		if ( '' === $ps_route ) {
			$ps_route = 'connections';
		}

		$query_args = array_merge(
			array( 'page' => 'postman' ),
			$query_args,
			array( 'ps-route' => $ps_route )
		);

		return add_query_arg( $query_args, admin_url( 'admin.php' ) );
	}

	/**
	 * @param array<string,mixed> $query_args
	 */
	public static function redirectAfterOAuth(
		int $connection_id,
		array $query_args = array(),
		?string $force_ps_route = null,
		?string $incoming_state = null
	): string {
		$forced = null !== $force_ps_route ? sanitize_text_field( (string) $force_ps_route ) : '';
		if ( '' !== $forced ) {
			$route = $forced;
		} else {
			$route = self::resolvePsRoute( $connection_id, $incoming_state );
		}

		return self::buildAdminRedirect( $route, $query_args );
	}

	public static function clearForCurrentUser( ?int $connection_id = null ): void {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}
		delete_transient( self::TRANSIENT_PREFIX . $user_id );
		delete_user_meta( $user_id, self::USER_META_KEY );

		if ( null !== $connection_id && $connection_id >= 0 ) {
			delete_transient( self::connectionTransientKey( $user_id, $connection_id ) );
		}
	}
}