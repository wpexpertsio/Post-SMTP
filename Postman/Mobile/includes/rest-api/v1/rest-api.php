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
		
		register_rest_route( 'post-smtp/v1', '/get-logs', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'get_logs' ),
            'permission_callback'   => '__return_true',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/disconnect-site', array(
            'methods'               => WP_REST_Server::EDITABLE,
            'callback'              => array( $this, 'disconnect_site' ),
            'permission_callback'   => '__return_true',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/get-log', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'get_log' ),
            'permission_callback'   => '__return_true',
        ) );

    }
	
	public function connect_app( WP_REST_Request $request ) {
		
		$nonce = get_transient( 'post_smtp_auth_nonce' );
		$auth_key = $request->get_header( 'auth_key' );
		$fcm_token = $request->get_header( 'fcm_token' );
		$device = $request->get_header( 'device' );
		
		if( $auth_key == $nonce ) {
			
			$data = array(
				$fcm_token	=>	array(
					'auth_key'	=>	$auth_key,
					'fcm_token'	=>	$fcm_token,
					'device'	=>	$device
				)
			);
			
			update_option( 'post_smtp_mobile_app_connection', $data );
			
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
	
	private function validate( $fcm_token ) {
		
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
	
	public function get_logs( WP_REST_Request $request ) {
		
		$args['order_by'] = 'time';
		$args['order'] = 'DESC';
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		$start = $request->get_param( 'start' ) !== null ? $request->get_param( 'start' ) : 0;
		$end = $request->get_param( 'end' ) !== null ? $request->get_param( 'end' ) : 25;
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		if( $this->validate( $fcm_token ) ) {
			
			$logs_query = new PostmanEmailQueryLog();
			$args['start'] = $start;
			$args['end'] = $end;
			
			wp_send_json_success(
				$logs_query->get_logs( $args ),
				200
			);
			
		}
		
	}
	
	public function get_log( WP_REST_Request $request ) {
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		$id = $request->get_param( 'id' ) !== null ? $request->get_param( 'id' ) : 1;
		$type = $request->get_param( 'type' ) !== null ? $request->get_param( 'type' ) : 'log';
		
		if( $this->validate( $fcm_token ) ) {
			
			$url = admin_url( "admin.php?access_token={$fcm_token}&type={$type}&log_id={$id}" );
			
			wp_send_json_success(
			 	$url,
				200
			);
			
		}
		
	}
	
	public function disconnect_site( WP_REST_Request $request ) {
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		if( $this->validate( $fcm_token ) ) {
			
			$response = delete_option( 'post_smtp_mobile_app_connection' );
			
			if( $response ) {
				
				wp_send_json_success(
					array(
						'message'	=> 'Site Disconnected.'
					),
					200
				);	
			}
			
			wp_send_json_error( 
				array(
					'error'	=>	'Invalid Request.'
				), 
				403 
			);
			
		}
		
	}

}

new Post_SMTP_Mobile_Rest_API();