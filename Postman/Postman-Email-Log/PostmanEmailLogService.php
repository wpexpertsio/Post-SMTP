<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once dirname(__DIR__ ) . '/PostmanLogFields.php';
require_once POST_SMTP_PATH . '/Postman/Extensions/Core/Notifications/PostmanNotify.php';
require_once POST_SMTP_PATH . '/Postman/Extensions/Core/StatusSolution.php';

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

		/**
		 * Constructor
		 */
		private function __construct() {
			$this->logger = new PostmanLogger( get_class( $this ) );

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

			// truncate the log (remove older entries)
			$purger = new PostmanEmailLogPurger();
			$purger->truncateLogItems( PostmanOptions::getInstance()->getMailLoggingMaxEntries() );
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
		private $posts;
		private $logger;

		/**
		 *
		 * @return mixed
		 */
		function __construct( $args = array() ) {
			$this->logger = new PostmanLogger( get_class( $this ) );
			$defaults = array(
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
			$args = wp_parse_args( $args, $defaults );
			$query = new WP_Query( $args );
			$this->posts = $query->posts;
		}

		/**
		 *
		 * @param array   $posts
		 * @param mixed $postid
		 */
		function verifyLogItemExistsAndRemove( $postid ) {
			$force_delete = true;
			foreach ( $this->posts as $post ) {
				if ( $post->ID == $postid ) {
					$this->logger->debug( 'deleting log item ' . intval( $postid ) );
					wp_delete_post( $postid, $force_delete );
					return;
				}
			}
			$this->logger->warn( 'could not find Postman Log Item #' . $postid );
		}
		function removeAll() {
			$this->logger->debug( sprintf( 'deleting %d log items ', sizeof( $this->posts ) ) );
			$force_delete = true;
			foreach ( $this->posts as $post ) {
				wp_delete_post( $post->ID, $force_delete );
			}
		}

		/**
		 *
		 * @param mixed $size
		 */
		function truncateLogItems( $size ) {
			$index = count( $this->posts );
			$force_delete = true;
			while ( $index > $size ) {
				$postid = $this->posts [ -- $index ]->ID;
				$this->logger->debug( 'deleting log item ' . $postid );
				wp_delete_post( $postid, $force_delete );
			}
		}
	}
}
