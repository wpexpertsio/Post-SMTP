<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanSendGridMailEngine' ) ) {

	require_once 'sendgrid/sendgrid-php.php';

	/**
	 * Sends mail with the SendGrid API
	 * https://sendgrid.com/docs/API_Reference/Web_API/mail.html
	 *
	 * @author jasonhendriks
	 */
	class PostmanSendGridMailEngine implements PostmanMailEngine {

		// logger for all concrete classes - populate with setLogger($logger)
		protected $logger;

		// the result
		private $transcript;

		private $apiKey;

		/**
		 *
		 * @param mixed $senderEmail
		 * @param mixed $accessToken
		 */
		function __construct( $apiKey ) {
			assert( ! empty( $apiKey ) );
			$this->apiKey = $apiKey;

			// create the logger
			$this->logger = new PostmanLogger( get_class( $this ) );
		}

		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanSmtpEngine::send()
		 */
		public function send( PostmanMessage $message ) {
			$options = PostmanOptions::getInstance();

            $email = new \SendGrid\Mail\Mail();

            // add the From Header
			$sender = $message->getFromAddress();

			$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
			$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

            $email->setFrom($senderEmail, $senderName);

            // now log it
			$sender->log( $this->logger, 'From' );

            // add the to recipients
			foreach ( ( array ) $message->getToRecipients() as $recipient ) {
                $emails[] = new \SendGrid\Mail\To($recipient->getEmail(), $recipient->getName() );
			}

			$email->addTos( $emails );

			// add the subject
			if ( null !== $message->getSubject() ) {
                $email->setSubject($message->getSubject());
            }

			// add the message content

			$textPart = $message->getBodyTextPart();
			if ( ! empty( $textPart ) ) {
				$this->logger->debug( 'Adding body as text' );
                $email->addContent("text/plain", $textPart);
            }

			$htmlPart = $message->getBodyHtmlPart();
			if ( ! empty( $htmlPart ) ) {
				$this->logger->debug( 'Adding body as html' );
                $email->addContent("text/html", $htmlPart);
			}

			// add the reply-to
			$replyTo = $message->getReplyTo();
			// $replyTo is null or a PostmanEmailAddress object
			if ( isset( $replyTo ) ) {
				$email->setReplyTo( $replyTo->getEmail(), $replyTo->getName() );
			}

			// add the Postman signature - append it to whatever the user may have set
			if ( ! $options->isStealthModeEnabled() ) {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
                $email->addHeader( 'X-Mailer', sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' ) );
			}

			// add the headers - see http://framework.zend.com/manual/1.12/en/zend.mail.additional-headers.html
			foreach ( ( array ) $message->getHeaders() as $header ) {
				$this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
                $email->addHeader( $header ['name'], $header ['content'] );
			}

			// if the caller set a Content-Type header, use it
			$contentType = $message->getContentType();
			if ( ! empty( $contentType ) ) {
				$this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
			}

			// add the cc recipients
            $ccEmails = array();
			foreach ( ( array ) $message->getCcRecipients() as $recipient ) {
                $recipient->log( $this->logger, 'Cc' );
                $ccEmails[] = new \SendGrid\Mail\Cc( $recipient->getEmail(), $recipient->getName() );
			}
            $email->addCcs($ccEmails);

            // add the bcc recipients
            $bccEmails = array();
			foreach ( ( array ) $message->getBccRecipients() as $recipient ) {
                $recipient->log($this->logger, 'Bcc');
                $bccEmails[] = new \SendGrid\Mail\Bcc( $recipient->getEmail(), $recipient->getName() );
			}
            $email->addBccs($bccEmails);

            // add the messageId
			$messageId = $message->getMessageId();
			if ( ! empty( $messageId ) ) {
				$email->addHeader( 'message-id', $messageId );
			}

			// add attachments
			$this->logger->debug( 'Adding attachments' );

			$attachments = $this->addAttachmentsToMail( $message );

			foreach ( $attachments as $index => $attachment ) {

                $email->addAttachment(
                    $attachment['content'],
                    $attachment['type'],
                    $attachment['file_name'],
                    "attachment"
                );
			}

			try {

				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Creating SendGrid service with apiKey=' . $this->apiKey );
				}
				$sendgrid = new \SendGrid( $this->apiKey );

				// send the message
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}

				$response = $sendgrid->send($email);
				if ( $this->logger->isInfo() ) {
					$this->logger->info( sprintf( 'Message %d accepted for delivery', PostmanState::getInstance()->getSuccessfulDeliveries() + 1 ) );
				}

				$response_body = json_decode( $response->body() );

                $response_code = $response->statusCode();
				$email_sent = ( $response_code >= 200 and $response_code < 300 );

				if ( isset( $response_body->errors[0]->message ) || ! $email_sent ) {

					$e = ! isset( $response_body->errors[0]->message ) ? $this->errorCodesMap($response_code) : $response_body->errors[0]->message;
					$this->transcript = $e;
					$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
					$this->transcript .= print_r( $email, true );

					$this->logger->debug( 'Transcript=' . $this->transcript );

					throw new Exception( $e );
				}
				$this->transcript = print_r( $response->body(), true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $email, true );
			} catch ( SendGrid\Exception $e ) {
				$this->transcript = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $email, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );

				throw $e;
			}
		}
		

		private function errorCodesMap($error_code) {
			switch ($error_code) {
				case 413:
					$message = sprintf( __( 'ERROR: The JSON payload you have included in your request is too large. Status code is %1$s', 'post-smtp' ), $error_code );
					break;
				case 429:
					$message = sprintf( __( 'ERROR: The number of requests you have made exceeds SendGrid rate limitations. Status code is %1$s', 'post-smtp' ), $error_code );
					break;
				case 500:
					$message = sprintf( __( 'ERROR: An error occurred on a SendGrid server. Status code is %1$s', 'post-smtp' ), $error_code );
					break;
				case 513:
					$message = sprintf( __( 'ERROR: The SendGrid v3 Web API is not available. Status code is %1$s', 'post-smtp' ), $error_code );
					break;
				case 502:
					$message =  sprintf( __( 'ERROR: No recipient supplied. Status code is %1$s', 'post-smtp' ), $error_code );
					break;
				default:
					$message = sprintf( __( 'ERROR: Status code is %1$s', 'post-smtp' ), $error_code );
			}

			return $message;
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
				$attArray = explode( PHP_EOL, $attachments );
			} else {
				$attArray = $attachments;
			}
			// otherwise WordPress sends an array
			$attachments = array();
			foreach ( $attArray as $file ) {
				if ( ! empty( $file ) ) {
					$this->logger->debug( 'Adding attachment: ' . $file );

					$file_name = basename( $file );
					$file_parts = explode( '.', $file_name );
					$file_type = wp_check_filetype( $file );
					$attachments[] = array(
						'content' => base64_encode( file_get_contents( $file ) ),
						'type' => $file_type['type'],
						'file_name' => $file_name,
						'disposition' => 'attachment',
						'id' => $file_parts[0],
					);
				}
			}

			return $attachments;
		}

		// return the SMTP session transcript
		public function getTranscript() {
			return $this->transcript;
		}
	}
}
