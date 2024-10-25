<?php

class PostmanSendpulse extends PostmanServiceRequest {

	/**
	 * Success Code
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private $email_sent_code = 200;

	/**
	 * API Key and Secret Key
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private $api_key = ' ';
	private $secret_key = ' ';


	/**
	 * Parameters used to get Token
	 *
	 * @since 2.9.0
	 * @version 1.0 
	 */
	private $grant_type = 'client_credentials';
	private $auth_response_body = ' ';
	private $auth_response = ' ';
	private $token = ' ';

	/**
	 * Base URL
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */

	private $base_url = 'https://api.sendpulse.com';


	/**
	 * Constructor PostmanSendpulse
	 *
	 * @param mixed $api_key Api Key.
	 * @param mixed $secret_key Secret Key.
	 * @since 2.9.0
	 * @version 1.0
	 */
	public function __construct( $api_key, $secret_key )
	{

		$this->api_key = $api_key;
		$this->secret_key = $secret_key;
		parent::__construct( $this->base_url );
	}


	/**
	 * Header to get token
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private function auth_headers() {

		return array(
			'Content-Type'        =>  'application/json'
		);
	}

	/**
	 * Body to get token
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private function auth_body() {

		return array(
			'grant_type'        =>  $this->grant_type,
			'client_id'         =>  $this->api_key,
			'client_secret'     =>  $this->secret_key
		);
	}

	/**
	 * Authenciation to get Token
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private function authentication() {

		$content = json_encode( $this->auth_body() );

		$this->auth_response_body = $this->request(
			'POST',
			'/oauth/access_token',
			$this->auth_headers(),
			$content,
			$this->email_sent_code

		);

		$this->auth_response = json_decode( $this->auth_response_body['body'], true );

		//Auto delete token when token expires after given time period.

		set_transient( 'sendpulse_token', $this->auth_response['access_token'], $this->auth_response['expires_in'] );
	}

	/**
	 * Prepares Header for Request
	 *
	 * @since 2.9.0
	 * @version 1.0
	 */
	private function get_headers()
	{

		if ( get_transient('sendpulse_token') ) {

			$this->token = get_transient( 'sendpulse_token' );
		} else {

			$this->authentication();
			$this->token = get_transient( 'sendpulse_token' );
		}

		return array(

			'Content-Type'      =>  'application/json',
			'Authorization'     =>  'Bearer ' . $this->token

		);
	}

	/**
	 * Sends Email using Sendpulse transmissions end point
	 *
	 * @param mixed $content
	 * @since 2.9.0
	 * @version 1.0
	 */
	public function send( $content ) {

		$content = json_encode( $content );

		return $this->request(
			'POST',
			'/smtp/emails',
			$this->get_headers(),
			$content,
			$this->email_sent_code
		);
	}
}
