<?php
	/**
	 * PostmanSmtp2GoEngine
	 *
	 * @package Postman
	 */

	defined( 'ABSPATH' ) || exit;

	if ( ! class_exists( 'PostmanSmtp2GoEngine' ) ) {

		require_once 'Services/Smtp2Go/Handler.php';

		class PostmanSmtp2GoEngine implements PostmanMailEngine {
			protected $logger;
			protected $transcript;
			protected $apiKey;

			public function __construct( $apiKey ) {
				assert( ! empty( $apiKey ) );
				$this->apiKey = $apiKey;

				$this->logger = new PostmanLogger( get_class( $this ) );
			}

			public function send( $message ) {
				$options = PostmanOptions::getInstance();
				$smtp2go = new PostmanSmtp2GoHandler( $this->apiKey );
				$content = array();
				$headers = array();

				$sender = $message->getFromAddress();
				$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
				$senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

				$content['sender'] = $senderName . '<' . $senderEmail . '>';

				$sender->log( $this->logger, 'From' );

				$duplicates = array();

				foreach ( (array) $message->getToRecipients() as $recipient ) {
					if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
						$name  = $recipient->getName();
						$email = $recipient->getEmail();
						$content['to'][] = $name . '<' . $email . '>';
					}

					$duplicates[] = $recipient->getEmail();
				}

				if ( null !== $message->getSubject() ) {
					$content['subject'] = $message->getSubject();
				}

				$textPart = $message->getBodyTextPart();
				$htmlPart = $message->getBodyHtmlPart();

				if ( ! empty( $textPart ) ) {
					$this->logger->debug( 'Adding body as text' );
					$content['text_body'] = $textPart;
				}

				if ( ! empty( $htmlPart ) ) {
					$this->logger->debug( 'Adding body as html' );
					$content['html_body'] = $htmlPart;
				}

				foreach ( (array) $message->getHeaders() as $header ) {
					$this->logger->debug(
						sprintf(
							'Adding user header %s=%s',
							$header['name'],
							$header['content']
						)
					);

					$headers[ $header['name'] ] = $header['content'];
				}

				foreach ( (array) $message->getCcRecipients() as $recipient ) {
					if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
						$name  = $recipient->getName();
						$email = $recipient->getEmail();
						$content['cc'][] = $name . '<' . $email . '>';
					}
					$duplicates[] = $recipient->getEmail();
				}

				foreach ( (array) $message->getCcRecipients() as $recipient ) {
					if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
						$name  = $recipient->getName();
						$email = $recipient->getEmail();
						$content['bcc'][] = $name . '<' . $email . '>';
					}
					$duplicates[] = $recipient->getEmail();
				}

				if ( ! empty( $headers ) ) {
					$content['headers'] = $headers;
				}

				$this->logger->debug( 'Adding attachments' );

				$attachments = $this->addAttachmentsToMail( $message );

				if ( ! empty( $attachments ) ) {
					$content['attachments'] = $attachments;
				}

				try {
					if ( $this->logger->isDebug() ) {
						$this->logger->debug( 'Sending mail' );
					}

					$response = $smtp2go->send( $content );
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

			public function addAttachmentsToMail( $message ) {
				$attachments = $message->getAttachments();
				if ( ! is_array( $attachments ) ) {
					$attArray = explode( PHP_EOL, $attachments );
				} else {
					$attArray = $attachments;
				}

				$attachments = array();

				foreach ( $attArray as $file ) {
					if ( ! empty( $file ) ) {
						$this->logger->debug( 'Adding attachment: ' . $file );

						$file_name = basename( $file );
						$file_parts = explode( '.', $file_name );
						$file_type = wp_check_filetype( $file );

						$attachments[] = array(
							'filename' => $file_name,
							'mimetype' => $file_type['type'],
							'fileblob' => base64_encode( file_get_contents( $file ) ),
						);
					}
				}

				return $attachments;
			}

			public function getTranscript() {
				return $this->transcript;
			}
		}
	}
