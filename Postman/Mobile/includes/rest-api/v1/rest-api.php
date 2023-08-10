<?php

class Post_SMTP_Mobile_Rest_API {


    /**
     * Register routes
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function __construct() {

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

    }

    /**
     * Register routes
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function register_routes() {

        register_rest_route( 'post-smtp/v1', '/connect-app', array(
            'methods'               => WP_REST_Server::CREATABLE,
            'callback'              => array( $this, 'connect_app' ),
            'permission_callback'   => '__return_true',
        ) );


    }
	
	public function connect_app( WP_REST_Request $request ) {
		
		$nonce = get_transient( 'post_smtp_auth_nonce' );
		$auth_key = $request->get_header( 'auth_key' );
		$fcm_token = $request->get_header( 'fcm_token' );
		
		if( $auth_key == $nonce ) {
			
			//update_option( 'post_smtp_fcm_token', $fcm_token );
			
			wp_send_json_success( 
				array(
					'fcm_token'	=>	$fcm_token
				), 
				200 
			);
			
		}
		
		delete_transient( 'post_smtp_auth_nonce' );
		
		wp_send_json_error( 
			array(
				'error'	=>	'Refresh QR Code page, and scan again.'
			), 
			200 
		);
		
	}

}

new Post_SMTP_Mobile_Rest_API();