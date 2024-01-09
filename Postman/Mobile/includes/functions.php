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