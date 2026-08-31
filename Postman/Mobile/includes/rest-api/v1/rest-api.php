<?php

class Post_SMTP_Mobile_Rest_API {
	
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

        register_rest_route( 'post-smtp/v1', '/connect-app', array(
            'methods'               => WP_REST_Server::CREATABLE,
            'callback'              => array( $this, 'connect_app' ),
            'permission_callback'   => 'post_smtp_mobile_permission_connect_app',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/get-logs', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'get_logs' ),
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/disconnect-site', array(
            'methods'               => WP_REST_Server::EDITABLE,
            'callback'              => array( $this, 'disconnect_site' ),
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/get-log', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'get_log' ),
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );
		
		register_rest_route( 'post-smtp/v1', '/resend-email', array(
            'methods'               => WP_REST_Server::CREATABLE,
            'callback'              => array( $this, 'resend_email' ),
            'permission_callback'   => 'post_smtp_mobile_permission_fcm_token',
        ) );

    }
	
	public function connect_app( WP_REST_Request $request ) {
		
		$nonce = get_transient( 'post_smtp_auth_nonce' );
		$auth_key = sanitize_text_field( (string) $request->get_header( 'auth_key' ) );
		$fcm_token = sanitize_text_field( (string) $request->get_header( 'fcm_token' ) );
		$device = sanitize_text_field( (string) $request->get_header( 'device' ) );
		$server_url = esc_url_raw( (string) $request->get_header( 'server_url' ) );
		
		if( hash_equals( (string) $nonce, $auth_key ) ) {
			
			$data = array(
				$fcm_token	=>	array(
					'auth_key'				=>	$auth_key,
					'fcm_token'				=>	$fcm_token,
					'device'				=>	$device,
					'enable_notification'	=>	1
				)
			);
			
			update_option( 'post_smtp_mobile_app_connection', $data );
			update_option( 'post_smtp_server_url', $server_url );
			delete_transient( 'post_smtp_auth_nonce' );
			
			$response = array(
				'fcm_token'			=>	$fcm_token,
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
		
		wp_send_json_error( 
			array(
				'error'	=>	'Regenerate QR Code, and scan again.'
			), 
			200 
		);
		
	}
	
	public function get_logs( WP_REST_Request $request ) {
		
		$args['order_by'] = 'time';
		$args['order'] = 'DESC';
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? sanitize_text_field( (string) $request->get_header( 'fcm_token' ) ) : '';
		$app_build_number = $request->get_header( 'app_build_number' ) !== null ? absint( $request->get_header( 'app_build_number' ) ) : 0;
		$start = $request->get_param( 'start' ) !== null ? absint( $request->get_param( 'start' ) ) : 0;
		$end = $request->get_param( 'end' ) !== null ? absint( $request->get_param( 'end' ) ) : 25;
		$filter_param = $request->get_param( 'filter' ) !== null ? sanitize_key( (string) $request->get_param( 'filter' ) ) : 'all';
		$this->filter = in_array( $filter_param, array( 'success', 'failed' ), true ) ? $filter_param : '';
		$query = $request->get_param( 'query' ) !== null && $request->get_param( 'query' ) !== '' ? sanitize_text_field( (string) $request->get_param( 'query' ) ) : '';
		
		if( empty( $query ) && !empty( $this->filter ) ) {
			
			add_filter( 'post_smtp_get_logs_query_after_table', array( $this, 'filter_query' ) );
			
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
		
		if( !empty( $app_build_number ) &&  $app_build_number >= 14 ) {
			
			$response = array(
				'logs'				=>	$logs_query->get_logs( $args ),
				'plugin_version'	=>	POST_SMTP_VER
			);
			
		}
		else {
			$response = $logs_query->get_logs( $args );
		}
		
		wp_send_json_success(
			$response,
			200
		);
		
	}
	
	public function get_log( WP_REST_Request $request ) {
		
		$id = $request->get_param( 'id' ) !== null ? absint( $request->get_param( 'id' ) ) : 1;
		$type_param = $request->get_param( 'type' ) !== null ? sanitize_key( (string) $request->get_param( 'type' ) ) : 'log';
		$type = in_array( $type_param, array( 'log', 'transcript', 'details' ), true ) ? $type_param : 'log';
		$view_token = post_smtp_create_mobile_log_view_token( $id, $type );
		
		$url = add_query_arg(
			array(
				'ps_log_view' => $view_token,
			),
			admin_url( 'admin.php' )
		);
		
		wp_send_json_success(
			$url,
			200
		);
		
	}
	
	public function resend_email( WP_REST_Request $request ) {
		
		$id = $request->get_param( 'id' ) !== null ? absint( $request->get_param( 'id' ) ) : 0;
		
		if( empty( $id ) ){
				
				wp_send_json_error( 
					array(
						'error'	=>	'Enter email id.'
					), 
					401 
				);
				
			}
			
			if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
				require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';

			}

            $response = '';
            $email_query_log = new PostmanEmailQueryLog();
            $log = $email_query_log->get_log( $id );
            $to = '';

            if( $log ) {

				$to = $log['original_to'];

                /**
                 * Fires before resending email
                 * 
                 * @param array attachments
                 * @since 2.5.9
                 * @version 1.0.0
                 */
                $attachments = apply_filters( 'post_smtp_resend_attachments', array(), $id );

                $success = wp_mail( $to, $log['original_subject'], $log['original_message'], $log['original_headers'], $attachments );

                // Postman API: retrieve the result of sending this message from Postman
                $result = apply_filters( 'postman_wp_mail_result', null );
                $transcript = $result ['transcript'];
     
                // post-handling
                if ( $success ) {
				
                    wp_send_json_success(
						array(
							'message'	=>	'Email successfully resend.'
						),
						200
					);

                }
                else {
					
					wp_send_json_error( 
						array(
							'message'	=>	'Email not send.'
						), 
						200
					);
					
                }

            }
			else {
				
				wp_send_json_error( 
					array(
						'error'	=>	'Invalid email id.'
					), 
					401 
				);
				
			}
		
	}
	
	public function disconnect_site( WP_REST_Request $request ) {
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		$response = delete_option( 'post_smtp_mobile_app_connection' );
		$response = delete_option( 'post_smtp_server_url' );
		
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
	
	public function filter_query( $query ) {
		
		if ( $this->filter == 'success' ) {
			$query .= " WHERE (`success` = 1 OR `success` = 'Sent ( ** Fallback ** )' OR `success` LIKE '( ** Fallback ** )%') ";
		} else {
			$query .= " WHERE (`success` != 1 AND `success` != 'Sent ( ** Fallback ** )' AND `success` NOT LIKE '( ** Fallback ** )%') ";
		}
		
		return $query;
		
	}

}

new Post_SMTP_Mobile_Rest_API();