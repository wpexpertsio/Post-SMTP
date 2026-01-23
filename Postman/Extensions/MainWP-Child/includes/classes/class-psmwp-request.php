<?php

// Ensure Post SMTP logging helpers are available.
if ( ! function_exists( 'postman_add_log_meta' ) && defined( 'POST_SMTP_PATH' ) ) {
	require_once POST_SMTP_PATH . '/includes/postman-functions.php';
}

if( !class_exists( 'Post_SMTP_MainWP_Child_Request' ) ):

class Post_SMTP_MainWP_Child_Request {


    private $base_url = false;


    /**
     * Constructor
     * 
     * @since 2.6.0
     * @version 2.6.0
     */
    public function __construct() {

		$server = apply_filters( 'mainwp_child_get_encrypted_option', false, 'mainwp_child_server', false );
		$server = str_replace( 'wp-admin/', '', $server );
		
        if( $server ) {
            
			$this->base_url = $server . 'wp-json/post-smtp-for-mainwp/v1/send-email';
			
        }

    }


    /**
     * Process email
     * 
     * @param string|array $to Array or comma-separated list of email addresses to send message.
     * @param string $subject Email subject
     * @param string $message Message contents
     * @param string|array $headers Optional. Additional headers.
     * @param string|array $attachments Optional. Files to attach.
     * @return bool Whether the email contents were sent successfully.
     * @since 2.6.0
     * @version 2.6.0
     */
    public function process_email( $to, $subject, $message, $headers = '', $attachments = array() ) {

		$body = array();
		$pubkey = get_option( 'mainwp_child_pubkey' );
		$pubkey = $pubkey ? md5( $pubkey ) : '';
        $request_headers = array(
            'Site-Id'	=>	get_option( 'mainwp_child_siteid' ),
			'API-Key'	=>	$pubkey
        );

		// Let's manage attachments.
		if ( ! empty( $attachments ) && $attachments ) {

			$_attachments = $attachments;
			$attachments  = array();

			foreach ( $_attachments as $attachment ) {

				$attachments[ $attachment ] = file_get_contents( $attachment );

			}

		}

		$body         = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
		$action_nonce = apply_filters( 'mainwp_child_create_action_nonce', false, 'post-smtp-send-mail' );
		$ping_nonce   = apply_filters( 'mainwp_child_get_ping_nonce', '' );
		$this->base_url = "$this->base_url/?actionnonce={$action_nonce}&pingnonce={$ping_nonce}";

        $response = wp_remote_post(
            $this->base_url,
            array(
                'method'  => 'POST',
                'body'    => $body,
                'headers' => $request_headers,
            )
        );

		// Transport-level failure.
		if ( is_wp_error( $response ) ) {
			$this->log_email_result(
				$to,
				$subject,
				$headers,
				false,
				0,
				'',
				$response->get_error_message()
			);

			return $response;
		}

		$code       = wp_remote_retrieve_response_code( $response );
		$body_raw   = wp_remote_retrieve_body( $response );

		$success        = false;
		$status_message = '';
		$parent_logs    = array();

		// Try to understand a standard WP REST-style response.
		if ( ! empty( $body_raw ) ) {
			$decoded = json_decode( $body_raw, true );

			if ( is_array( $decoded ) && array_key_exists( 'success', $decoded ) ) {
				$success = (bool) $decoded['success'];

				if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
					if ( isset( $decoded['data']['message'] ) ) {
						$status_message = sanitize_text_field( $decoded['data']['message'] );
					}

					// Prefer the new multi-log format from the parent, but fall back to single log for backwards compatibility.
					if ( isset( $decoded['data']['logs'] ) && is_array( $decoded['data']['logs'] ) ) {
						$parent_logs = $decoded['data']['logs'];
					} elseif ( isset( $decoded['data']['log'] ) && is_array( $decoded['data']['log'] ) ) {
						$parent_logs = array( $decoded['data']['log'] );
					}
				}
			} else {
				// Fallback: treat 2xx as success when body is non-empty.
				$success = ( $code >= 200 && $code < 300 );
			}
		} else {
			// Empty body: rely on status code.
			$success = ( $code >= 200 && $code < 300 );
		}

		/**
		 * If the parent sent back detailed Email Log entries (for example, a primary attempt that failed
		 * and a fallback attempt that succeeded), derive the overall success from those logs so that
		 * wp_post_smtp_mainwp_logs accurately reflects whether at least one attempt delivered the email.
		 *
		 * - If any parent log has a "success" value that represents delivery (1 or "Sent ( ** Fallback ** )"),
		 *   we treat the whole operation as successful.
		 * - If we have logs but none indicate delivery, we treat the operation as failed.
		 */
		if ( ! empty( $parent_logs ) && is_array( $parent_logs ) ) {
			$any_success_log = false;

			foreach ( $parent_logs as $parent_log ) {
				if ( ! is_array( $parent_log ) || ! array_key_exists( 'success', $parent_log ) ) {
					continue;
				}

				$log_success = $parent_log['success'];

				// In the logs table, "success" is:
				// - int(1) for normal success
				// - string 'Sent ( ** Fallback ** )' when fallback delivered
				// - other strings for errors / queue states.
				if (
					1 === $log_success ||
					'1' === $log_success ||
					true === $log_success ||
					'Sent ( ** Fallback ** )' === $log_success
				) {
					$any_success_log = true;
					break;
				}
			}

			// If we have detailed logs, prefer their outcome over the top-level "success" flag.
			$success = $any_success_log;
		}

		$this->log_email_result(
			$to,
			$subject,
			$headers,
			$success,
			$code,
			$body_raw,
			$status_message
		);

		// If the parent sent back Email Log entries (primary + fallback attempts), mirror them into the
		// child site's Email Log table so the child can display the same details locally.
		if ( ! empty( $parent_logs ) && is_array( $parent_logs ) ) {
			foreach ( $parent_logs as $parent_log ) {
				if ( is_array( $parent_log ) ) {
					$this->sync_parent_log_to_child( $parent_log );
				}
			}
		} elseif ( $success ) {
			// Parent responded without per-attempt Email Logs. To avoid having no entries in wp_post_smtp_logs
			// on the child site, create a minimal log row that at least records the successful delivery.
			$this->create_basic_child_log( $to, $subject, $message, $headers, $status_message );
		} else {
			// Overall send failed and the parent did not provide detailed logs; record a minimal failure
			// entry in wp_post_smtp_logs so the child Email Log and failure widgets can still show it.
			$this->create_basic_child_failure_log( $to, $subject, $message, $headers, $status_message );
		}

		if ( ! $success ) {
			return new WP_Error(
				'post_smtp_mainwp_email_failed',
				__( 'Email sending failed on MainWP Dashboard site.', 'post-smtp' ),
				array(
					'status_code' => $code,
					'response'    => $body_raw,
					'message'     => $status_message,
				)
			);
		}

		return true;

    }

	/**
	 * Log the result of a MainWP email request in the child site's database.
	 *
	 * @since 2.6.0
	 * @version 2.6.0
	 *
	 * @param string|array $to            Recipient(s).
	 * @param string       $subject       Email subject.
	 * @param string|array $headers       Headers.
	 * @param bool         $success       Whether the request was successful.
	 * @param int          $status_code   HTTP status code.
	 * @param string       $response_body Raw response body.
	 * @param string       $status_message Parsed status / error message.
	 */
	private function log_email_result( $to, $subject, $headers, $success, $status_code, $response_body, $status_message ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'post_smtp_mainwp_logs';

		// Ensure table exists (cheap, idempotent).
		$charset_collate = $wpdb->get_charset_collate();

		$create_sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			to_email longtext NULL,
			subject text NULL,
			headers longtext NULL,
			success tinyint(1) NOT NULL DEFAULT 0,
			status_code int(11) NOT NULL DEFAULT 0,
			status_message text NULL,
			response_body longtext NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset_collate};";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $create_sql );

		// Normalize data for storage.
		if ( is_array( $to ) ) {
			$to = implode( ', ', $to );
		}

		if ( is_array( $headers ) ) {
			$headers = maybe_serialize( $headers );
		}

		// Avoid storing extremely large bodies.
		if ( ! empty( $response_body ) && strlen( $response_body ) > 10000 ) {
			$response_body = substr( $response_body, 0, 10000 );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			array(
				'created_at'     => current_time( 'mysql' ),
				'to_email'       => $to,
				'subject'        => $subject,
				'headers'        => $headers,
				'success'        => $success ? 1 : 0,
				'status_code'    => (int) $status_code,
				'status_message' => $status_message,
				'response_body'  => $response_body,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * When the MainWP Dashboard does not return individual Email Log rows in its API response,
	 * create a minimal log entry in the child's main Email Log table so that successful sends
	 * are still visible under wp_post_smtp_logs.
	 *
	 * This is a fallback and only runs when we know the overall send was successful but we
	 * did not receive detailed logs to mirror.
	 *
	 * @param string|array $to
	 * @param string       $subject
	 * @param string       $message
	 * @param string|array $headers
	 * @param string       $status_message
	 */
	private function create_basic_child_log( $to, $subject, $message, $headers, $status_message ) {
		if ( ! class_exists( 'PostmanEmailLogs' ) && defined( 'POST_SMTP_PATH' ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanEmailLogs.php';
		}

		if ( ! class_exists( 'PostmanEmailLogs' ) ) {
			return;
		}

		$email_logs = new PostmanEmailLogs();

		$data = array(
			// Match the semantics from PostmanEmailLogService::writeToEmailLog for successful sends.
			'success'          => 1,
			'solution'         => is_string( $status_message ) ? $status_message : '',
			'original_subject' => is_string( $subject ) ? sanitize_text_field( $subject ) : '',
			'original_message' => is_string( $message ) ? $message : '',
			'time'             => current_time( 'timestamp' ),
		);

		// Populate recipient fields if we have them.
		if ( ! empty( $to ) ) {
			$sanitized_to = '';

			if ( class_exists( 'PostmanEmailLogService' ) ) {
				// Re-use the core sanitizer so formats like "Name <email@domain>" are handled correctly.
				$service      = PostmanEmailLogService::getInstance();
				$sanitized_to = $service->sanitize_emails( $to );
			} else {
				$emails       = is_array( $to ) ? $to : explode( ',', (string) $to );
				$emails       = array_map( 'trim', $emails );
				$emails       = array_map( 'sanitize_email', $emails );
				$emails       = array_filter( $emails );
				$sanitized_to = implode( ', ', $emails );
			}

			if ( ! empty( $sanitized_to ) ) {
				$data['to_header']     = $sanitized_to;
				$data['original_to']   = $sanitized_to;
			}
		}

		// Optionally persist headers for debugging / resends.
		if ( ! empty( $headers ) ) {
			$data['original_headers'] = is_array( $headers ) ? maybe_serialize( $headers ) : (string) $headers;
		}

		$email_logs->save( $data );
	}

	/**
	 * Create a minimal failure log in the child's main Email Log table when the MainWP Dashboard
	 * reports that sending failed and does not return detailed log rows to mirror.
	 *
	 * The "success" field is populated with the error message so that it is rendered as a Failed
	 * status in the Email Log UI, matching how core Post SMTP treats failure entries.
	 *
	 * @param string|array $to
	 * @param string       $subject
	 * @param string       $message
	 * @param string|array $headers
	 * @param string       $status_message
	 */
	private function create_basic_child_failure_log( $to, $subject, $message, $headers, $status_message ) {
		if ( ! class_exists( 'PostmanEmailLogs' ) && defined( 'POST_SMTP_PATH' ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanEmailLogs.php';
		}

		if ( ! class_exists( 'PostmanEmailLogs' ) ) {
			return;
		}

		$email_logs = new PostmanEmailLogs();

		// If we don't have a meaningful status message, fall back to a generic one.
		if ( ! is_string( $status_message ) || '' === trim( $status_message ) ) {
			$status_message = __( 'Email sending failed on MainWP Dashboard site.', 'post-smtp' );
		}

		$data = array(
			// For failures, Post SMTP stores a descriptive string in "success" which is later used
			// as the title attribute on the "Failed" label in the UI.
			'success'          => $status_message,
			'solution'         => '',
			'original_subject' => is_string( $subject ) ? sanitize_text_field( $subject ) : '',
			'original_message' => is_string( $message ) ? $message : '',
			'time'             => current_time( 'timestamp' ),
		);

		// Populate recipient fields if we have them.
		if ( ! empty( $to ) ) {
			$sanitized_to = '';

			if ( class_exists( 'PostmanEmailLogService' ) ) {
				$service      = PostmanEmailLogService::getInstance();
				$sanitized_to = $service->sanitize_emails( $to );
			} else {
				$emails       = is_array( $to ) ? $to : explode( ',', (string) $to );
				$emails       = array_map( 'trim', $emails );
				$emails       = array_map( 'sanitize_email', $emails );
				$emails       = array_filter( $emails );
				$sanitized_to = implode( ', ', $emails );
			}

			if ( ! empty( $sanitized_to ) ) {
				$data['to_header']   = $sanitized_to;
				$data['original_to'] = $sanitized_to;
			}
		}

		// Optionally persist headers for debugging / resends.
		if ( ! empty( $headers ) ) {
			$data['original_headers'] = is_array( $headers ) ? maybe_serialize( $headers ) : (string) $headers;
		}

		$email_logs->save( $data );
	}

	/**
	 * Mirror the parent site's Post SMTP email log into the child site's own Email Log table.
	 *
	 * @param array $log Parent site log data.
	 */
	private function sync_parent_log_to_child( $log ) {

		if ( ! is_array( $log ) || empty( $log ) ) {
			return;
		}

		if ( ! class_exists( 'PostmanEmailLogs' ) && defined( 'POST_SMTP_PATH' ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanEmailLogs.php';
		}

		if ( ! class_exists( 'PostmanEmailLogs' ) ) {
			return;
		}

		$email_logs = new PostmanEmailLogs();

		$allowed_fields = array(
			'solution',
			'success',
			'from_header',
			'to_header',
			'cc_header',
			'bcc_header',
			'reply_to_header',
			'transport_uri',
			'original_to',
			'original_subject',
			'original_message',
			'original_headers',
			'session_transcript',
			'time',
		);

		$data = array();

		foreach ( $allowed_fields as $field ) {
			if ( isset( $log[ $field ] ) ) {
				$data[ $field ] = $log[ $field ];
			}
		}

		if ( empty( $data ) ) {
			return;
		}

		if ( empty( $data['time'] ) ) {
			$data['time'] = current_time( 'timestamp' );
		}

		$child_log_id = $email_logs->save( $data );

		if ( ! $child_log_id || ! function_exists( 'postman_add_log_meta' ) ) {
			return;
		}

		// Link back to the parent log ID when available.
		if ( isset( $log['id'] ) ) {
			postman_add_log_meta( $child_log_id, 'mainwp_parent_log_id', absint( $log['id'] ) );
		}
	}


}

endif;