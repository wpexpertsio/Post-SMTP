<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once dirname(__DIR__) . '/PostmanLogFields.php';
require_once 'PostmanEmailLogService.php';
require_once 'PostmanEmailLogView.php';
require_once 'PostmanEmailLogMigration.php';

/**
 *
 * @author jasonhendriks
 */
class PostmanEmailLogController {
	const RESEND_MAIL_AJAX_SLUG = 'postman_resend_mail';
	private $rootPluginFilenameAndPath;
	private $logger;

	/**
	 */
	function __construct( $rootPluginFilenameAndPath ) {
		$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
		$this->logger = new PostmanLogger( get_class( $this ) );
		if ( PostmanOptions::getInstance()->isMailLoggingEnabled() ) {
			add_action( 'admin_menu', array(
					$this,
					'postmanAddMenuItem',
			),20 );
		} else {
			$this->logger->trace( 'not creating PostmanEmailLog admin menu item' );
		}
		if ( PostmanUtils::isCurrentPagePostmanAdmin( 'postman_email_log' ) ) {
			$this->logger->trace( 'on postman email log page' );

			add_action( 'admin_post_delete', array(
					$this,
					'delete_log_item',
			) );
			add_action( 'admin_post_view', array(
					$this,
					'view_log_item',
			) );
			add_action( 'admin_post_transcript', array(
					$this,
					'view_transcript_log_item',
			) );
			add_action( 'admin_init', array(
					$this,
					'on_admin_init',
			) );
		}
		
		$email_logs = new PostmanEmailLogs;

        add_action( 'wp_ajax_post_smtp_log_trash_all', array( $this, 'post_smtp_log_trash_all' ) );

		add_action( 'wp_ajax_ps-get-email-logs', array( $email_logs, 'get_logs_ajax' ) );

		add_action( 'wp_ajax_ps-delete-email-logs', array( $email_logs, 'delete_logs_ajax' ) );

		add_action( 'wp_ajax_ps-export-email-logs', array( $email_logs, 'export_log_ajax' ) );
		
		add_action( 'wp_ajax_ps-view-log', array( $email_logs, 'view_log_ajax' ) );

		add_action( 'wp_ajax_ps-resend-email', array( $email_logs, 'resend_email' ) );

		if ( is_admin() ) {
			$actionName = self::RESEND_MAIL_AJAX_SLUG;
			$fullname = 'wp_ajax_' . $actionName;

			add_action( $fullname, array(
					$this,
					'resendMail',
			) );
		}
	}

	function post_smtp_log_trash_all() {
	    check_admin_referer('post-smtp', 'security' );

	    if ( ! current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_LOGS ) ) {
	        wp_send_json_error( 'No permissions to manage Post SMTP logs.');
        }

	    $purger = new PostmanEmailLogPurger();
	    $purger->removeAll();
	    wp_send_json_success();
    }

	/**
	 */
	function on_admin_init() {
		$this->handleBulkAction();
		// register the stylesheet and javascript external resources
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		
		wp_register_script( 'postman-datatable', plugins_url( 'assets/js/dataTable.min.js', $this->rootPluginFilenameAndPath ), array(), $pluginData ['version'] );
		
		wp_register_style( 'postman-datatable', plugins_url( 'assets/css/dataTable.min.css', $this->rootPluginFilenameAndPath ), array(), $pluginData ['version'] );

		wp_register_script( 'postman-email-logs-script', plugins_url( 'script/postman-email-logs.js', $this->rootPluginFilenameAndPath ), array(
				PostmanViewController::JQUERY_SCRIPT,
				PostmanViewController::POSTMAN_SCRIPT,
				'postman-datatable'
		), $pluginData ['version'] );

		$localize = array(
			'DTCols'	=>	array(
				array( 'data'	=>	'id' ),
				array( 'data'	=>	'original_subject' ),
				array( 'data'	=>	'to_header' ),
				array( 'data'	=>	'time' ),
				array( 'data'	=>	'success' ),
				array( 'data'	=>	'actions' )
			)
		);

		/**
		 * Filters JS localize
		 * 
		 * @param array $localize
		 * @since 2.5.0
		 * @version 1.0.0 
		 */
		$localize = apply_filters( 'post_smtp_email_logs_localize', $localize );

		wp_localize_script( 
			'postman-email-logs-script', 
			'PSEmailLogs', 
			$localize
		);

		$this->handleCsvExport();
	}

	/**
	* Handles CSV Export
	*
	* @since 2.1.1 used implode, to prevent email logs from being broken
	* @version 1.0.1
	*/
	function handleCsvExport() {
	    if ( ! isset( $_GET['postman_export_csv'] ) ) {
	        return;
        }

        if ( ! isset( $_REQUEST['post-smtp-log-nonce'] ) || ! wp_verify_nonce( $_REQUEST['post-smtp-log-nonce'], 'post-smtp' ) ) {
            wp_die( 'Security check' );
        }

        if (  current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_LOGS ) ) {
            $args = array(
                'post_type' => PostmanEmailLogPostType::POSTMAN_CUSTOM_POST_TYPE_SLUG,
                'post_status' => PostmanEmailLogService::POSTMAN_CUSTOM_POST_STATUS_PRIVATE,
                'posts_per_page' => -1,
            );
            $logs = new WP_Query($args);

            if ( empty( $logs->posts ) ) {
                return;
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="email-logs.csv"');

            $fp = fopen('php://output', 'wb');

            $headers = array_keys( PostmanLogFields::get_instance()->get_fields() );
            $headers[] = 'delivery_time';

            fputcsv($fp, $headers);

	        $date_format = get_option( 'date_format' );
	        $time_format = get_option( 'time_format' );

            foreach ( $logs->posts as $log ) {
                $meta = PostmanLogFields::get_instance()->get($log->ID);
                $data = [];
                foreach ( $meta as $header => $line ) {
                    $data[] = is_array( $line[0] ) ? implode( PostmanMessage::EOL, $line[0] ) : $line[0];
                }
                $data[] = date( "$date_format $time_format", strtotime( $log->post_date ) );
                fputcsv($fp, $data);
            }

            fclose($fp);
            die();

        }
    }

	/**
	 */
	public function resendMail() {
        check_admin_referer( 'resend', 'security' );

		// get the email address of the recipient from the HTTP Request
		$postid = $this->getRequestParameter( 'email' );
		if ( ! empty( $postid ) ) {
			$meta_values = PostmanLogFields::get_instance()->get( $postid );

			if ( isset( $_POST['mail_to'] ) && ! empty( $_POST['mail_to'] ) ) {
				$emails = explode( ',', $_POST['mail_to'] );
				$to = array_map( 'sanitize_email', $emails );
			} else {
				$to = $meta_values ['original_to'] [0];
			}

			$success = wp_mail( $to, $meta_values ['original_subject'] [0], $meta_values ['original_message'] [0], $meta_values ['original_headers'] [0] );

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
						'message' => $statusMessage,
						'transcript' => $transcript,
				);
				$this->logger->trace( 'AJAX response' );
				$this->logger->trace( $response );
				// send the JSON response
				wp_send_json_success( $response );
			} else {
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
				// send the JSON response
				wp_send_json_error( $response );
			}
		} else {
			// compose the JSON response for the caller
			$response = array();
			// send the JSON response
			wp_send_json_error( $response );
		}
	}

	/**
	 * TODO move this somewhere reusable
	 *
	 * @param mixed $parameterName
	 * @return mixed
	 */
	private function getRequestParameter( $parameterName ) {
		if ( isset( $_POST [ $parameterName ] ) ) {
			$value = filter_var( $_POST [ $parameterName ], FILTER_SANITIZE_STRING );
			$this->logger->trace( sprintf( 'Found parameter "%s"', $parameterName ) );
			$this->logger->trace( $value );
			return $value;
		}
	}

	/**
	 * From https://www.skyverge.com/blog/add-custom-bulk-action/
	 */
	function handleBulkAction() {
		// only do this for administrators
		if ( PostmanUtils::isAdmin() && isset( $_REQUEST ['email_log_entry'] ) ) {
			$this->logger->trace( 'handling bulk action' );
			if ( wp_verify_nonce( $_REQUEST ['_wpnonce'], 'bulk-email_log_entries' ) ) {
				$this->logger->trace( sprintf( 'nonce "%s" passed validation', sanitize_text_field($_REQUEST ['_wpnonce']) ) );
				if ( isset( $_REQUEST ['action'] ) && ($_REQUEST ['action'] == 'bulk_delete' || $_REQUEST ['action2'] == 'bulk_delete') ) {
					$this->logger->trace( sprintf( 'handling bulk delete' ) );
					$purger = new PostmanEmailLogPurger();
					$postids = array_map( 'absint', $_REQUEST ['email_log_entry'] );
					foreach ( $postids as $postid ) {
						$purger->verifyLogItemExistsAndRemove( $postid );
					}
					$mh = new PostmanMessageHandler();
					$mh->addMessage( __( 'Mail Log Entries were deleted.', 'post-smtp' ) );
				} else {
					$this->logger->warn( sprintf( 'action "%s" not recognized', sanitize_text_field($_REQUEST ['action']) ) );
				}
			} else {
				$this->logger->warn( sprintf( 'nonce "%s" failed validation', sanitize_text_field($_REQUEST ['_wpnonce']) ) );
			}
			$this->redirectToLogPage();
		}
	}

	/**
	 */
	function delete_log_item() {
		// only do this for administrators
		if ( PostmanUtils::isAdmin() ) {
			$this->logger->trace( 'handling delete item' );
			$postid = absint($_REQUEST ['email']);
			if ( wp_verify_nonce( $_REQUEST ['_wpnonce'], 'delete_email_log_item_' . $postid ) ) {
				$this->logger->trace( sprintf( 'nonce "%s" passed validation', sanitize_text_field($_REQUEST ['_wpnonce']) ) );
				$purger = new PostmanEmailLogPurger();
				$purger->verifyLogItemExistsAndRemove( $postid );
				$mh = new PostmanMessageHandler();
				$mh->addMessage( __( 'Mail Log Entry was deleted.', 'post-smtp' ) );
			} else {
				$this->logger->warn( sprintf( 'nonce "%s" failed validation', sanitize_text_field($_REQUEST ['_wpnonce']) ) );
			}
			$this->redirectToLogPage();
		}
	}

	/**
	 */
	function view_log_item() {
		// only do this for administrators
		if ( PostmanUtils::isAdmin() ) {
			$this->logger->trace( 'handling view item' );
			$postid = absint( $_REQUEST ['email'] );
			$post = get_post( $postid );

			if ( $post->post_type !== 'postman_sent_mail' ) {
			    return;
            }

			$meta_values = PostmanLogFields::get_instance()->get( $postid );
			// https://css-tricks.com/examples/hrs/
			print '<html><head><style>body {font-family: monospace;} hr {
    border: 0;
    border-bottom: 1px dashed #ccc;
    background: #bbb;
}</style></head><body>';
			print '<table>';
			if ( ! empty( $meta_values ['from_header'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'From', 'Who is this message From?', 'post-smtp' ), esc_html( $meta_values ['from_header'] [0] ) );
			}
			// show the To header (it's optional)
			if ( ! empty( $meta_values ['to_header'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'To', 'Who is this message To?', 'post-smtp' ), esc_html( $meta_values ['to_header'] [0] ) );
			}
			// show the Cc header (it's optional)
			if ( ! empty( $meta_values ['cc_header'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'Cc', 'Who is this message Cc\'d to?', 'post-smtp' ), esc_html( $meta_values ['cc_header'] [0] ) );
			}
			// show the Bcc header (it's optional)
			if ( ! empty( $meta_values ['bcc_header'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'Bcc', 'Who is this message Bcc\'d to?', 'post-smtp' ), esc_html( $meta_values ['bcc_header'] [0] ) );
			}
			// show the Reply-To header (it's optional)
			if ( ! empty( $meta_values ['reply_to_header'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', __( 'Reply-To', 'post-smtp' ), esc_html( $meta_values ['reply_to_header'] [0] ) );
			}
			printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'Date', 'What is the date today?', 'post-smtp' ), $post->post_date );
			printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'Subject', 'What is the subject of this message?', 'post-smtp' ), esc_html( $post->post_title ) );
			// The Transport UI is always there, in more recent versions that is
			if ( ! empty( $meta_values ['transport_uri'] [0] ) ) {
				printf( '<tr><th style="text-align:right">%s:</th><td>%s</td></tr>', _x( 'Delivery-URI', 'What is the unique URI of the configuration?', 'post-smtp' ), esc_html( $meta_values ['transport_uri'] [0] ) );
			}
			print '</table>';
			print '<hr/>';
			print '<pre>';
			print $this->sanitize_message( $post->post_content );
			print '</pre>';
			print '</body></html>';
			die();
		}
	}

	function sanitize_message( $message ) {
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags['style'] = array();

		return wp_kses( $message, $allowed_tags );
	}

	/**
	 */
	function view_transcript_log_item() {
		// only do this for administrators
		if ( PostmanUtils::isAdmin() ) {
			$this->logger->trace( 'handling view transcript item' );
			$postid = absint($_REQUEST ['email']);
			$post = get_post( $postid );
			$meta_values = PostmanLogFields::get_instance()->get( $postid );
			// https://css-tricks.com/examples/hrs/
			print '<html><head><style>body {font-family: monospace;} hr {
    border: 0;
    border-bottom: 1px dashed #ccc;
    background: #bbb;
}</style></head><body>';
			printf( '<p>%s</p>', __( 'This is the conversation between Postman and the mail server. It can be useful for diagnosing problems. <b>DO NOT</b> post it on-line, it may contain your account password.', 'post-smtp' ) );
			print '<hr/>';
			print '<pre>';
			if ( ! empty( $meta_values ['session_transcript'] [0] ) ) {
				print esc_html( $meta_values ['session_transcript'] [0] );
			} else {
				/* Translators: Meaning "Not Applicable" */
				print __( 'n/a', 'post-smtp' );
			}
			print '</pre>';
			print '</body></html>';
			die();
		}
	}

	/**
	 * For whatever reason, PostmanUtils::get..url doesn't work here? :(
	 */
	function redirectToLogPage() {
		PostmanUtils::redirect( PostmanUtils::POSTMAN_EMAIL_LOG_PAGE_RELATIVE_URL );
		die();
	}

	/**
	 * Register the page
	 */
	function postmanAddMenuItem() {
		// only do this for administrators
		if ( PostmanUtils::isAdmin() ) {
			$this->logger->trace( 'created PostmanEmailLog admin menu item' );
			/*
			Translators where (%s) is the name of the plugin */
			$pageTitle = sprintf( __( '%s Email Log', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) );
			$pluginName = _x( 'Email Log', 'The log of Emails that have been delivered', 'post-smtp' );

			$page = add_submenu_page( PostmanViewController::POSTMAN_MENU_SLUG, $pageTitle, $pluginName, Postman::MANAGE_POSTMAN_CAPABILITY_LOGS, 'postman_email_log', array( $this, 'postman_render_email_page' ) );

			// When the plugin options page is loaded, also load the stylesheet
			add_action( 'admin_print_styles-' . $page, array(
					$this,
					'postman_email_log_enqueue_resources',
			) );
		}
	}

	/**
	 * Enqueus Styles/ Scripts
	 * 
	 * @since 2.1 Changed stylesheet
	 * @version 1.0
	 */
	function postman_email_log_enqueue_resources() {

		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman-datatable' );
		wp_enqueue_style( 'postman-datatable' );
		wp_enqueue_script( 'postman-email-logs-script' );
		wp_enqueue_script( 'sprintf' );

	}

	/**
	 * *************************** RENDER TEST PAGE ********************************
	 * ******************************************************************************
	 * This function renders the admin page and the example list table.
	 * Although it's
	 * possible to call prepare_items() and display() from the constructor, there
	 * are often times where you may need to include logic here between those steps,
	 * so we've instead called those methods explicitly. It keeps things flexible, and
	 * it's the way the list tables are used in the WordPress core.
	 */
	function postman_render_email_page() {

		$new_logging = get_option( 'postman_db_version' );

		//Logging with new system
		if( $new_logging ) {

			require 'PostmanEmailLogTable.php';

		}
		//Still logging with old system
		else {

			require 'PostmanEmailLogLegacy.php';

		}

	}

}
