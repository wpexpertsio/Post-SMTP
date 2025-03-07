<?php

if ( ! class_exists( 'PSD_Rest_API' ) ) {
	/**
	 * class PSD_Rest_API
	 */
	class PSD_Rest_API {

		private $namespace = 'psd/v1';

		/**
		 * PSD_Rest_API constructor.
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Register routes
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/get-logs',
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_logs' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);

			register_rest_route(
				$this->namespace,
				'/get-details',
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_details' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);

			register_rest_route(
				$this->namespace,
				'/resend-email',
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'resend_email' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);

			register_rest_route(
				$this->namespace,
				'email-count',
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'email_count' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				),
			);

			register_rest_route(
				$this->namespace,
				'minimize-maximize-ad',
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'minimize_maximize_ad' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				),
			);

			register_rest_route(
				$this->namespace,
				'get-failed-logs',
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_failed_logs' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);

			register_rest_route(
				$this->namespace,
				'/open-notification',
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'open_notification' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);

			register_rest_route(
				$this->namespace,
				'/remove-notification',
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'remove_notification' ),
					'permission_callback' => array( $this, 'get_logs_permission' )
				)
			);
		}

		/**
		 * Get logs
		 *
		 * @param WP_REST_Request $request
		 */
		public function get_logs( $request ) {

			$logs_query = new PostmanEmailQueryLog;

			$data = $logs_query->get_logs(
				array(
					'order'    => 'desc',
					'order_by' => 'id',
				)
			);
			$data = array_slice( $data, 0, 4 );
			$data = array_map(
				function( $log ) {
					$data = array(
						'id'            => $log->id,
						'subject'       => $log->original_subject,
						'sent_to'       => $log->to_header,
						'delivery_time' => gmdate( 'F d, Y h:i a', $log->time ),
					);

					if ( 1 === absint( $log->success ) ) {
						$data['status'] = 'success';
					} elseif ( 'In Queue' === $log->success ) {
						$data['status'] = 'in_queue';
					} else {
						$data['status'] = 'failed';
						$data['error']  = $log->success;
					}

					return $data;
				},
				$data
			);

			return array(
				'success' => true,
				'message' => 'Logs fetched successfully',
				'status'  => 200,
				'logs'    => $data,
			);
		}

		/**
		 * Get details
		 *
		 * @param WP_REST_Request $request
		 */
		public function get_details( $request ) {
			$id              = $request->get_param( 'id' );
			$type            = $request->get_param( 'type' );
			$email_query_log = new PostmanEmailQueryLog();
			$response        = '';

			if ( 'show_transcript' === $type ) {
				$response = $email_query_log->get_log( $id, array( 'session_transcript' ) );
			}

			if ( 'show_view' === $type ) {
				$response     = $email_query_log->get_log( $id );
				$new_response = $response;

				foreach ( $new_response as $key => $value ) {
					if ( 'original_message' === $key ) {
						$response['log_url'] = admin_url( "admin.php?page=postman_email_log&view=log&log_id={$id}" );
					} else {
						$response[ $key ] = esc_html( $value );
					}
				}

				if( isset( $response['time'] ) ) {

					//WordPress Date, Time Format
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );

					$response['time'] = date( "{$date_format} {$time_format}", $response['time'] );

				}

				if ( $response ) {
					$response = apply_filters( 'post_smtp_before_view_log', $response, $type );
				}
			}

			if ( ! empty( $response ) ) {
				return array(
					'success' => true,
					'message' => 'Details fetched successfully',
					'status'  => 200,
					'details' => $response,
				);
			}

			return array(
				'success' => false,
				'message' => 'Details not found',
				'status'  => 404,
			);
		}

		/**
		 * Resend email
		 *
		 * @param WP_REST_Request $request
		 */
		public function resend_email( $request ) {
			$id              = $request->get_param( 'id' );
			$recipient_email = $request->get_param( 'sent_to' );

			$logger          = new PostmanLogger( get_class( $this ) );
			$email_query_log = new PostmanEmailQueryLog();
			$log             = $email_query_log->get_log( $id );
			$headers         = '';

			if ( $log ) {
				if ( ! empty( $recipient_email ) ) {
					$emails = explode( ',', $recipient_email );
					$to     = array_map(
						function( $email ) {
							return sanitize_text_field( wp_unslash( $email ) );
						},
						$emails
					);
				} else {
					$to = $log['original_to'];
				}

				if ( $log['original_headers'] ) {
					$headers = is_serialized( $log['original_headers'] ) ? unserialize( $log['original_headers'] ) : $log['original_headers'];
				}

				$attachments = apply_filters( 'post_smtp_resend_attachments', array(), $id );
				$success     = wp_mail( $to, $log['original_subject'], $log['original_message'], $headers, $attachments );
				$result      = apply_filters( 'postman_wp_mail_result', null );
				$transcript  = $result['transcript'];

				if ( $success ) {
					$logger->debug( 'Email was successfully re-sent' );
					$statusMessage = sprintf( __( 'Your message was delivered (%d ms) to the SMTP server! Congratulations :)', 'post-smtp' ), $result ['time'] );

					$response = array(
						'success'       => true,
						'message'       => $statusMessage,
						'transcript'    => $transcript,
					);

					$logger->trace( 'RestAPI response' );
					$logger->trace( $response );
				} else {
					$logger->error( 'Email was not successfully re-sent - ' . $result ['exception']->getCode() );
					// the message was NOT sent successfully, generate an appropriate message for the user
					$statusMessage = $result ['exception']->getMessage();

					// compose the JSON response for the caller
					$response = array(
						'message' => $statusMessage,
						'transcript' => $transcript,
					);
					$logger->trace( 'RestAPI response' );
					$logger->trace( $response );
				}

			} else {
				$response = array(
					'success' => false,
					'message' => __( 'Error Resending Email', 'post-smtp' )
				);
			}

			return $response;
		}

		/**
		 * Email count
		 *
		 * @param WP_REST_Request $request
		 */
		public function email_count( $request ) {
			$period        = $request->get_param( 'period' );
            $opened_emails = $request->get_param( 'opened' );
			$current_time  = current_time( 'timestamp' );

			switch ( $period ) {
				case 'day':
				default:
					$filter = strtotime( 'today', $current_time );
					break;
				case 'week':
					$today  = strtotime( 'today', $current_time );
					$filter = strtotime( '-7 days', $today );
					break;
				case 'month':
					$today  = strtotime( 'today', $current_time );
					$filter = strtotime( '-1 month', $today );
					break;
			}
            $logs_query = new PostmanEmailQueryLog;
            $logs = $logs_query->get_logs(
                array(
                    'order' => 'desc',
                    'order_by' => 'id',
                    'to' => $current_time,
                    'from' => $filter,
                )
            );

            $success = $logs;
            $success = array_filter($success, function ($log) {
                return 1 === absint($log->success);
            });

            $failed = $logs;
            $failed = array_filter($failed, function ($log) {
                return 1 !== absint($log->success) && 'In Queue' !== $log->success;
            });


            $data = array(
                'success' => true,
                'message' => 'Email count fetched successfully',
                'status'  => 200,
                'count'   => array(
                    'success' => count( $success ),
                    'failed'  => count( $failed ),
                    'total'   => count( $logs ),
                ),
            );

            $opened_emails_count = apply_filters(
                'post_smtp_dashboard_opened_emails_count',
                0,
                array(
                    'period'       => $period,
                    'current_time' => $current_time,
                    'filter'       => $filter,
                )
            );


            if ( $opened_emails_count ) {
                $data['count']['opened'] = $opened_emails_count;
            }

            return $data;
		}

		/**
		 * Minimize or maximize ad
		 *
		 * @param WP_REST_Request $request
		 */
		public function minimize_maximize_ad( $request ) {
			$minimize_maximize = $request->get_param( 'minimize_maximize' );

			update_option(
				'postman_dashboard_ad',
				$minimize_maximize
			);

			return array(
				'success' => true,
				'message' => 'Ad minimized or maximized successfully',
				'status'  => 200,
			);
		}

		/**
		 * Get failed logs
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function get_failed_logs( $request ) {
			$logs_query = new PostmanEmailQueryLog;
			$data       = $logs_query->get_logs(
				array(
					'order'    => 'desc',
					'order_by' => 'id',
				)
			);

			$current_time = current_time( 'timestamp' );
			$data = array_map(
				function( $log ) use ( $current_time ) {
					// calculate time difference and convert it into h m or s
					$time_diff = $current_time - $log->time;
					$time_diff = human_time_diff( $log->time, $current_time );
					
					$data = array(
						'id'            => $log->id,
						'subject'       => $log->original_subject,
						'sent_to'       => $log->to_header,
						'status'        => 1 === absint( $log->success ) ? 'success' : 'failed',
						'delivery_time' => $time_diff,
						'opened'        => 'yes' === postman_get_log_meta( $log->id, 'opened_in_dashboard' ),
					);

					if ( 1 !== absint( $log->success ) ) {
						$data['error'] = $log->success;
					}

					return $data;
				},
				$data
			);
			$data = array_filter(
				$data,
				function( $log ) {
					if ( 'failed' === $log['status'] ) {
						$deleted_notification = postman_get_log_meta( $log['id'], 'notification_deleted' );
						if ( ! $deleted_notification ) {
							return true;
						}

						if ( 'yes' !== $deleted_notification ) {
							return true;
						}
					}
					return false;
				}
			);

			return array(
				'success' => true,
				'message' => 'Failed logs fetched successfully',
				'status'  => 200,
				'logs'    => $data,
			);

		}

		/**
		 * Open notification
		 *
		 * @param WP_REST_Request $request
		 */
		public function open_notification( $request ) {
			$notification_id = $request->get_param( 'id' );

			postman_update_log_meta(
				$notification_id,
				'opened_in_dashboard',
				'yes'
			);

			return array(
				'success' => true,
				'message' => 'Notification opened successfully',
				'status'  => 200,
			);
		}

		/**
		 * Remove notification
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function remove_notification( $request ) {
			$notification_id = $request->get_param( 'id' );

			postman_update_log_meta(
				$notification_id,
				'notification_deleted',
				'yes'
			);

			return array(
				'success' => true,
				'message' => 'Notification removed successfully',
				'status'  => 200,
			);

		}

		/**
		 * Get logs permission
		 *
		 * @return bool
		 */
		public function get_logs_permission() {
			return is_user_logged_in();
		}
	}

	new PSD_Rest_API();
}
