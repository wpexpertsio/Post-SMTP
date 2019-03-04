<?php
define( 'MAILSTER_POSTMAN_REQUIRED_VERSION', '2.0' );
define( 'MAILSTER_POSTMAN_ID', 'postman' );

/**
 * Enables MyMail to deliver via Postman
 *
 * @author jasonhendriks
 */
if ( ! class_exists( 'PostmanMyMailConnector' ) ) {
	class PostmanMyMailConnector {

		// PostmanLogger
		private $logger;

		/**
		 * No-argument constructor
		 */
		public function __construct( $file ) {
			register_activation_hook( $file, array(
					$this,
					'activate',
			) );
			register_deactivation_hook( $file, array(
					$this,
					'deactivate',
			) );

			add_action( 'init', array(
					$this,
					'init',
			), 1 );
		}

		/**
		 * Initialize the Connector
		 */
		public function init() {
			if ( ! defined( 'MAILSTER_VERSION' ) || version_compare( MAILSTER_POSTMAN_REQUIRED_VERSION, MAILSTER_VERSION, '>' ) ) {
				// no-op
			} else {
				// create an instance of the Logger
				$this->logger = new PostmanLogger( get_class( $this ) );
				$this->logger->debug( 'Starting' );

				add_filter( 'mailster_delivery_methods', array(
						&$this,
						'delivery_method',
				) );
				add_action( 'mailster_deliverymethod_tab_postman', array(
						&$this,
						'deliverytab',
				) );

				if ( mailster_option( 'deliverymethod' ) == MAILSTER_POSTMAN_ID ) {
					add_action( 'mailster_initsend', array(
							&$this,
							'initsend',
					) );
					add_action( 'mailster_presend', array(
							&$this,
							'presend',
					) );
					add_action( 'mailster_dosend', array(
							&$this,
							'dosend',
					) );
					add_action( 'MAILSTER_POSTMAN_cron', array(
							&$this,
							'reset',
					) );
				}
			}
		}

		/**
		 * initsend function.
		 *
		 * uses mailster_initsend hook to set initial settings
		 *
		 * @access public
		 * @param mixed $mailobject
		 * @return void
		 */
		public function initsend( $mailobject ) {
			$this->logger->trace( 'initsend' );
			// disable dkim
			$mailobject->dkim = false;
		}

		/**
		 * presend function.
		 *
		 * uses the mailster_presend hook to apply setttings before each mail
		 *
		 * @access public
		 * @param mixed $mailobject
		 * @return void
		 */
		public function presend( $mailobject ) {

			// embedding images doesn't work
			$mailobject->embed_images = false;

			// use pre_send from the main class
			// need the raw email body to send so we use the same option
			$mailobject->pre_send();
		}

		/**
		 * dosend function.
		 *
		 * uses the mailster_dosend hook and triggers the send
		 *
		 * @access public
		 * @param mixed $mailobject
		 * @return void
		 */
		public function dosend( $mailobject ) {
			$this->logger->trace( 'dosend' );
			$this->logger->trace( $mailobject->mailer );

			// create a PostmanWpMail instance
			$postmanWpMail = new PostmanWpMail();
			$postmanWpMail->init();

			// create a PostmanMessage instance
			$message = $postmanWpMail->createNewMessage();
			$message->addHeaders( $mailobject->headers );
			$message->setBodyTextPart( $mailobject->mailer->AltBody );
			$message->setBodyHtmlPart( $mailobject->mailer->Body );
			$message->setBody( $mailobject->mailer->AltBody . $mailobject->mailer->Body );
			$message->setSubject( $mailobject->subject );
			$message->addTo( $mailobject->to );
			$message->setReplyTo( $mailobject->reply_to );
			$message->setAttachments( $mailobject->attachments );

			// create a PostmanEmailLog instance
			$log = new PostmanEmailLog();

			// send the message and store the result
			$mailobject->sent = $postmanWpMail->sendMessage( $message, $log );

			// give error message back to MyMail
			$result = apply_filters( 'postman_wp_mail_result', null );
			if ( ! $mailobject->sent ) {
				$mailobject->set_error( $result ['exception']->getMessage() );
			}
		}

		/**
		 * reset function.
		 *
		 * resets the current time
		 *
		 * @access public
		 * @param mixed $message
		 * @return array
		 */
		public function reset() {
			update_option( '_transient__mailster_send_period_timeout', false );
			update_option( '_transient__mailster_send_period', 0 );
		}

		/**
		 * delivery_method function.
		 *
		 * add the delivery method to the options
		 *
		 * @access public
		 * @param mixed $delivery_methods
		 * @return void
		 */
		public function delivery_method( $delivery_methods ) {
			$delivery_methods [ MAILSTER_POSTMAN_ID ] = __( 'Postman SMTP', Postman::TEXT_DOMAIN );
			return $delivery_methods;
		}

		/**
		 * deliverytab function.
		 *
		 * the content of the tab for the options
		 *
		 * @access public
		 * @return void
		 */
		public function deliverytab() {
			apply_filters( 'print_postman_status', null );
		}

		/**
		 * activate function.
		 *
		 * @access public
		 * @return void
		 */
		public function activate() {
			if ( defined( 'MAILSTER_VERSION' ) && version_compare( MAILSTER_POSTMAN_REQUIRED_VERSION, MAILSTER_VERSION, '<=' ) ) {
				mailster_notice( sprintf( __( 'MyMail: Change the delivery method in the %s!', Postman::TEXT_DOMAIN ), sprintf( '<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=mailster_delivery_method#delivery">%s</a>', __( 'Settings', 'postman-smtp' ) ) ), '', false, 'delivery_method' );
				$this->reset();
			}
		}

		/**
		 * deactivate function.
		 *
		 * @access public
		 * @return void
		 */
		public function deactivate() {
			if ( defined( 'MAILSTER_VERSION' ) && function_exists( 'mailster_option' ) && version_compare( MAILSTER_POSTMAN_REQUIRED_VERSION, MAILSTER_VERSION, '<=' ) ) {
				if ( mailster_option( 'deliverymethod' ) == MAILSTER_POSTMAN_ID ) {
					mailster_update_option( 'deliverymethod', 'simple' );
					/* Translators where %s is the name of the page */
					mailster_notice( sprintf( __( 'MyMail: Change the delivery method in the %s!', Postman::TEXT_DOMAIN ), sprintf( '<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=mailster_delivery_method#delivery">%s</a>', __( 'Settings', 'postman-smtp' ) ) ), '', false, 'delivery_method' );
				}
			}
		}
	}
}
