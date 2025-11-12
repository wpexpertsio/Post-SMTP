<?php

class PostmanSweego extends PostmanServiceRequest {
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
    private $base_url = 'https://api.sweego.io';

    /**
     * Options instance
     * @var PostmanOptions
     */
    private $options;

    /**
     * constructor PostmanSweego
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
            'Accept'        =>  'application/json',
            'Api-Key'   =>  $this->api_key
        );
    }

    /**
     * Sends Email using Sweego API
     * @param $content
     * @return mixed
     */
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
