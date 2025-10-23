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
     * Options instance
     * @var PostmanOptions
     */
    private $options;

    /**
     * constructor PostmanMaileroo
     * @param $api_key
     * @param $endpoint
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;
        parent::__construct( $this->base_url );
    }

    /**
     * Prepares Header for Request
     */
    private function get_headers() {
        return array(
            'Content-Type'  =>  'application/json',
            'Authorization' =>  'Bearer ' . $this->api_key
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
