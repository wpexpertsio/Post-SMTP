<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'PostmanEmailItMailEngine' ) ) {

	require_once 'Services/Emailit/Handler.php';

	/**
	 * Sends mail with the EmailIt API.
	 */
	class PostmanEmailItMailEngine implements PostmanMailEngine {

		protected $logger;
		private   $transcript;
		private   $apiKey;

		public function __construct( $apiKey ) {
			assert( ! empty( $apiKey ) );
			$this->apiKey  = $apiKey;
			$this->logger  = new PostmanLogger( get_class( $this ) );
		}

		/**
		 * Send an email via Emailit, including attachments if provided.
		 *
		 * @param PostmanMessage $message The message object.
		 * @return void
		 * @throws Exception On error.
		 */
		public function send( PostmanMessage $message ) {
			$options  = PostmanOptions::getInstance();
			$emailit  = new PostmanEmailIt( $this->apiKey );

			$recipients = [];
			$duplicates = [];

			// Sender.
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

			$from = $senderEmail;
			if ( ! empty( $senderName ) ) {
				$from = sprintf( '%s <%s>', $senderName, $senderEmail );
			}

			$content = [
				'from'    => $from,
				'to'      => implode( ',', $recipients ),
				'subject' => $subject,
				'html'    => $htmlContent,
				'text'    => wp_strip_all_tags( $textPart ?: $htmlPart ),
			];

			// Attachments.
			$attachments = $this->addAttachmentsToMail( $message );
			if ( ! empty( $attachments ) ) {
				$content['attachments'] = $attachments;
			}

			// Send.
			try {
				$this->logger->debug( 'Sending mail via EmailIt' );
				$response = $emailit->send( $content );
				$responseCode = wp_remote_retrieve_response_code( $response );
				$responseBody = wp_remote_retrieve_body( $response );

				if ( $responseCode === 200 || $responseCode === 202 ) {
					$this->transcript  = 'Email sent successfully.' . PHP_EOL;
					$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS . PHP_EOL;
					$this->transcript .= print_r( $content, true );
					$this->logger->debug( 'Transcript=' . $this->transcript );
				} else {
					$decodedBody  = json_decode( $responseBody, true );
					$errorMessage = $this->extractErrorMessage( $decodedBody, $responseCode );
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
		 * @param int $response_code HTTP status code returned by the EmailIt API.
		 * @return string Translated error message.
		 */
		private function getErrorMessageFromCode( $response_code ) {
			switch ( $response_code ) {
				case 400:
					return __( 'Bad request. Please check your email data.', 'suremails' );
				case 401:
					return __( 'Unauthorized. Please check your API key.', 'suremails' );
				case 403:
					return __( 'Forbidden. Access denied.', 'suremails' );
				case 404:
					return __( 'Not found. Please check the API endpoint.', 'suremails' );
				case 422:
					return __( 'Domain verification required. Your sending domain must be verified in Emailit before you can send emails. Please verify your domain in your Emailit dashboard at https://app.emailit.com/domains', 'suremails' );
				case 429:
					return __( 'Rate limit exceeded. Please try again later.', 'suremails' );
				case 500:
					return __( 'Internal server error. Please try again later.', 'suremails' );
				default:
					// translators: %d is the HTTP error code.
					return sprintf( __( 'HTTP error %d occurred.', 'suremails' ), $response_code );
			}
		}
	}
}
