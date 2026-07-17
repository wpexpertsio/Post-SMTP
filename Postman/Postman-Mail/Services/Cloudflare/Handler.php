<?php

class PostmanCloudflare extends PostmanServiceRequest {

	/**
	 * Successful response code for send endpoint.
	 *
	 * @var int
	 */
	private $email_sent_code = 200;

	/**
	 * @var string
	 */
	private $api_token = '';

	/**
	 * @var string
	 */
	private $account_id = '';

	/**
	 * @var string
	 */
	private $base_url = 'https://api.cloudflare.com/client/v4';

	public function __construct( $api_token, $account_id ) {
		$this->api_token  = $api_token;
		$this->account_id = $account_id;
		parent::__construct( $this->base_url );
	}

	private function get_headers() {
		return array(
			'Authorization' => 'Bearer ' . $this->api_token,
			'Content-Type'  => 'application/json',
		);
	}

	public function send( $content ) {
		$content = json_encode( $content );

		return $this->request(
			'POST',
			'/accounts/' . rawurlencode( $this->account_id ) . '/email/sending/send',
			$this->get_headers(),
			$content,
			$this->email_sent_code
		);
	}
}
