<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require 'Postman-Email-Log/PostmanEmailQueryLog.php';

class PostmanEmailLogs {

    private $db;
    public $db_name = 'post_smtp_logs';
    public $meta_table = 'post_smtp_logmeta';
    private $logger;

    private $fields = array(
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
        'time'
    );

    private static $instance;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function __construct() {

        global $wpdb;

        $this->db = $wpdb;
		$this->logger = new PostmanLogger( get_class( $this ) );

        //Render Message body in iframe
        if( 
            isset( $_GET['page'] ) && $_GET['page'] == 'postman_email_log' 
            && 
            isset( $_GET['view'] ) && $_GET['view'] == 'log' 
            &&
            isset( $_GET['log_id'] ) && !empty( $_GET['log_id'] )
        ) {

            $id = sanitize_text_field( $_GET['log_id'] );
            $email_query_log = new PostmanEmailQueryLog();
            $log = $email_query_log->get_log( $id, '' );
            $msg = $log['original_message'];
            $msg = preg_replace( "/<script\b[^>]*>(.*?)<\/script>/s", '', $msg );

            // Strip <xml> and comment tags.
            $msg = preg_replace( '/<xml\b[^>]*>(.*?)<\/xml>/is', '', $msg );
            $msg = preg_replace( '/<!--(.*?)-->/', '', $msg );

            $allowed_html = wp_kses_allowed_html( 'post' );
            $allowed_html['style'][''] = true;

            $msg = wp_kses( $msg, $allowed_html );

            echo '<pre>' . $msg . '</pre>';

            die;

        }

    }


    /**
     * Installs the Table | Creates the Table
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function install_table() {
        
        if( !function_exists( 'dbDelta' ) ) {

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->db->prefix}{$this->db_name}` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,";

        foreach ( $this->fields as $field ) {

            if( $field == 'time' ) {

                $sql .= "`" . $field . "` BIGINT(20) DEFAULT NULL,";
                continue; 

            } 

            $sql .= "`" . $field . "` longtext DEFAULT NULL,";
            
        }

        $sql .=  "PRIMARY KEY (`id`)) ENGINE=InnoDB";
        $sql .= empty( $this->db->charset ) ? '' : " CHARSET={$this->db->charset}";
        $sql .= empty( $this->db->collate ) ? '' : " COLLATE={$this->db->collate}";
        $sql .= ";";

        $response = dbDelta( $sql );

        if( !$this->db->last_error ) {
            
            $sql = "
            CREATE TABLE IF NOT EXISTS `{$this->db->prefix}{$this->meta_table}` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `log_id` bigint(20) NOT NULL,
                `meta_key` longtext DEFAULT NULL,
                `meta_value` longtext DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB";

            $sql .= empty( $this->db->charset ) ? '' : " CHARSET={$this->db->charset}";
            $sql .= empty( $this->db->collate ) ? '' : " COLLATE={$this->db->collate}";
            $sql .= ";";
    
            $response = dbDelta( $sql );

        }

        if( !$this->db->last_error ) {

            update_option( 'postman_db_version', POST_SMTP_DB_VERSION );

        }

    }


    /**
     * Update Table
     * 
     * @since 2.5.1
     * @version 1.0.0
     */
    public function update_table() {

        $sql = "ALTER TABLE `{$this->db->prefix}{$this->db_name}`
        MODIFY COLUMN solution longtext DEFAULT NULL,
        MODIFY COLUMN success longtext DEFAULT NULL,
        MODIFY COLUMN from_header longtext DEFAULT NULL,
        MODIFY COLUMN to_header longtext DEFAULT NULL,
        MODIFY COLUMN cc_header longtext DEFAULT NULL,
        MODIFY COLUMN bcc_header longtext DEFAULT NULL,
        MODIFY COLUMN reply_to_header longtext DEFAULT NULL,
        MODIFY COLUMN transport_uri longtext DEFAULT NULL,
        MODIFY COLUMN original_to longtext DEFAULT NULL,
        MODIFY COLUMN original_subject longtext DEFAULT NULL,
        MODIFY COLUMN original_headers longtext DEFAULT NULL;";
        
        $response = $this->db->query( $sql );

        if( !$this->db->last_error ) {

            $sql = "ALTER TABLE `{$this->db->prefix}{$this->meta_table}`
            MODIFY COLUMN meta_key longtext,
            MODIFY COLUMN meta_value longtext;";
            
            $response = $this->db->query( $sql );

        }

        if( !$this->db->last_error ) {

            update_option( 'postman_db_version', POST_SMTP_DB_VERSION );

        }

    }


    public static function get_data( $post_id ) {
        $fields = array();
        foreach ( self::$fields as $field ) {
            $fields[$field][0] = get_post_meta( $post_id, $field, true );
        }

        return $fields;
    }

    public static function get_fields() {
        return self::$fields;
    }

    function migrate_data() {
        $args = array(
            'post_type' => 'postman_sent_mail',
            'posts_per_page' => -1,
        );

        $logs = new WP_Query($args);

        $failed_records = 0;
        foreach ( $logs->posts as $log ) {

            foreach ($this->fields as $key ) {
                $value = $this->get_meta( $log->ID, $key, true );

                if ( $this->add_meta( $log->ID, $key, $value ) ) {
                    delete_post_meta( $log->ID, $key );
                } else {
                    $failed_records++;
                }
            }
        }
    }


    /**
     * Delete Log Items, But Keeps recent $keep
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function truncate_log_items( $keep ) {

        return $this->db->get_results(
            $this->db->prepare(
                "DELETE logs FROM `{$this->db->prefix}{$this->db_name}` logs
                LEFT JOIN 
                (SELECT id 
                FROM `{$this->db->prefix}{$this->db_name}` 
                ORDER BY id DESC
                LIMIT %d) logs2 USING(id) 
                WHERE logs2.id IS NULL;",
                $keep
            )
        );

    }


    /**
     * Insert Log Into table
     * 
     * @param array $data
     * @param int $id (Update Existing Record)
     * @since 2.5.0
     * @version 1.0.0
     */
    public function save( $data, $id = '' ) {
        
        $data['time'] = !isset( $data['time'] ) ? current_time( 'timestamp' ) : $data['time'];

        if( !empty( $id ) ) {

            return $this->update( $data, $id );

        }
        else {

            return $this->db->insert(
                $this->db->prefix . $this->db_name,
                $data  
            ) ? $this->db->insert_id : false;

        }

    }

    
    /**
     * Update Log
     * 
     * @param array $data
     * @param int $id
     * @since 2.5.0
     * @version 1.0.0
     */
    public function update( $data, $id ) {

        return $this->db->update(
            $this->db->prefix . $this->db_name,
            $data,
            array( 'id' => $id )
        );

    }


    /**
     * Get Logs
     * 
     * @since 2.5.0
     * @version 1.0
     */
    public function get_logs_ajax() {

        if( !wp_verify_nonce( $_GET['security'], 'security' ) ) {

            return;

        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'ps-get-email-logs' ) {

            $logs_query = new PostmanEmailQueryLog;

            $query = array();
            $query['start'] = sanitize_text_field( $_GET['start'] );
            $query['end'] = sanitize_text_field( $_GET['length'] );
            $query['search'] = sanitize_text_field( $_GET['search']['value'] );
            $query['order'] = sanitize_text_field( $_GET['order'][0]['dir'] );
            
			//MainWP | Get Sites
            if( isset( $_GET['site_id'] ) ) {

                $query['site_id'] = sanitize_text_field( $_GET['site_id'] );

            }

            //Column Name
            $query['order_by'] = sanitize_text_field( $_GET['columns'][$_GET['order'][0]['column']]['data'] );

            //Date Filter :)
            if( isset( $_GET['from'] ) ) {

                $query['from'] = strtotime( sanitize_text_field( $_GET['from'] ) );

            }

            if( isset( $_GET['to'] ) ) {

                $query['to'] = strtotime( sanitize_text_field( $_GET['to'] ) ) + 86400;

            }

            $data = $logs_query->get_logs( $query );

            //WordPress Date, Time Format
            $date_format = get_option( 'date_format' );
		    $time_format = get_option( 'time_format' );
            $search = array(
                '<',
                '>',
                '"',
                "'"
            );
            $replace = array(
                '&lt;',
                '&gt;',
                '&quot;',
                '&#039;'
            );

            //Lets manage the Date format :)
            foreach( $data as $row ) {

                $row->time = date( "{$date_format} {$time_format}", $row->time );

                if( $row->success == 1 ) {

                    $row->success = '<span title="Success">Success</span>';

                }
                elseif( $row->success == 'In Queue' ) {

                    $row->success = '<span title="In Queue">In Queue</span>';

                }
                else {

                    $row->success = '<span title="'.str_replace( $search, $replace, $row->success ).'">Failed</span><a href="#" class="ps-status-log ps-popup-btn">View details</a>';
                    
                }
                
        
                $row->actions = '';
				
				/**
                 * Filter the row data
                 * 
                 * @since 2.5.0
                 * @version 1.0.0
                 */
                $row = apply_filters( 'ps_email_logs_row', $row );

                //Escape HTML
                $row->original_subject = esc_html( $row->original_subject );

            }

            $total_rows = $logs_query->get_total_row_count();
            $total_rows = ( is_array( $total_rows ) && !empty( $total_rows ) ) ? $total_rows[0] : '';
            $total_rows = isset( $total_rows->count ) ? (int)$total_rows->count : '';

            $filtered_rows = $logs_query->get_filtered_rows_count();
            $filtered_rows = ( is_array( $filtered_rows ) && !empty( $filtered_rows ) ) ? $filtered_rows[0] : '';
            $filtered_rows = isset( $filtered_rows->count ) ? (int)$filtered_rows->count : '';

            $logs['data'] = $data;
            $logs['recordsTotal'] = $total_rows;
            $logs['recordsFiltered'] = $filtered_rows;
            $logs['draw'] = sanitize_text_field( $_GET['draw'] );

            echo json_encode( $logs );
            die;

        }

    }


    /**
	 * Delete Logs | AJAX callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function delete_logs_ajax() {

		if( !wp_verify_nonce( $_POST['security'], 'security' ) ) {

            return;

        }
		
		if( isset( $_POST['action'] ) && $_POST['action'] == 'ps-delete-email-logs' ) {

			$args = array();

			//Delete all
			if( !isset( $_POST['selected'] ) ) {

				$args = array( -1 );

			}
			//Delete selected
			else {

				$args = wp_parse_id_list( $_POST['selected'] );

			}

			$email_query_log = new PostmanEmailQueryLog();
			$delete = $email_query_log->delete_logs( $args );

			if( $delete ) {

                /**
                 * Fires after deleting logs
                 * 
                 * @param array $args
                 * @since 2.5.0
                 * @version 1.0.0
                 */
                do_action( 'postman_delete_logs_successfully', $args );

				$response = array(
					'success' => true,
					'message' => __( 'Logs deleted successfully', 'post-smtp' )
				);

			}
			else {

				$response = array(
					'success' => false,
					'message' => __( 'Error deleting logs', 'post-smtp' )
				);

			}

			wp_send_json( $response );

		}

	}


	/**
	 * Export Logs | AJAX callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function export_log_ajax() {

		if( !wp_verify_nonce( $_POST['security'], 'security' ) ) {

            return;

        }

		if( isset( $_POST['action'] ) && $_POST['action'] == 'ps-export-email-logs' ) {

			$args = array();

			//Export all
			if( !isset( $_POST['selected'] ) ) {

				$args = array( -1 );

			}
			//Export selected
			else {

				$args = wp_parse_id_list( $_POST['selected'] );

			}

			$email_query_log = new PostmanEmailQueryLog();
			$logs = $email_query_log->get_all_logs( $args );
            $csv_headers = array(
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
                'delivery_time'
            );
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="email-logs.csv"');

            $fp = fopen('php://output', 'wb');

            $headers = $csv_headers;

            fputcsv($fp, $headers);

            $date_format = get_option( 'date_format' );
	        $time_format = get_option( 'time_format' );

            foreach ( $logs as $log ) {

                $data[0] = $log->solution;
                $data[1] = $log->success;
                $data[2] = $log->from_header;
                $data[3] = $log->to_header;
                $data[4] = $log->cc_header;
                $data[5] = $log->bcc_header;
                $data[6] = $log->reply_to_header;
                $data[7] = $log->transport_uri;
                $data[8] = $log->original_to;
                $data[9] = $log->original_subject;
                $data[10] = $log->original_message;
                $data[11] = $log->original_headers;
                $data[12] = $log->session_transcript;
                $data[13] = date( "$date_format $time_format", $log->time );
                
                fputcsv($fp, $data);

            }

            fclose($fp);

            exit();

		}

	}


	/**
	 * View Log | AJAX callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function view_log_ajax() {

		if( !wp_verify_nonce( $_POST['security'], 'security' ) ) {

            return;

        }

		if( isset( $_POST['action'] ) && $_POST['action'] == 'ps-view-log' ) {

			$id = sanitize_text_field( $_POST['id'] );
			$type = array( sanitize_text_field( $_POST['type'] ) );
			$type = $type[0] == 'original_message' ? '' : $type;

			$email_query_log = new PostmanEmailQueryLog();
			$log = $email_query_log->get_log( $id, $type );
            $_log = $log;

            //Escape HTML
            foreach( $_log as $key => $value ) {

                if( $key == 'original_message') {

                    $log['log_url'] = admin_url( "admin.php?page=postman_email_log&view=log&log_id={$id}" );

                }
                else {
                
                    $log[$key] = esc_html( $value );
                
                }

            }

			if( isset( $log['time'] ) ) {

				//WordPress Date, Time Format
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				
				$log['time'] = date( "{$date_format} {$time_format}", $log['time'] );

			}

			if( $log ) {

                /**
                 * Fires before viewing logs
                 * 
                 * @param array $log
                 * @param string $type
                 * @since 2.5.9
                 * @version 1.0.0
                 */
                $log = apply_filters( 'post_smtp_before_view_log', $log, $type );

				$response = array(
					'success' => true,
					'data' => $log,
				);

			}
			else {

				$response = array(
					'success' => false,
					'message' => __( 'Error Viewing', 'post-smtp' )
				);

			}

			wp_send_json( $response );

		}

	}


    /**
     * Resend Email | AJAX callback
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function resend_email() {

        if( !wp_verify_nonce( $_POST['security'], 'security' ) ) {

            return;

        }

        if( isset( $_POST['action'] ) && $_POST['action'] == 'ps-resend-email' ) {

            $id =  intval( $_POST['id'] );
            $response = '';
            $email_query_log = new PostmanEmailQueryLog();
            $log = $email_query_log->get_log( $id );
            $to = '';
            $headers = '';

            if( $log ) {

                if( isset( $_POST['to'] ) ) {

                    $emails = explode( ',', $_POST['to'] );
				    $to = array_map( 'sanitize_email', $emails );

                } 
                else {

                    $to = $log['original_to'];

                }

                if( $log['original_headers'] ){

					$headers = is_serialized( $log['original_headers'] ) ? unserialize( $log['original_headers'] ) : $log['original_headers'];

				}

                /**
                 * Fires before resending email
                 * 
                 * @param array attachments
                 * @since 2.5.9
                 * @version 1.0.0
                 */
                $attachments = apply_filters( 'post_smtp_resend_attachments', array(), $id );

                $success = wp_mail( $to, $log['original_subject'], $log['original_message'], $headers, $attachments );

                // Postman API: retrieve the result of sending this message from Postman
                $result = apply_filters( 'postman_wp_mail_result', null );
                $transcript = $result ['transcript'];
     
                // post-handling
                if ( $success ) {

                    $this->logger->debug( 'Email was successfully re-sent' );
                    // the message was sent successfully, generate an appropriate message for the user
                    $statusMessage = sprintf( __( 'Your message was delivered (%d ms) to the SMTP server! Congratulations :)', 'post-smtp' ), $result ['time'] );

                    // compose the JSON response for the caller
                    $response = array(
                        'success'       => true,
                        'message'       => $statusMessage,
                        'transcript'    => $transcript,
                    );
                    $this->logger->trace( 'AJAX response' );
                    $this->logger->trace( $response );

                }
                else {

                    $this->logger->error( 'Email was not successfully re-sent - ' . $result ['exception']->getCode() );
                    // the message was NOT sent successfully, generate an appropriate message for the user
                    $statusMessage = $result ['exception']->getMessage();
    
                    // compose the JSON response for the caller
                    $response = array(
                            'message' => $statusMessage,
                            'transcript' => $transcript,
                    );
                    $this->logger->trace( 'AJAX response' );
                    $this->logger->trace( $response );

                }

            }
            else {

                $response = array(
                    'success' => false,
                    'message' => __( 'Error Resending Email', 'post-smtp' )
                );

            }

            wp_send_json( $response );

        }

    }


    /**
     * Drop Tables
     * 
     * @since 2.5.2
     * @version 1.0.0
     */
    public function uninstall_tables() {

        $sql = "DROP TABLE IF EXISTS `{$this->db->prefix}{$this->db_name}`;";
        $response = $this->db->query( $sql );

        if( !$this->db->last_error ) {

            $sql = "DROP TABLE IF EXISTS `{$this->db->prefix}{$this->meta_table}`;";
            $response = $this->db->query( $sql );

        }

        if( !$this->db->last_error ) {

            delete_option( 'postman_db_version' );

            return true;

        }

        return false;

    }

}