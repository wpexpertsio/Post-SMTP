<?php

class PostmanMailtrap extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.9.0
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 2.9.0
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 2.9.0
     * @version 1.0
     */
    private $base_url = 'https://send.api.mailtrap.io/api';

    /**
     * constructor PostmanMailtrap
     * 
     * @param $api_key
     * @since 2.9.0
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );
        
        // Set timeout for API requests
        $this->set_additional_args( array(
            'timeout' => 30
        ) );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.9.0
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Api-Token'     =>  $this->api_key,
            'Content-Type'  =>  'application/json',
            'Accept'        =>  'application/json'
        );

    }

    /**
     * Sends Email using Mailtrap send endpoint
     * 
     * @param array $content
     * @since 2.9.0
     * @version 1.0
     */
    public function send( $content ) {

        $content = json_encode( $content );
        
        if ( $content === false ) {
            throw new Exception( 'Failed to encode email content to JSON' );
        }

        return $this->request(
            'POST',
            '/send',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}
