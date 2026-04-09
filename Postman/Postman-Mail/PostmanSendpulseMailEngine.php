<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( "PostmanSendpulseMailEngine" ) ) :

	require 'Services/SendPulse/Handler.php';
	class PostmanSendpulseMailEngine implements PostmanMailEngine {


		protected $logger;

		private $transcript;

		private $api_key;

		private $secret_key;



		/**
		 * Constructor PostmanSendpulseMailEngine
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function __construct( $api_key, $secret_key ) {

			$this->api_key = $api_key;

			$this->secret_key = $secret_key;

			// create the logger.
			$this->logger = new PostmanLogger( get_class( $this ) );
		}

		/**
		 * Get Transcript
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getTranscript()
		{
			return $this->transcript;
		}

		/**
		 * Add attachment to the mail
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */

		private function addAttachmentsToMail( PostmanMessage $message ) {

			$attachments = $message->getAttachments();
			if ( ! is_array( $attachments ) ) {
				// WordPress may a single filename or a newline-delimited string list of multiple filenames.
				$attArray = explode( PHP_EOL, $attachments );
			} else {
				$attArray = $attachments;
			}
			// otherwise WordPress sends an array.
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

		/**
		 * Structure Email to Send
		 *
		 * @param mixed $message class object.
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function send( PostmanMessage $message ) {

			$options = PostmanOptions::getInstance();
			// Sendpulse preparation.
			if ( $this->logger->isDebug() ) {

				$this->logger->debug( 'Creating SendGrid service with apiKey=' . $this->apiKey );
			}

			$sendpulse = new PostmanSendpulse( $this->api_key, $this->secret_key );
			$sender = $message->getFromAddress();
			$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
			$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
			$headers = array();

			$sender->log($this->logger, 'From');

			$sendSmtpEmail['from'] = array(
				'name'  =>  $senderName,
				'email' =>  $senderEmail
			);

			$tos = array();
			$duplicates = array();

			// add the to recipients.
			foreach ( (array) $message->getToRecipients() as $key => $recipient ) {

				if ( ! array_key_exists( $recipient->getEmail(), $duplicates ) ) {

					$tos[] = array(
						'email' =>  $recipient->getEmail()
					);

					if ( ! empty( $recipient->getName() ) ) {

						$tos[$key]['name'] = $recipient->getName();
					}

					$duplicates[] = $recipient->getEmail();
				}
			}
			$sendSmtpEmail['to'] = $tos;

			$sendSmtpEmail['subject'] = $message->getSubject();

			$textPart = $message->getBodyTextPart();
			if ( ! empty( $textPart ) ) {
				$this->logger->debug( 'Adding body as text' );
				$sendSmtpEmail['text'] = $textPart;
			}

			$htmlPart = $message->getBodyHtmlPart();
			if ( ! empty( $htmlPart ) ) {
				$this->logger->debug( 'Adding body as html' );
				$htmlPart = base64_encode( $htmlPart );
				$sendSmtpEmail['html'] = $htmlPart;
			}

			// add the reply-to.
			$replyTo = $message->getReplyTo();
			$replies = array();
			// $replyTo is null or a PostmanEmailAddress object
			if ( isset( $replyTo ) && ! empty( $replyTo->getName() ) ) {
				$replies['to'] = array(
					'email' => $replyTo->getEmail(),
					'name'  => $replyTo->getName(),
				);

				if ( isset( $replyTo ) ) {
					$replies['to'] = array(
						'email' => $replyTo->getEmail(),
					);
				}
				$sendSmtpEmail['to'] = $replies;
			}

			// add the Postman signature - append it to whatever the user may have set.
			if ( ! $options->isStealthModeEnabled() ) {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
				$headers['X-Mailer'] = sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData['version'], 'https://wordpress.org/plugins/post-smtp/' );
			}

			foreach ( (array) $message->getHeaders() as $header ) {
				$this->logger->debug( sprintf( 'Adding user header %s=%s', $header['name'], $header['content'] ) );
				$headers[ $header['name'] ] = $header['content'];
			}

			// add the messageId.
			$messageId = $message->getMessageId();
			if ( ! empty( $messageId ) ) {
				$headers['message-id'] = $messageId;
			}

			$sendSmtpEmail['headers'] = $headers;

			// if the caller set a Content-Type header, use it.
			$contentType = $message->getContentType();
			if ( ! empty( $contentType ) ) {
				$this->logger->debug('Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.');
			}

			$cc = array();
			$duplicates = array();
			foreach ( (array) $message->getCcRecipients() as $recipient ) {

				if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

					$recipient->log( $this->logger, 'Cc' );
					$cc[] = array(
						'email' =>  $recipient->getEmail(),
					);

					if ( ! empty( $recipient->getName() ) ) {
						$cc['name'] = $recipient->getName();
					}

					$duplicates[] = $recipient->getEmail();
				}
			}
			if ( ! empty( $cc ) )
				$sendSmtpEmail['cc'] = $cc;

			$bcc = array();
			$duplicates = array();
			foreach ( (array) $message->getBccRecipients() as $recipient ) {

				if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

					$recipient->log( $this->logger, 'Bcc' );
					$bcc[] = array(
						'email'  =>  $recipient->getEmail()
					);

					if (!empty($recipient->getName())) {
						$bcc['name'] = $recipient->getName();
					}

					$duplicates[] = $recipient->getEmail();
				}
			}

			if ( ! empty( $bcc ) )
				$sendSmtpEmail['bcc'] = $bcc;

			// add attachments.
			$this->logger->debug( 'Adding attachments' );
			$attachments_array = array();

			$attachments = $this->addAttachmentsToMail( $message );

			if ( ! empty( $attachments ) ) {
				$attachments_array = [];

				foreach ( $attachments as $attachment ) {
					$attachments_array[ $attachment['file_name'] ] = $attachment['content'];
				}

				$sendSmtpEmail['attachments_binary'] = $attachments_array;
			}

			try {

				// send the message.
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}
				$final_message = array(

					'email' => $sendSmtpEmail,
				);

				$response = $sendpulse->send( $final_message );

				$this->transcript = print_r( $response, true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $sendSmtpEmail, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );
			} catch ( Exception $e ) {

				$this->transcript = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $sendSmtpEmail, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );

				throw $e;
			}
		}
	}
endif;
