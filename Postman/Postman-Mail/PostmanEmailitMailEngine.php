<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'PostmanEmailItMailEngine' ) ) {

	require_once 'Services/Emailit/Handler.php';

	/**
	 * Sends mail with the EmailIt API, supporting fallback and smart routing.
	 */
	class PostmanEmailItMailEngine implements PostmanMailEngine {

		protected $logger;
		private   $transcript;
		private   $apiKey;
		private   $is_fallback = false;
		private   $route_key = null;

		/**
		 * @param string|array $credentials API key string or array with keys: api_key, is_fallback, route_key
		 */
		public function __construct( $credentials ) {
			if ( is_array( $credentials ) ) {
				$this->apiKey = isset( $credentials['api_key'] ) ? $credentials['api_key'] : ( isset( $credentials[0] ) ? $credentials[0] : '' );
				$this->is_fallback = !empty( $credentials['is_fallback'] );
				$this->route_key = isset( $credentials['route_key'] ) ? $credentials['route_key'] : null;
			} else {
				$this->apiKey = $credentials;
			}
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
			$options            = PostmanOptions::getInstance();
			$postman_db_version = get_option( 'postman_db_version' );
			$emailit  = new PostmanEmailIt( $this->apiKey );

			// Sender logic (primary, fallback, routing)
			$sender      = $message->getFromAddress();
			$senderEmail = '';
			$senderName  = '';
			if ( $postman_db_version != POST_SMTP_DB_VERSION ) {
				$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
				$senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
			} else {
				$connection_details = get_option( 'postman_connections' );
				if ( $this->is_fallback == null ) {
					$route_key = get_transient( 'post_smtp_smart_routing_route' );
					if( $route_key != null && isset($connection_details[ $route_key ]) ){
						$senderEmail = $connection_details[ $route_key ]['sender_email'];
						$senderName  = isset($connection_details[ $route_key ]['sender_name']) ? $connection_details[ $route_key ]['sender_name'] : $options->getMessageSenderName();
					}else{
						$primary     = $options->getSelectedPrimary();
						$senderEmail = isset($connection_details[ $primary ]['sender_email']) ? $connection_details[ $primary ]['sender_email'] : $options->getMessageSenderEmail();
						$senderName  = isset($connection_details[ $primary ]['sender_name']) ? $connection_details[ $primary ]['sender_name'] : $options->getMessageSenderName();
					}
				} else {
					$fallback    = $options->getSelectedFallback();
					$senderEmail = isset($connection_details[ $fallback ]['sender_email']) ? $connection_details[ $fallback ]['sender_email'] : $options->getMessageSenderEmail();
					$senderName  = isset($connection_details[ $fallback ]['sender_name']) ? $connection_details[ $fallback ]['sender_name'] : $options->getMessageSenderName();
				}
			}
			$sender->log( $this->logger, 'From' );

			// Recipients
			$recipients = [];
			$duplicates = [];
			foreach ( (array) $message->getToRecipients() as $recipient ) {
				if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
					$recipients[] = $recipient->getEmail();
					$duplicates[] = $recipient->getEmail();
				}
			}

			// CC and BCC
			$ccs = [];
			$cc_names = [];
			foreach ( (array) $message->getCcRecipients() as $cc ) {
				$ccs[] = $cc->getEmail();
				if ( !empty($cc->getName()) ) $cc_names[] = $cc->getName();
			}
			$bccs = [];
			$bcc_names = [];
			foreach ( (array) $message->getBccRecipients() as $bcc ) {
				$bccs[] = $bcc->getEmail();
				if ( !empty($bcc->getName()) ) $bcc_names[] = $bcc->getName();
			}

			// Subject and Body
			$subject     = $message->getSubject();
			$textPart    = $message->getBodyTextPart();
			$htmlPart    = $message->getBodyHtmlPart();
			$htmlContent = ! empty( $htmlPart ) ? $htmlPart : nl2br( $textPart );
			if ( empty( $htmlContent ) ) {
				$htmlContent = '<p>(No content)</p>';
			}

			$content = [
				'from'      => $senderEmail,
				'from_name' => $senderName,
				'to'        => implode( ',', $recipients ),
				'subject'   => $subject,
				'html'      => $htmlContent,
				'text'      => wp_strip_all_tags( $textPart ?: $htmlPart ),
			];
			if ( !empty( $ccs ) ) {
				$content['cc'] = implode( ',', $ccs );
				if ( !empty($cc_names) ) $content['cc_names'] = implode( ',', $cc_names );
			}
			if ( !empty( $bccs ) ) {
				$content['bcc'] = implode( ',', $bccs );
				if ( !empty($bcc_names) ) $content['bcc_names'] = implode( ',', $bcc_names );
			}

			// Attachments
			$attachments = $this->addAttachmentsToMail( $message );
			if ( ! empty( $attachments ) ) {
				$content['attachments'] = $attachments;
			}

			// Add fallback and route info for logging/debugging
			if ( $this->is_fallback ) {
				$content['fallback'] = true;
			}
			if ( $this->route_key ) {
				$content['route_key'] = $this->route_key;
			}

			// Send
			try {
				$this->logger->debug( 'Sending mail via EmailIt' . ( $this->is_fallback ? ' (Fallback)' : '' ) . ( $this->route_key ? ' [Route: ' . $this->route_key . ']' : '' ) );
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
