<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'PostmanSweegoMailEngine' ) ) {

	require_once 'Services/Sweego/Handler.php';

	/**
	 * Sends mail with the Sweego API.
	 */
	class PostmanSweegoMailEngine implements PostmanMailEngine {

		protected $logger;
		private   $transcript;
		private   $apiKey;

		public function __construct( $apiKey ) {
			assert( ! empty( $apiKey ) );
			$this->apiKey  = $apiKey;
			$this->logger  = new PostmanLogger( get_class( $this ) );
		}

		/**
		 * Send an email via Sweego, including attachments if provided.
		 *
		 * @param PostmanMessage $message The message object.
		 * @return void
		 * @throws Exception On error.
		 */
	public function send( PostmanMessage $message ) {
		$options  = PostmanOptions::getInstance();
		$sweego  = new PostmanSweego( $this->apiKey );

		$recipients = [];
		$duplicates = [];			// Sender.
			$sender      = $message->getFromAddress();
			$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
			$senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
			$sender->log( $this->logger, 'From' );

			// Recipients.
			foreach ( (array) $message->getToRecipients() as $recipient ) {
				if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
					$recipients[] = $recipient->getEmail();
					$duplicates[] = $recipient->getEmail();
				}
			}

		// Subject and Body.
		$subject     = $message->getSubject();
		$textPart    = $message->getBodyTextPart();
		$htmlPart    = $message->getBodyHtmlPart();
		$htmlContent = ! empty( $htmlPart ) ? $htmlPart : nl2br( $textPart );

		if ( empty( $htmlContent ) ) {
			$htmlContent = '<p>(No content)</p>';
		}

		// Prepare content for Sweego API
		$content = [
			'provider'      => 'string',
			'campaign-type' => 'market',
			'from'          => [
				'email' => $senderEmail,
				'name'  => $senderName,
			],
			'recipients'    => [],
			'subject'       => $subject,
			'message-html'  => $htmlContent,
			'message-txt'   => wp_strip_all_tags( $textPart ?: $htmlPart ),
		];

		// Add recipients
		foreach ( (array) $message->getToRecipients() as $recipient ) {
			$content['recipients'][] = [
				'email' => $recipient->getEmail(),
				'name'  => $recipient->getName() ?: '',
			];
		}

		// Add CC recipients if any
		$ccRecipients = $message->getCcRecipients();
		if ( ! empty( $ccRecipients ) ) {
			$content['cc'] = [];
			foreach ( $ccRecipients as $ccRecipient ) {
				$content['cc'][] = [
					'email' => $ccRecipient->getEmail(),
					'name'  => $ccRecipient->getName() ?: '',
				];
			}
		}

		// Add BCC recipients if any
		$bccRecipients = $message->getBccRecipients();
		if ( ! empty( $bccRecipients ) ) {
			$content['bcc'] = [];
			foreach ( $bccRecipients as $bccRecipient ) {
				$content['bcc'][] = [
					'email' => $bccRecipient->getEmail(),
					'name'  => $bccRecipient->getName() ?: '',
				];
			}
		}

		// Add Reply-To if set
		$replyTo = $message->getReplyTo();
		if ( $replyTo ) {
			$content['replyTo'] = [
				'email' => $replyTo->getEmail(),
				'name'  => $replyTo->getName() ?: '',
			];
		}			// Attachments.
			$attachments = $this->addAttachmentsToMail( $message );
			if ( ! empty( $attachments ) ) {
				$content['attachments'] = $attachments;
			}

		// Send.
		try {
			$this->logger->debug( 'Sending mail via Sweego' );
			
			// Make API call using Sweego Handler
			$response = $sweego->send( $content );
			$responseCode = $sweego->get_response_code();
			$responseBody = $sweego->get_response_body();

			// Log the response for debugging
			$this->logger->debug( 'Sweego API Response Code: ' . $responseCode );
			$this->logger->debug( 'Sweego API Response Body: ' . $responseBody );

			if ( $responseCode === 200 || $responseCode === 202 ) {
				$this->transcript  = 'Email sent successfully via Sweego API.' . PHP_EOL;
				$this->transcript .= 'Response Code: ' . $responseCode . PHP_EOL;
				$this->transcript .= 'Response: ' . $responseBody . PHP_EOL;
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS . PHP_EOL;
				$this->transcript .= print_r( $content, true );
				$this->logger->debug( 'Transcript=' . $this->transcript );
			} else {
				$decodedBody  = json_decode( $responseBody, true );
				$errorMessage = $this->extractErrorMessage( $decodedBody, $responseCode );
				$this->logger->error( 'Sweego API Error: ' . $errorMessage );
				throw new Exception( $errorMessage );
			}
		} catch ( Exception $e ) {
			$this->transcript  = $e->getMessage() . PHP_EOL;
			$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS . PHP_EOL;
			$this->transcript .= print_r( $content, true );
			$this->logger->debug( 'Transcript=' . $this->transcript );
			throw $e;
		}
	}

	/**
	 * Prepares attachments for the API.
	 *
	 * @param PostmanMessage $message The message object.
	 * @return array
	 */
	private function addAttachmentsToMail( PostmanMessage $message ) {
			$attachments = $message->getAttachments();
			$attArray    = is_array( $attachments ) ? $attachments : explode( PHP_EOL, $attachments );
			$result      = [];

			foreach ( $attArray as $file ) {
				if ( ! empty( $file ) ) {
					$this->logger->debug( 'Adding attachment: ' . $file );
					$fileName = basename( $file );
					$fileType = wp_check_filetype( $file );
					$result[] = [
						'content'     => base64_encode( file_get_contents( $file ) ),
						'type'        => $fileType['type'],
						'filename'    => $fileName,
						'disposition' => 'attachment',
						'name'        => pathinfo( $fileName, PATHINFO_FILENAME ),
					];
				}
			}

			return $result;
		}

		/**
		 * Return debug transcript.
		 *
		 * @return string
		 */
		public function getTranscript() {
			return $this->transcript;
		}

		/**
		 * Extracts the error message from a JSON-decoded response.
		 *
		 * @param array $decodedBody   The response body.
		 * @param int   $responseCode  HTTP code.
		 * @return string
		 */
		private function extractErrorMessage( $decodedBody, $responseCode ) {
			if ( is_array( $decodedBody ) ) {
				if ( isset( $decodedBody['message'] ) && is_string( $decodedBody['message'] ) ) {
					return $decodedBody['message'];
				}
				if ( isset( $decodedBody['error'] ) ) {
					return is_string( $decodedBody['error'] )
						? $decodedBody['error']
						: ( $decodedBody['error']['message'] ?? $this->getErrorMessageFromCode( $responseCode ) );
				}
			}

			return $this->getErrorMessageFromCode( $responseCode );
		}

		/**
		 * Return a user-friendly error message based on HTTP response code.
		 *
		 * @param int $response_code HTTP status code returned by the Sweego API.
		 * @return string Translated error message.
		 */
		private function getErrorMessageFromCode( $response_code ) {
			switch ( $response_code ) {
				case 400:
					return __( 'Bad request. Please check your email data.', 'post-smtp' );
				case 401:
					return __( 'Unauthorized. Please check your API key.', 'post-smtp' );
				case 403:
					return __( 'Forbidden. Access denied.', 'post-smtp' );
				case 404:
					return __( 'Not found. Please check the API endpoint.', 'post-smtp' );
				case 422:
					return __( 'Domain verification required. Your sending domain must be verified in Sweego before you can send emails. Please verify your domain in your Sweego dashboard.', 'post-smtp' );
				case 429:
					return __( 'Rate limit exceeded. Please try again later.', 'post-smtp' );
				case 500:
					return __( 'Internal server error. Please try again later.', 'post-smtp' );
				default:
					// translators: %d is the HTTP error code.
					return sprintf( __( 'HTTP error %d occurred.', 'post-smtp' ), $response_code );
			}
		}
	}
}
