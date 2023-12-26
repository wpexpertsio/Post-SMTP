<?php

class Post_SMTP_Mobile_Rest_API {
	
	private $filter = '';


    /**
     * Register routes
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

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
		
		register_rest_route( 'post-smtp/v1', '/resend-email', array(
            'methods'               => WP_REST_Server::CREATABLE,
            'callback'              => array( $this, 'resend_email' ),
            'permission_callback'   => '__return_true',
        ) );

    }
	
	public function connect_app( WP_REST_Request $request ) {
		
		$nonce = get_transient( 'post_smtp_auth_nonce' );
		$auth_key = $request->get_header( 'auth_key' );
		$fcm_token = $request->get_header( 'fcm_token' );
		$device = $request->get_header( 'device' );
		$server_url = $request->get_header( 'server_url' );
		
		if( $auth_key === $nonce ) {
			
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
			
			wp_send_json_success( 
				array(
					'fcm_token'	=>	$fcm_token
				), 
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
		$this->filter = $request->get_param( 'filter' ) !== 'all' ? $request->get_param( 'filter' ) : '';
		$query = $request->get_param( 'query' ) !== '' ? $request->get_param( 'query' ) : '';
		
		if( empty( $query ) && !empty( $this->filter ) ) {
			
			add_filter( 'post_smtp_get_logs_query_after_table', array( $this, 'filter_query' ) );
			
		}
		
		if( !empty( $query ) ) {
			
			$args['search'] = $query;
			
		}
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		if( $this->validate( $fcm_token ) ) {
			
			$logs_query = new PostmanEmailQueryLog();
			$args['start'] = $start;
			$args['end'] = $end;
			
			if( empty( $args ) ) {
				
				wp_send_json_success(
					array( 'message' => 'Logs not found.' ),
					200
				);
				
			}
			
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
	
	public function resend_email( WP_REST_Request $request ) {
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		$id = $request->get_param( 'id' ) !== null ? $request->get_param( 'id' ) : '';
		
		if( $this->validate( $fcm_token ) ) {
			
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
		
	}
	
	public function disconnect_site( WP_REST_Request $request ) {
		
		$fcm_token = $request->get_header( 'fcm_token' ) !== null ? $request->get_header( 'fcm_token' ) : '';
		
		if( !class_exists( 'PostmanEmailQueryLog' ) ) {
			
			require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			
		}
		
		if( $this->validate( $fcm_token ) ) {
			
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
		
	}
	
	public function filter_query( $query ) {
		
		$query .= $this->filter == 'success' ? ' WHERE `success` = 1 ' : ' WHERE `success` != 1 ';
		
		return $query;
		
	}

}

new Post_SMTP_Mobile_Rest_API();