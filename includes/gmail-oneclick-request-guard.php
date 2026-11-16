<?php
/**
 * Strip Gmail one-click OAuth tokens from the request superglobals before WordPress copies them into object cache.
 *
 * Loaded from mu-plugins (earliest) and postman-smtp.php (before Freemius).
 *
 * @package PostSMTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'post_smtp_gmail_oneclick_guard_is_callback' ) ) {
	/**
	 * @return bool
	 */
	function post_smtp_gmail_oneclick_guard_is_callback() {
		if ( ! isset( $_GET['action'] ) ) {
			return false;
		}

		$action = (string) $_GET['action'];
		if ( 'gmail_oauth_redirect' !== $action ) {
			return false;
		}

		return isset( $_GET['access_token'] ) || isset( $_GET['error'] );
	}
}

if ( ! function_exists( 'post_smtp_gmail_oneclick_guard_strip_request' ) ) {
	/**
	 * Move callback params off $_GET and rebuild QUERY_STRING so core never caches multi-KB query args.
	 */
	function post_smtp_gmail_oneclick_guard_strip_request() {
		static $done = false;
		if ( $done ) {
			return;
		}

		if ( ! post_smtp_gmail_oneclick_guard_is_callback() ) {
			return;
		}

		$done = true;

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
			'gmail_connect_tab',
		);

		if ( ! isset( $GLOBALS['post_smtp_gmail_oneclick_incoming'] ) || ! is_array( $GLOBALS['post_smtp_gmail_oneclick_incoming'] ) ) {
			$GLOBALS['post_smtp_gmail_oneclick_incoming'] = array();
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				continue;
			}
			$GLOBALS['post_smtp_gmail_oneclick_incoming'][ $key ] = is_string( $_GET[ $key ] )
				? $_GET[ $key ]
				: (string) $_GET[ $key ];
		}

		foreach ( array( 'access_token', 'refresh_token' ) as $heavy ) {
			unset( $_GET[ $heavy ], $_REQUEST[ $heavy ] );
		}

		if ( ! empty( $_SERVER['QUERY_STRING'] ) && is_string( $_SERVER['QUERY_STRING'] ) ) {
			$query = array();
			parse_str( $_SERVER['QUERY_STRING'], $query );
			unset( $query['access_token'], $query['refresh_token'] );
			$_SERVER['QUERY_STRING'] = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		}

		if ( ! empty( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ) {
			$uri  = $_SERVER['REQUEST_URI'];
			$hash = '';
			if ( false !== strpos( $uri, '#' ) ) {
				list( $uri, $hash ) = explode( '#', $uri, 2 );
				$hash = '#' . $hash;
			}
			$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $uri ) : parse_url( $uri );
			if ( is_array( $parts ) && isset( $parts['query'] ) ) {
				$q = array();
				parse_str( $parts['query'], $q );
				unset( $q['access_token'], $q['refresh_token'] );
				$path  = isset( $parts['path'] ) ? $parts['path'] : '';
				$query = http_build_query( $q, '', '&', PHP_QUERY_RFC3986 );
				$_SERVER['REQUEST_URI'] = $path . ( '' !== $query ? '?' . $query : '' ) . $hash;
			}
		}
	}
}

if ( ! function_exists( 'post_smtp_gmail_oneclick_guard_has_incoming' ) ) {
	/**
	 * @return bool
	 */
	function post_smtp_gmail_oneclick_guard_has_incoming() {
		return ! empty( $GLOBALS['post_smtp_gmail_oneclick_incoming'] )
			&& is_array( $GLOBALS['post_smtp_gmail_oneclick_incoming'] )
			&& (
				! empty( $GLOBALS['post_smtp_gmail_oneclick_incoming']['access_token'] )
				|| ! empty( $GLOBALS['post_smtp_gmail_oneclick_incoming']['error'] )
			);
	}
}