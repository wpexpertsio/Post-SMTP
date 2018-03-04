<?php

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

		private $personalization;
		private $apiKey;

		/**
		 *
		 * @param unknown $senderEmail
		 * @param unknown $accessToken
		 */
		function __construct( $apiKey ) {
			assert( ! empty( $apiKey ) );
			$this->apiKey = $apiKey;

			// create the logger
			$this->logger = new PostmanLogger( get_class( $this ) );

			// create the Message
			$this->personalization = new SendGrid\Personalization();
		}

		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanSmtpEngine::send()
		 */
		public function send( PostmanMessage $message ) {
			$options = PostmanOptions::getInstance();

			// add the Postman signature - append it to whatever the user may have set
			if ( ! $options->isStealthModeEnabled() ) {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
				$this->personalization->addHeader( 'X-Mailer', sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' ) );
			}

			// add the headers - see http://framework.zend.com/manual/1.12/en/zend.mail.additional-headers.html
			foreach ( ( array ) $message->getHeaders() as $header ) {
				$this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
				$this->personalization->addHeader( $header ['name'], $header ['content'] );
			}

			// if the caller set a Content-Type header, use it
			$contentType = $message->getContentType();
			if ( ! empty( $contentType ) ) {
				$this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
			}

			// add the From Header
			$sender = $message->getFromAddress();

			$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
			$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

			$from = new SendGrid\Email( $senderName, $senderEmail );

			// now log it
			$sender->log( $this->logger, 'From' );

			// add the to recipients
			$counter = 0;
			foreach ( ( array ) $message->getToRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'To' );
				if ( $counter == 0 ) {
					$to = new SendGrid\Email($recipient->getName(), $recipient->getEmail());
					$this->personalization->addTo( $to );
				} else {
					$email = new SendGrid\Email($recipient->getName(), $recipient->getEmail());
					$this->personalization->addTo( $email );
				}

				$counter++;
			}

			// add the cc recipients
			foreach ( ( array ) $message->getCcRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'Cc' );
				$this->personalization->addCc( $recipient->getEmail(), $recipient->getName() );
			}

			// add the bcc recipients
			foreach ( ( array ) $message->getBccRecipients() as $recipient ) {
				$recipient->log( $this->logger, 'Bcc' );
				$this->personalization->addBcc( $recipient->getEmail(), $recipient->getName() );
			}

			// add the messageId
			$messageId = $message->getMessageId();
			if ( ! empty( $messageId ) ) {
				$this->personalization->addHeader( 'message-id', $messageId );
			}

			// add the subject
			if ( null !== $message->getSubject() ) {
				$subject = $message->getSubject();
			}

			// add the message content

			$textPart = $message->getBodyTextPart();
			if ( ! empty( $textPart ) ) {
				$this->logger->debug( 'Adding body as text' );
				$content = new SendGrid\Content("text/plain", $textPart);
			}

			$htmlPart = $message->getBodyHtmlPart();
			if ( ! empty( $htmlPart ) ) {
				$this->logger->debug( 'Adding body as html' );
				$content = new SendGrid\Content("text/html", $htmlPart);
			}

			// add attachments
			$this->logger->debug( 'Adding attachments' );

			$mail = new SendGrid\Mail($from, $subject, $to, $content);
			$mail->addPersonalization($this->personalization);


			// add the reply-to
			$replyTo = $message->getReplyTo();
			// $replyTo is null or a PostmanEmailAddress object
			if ( isset( $replyTo ) ) {
				$reply_to = new SendGrid\ReplyTo( $replyTo->getEmail(), $replyTo->getName() );
				$mail->setReplyTo($reply_to);
			}

			$attachments = $this->addAttachmentsToMail( $message );

			foreach ( $attachments as $index => $attachment ) {
				$attach = new SendGrid\Attachment();
				$attach->setContent($attachment['content']);
				$attach->setType($attachment['type']);
				$attach->setFilename($attachment['file_name']);
				$attach->setDisposition("attachment");
				$attach->setContentId($attachment['id']);
				$mail->addAttachment($attach);
			}

			try {

				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Creating SendGrid service with apiKey=' . $this->apiKey );
				}
				$sendgrid = new SendGrid( $this->apiKey );

				// send the message
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}

				$response = $sendgrid->client->mail()->send()->post($mail);
				if ( $this->logger->isInfo() ) {
					$this->logger->info( );
				}

				$response_body = json_decode( $response->body() );

				if ( isset( $response_body->errors[0]->message ) ) {
					$this->transcript = $response_body->errors[0]->message;
					$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
					$this->transcript .= print_r( $mail, true );
					throw new Exception( $response_body->errors[0]->message );
				}
				$this->transcript = print_r( $response->body(), true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $mail, true );
			} catch ( SendGrid\Exception $e ) {
				$this->transcript = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $mail, true );
				throw $e;
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
					$attachments[] = array(
						'content' => base64_encode( file_get_contents( $file ) ),
						'type' => mime_content_type( $file ),
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

