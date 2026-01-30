<?php
	/**
	 * PostmanSmtp2GoHandler
	 *
	 * @package Postman
	 */

	defined( 'ABSPATH' ) || exit;

	if ( ! class_exists( 'PostmanSmtp2GoHandler' ) ) {
		class PostmanSmtp2GoHandler extends PostmanServiceRequest {
			private $email_sent_code = 200;
			private $api_key = '';
			private $base_url = 'https://api.smtp2go.com/v3/email';

		public function __construct( $api_key ) {
			$this->api_key = $api_key;

			parent::__construct( $this->base_url );
			
			// Set 30-second timeout for SMTP2GO requests
			$this->set_additional_args( array( 'timeout' => 30 ) );
		}			
		
		private function get_headers() {
				return array(
					'Content-Type'  =>  'application/json',
					'X-Smtp2go-Api-Key' => $this->api_key,
					'accept' => 'application/json',
				);
			}

			public function send( $content ) {
				$content = json_encode( $content );

				$response = $this->request(
					'POST',
					'/send',
					$this->get_headers(),
					$content,
					$this->email_sent_code
				);

				// SMTP2Go returns HTTP 200 even when the email failed; check the JSON body.
				$body = $this->get_response_body();
				if ( ! empty( $body ) ) {
					$data = json_decode( $body, true );
					if ( json_last_error() === JSON_ERROR_NONE && isset( $data['data'] ) ) {
						$api_data = $data['data'];
						$succeeded = isset( $api_data['succeeded'] ) ? (int) $api_data['succeeded'] : 1;
						$failed = isset( $api_data['failed'] ) ? (int) $api_data['failed'] : 0;
						if ( $succeeded === 0 || $failed > 0 ) {
							$message = __( 'SMTP2Go reported a failure.', 'post-smtp' );
							if ( ! empty( $api_data['failures'] ) && is_array( $api_data['failures'] ) ) {
								$message = implode( ' ', $api_data['failures'] );
							} elseif ( ! empty( $api_data['failures'] ) && is_string( $api_data['failures'] ) ) {
								$message = $api_data['failures'];
							}
							throw new Exception( $message );
						}
					}
				}

				return $response;
			}
		}
	}
