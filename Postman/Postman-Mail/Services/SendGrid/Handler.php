<?php

class PostmanSendGrid extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.4
     * @version 1.0
     */
    private $email_sent_code = 202;

    /**
     * API Key
     * 
     * @since 2.4
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 2.4
     * @version 1.0
     */
    private $base_url = 'https://api.sendgrid.com/v3/mail';

    /**
     * constructor PostmanSendGrid
     * 
     * @param $api_key
     * @since 2.4
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;

        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.4
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Content-Type'  =>  'application/json',
            'Authorization' =>  'Bearer ' . $this->api_key
        );

    }

    /**
     * Sends Email using SendGrid email end point
     * 
     * @param $api_key
     * @since 2.4
     * @version 1.0
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