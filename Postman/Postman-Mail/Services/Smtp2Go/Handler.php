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

				return $this->request(
					'POST',
					'/send',
					$this->get_headers(),
					$content,
					$this->email_sent_code
				);
			}
		}
	}
