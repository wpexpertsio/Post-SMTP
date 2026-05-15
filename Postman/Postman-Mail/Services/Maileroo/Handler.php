<?php

class PostmanMaileroo extends PostmanServiceRequest {
    /**
     * Success Code
     */
    private $email_sent_code = 200;

    /**
     * API Key
     */
    private $api_key = '';

    /**
     * Base URL (endpoint)
     */
    private $base_url = 'https://smtp.maileroo.com/';

    /**
     * constructor PostmanMaileroo
     *
     * @param string $api_key Plaintext API key.
     */
    public function __construct( $api_key ) {
        $this->api_key = trim( (string) $api_key );
        parent::__construct( $this->base_url );
    }

    /**
     * Prepares Header for Request
     *
     * Maileroo v2 requires X-API-Key (Bearer is rejected with 401).
     */
    private function get_headers() {
        return array(
            'X-API-Key'    => $this->api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        );
    }

    /**
     * Sends Email using Maileroo API
     * @param $content
     * @return mixed
     */
    public function send( $content ) {
        $content = json_encode( $content );
        return $this->request(
            'POST',
            'api/v2/emails',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );
    }
}
