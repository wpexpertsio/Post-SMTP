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
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );

		register_rest_route( 'post-smtp/v2', '/validate-license', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'validate_license' ),
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );

    }
	
	/**
     * Get Logs | Route: /get-logs
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
	public function get_logs( WP_REST_Request $request ) {
		
		$args['order_by'] = 'time';
		$args['order'] = 'DESC';
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? sanitize_text_field( (string) $request->get_header( 'fcm_token' ) ) : '';
		$start = $request->get_param( 'start' ) !== null ? absint( $request->get_param( 'start' ) ) : 0;
		$end = $request->get_param( 'end' ) !== null ? absint( $request->get_param( 'end' ) ) : 25;
		$this->filter = $request->get_param( 'filter' ) !== 'all' ? sanitize_text_field( (string) $request->get_param( 'filter' ) ) : '';
		$query = $request->get_param( 'query' ) !== '' ? sanitize_text_field( (string) $request->get_param( 'query' ) ) : '';
		$mainwp_site_id = $request->get_param( 'mainwp_site_id' ) !== '' ? sanitize_text_field( (string) $request->get_param( 'mainwp_site_id' ) ) : '';
		
		if( $this->has_mainwp ) {
			
			$args['site_id'] = empty( $mainwp_site_id ) ? 'main_site' : $mainwp_site_id;
			
		}
		
		if( empty( $query ) && !empty( $this->filter ) && $this->filter !== 'all' ) {
			
			$args['status'] = $this->filter;
			
		}
		else {
			
			$args['status'] = '';
			
		}
		
		if( !empty( $query ) ) {
			
			$args['search'] = $query;
			
		}
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
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

	/**
     * Validat License | Route: /validate-license
     * 
     * @since 2.9.4
     * @version 1.0.0
     */
	public function validate_license( WP_REST_Request $request ) {

		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';

		/**
		 * Validate License
		 * 
		 * @param bool $validate_license
		 * 
		 * @since 2.9.4
		 */
		$validate_license = apply_filters( 'post_smtp_mobile_validate_license', false );

		if( ! $validate_license ) {
			wp_send_json_error(
				array( 'message' => 'License not found.' ),
				404
			);
		}
			
		$response = array();

		/**
		 * License Response
		 * 
		 * @param array $response
		 * 
		 * @since 2.9.4
		 */
		$response = apply_filters( 'post_smtp_mobile_license_response', $response );

		if( !empty( $response ) ) {

			wp_send_json_success(
				$response,
				200
			);

		}

		wp_send_json_error(
			array( 'message' => 'License not found.' ),
			404
		);

	}

}

new Post_SMTP_Mobile_Rest_API_V2();