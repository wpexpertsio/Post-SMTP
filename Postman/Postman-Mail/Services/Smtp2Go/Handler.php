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

			/**
			 * Fetch provider logs from SMTP2GO API
			 *
			 * @param string $from Optional start date (YYYY-MM-DD)
			 * @param string $to   Optional end date (YYYY-MM-DD)
			 * @return array       List of logs with id, subject, from, to, date, and status.
			 */
			public function get_logs( $from = '', $to = '' ) {
				$baseurl = 'https://api.smtp2go.com/v3';
				
				$endpoint = apply_filters( 'post_smtp_events_endpoint', 'smtp2go' );
				$url = $baseurl . $endpoint;
				$body = array();

				if ( ! empty( $from ) ) {
					$body['start_date'] = $from;
				}
				if ( ! empty( $to ) ) {
					$body['end_date'] = $to;
				}
				$body['limit'] = 50;
				$args = array(
					'headers' => $this->get_headers(),
					'body'    => wp_json_encode( $body ),
					'timeout' => 30,
				);

				$response = wp_remote_post( $url, $args );
				if ( is_wp_error( $response ) ) {
					return array();
				}
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( empty( $body['data']['events'] ) || ! is_array( $body['data']['events'] ) ) {
					return array();
				}
				$events = $body['data']['events'];
				return array_map( static function( $event ) {
					return array(
						'id'      => $event['messageId'] ?? $event['email_id'] ?? '',
						'subject' => $event['subject'] ?? '',
						'from'    => $event['from'] ?? $event['sender'] ?? '',
						'to'      => $event['email'] ?? $event['to'] ?? $event['recipient'] ?? '',
						'date'    => $event['date'] ?? '',
						'status'  => $event['event'] ?? '',
					);
				}, $events );
			}
		}
	}
