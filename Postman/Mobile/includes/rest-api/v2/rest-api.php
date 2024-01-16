<?php

class Post_SMTP_Mobile_Rest_API_V2 {
	
	private $filter = '';
	private $has_mainwp = false;


    /**
     * Register routes
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		
		$this->has_mainwp = is_plugin_active( 'mainwp/mainwp.php' );

    }

    /**
     * Register routes
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function register_routes() {
		
		register_rest_route( 'post-smtp/v2', '/get-logs', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'get_logs' ),
            'permission_callback'   => '__return_true',
        ) );

    }
	
	public function get_logs( WP_REST_Request $request ) {
		
		$args['order_by'] = 'time';
		$args['order'] = 'DESC';
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		$start = $request->get_param( 'start' ) !== null ? $request->get_param( 'start' ) : 0;
		$end = $request->get_param( 'end' ) !== null ? $request->get_param( 'end' ) : 25;
		$this->filter = $request->get_param( 'filter' ) !== 'all' ? $request->get_param( 'filter' ) : '';
		$query = $request->get_param( 'query' ) !== '' ? $request->get_param( 'query' ) : '';
		$mainwp_site_id = $request->get_param( 'mainwp_site_id' ) !== '' ? $request->get_param( 'mainwp_site_id' ) : '';
		
		if( $this->has_mainwp ) {
			
			$args['site_id'] = empty( $mainwp_site_id ) ? 'main_site' : $mainwp_site_id;
			
		}
		
		if( empty( $query ) && !empty( $this->filter ) ) {
			
			add_filter( 'post_smtp_get_logs_query_after_table', array( $this, 'filter_query' ) );
			
		}
		
		if( !empty( $query ) ) {
			
			$args['search'] = $query;
			
		}
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		if( post_smtp_mobile_validate( $fcm_token ) ) {
			
			$logs_query = new PostmanEmailQueryLog();
			$args['start'] = $start;
			$args['end'] = $end;
			
			if( empty( $args ) ) {
				
				wp_send_json_success(
					array( 'message' => 'Logs not found.' ),
					200
				);
				
			}
			
			$response = array(
				'logs'				=>	$logs_query->get_logs( $args ),
				'plugin_version'	=>	POST_SMTP_VER
			);
			
			if( $this->has_mainwp ) {
				
				$response['mainwp'] = post_smtp_mobile_get_child_sites();
				
			}
			
			wp_send_json_success(
				$response,
				200
			);
			
		}
		
	}

}

new Post_SMTP_Mobile_Rest_API_V2();