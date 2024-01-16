<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once dirname(__DIR__ ) . '/PostmanLogFields.php';
require_once POST_SMTP_PATH . '/Postman/Extensions/Core/Notifications/PostmanNotify.php';
require_once POST_SMTP_PATH . '/Postman/Extensions/Core/StatusSolution.php';
require_once POST_SMTP_PATH . '/Postman/PostmanEmailLogs.php';

if ( ! class_exists( 'PostmanEmailLog' ) ) {
	class PostmanEmailLog {
		public $sender;
		public $toRecipients;
		public $ccRecipients;
		public $bccRecipients;
		public $subject;
		public $body;
		public $success;
		public $statusMessage;
		public $sessionTranscript;
		public $transportUri;
		public $replyTo;
		public $originalTo;
		public $originalSubject;
		public $originalMessage;
		public $originalHeaders;

		public function setStatusMessage( $message ) {
		    $this->statusMessage .= $message;
        }
	}
}

if ( ! class_exists( 'PostmanEmailLogService' ) ) {

	/**
	 * This class creates the Custom Post Type for Email Logs and handles writing these posts.
	 *
	 * @author jasonhendriks
	 */
	class PostmanEmailLogService {

		/*
		 * Private content is published only for your eyes, or the eyes of only those with authorization
		 * permission levels to see private content. Normal users and visitors will not be aware of
		 * private content. It will not appear in the article lists. If a visitor were to guess the URL
		 * for your private post, they would still not be able to see your content. You will only see
		 * the private content when you are logged into your WordPress blog.
		 */
		const POSTMAN_CUSTOM_POST_STATUS_PRIVATE = 'private';

		// member variables
		private $logger;
		private $inst;
		public $new_logging = false;

		/**
		 * Constructor
		 */
		private function __construct() {

			$this->logger = new PostmanLogger( get_class( $this ) );
			$this->new_logging = get_option( 'postman_db_version' );

			add_action('post_smtp_on_success', array( $this, 'write_success_log' ), 10, 4 );
			add_action('post_smtp_on_failed', array( $this, 'write_failed_log' ), 10, 5 );

		}

		/**
		 * singleton instance
		 */
		public static function getInstance() {
			static $inst = null;
			if ( $inst === null ) {
				$inst = new PostmanEmailLogService();
			}
			return $inst;
		}

		public function write_success_log($log, $message, $transcript, $transport) {
		    $options = PostmanOptions::getInstance();
            if ( $options->getRunMode() == PostmanOptions::RUN_MODE_PRODUCTION || $options->getRunMode() == PostmanOptions::RUN_MODE_LOG_ONLY ) {
                $this->writeSuccessLog( $log, $message, $transcript, $transport );
            }
        }

        public function write_failed_log($log, $message, $transcript, $transport, $statusMessage) {
            $options = PostmanOptions::getInstance();
            if ( $options->getRunMode() == PostmanOptions::RUN_MODE_PRODUCTION || $options->getRunMode() == PostmanOptions::RUN_MODE_LOG_ONLY ) {
                $this->writeFailureLog( $log, $message, $transcript, $transport, $statusMessage );
            }
        }

		/**
		 * Logs successful email attempts
		 *
		 * @param PostmanMessage         $message
		 * @param mixed                $transcript
		 * @param PostmanModuleTransport $transport
		 */
		public function writeSuccessLog( PostmanEmailLog $log, PostmanMessage $message, $transcript, PostmanModuleTransport $transport ) {
			if ( PostmanOptions::getInstance()->isMailLoggingEnabled() ) {
				$statusMessage = '';
				$status = true;
				$subject = $message->getSubject();
				if ( empty( $subject ) ) {
					$statusMessage = sprintf( '%s: %s', __( 'Warning', 'post-smtp' ), __( 'An empty subject line can result in delivery failure.', 'post-smtp' ) );
					$status = 'WARN';
				}
				$this->createLog( $log, $message, $transcript, $statusMessage, $status, $transport );
				$this->writeToEmailLog( $log );
			}
		}

		/**
		 * Logs failed email attempts, requires more metadata so the email can be resent in the future
		 *
		 * @param PostmanMessage         $message
		 * @param mixed                $transcript
		 * @param PostmanModuleTransport $transport
		 * @param mixed                $statusMessage
		 * @param mixed                $originalTo
		 * @param mixed                $originalSubject
		 * @param mixed                $originalMessage
		 * @param mixed                $originalHeaders
		 */
		public function writeFailureLog( PostmanEmailLog $log, PostmanMessage $message = null, $transcript, PostmanModuleTransport $transport, $statusMessage ) {
			if ( PostmanOptions::getInstance()->isMailLoggingEnabled() ) {
				$this->createLog( $log, $message, $transcript, $statusMessage, false, $transport );
				$this->writeToEmailLog( $log,$message );
			}
		}

		/**
		 * Writes an email sending attempt to the Email Log
		 *
		 * From http://wordpress.stackexchange.com/questions/8569/wp-insert-post-php-function-and-custom-fields
		 */
		private function writeToEmailLog( PostmanEmailLog $log, PostmanMessage $message = null ) {

		    $options = PostmanOptions::getInstance();

            $new_status = $log->statusMessage;

			if ( $options->is_fallback && empty( $log->statusMessage ) ) {
                $new_status = 'Sent ( ** Fallback ** )';
            }

            if ( $options->is_fallback &&  ! empty( $log->statusMessage ) ) {
                $new_status = '( ** Fallback ** ) ' . $log->statusMessage;
            }

            $new_status = apply_filters( 'post_smtp_log_status', $new_status, $log, $message );
			
			//If Table exists, Insert Log into Table
			if( $this->new_logging ) {

				$data = array();
				$data['solution'] = apply_filters( 'post_smtp_log_solution', null, $new_status, $log, $message );
				$data['success'] = empty( $new_status ) ? 1 : $new_status;
				$data['from_header'] = $log->sender;
				$data['to_header'] = !empty( $log->toRecipients ) ? $log->toRecipients : '';
				$data['cc_header'] = !empty( $log->ccRecipients ) ? $log->ccRecipients : '';
				$data['bcc_header'] = !empty( $log->bccRecipients ) ? $log->bccRecipients : '';
				$data['reply_to_header'] = !empty( $log->replyTo ) ? $log->replyTo : '';
				$data['transport_uri'] = !empty( $log->transportUri ) ? $log->transportUri : '';
				$data['original_to'] = is_array( $log->originalTo ) ? implode( ',', $log->originalTo ) : $log->originalTo;
				$data['original_subject'] = !empty( $log->originalSubject ) ? $log->originalSubject : '';
				$data['original_message'] = $log->originalMessage;
				$data['original_headers'] = is_array($log->originalHeaders) ? serialize($log->originalHeaders) : $log->originalHeaders;
				$data['session_transcript'] = $log->sessionTranscript;

				$email_logs = new PostmanEmailLogs();

				/**
				 * Filter the email log id
				 * 
				 * @param string $log_id
				 * @since 2.5.0
				 * @version 1.0.0
				 */
				$log_id = apply_filters( 'post_smtp_update_email_log_id', '' );

				$log_id = $email_logs->save( $data, $log_id );

				/**
				 * Fires after the email log is saved
				 * 
				 * @param string $log_id
				 * @since 2.5.0
				 * @version 1.0.0
				 */

				do_action( 'post_smtp_after_email_log_saved', $log_id );

				$this->logger->debug( sprintf( 'Saved message #%s to the database', $log_id ) );
				$this->logger->trace( $log );

			} 
			//Do as previous
			else {

				// nothing here is sanitized as WordPress should take care of
				// making database writes safe
				$my_post = array(
					'post_type' => PostmanEmailLogPostType::POSTMAN_CUSTOM_POST_TYPE_SLUG,
					'post_title' => $log->subject,
					'post_content' => $log->body,
					'post_excerpt' => $new_status,
					'post_status' => PostmanEmailLogService::POSTMAN_CUSTOM_POST_STATUS_PRIVATE,
				);

				// Insert the post into the database (WordPress gives us the Post ID)
				$post_id = wp_insert_post( $my_post, true );

				if ( is_wp_error( $post_id ) ) {
					add_action( 'admin_notices', function() use( $post_id ) {
						$class = 'notice notice-error';
						$message = $post_id->get_error_message();
	
						printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
					});
	
					return;
				}

				$this->logger->debug( sprintf( 'Saved message #%s to the database', $post_id ) );
				$this->logger->trace( $log );

				$solution = apply_filters( 'post_smtp_log_solution', null, $new_status, $log, $message );

				// Write the meta data related to the email
				PostmanLogFields::get_instance()->update( $post_id, 'solution', $solution );
				PostmanLogFields::get_instance()->update( $post_id, 'success', $log->success );
				PostmanLogFields::get_instance()->update( $post_id, 'from_header', $log->sender );
				if ( ! empty( $log->toRecipients ) ) {
					PostmanLogFields::get_instance()->update( $post_id, 'to_header', $log->toRecipients );
				}
				if ( ! empty( $log->ccRecipients ) ) {
					PostmanLogFields::get_instance()->update( $post_id, 'cc_header', $log->ccRecipients );
				}
				if ( ! empty( $log->bccRecipients ) ) {
					PostmanLogFields::get_instance()->update( $post_id, 'bcc_header', $log->bccRecipients );
				}
				if ( ! empty( $log->replyTo ) ) {
					PostmanLogFields::get_instance()->update( $post_id, 'reply_to_header', $log->replyTo );
				}
				PostmanLogFields::get_instance()->update( $post_id, 'transport_uri', $log->transportUri );

				if ( ! $log->success || true ) {
					// alwas add the meta data so we can re-send it
					PostmanLogFields::get_instance()->update( $post_id, 'original_to', $log->originalTo );
					PostmanLogFields::get_instance()->update( $post_id, 'original_subject', $log->originalSubject );
					PostmanLogFields::get_instance()->update( $post_id, 'original_message', $log->originalMessage );
					PostmanLogFields::get_instance()->update( $post_id, 'original_headers', $log->originalHeaders );
				}

				// we do not sanitize the session transcript - let the reader decide how to handle the data
				PostmanLogFields::get_instance()->update( $post_id, 'session_transcript', $log->sessionTranscript );

			}

			// truncate the log (remove older entries)
			$purger = new PostmanEmailLogPurger();

			/**
			 * Filter whether to truncate the log
			 * 
			 * @param bool $truncate
			 * @since 2.6.1
			 * @version 1.0.0
			 */
			if( apply_filters( 'post_smtp_truncate_the_log', true ) ) {

				$purger->truncateLogItems( PostmanOptions::getInstance()->getMailLoggingMaxEntries() );

			}
			
		}

		/**
		 * Creates a Log object for use by writeToEmailLog()
		 *
		 * @param PostmanMessage         $message
		 * @param mixed                $transcript
		 * @param mixed                $statusMessage
		 * @param mixed                $success
		 * @param PostmanModuleTransport $transport
		 * @return PostmanEmailLog
		 */
		private function createLog( PostmanEmailLog $log, PostmanMessage $message = null, $transcript, $statusMessage, $success, PostmanModuleTransport $transport ) {
			if ( $message ) {
				$log->sender = $message->getFromAddress()->format();
				$log->toRecipients = $this->flattenEmails( $message->getToRecipients() );
				$log->ccRecipients = $this->flattenEmails( $message->getCcRecipients() );
				$log->bccRecipients = $this->flattenEmails( $message->getBccRecipients() );
				$log->subject = $message->getSubject();
				$log->body = $message->getBody();
				if ( null !== $message->getReplyTo() ) {
					$log->replyTo = $message->getReplyTo()->format();
				}
			}
			$log->success = $success;
			$log->statusMessage = $statusMessage;
			$log->transportUri = PostmanTransportRegistry::getInstance()->getPublicTransportUri( $transport );
			$log->sessionTranscript = $log->transportUri . "\n\n" . $transcript;
			return $log;
		}

		/**
		 * Creates a readable "TO" entry based on the recipient header
		 *
		 * @param array $addresses
		 * @return string
		 */
		private static function flattenEmails( array $addresses ) {
			$flat = '';
			$count = 0;
			foreach ( $addresses as $address ) {
				if ( $count >= 3 ) {
					$flat .= sprintf( __( '.. +%d more', 'post-smtp' ), sizeof( $addresses ) - $count );
					break;
				}
				if ( $count > 0 ) {
					$flat .= ', ';
				}
				$flat .= $address->format();
				$count ++;
			}
			return $flat;
		}
	}
}

if ( ! class_exists( 'PostmanEmailLogPurger' ) ) {
	class PostmanEmailLogPurger {

		private $logs;
		private $logger;
		private $new_logging;
		private $email_logs;

		/**
		 *
		 * @return mixed
		 */
		function __construct() {

			$this->new_logging = get_option( 'postman_db_version' );

			$this->get_logs();
			$this->email_logs = new PostmanEmailLogs();
			$this->logger = new PostmanLogger( get_class( $this ) );
			
		}


		/**
		 * Get Logs
		 * 
		 * @since 2.5.0
		 * @version 1.0.0
		 */
		public function get_logs() {

			if( !$this->new_logging ) {

				$this->get_old_logs();

			}

		}


		/**
		 * Gets Logs From _posts table
		 * 
		 * @since 2.5.0
		 * @version 1.0.0
		 */
		public function get_old_logs() {

			$args = array(
					'posts_per_page' => -1,
					'offset' => 0,
					'category' => '',
					'category_name' => '',
					'orderby' => 'date',
					'order' => 'DESC',
					'include' => '',
					'exclude' => '',
					'meta_key' => '',
					'meta_value' => '',
					'post_type' => PostmanEmailLogPostType::POSTMAN_CUSTOM_POST_TYPE_SLUG,
					'post_mime_type' => '',
					'post_parent' => '',
					'post_status' => 'private',
					'suppress_filters' => true,
			);
			
			$query = new WP_Query( $args );
			$this->logs = $query->posts;

		}
		
		
		/**
		 * Get logs from _post_smtp_logs table
		 * 
		 * @since 2.5.0
		 * @version 1.0.0
		 */
		public function get_new_logs() {

			$logs = new PostmanEmailLogs();
			$this->logs = $logs->get_logs();

		}

		/**
		 *
		 * @param array   $posts
		 * @param mixed $postid
		 */
		function verifyLogItemExistsAndRemove( $postid ) {
			$force_delete = true;
			foreach ( $this->logs as $post ) {
				if ( $post->ID == $postid ) {
					$this->logger->debug( 'deleting log item ' . intval( $postid ) );
					wp_delete_post( $postid, $force_delete );
					return;
				}
			}
			$this->logger->warn( 'could not find Postman Log Item #' . $postid );
		}
		function removeAll() {
			
			if( $this->new_logging ) {

				$email_query_log = new PostmanEmailQueryLog();
				$delete = $email_query_log->delete_logs( array( -1 ) );
				$this->logger->debug( sprintf( 'Delete Response: %s', $delete ) );

			}
			else {
				
				$this->logger->debug( sprintf( 'deleting %d log items ', sizeof( $this->logs ) ) );
				$force_delete = true;
				foreach ( $this->logs as $post ) {
					wp_delete_post( $post->ID, $force_delete );
				}

			}

		}

		/**
		 *
		 * @param mixed $size
		 */
		function truncateLogItems( $size ) {

			if( $this->new_logging ) {

				$this->email_logs->truncate_log_items( $size );
			
			}
			else {

				$index = count( $this->logs );
				$force_delete = true;
				while ( $index > $size ) {
					$postid = $this->logs [ -- $index ]->ID;
					$this->logger->debug( 'deleting log item ' . $postid );
					wp_delete_post( $postid, $force_delete );
				}

			}

		}
	}
}
