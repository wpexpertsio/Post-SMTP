<?php

class PostmanResend extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $base_url = 'https://api.resend.com';

    /**
     * constructor PostmanResend
     * 
     * @param $api_key
     * @since 3.2.0
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json'
        );

    }

    /**
     * Sends Email using Resend emails endpoint
     * 
     * @param $content
     * @since 3.2.0
     * @version 1.0
     */
    public function send( $content ) {

        $content = json_encode( $content );

        return $this->request(
            'POST',
            '/emails',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}
