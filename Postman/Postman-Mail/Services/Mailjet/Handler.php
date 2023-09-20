<?php

class PostmanMailjet extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.7
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 2.7
     * @version 1.0
     */
    private $api_key = '';
    private $secret_key = '';
    private $auth = false;

    
    /**
     * Base URL
     * 
     * @since 2.7
     * @version 1.0
     */
    private $base_url = 'https://api.mailjet.com/v3';

    /**
     * constructor PostmanMailjet
     * 
     * @param $api_key
     * @since 2.7
     * @version 1.0
     */
    public function __construct( $api_key, $secret_key ) {

        $this->api_key = $api_key;
        $this->secret_key =$secret_key;
        
        parent::__construct( $this->base_url );

    }

    private function get_auth(){

        if( isset( $this->api_key ) && isset( $this->secret_key ) ) {

            $this->auth = base64_encode( "{$this->api_key}:{$this->secret_key}" );

        }

        return $this->auth;

    }
    

    /**
     * Prepares Header for Request
     * 
     * @since 2.7
     * @version 1.0
     */
    private function get_headers() {

        $auth = $this->get_auth();

        return array(
            'Authorization' => "Basic {$auth}",
            'Content-Type'  =>  'application/json'
        );

    }

    /**
     * Sends Email using Mailjet transmissions end point
     * 
     * @since 2.7
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