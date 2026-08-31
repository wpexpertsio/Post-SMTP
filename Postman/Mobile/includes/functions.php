<?php

if( !function_exists( 'post_smtp_mobile_validate' ) ):
/**
 * Validate mobile FCM token and send JSON error on failure.
 *
 * @since 2.7.0
 * @param string $fcm_token Mobile device token.
 * @return bool
 */
function post_smtp_mobile_validate( $fcm_token ) {

	if ( post_smtp_mobile_is_valid( $fcm_token ) ) {
		return true;
	}

	if ( empty( $fcm_token ) ) {
		wp_send_json_error(
			array(
				'error' => 'Auth token missing.',
			),
			400
		);
	}

	wp_send_json_error(
		array(
			'error' => 'Invalid Auth Token.',
		),
		401
	);
}
endif;

/**
 * Get MainWP child sites
 * 
 * @since 2.8.9
 */
if( !function_exists( 'post_smtp_mobile_get_child_sites' ) ):
function post_smtp_mobile_get_child_sites() {

    $child_enabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
    $child_key     = $child_enabled['key'];
    $sites         = apply_filters( 'mainwp_getsites', __FILE__, $child_key );
    $site_ids      = array();

    foreach ( $sites as $site ) {

        $site_ids[ $site['id'] ] = array(
            'siteURL'	=>	$site['url'],
            'siteTitle'	=>	$site['name']
        );

    }
    
    return $site_ids ? $site_ids : 0;

}
endif;

/**
 * REST permission callback: mobile app pairing via QR auth key.
 *
 * @since 3.9.6
 * @param WP_REST_Request $request Request object.
 * @return bool
 */
if ( ! function_exists( 'post_smtp_mobile_permission_connect_app' ) ) :
function post_smtp_mobile_permission_connect_app( WP_REST_Request $request ) {
	$nonce    = get_transient( 'post_smtp_auth_nonce' );
	$auth_key = sanitize_text_field( (string) $request->get_header( 'auth_key' ) );

	return ! empty( $nonce ) && ! empty( $auth_key ) && hash_equals( (string) $nonce, $auth_key );
}
endif;

/**
 * REST permission callback: authenticated mobile device.
 *
 * @since 3.9.6
 * @param WP_REST_Request $request Request object.
 * @return bool
 */
if ( ! function_exists( 'post_smtp_mobile_permission_fcm_token' ) ) :
function post_smtp_mobile_permission_fcm_token( WP_REST_Request $request ) {
	$fcm_token = sanitize_text_field( (string) $request->get_header( 'fcm_token' ) );

	return post_smtp_mobile_is_valid( $fcm_token );
}
endif;
