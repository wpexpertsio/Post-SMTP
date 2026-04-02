<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'Services/MailGun/Handler.php';
require_once plugin_dir_path( __FILE__ ) . 'PostMailConnections.php';

if ( ! class_exists( 'PostmanMailgunMailEngine' ) ) {

	/**
	 * Sends mail with the SendGrid API
	 * https://sendgrid.com/docs/API_Reference/Web_API/mail.html
	 *
	 * @author jasonhendriks
	 */
	class PostmanMailgunMailEngine implements PostmanMailEngine {

		// logger for all concrete classes - populate with setLogger($logger)
		protected $logger;

		// the result
		private $transcript;

		private $apiKey;
		private $domainName;
		private $mailgunMessage;
		private $existing_db_version = '';
		private $is_fallback;

		/**
		 *
		 * @param mixed $senderEmail
		 * @param mixed $accessToken
		 */
		function __construct( $apiKey, $domainName ) {
			assert( ! empty( $apiKey ) );
			if ( is_array( $apiKey ) ) {
				// When passed as an array with additional data.
				assert( ! empty( $apiKey['api_key'] ) );
				$this->apiKey      = $apiKey['api_key'];
				$this->domainName  = $domainName;
				$this->is_fallback = $apiKey['is_fallback'] ?? null;
			} else {
				// When passed as a string (just the API key).
				assert( ! empty( $apiKey ) );
				$this->apiKey      = $apiKey;
				$this->domainName  = $domainName;
				$this->is_fallback = null;
			}

			// create the logger
			$this->logger         = new PostmanLogger( get_class( $this ) );
			$this->mailgunMessage = array(
				'from'    => '',
				'to'      => '',
				'subject' => '',
			);
		}

		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanSmtpEngine::send()
		 */
		public function send( PostmanMessage $message ) {

			if ( $this->logger->isDebug() ) {
				$this->logger->debug( 'Creating Mailgun service with apiKey=' . $this->apiKey );
			}

			$options = PostmanOptions::getInstance();
			$region  = $options->getMailgunRegion();

			$mailgun = new PostmanMailGun( $this->apiKey, $region, $this->domainName );
			$this->get_email_body( $message );
			$body = $this->mailgunMessage;

			$result = array();
			try {
				$response = $mailgun->send( $body );
				// send the message
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}

				if ( $this->logger->isInfo() ) {
					$this->logger->info( sprintf( 'Message %d accepted for delivery', PostmanState::getInstance()->getSuccessfulDeliveries() + 1 ) );
				}

				$this->transcript  = print_r( $result, true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $this->mailgunMessage, true );
			} catch ( Exception $e ) {
				$this->transcript  = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $this->mailgunMessage, true );
				throw $e;
			}
		}

		private function getRecipientVariables( $emails ) {
			$recipient_variables = array();
			foreach ( $emails as $key => $email ) {
				$recipient_variables[ $email ] = array( 'id' => $key );
			}

			return json_encode( $recipient_variables );
		}

		private function addHeader( $name, $value, $deprecated = '' ) {
			if ( $value && ! empty( $value ) ) {
				$this->mailgunMessage[ 'h:' . $name ] = preg_replace( '/.*:\s?/', '', $value );
			}
		}

		/**
		 * Add attachments to the message
		 *
		 * @param Postman_Zend_Mail $mail
		 */
		private function addAttachmentsToMail( PostmanMessage $message ) {
			$attachments = $message->getAttachments();

			if ( ! is_array( $attachments ) ) {
				// WordPress may a single filename or a newline-delimited string list of multiple filenames
				$attArray[] = explode( PHP_EOL, $attachments );
			} else {
				$attArray = $attachments;
			}

			$attachments = array();
			foreach ( $attArray as $file ) {
				if ( ! empty( $file ) ) {
					$this->logger->debug( 'Adding attachment: ' . $file );
					$attachments[] = array( 'filePath' => $file );
				}
			}

			if ( ! empty( $attachments ) ) {
				if ( $this->logger->isTrace() ) {
					$this->logger->trace( $attachments );
				}
				$this->mailgunMessage['attachment'] = $attachments;
			}
		}

		// return the SMTP session transcript
		public function getTranscript() {
			return $this->transcript;
		}

		private function get_email_body( $message ) {
			$postman_db_version = get_option( 'postman_db_version' );
			if ( is_a( $message, 'PostmanMessage' ) ) {
				$options = PostmanOptions::getInstance();

				// add the From Header
				$sender = $message->getFromAddress();
				{

				if ( $postman_db_version != POST_SMTP_DB_VERSION ) {
					$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
				} else {
					$connection_details = get_option( 'postman_connections' );
					if ( $this->is_fallback == null ) {
						$route_key = null;
						$route_key = get_transient( 'post_smtp_smart_routing_route' );
						if( $route_key != null ){
							// Smart routing is enabled, use the connection associated with the route_key.
							$senderEmail     = $connection_details[ $route_key ]['sender_email'];
						}else{
							$primary     = $options->getSelectedPrimary();
							$senderEmail = $connection_details[ $primary ]['sender_email'];
						}
					} else {
						$fallback    = $options->getSelectedFallback();
						$senderEmail = $connection_details[ $fallback ]['sender_email'];
					}
				}
					$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

					$this->mailgunMessage ['from'] = "{$senderName} <{$senderEmail}>";
					// now log it
					$sender->log( $this->logger, 'From' );
				}

				// add the to recipients
				$recipients = array();
				foreach ( (array) $message->getToRecipients() as $recipient ) {
					$recipient->log( $this->logger, 'To' );
					$recipients[] = $recipient->getEmail();
				}
				$this->mailgunMessage['to'] = $recipients;

				// add the subject
				if ( null !== $message->getSubject() ) {
					$this->mailgunMessage ['subject'] = $message->getSubject();
				}

				{ // add the message content
					$textPart = $message->getBodyTextPart();
				if ( ! empty( $textPart ) ) {
					$this->logger->debug( 'Adding body as text' );
					$this->mailgunMessage ['text'] = $textPart;
				}

					$htmlPart = $message->getBodyHtmlPart();
				if ( ! empty( $htmlPart ) ) {
					$this->logger->debug( 'Adding body as html' );
					$this->mailgunMessage ['html'] = $htmlPart;
				}
				}

				// add the reply-to
				$replyTo = $message->getReplyTo();
				// $replyTo is null or a PostmanEmailAddress object
				if ( isset( $replyTo ) ) {
					$this->addHeader( 'reply-to', $replyTo->format() );
				}

				// add the Postman signature - append it to whatever the user may have set
				if ( ! $options->isStealthModeEnabled() ) {
					$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
					$this->addHeader( 'X-Mailer', sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' ) );
				}

				// add the headers - see http://framework.zend.com/manual/1.12/en/zend.mail.additional-headers.html
				foreach ( (array) $message->getHeaders() as $header ) {
					$this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
					$this->addHeader( $header ['name'], $header ['content'], true );
				}

				// add the messageId
				$messageId = $message->getMessageId();
				if ( ! empty( $messageId ) ) {
					$this->addHeader( 'message-id', $messageId );
				}

				// if the caller set a Content-Type header, use it
				$contentType = $message->getContentType();
				if ( ! empty( $contentType ) ) {
					$this->logger->debug( 'Adding content-type ' . $contentType );
					$this->addHeader( 'Content-Type', $contentType );
				}

				// add the cc recipients
				$recipients = array();
				foreach ( (array) $message->getCcRecipients() as $recipient ) {
					$recipient->log( $this->logger, 'Cc' );
					$recipients[] = $recipient->getEmail();
				}
				$this->mailgunMessage['cc'] = implode( ',', $recipients );

				// add the bcc recipients
				$recipients = array();
				foreach ( (array) $message->getBccRecipients() as $recipient ) {
					$recipient->log( $this->logger, 'Bcc' );
					$recipients[] = $recipient->getEmail();
				}
				$this->mailgunMessage['bcc'] = implode( ',', $recipients );

				// add attachments
				$this->logger->debug( 'Adding attachments' );
				$this->addAttachmentsToMail( $message );

				// add the date
				$date = $message->getDate();
				if ( ! empty( $date ) ) {
					$this->addHeader( 'date', $message->getDate() );
				}

				// add the Sender Header, overriding what the user may have set
				$this->addHeader( 'Sender', $options->getEnvelopeSender() );
			}
		}
	}
}
