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
    private $usepass = '';

    
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

    private function get_usepass(){
        if(isset($api_key) && isset($secret_key )){
             $usepass = $api_key.':'.$secret_key;
             return $usepass;
        }
    }
    

    /**
     * Prepares Header for Request
     * 
     * @since 2.7
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Authorization' => 'Basic' . base64_encode($this->get_usepass()),
            'Content-Type'  =>  'application/json',
            'Accept'        =>  'application/json'
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