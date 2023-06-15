<?php

if( !class_exists( 'PSMWP_Rest_API' ) ):

class PSMWP_Rest_API {

    /**
     * PSMWP_Rest_API constructor.
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function __construct() {
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        
    }


    /**
     * Register routes
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function register_routes() {
        
        register_rest_route( 'psmwp/v1', '/activate-from-mainwp', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'activate_from_mainwp' ),
            'permission_callback' => '__return_true',
        ) );

    }


    /**
     * Activate from MainWP
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function activate_from_mainwp( WP_REST_Request $request ) {

        $params = $request->get_params();
		$headers = $request->get_headers();
		$api_key = empty( $request->get_header( 'api_key' ) ) ? '' : sanitize_text_field( $request->get_header( 'api_key' ) );
		$site_url = empty( $request->get_header( 'site_url' ) ) ? '' : sanitize_url( $request->get_header( 'site_url' ) );
		$action = $request->get_param( 'action' );

        //Lets Validate :D
		if( $this->validate( $api_key, $site_url ) ) {

            if( $action == 'enable_post_smtp' ) {
				
				update_option( 'post_smtp_use_from_main_site', '1' );
				
			}
			
			if( $action == 'disable_post_smtp' ) {
				
				 delete_option( 'post_smtp_use_from_main_site' );
				
			}
			
			wp_send_json_success(
				array(),
				200
			);

        }
		
		

    }


    /**
     * Validate request
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    private function validate( $api_key, $site_url ) {

        if( 
			empty( $api_key ) 
			||
			empty( $site_url ) 
		) {
			
			wp_send_json(
				array(
					'code'		=>	'incomplete_request',
					'message'	=>	'Empty API-Key or Site-URL.'
				),
				404
			);
			
		}
		
		$_site_url = site_url( '/' );
        $pubkey = get_option( 'mainwp_child_pubkey' );
		$pubkey = $pubkey ? md5( $pubkey ) : '';

		if( $_site_url != $site_url ) {
			
			wp_send_json(
				array(
					'code'		=>	'incorrect_site_url',
					'message'	=>	'Incorrect site URL.'
				),
				404
			);
			
		}

        if( $pubkey != $api_key ) {
			
			wp_send_json(
				array(
					'code'		=>	'incorrect_api_key',
					'message'	=>	'Incorrect API Key.'
				),
				404
			);
			
		}
		
		//Let's allow request
		if( 
            $pubkey == $api_key
            &&
            $_site_url == $site_url
		) {
			
			return true;
			
		}

    }

}

new PSMWP_Rest_API();

endif;