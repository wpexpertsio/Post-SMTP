<?php

if( !function_exists( 'post_smtp_mobile_validate' ) ):
function post_smtp_mobile_validate( $fcm_token ) {
    
    $device = get_option( 'post_smtp_mobile_app_connection' );
		
    if( empty( $fcm_token ) ) {
        
        wp_send_json_error( 
            array(
                'error'	=>	'Auth token missing.'
            ), 
            400 
        );
        
    }
    elseif( $device && isset( $device[$fcm_token] ) ) {
        
        return true;
        
    }
    else {
        
        wp_send_json_error( 
            array(
                'error'	=>	'Invalid Auth Token.'
            ), 
            401 
        );
        
    }
    
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