<?php

class PostmanMailerSend extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 3.3.0
     * @version 1.0
     */
    private $email_sent_code = 202;

    /**
     * API Key
     * 
     * @since 3.3.0
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 3.3.0
     * @version 1.0
     */
    private $base_url = 'https://api.mailersend.com/v1/';

     /**
     * Options instance
     * 
     * @var PostmanOptions
     */
    private $options;

    /**
     * constructor PostmanMailerSend
     * 
     * @param $api_key
     * @since 3.3.0
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        $this->options = PostmanOptions::getInstance();
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 3.3.0
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Content-Type'  =>  'application/json',
            'Authorization' =>  'Bearer ' . $this->api_key
        );

    }

    /**
     * Sends Email using MailerSend email end point
     * 
     * @param $api_key
     * @since 3.3.0
     * @version 1.0
     */
    public function send( $content ) {
        
        $content = json_encode( $content );
		
        return $this->request(
            'POST',
            'email',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}