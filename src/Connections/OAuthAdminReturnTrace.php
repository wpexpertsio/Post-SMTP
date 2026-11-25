<?php
/**
 * Local/debug trace for setup-wizard vs connection-edit OAuth returns (URLs change too fast to copy).
 *
 * Enable: define( 'POST_SMTP_OAUTH_RETURN_DEBUG', true ); in wp-config.php (works without WP_DEBUG).
 * View:   WP Admin → Post SMTP with ?ps-oauth-trace=1 on the URL (manage_options).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OAuthAdminReturnTrace {

	private const TRANSIENT_KEY = 'post_smtp_oauth_return_trace';

	private const MAX_EVENTS = 40;

	private const TTL = 2 * HOUR_IN_SECONDS;

	public static function isEnabled(): bool {
		if ( defined( 'POST_SMTP_OAUTH_RETURN_DEBUG' ) ) {
			return (bool) POST_SMTP_OAUTH_RETURN_DEBUG;
		}

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * @param array<string,mixed> $context
	 */
	public static function record( string $phase, array $context = array() ): void {
		if ( ! self::isEnabled() || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$phase = sanitize_key( $phase );
		if ( '' === $phase ) {
			return;
		}

		$event = array(
			'time'    => gmdate( 'c' ),
			'phase'   => $phase,
			'context' => self::sanitizeContext( $context ),
		);

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Post SMTP OAuth trace] ' . $phase . ' ' . wp_json_encode( $event['context'] ) );
		}

		$bucket = get_transient( self::TRANSIENT_KEY . '_' . $user_id );
		if ( ! is_array( $bucket ) ) {
			$bucket = array();
		}

		$bucket[] = $event;
		if ( count( $bucket ) > self::MAX_EVENTS ) {
			$bucket = array_slice( $bucket, -self::MAX_EVENTS );
		}

		set_transient( self::TRANSIENT_KEY . '_' . $user_id, $bucket, self::TTL );
	}

	public static function register(): void {
		if ( ! self::isEnabled() ) {
			return;
		}

		add_action( 'admin_init', array( self::class, 'maybeRenderTracePage' ), 1 );
		add_action( 'admin_notices', array( self::class, 'maybeFreshTraceNotice' ), 5 );
	}

	public static function maybeRenderTracePage(): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' !== $page ) {
			return;
		}

		$show = isset( $_GET['ps-oauth-trace'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ps-oauth-trace'] ) ) : '';
		if ( '' === $show || '0' === $show ) {
			return;
		}

		$user_id = get_current_user_id();
		$bucket  = get_transient( self::TRANSIENT_KEY . '_' . $user_id );
		if ( ! is_array( $bucket ) ) {
			$bucket = array();
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );

		$back = esc_url( remove_query_arg( 'ps-oauth-trace' ) );
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Post SMTP OAuth return trace</title>';
		echo '<style>body{font-family:system-ui,sans-serif;margin:1.5rem;max-width:960px}pre{background:#f4f4f5;padding:1rem;overflow:auto;font-size:12px;line-height:1.45}a{color:#2271b1}</style></head><body>';
		echo '<p><a href="' . $back . '">&larr; Back to Post SMTP</a></p>';
		echo '<h1>OAuth return trace</h1>';
		echo '<p>Enable with <code>define( \'POST_SMTP_OAUTH_RETURN_DEBUG\', true );</code> in wp-config.php. Events expire after ' . (int) ( self::TTL / MINUTE_IN_SECONDS ) . ' minutes.</p>';
		if ( empty( $bucket ) ) {
			echo '<p><em>No trace events yet. Run Office or Gmail OAuth from the wizard, then reload this page.</em></p>';
		} else {
			echo '<pre>';
			echo esc_html( wp_json_encode( $bucket, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		}
		echo '</body></html>';
		exit;
	}

	public static function maybeFreshTraceNotice(): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( 'postman' !== $page ) {
			return;
		}

		if ( isset( $_GET['ps-oauth-trace'] ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$bucket  = get_transient( self::TRANSIENT_KEY . '_' . $user_id );
		if ( ! is_array( $bucket ) || empty( $bucket ) ) {
			return;
		}

		$last = end( $bucket );
		if ( ! is_array( $last ) || empty( $last['time'] ) ) {
			return;
		}

		$ts = strtotime( (string) $last['time'] );
		if ( false === $ts || ( time() - $ts ) > 15 * MINUTE_IN_SECONDS ) {
			return;
		}

		$phase   = isset( $last['phase'] ) ? (string) $last['phase'] : '';
		$context = isset( $last['context'] ) && is_array( $last['context'] ) ? $last['context'] : array();
		$target  = isset( $context['target'] ) ? (string) $context['target'] : '';

		if ( '' === $target && 'office365_redirect' !== $phase && 'gmail_redirect' !== $phase ) {
			return;
		}

		$trace_url = esc_url( add_query_arg( 'ps-oauth-trace', '1', admin_url( 'admin.php?page=postman' ) ) );
		echo '<div class="notice notice-info"><p><strong>Post SMTP OAuth trace</strong> ';
		if ( '' !== $target ) {
			echo 'Last redirect: <code>' . esc_html( $target ) . '</code>. ';
		}
		echo '<a href="' . $trace_url . '">View full trace</a></p></div>';
	}

	/**
	 * @param array<string,mixed> $context
	 * @return array<string,mixed>
	 */
	private static function sanitizeContext( array $context ): array {
		$out = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$out[ $key ] = null === $value ? null : sanitize_text_field( (string) $value );
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = self::sanitizeContext( $value );
			}
		}

		return $out;
	}
}