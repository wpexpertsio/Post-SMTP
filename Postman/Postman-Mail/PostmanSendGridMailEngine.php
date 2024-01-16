<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanSendGridMailEngine' ) ) {

	require_once 'Services/SendGrid/Handler.php';

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

            $sendgrid = new PostmanSendGrid( $this->apiKey );
			$content = array();
			$recipients = array();

            // add the From Header
			$sender = $message->getFromAddress();

			$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
			$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

			$content['from'] = array(
				'email'	=>	$senderEmail,
				'name'	=>	$senderName
			); 

            // now log it
			$sender->log( $this->logger, 'From' );

			$duplicates = array();

            // add the to recipients
			foreach ( ( array ) $message->getToRecipients() as $recipient ) {
				
			    if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

			        $content['personalizations'][0]['to'][] = array(
						'email'	=>	$recipient->getEmail(),
						'name'	=>	$recipient->getName()
					);
					
					$duplicates[] = $recipient->getEmail();

                }

			}

			// add the subject
			if ( null !== $message->getSubject() ) {

				$content['subject']	= $message->getSubject();

            }

			// add the message content

			$textPart = $message->getBodyTextPart();
			if ( ! empty( $textPart ) ) {
				
				$this->logger->debug( 'Adding body as text' );
				
				$content['content'] = array(
					array(
						'value'	=>	$textPart,
						'type'	=>	'text/plain',
					)
				);

            }

			$htmlPart = $message->getBodyHtmlPart();
			if ( ! empty( $htmlPart ) ) {

				$this->logger->debug( 'Adding body as html' );
				
				$content['content'] = array(
					array(
						'value'	=>	$htmlPart,
						'type'	=>	'text/html',
					)
				);


			}

			// add the reply-to
			$replyTo = $message->getReplyTo();
			// $replyTo is null or a PostmanEmailAddress object
			if ( isset( $replyTo ) ) {
				
				$content['reply_to'] = array(
					'email'	=>	$replyTo->getEmail(),
					'name'	=>	$replyTo->getName()
				);
				
			}

			// add the Postman signature - append it to whatever the user may have set
			if ( ! $options->isStealthModeEnabled() ) {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
               //$email->addHeader( 'X-Mailer', sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' ) );
			}

			// add the headers - see http://framework.zend.com/manual/1.12/en/zend.mail.additional-headers.html
			foreach ( ( array ) $message->getHeaders() as $header ) {
				$this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
                //$email->addHeader( $header ['name'], $header ['content'] );
			}

			// if the caller set a Content-Type header, use it
			$contentType = $message->getContentType();
			if ( ! empty( $contentType ) ) {
				$this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
			}

			// add the cc recipients
            $ccEmails = array();
			foreach ( ( array ) $message->getCcRecipients() as $recipient ) {
				
                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
					
                    $recipient->log($this->logger, 'Cc');
					$content['personalizations'][0]['cc'][] = array(
						'email'	=>	$recipient->getEmail(),
						'name'	=>	$recipient->getName()
					);
					
                    $duplicates[] = $recipient->getEmail();
					
                }
				
			}


            // add the bcc recipients
            $bccEmails = array();
			foreach ( ( array ) $message->getBccRecipients() as $recipient ) {
				
                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
					
                    $recipient->log($this->logger, 'Bcc');
					$content['personalizations'][0]['bcc'][] = array(
						'email'	=>	$recipient->getEmail(),
						'name'	=>	$recipient->getName()	
					);
					
                    $duplicates[] = $recipient->getEmail();
					
                }
				
			}

            // add the messageId
			$messageId = '<' . $message->getMessageId() . '>';
			if ( ! empty( $messageId ) ) {
				//$email->addHeader( 'message-id', $messageId );
			}

			// add attachments
			$this->logger->debug( 'Adding attachments' );
			
			$attachments = $this->addAttachmentsToMail( $message );
			
			if( !empty( $attachments ) ) {
				
				$content['attachments'] = $this->addAttachmentsToMail( $message );	
				
			}

			try {
				
				// send the message
				if ( $this->logger->isDebug() ) {
					$this->logger->debug( 'Sending mail' );
				}

				$response = $sendgrid->send( $content );
				$this->transcript = print_r( $response, true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $content, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );
			
			} catch ( Exception $e ) {
				
				$this->transcript = $e->getMessage();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= print_r( $content, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );

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
					$file_type = wp_check_filetype( $file );
					$attachments[] = array(
						'content' => base64_encode( file_get_contents( $file ) ),
						'type' => $file_type['type'],
						'filename' => $file_name,
						'disposition' => 'attachment',
						'name' => $file_parts[0],
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