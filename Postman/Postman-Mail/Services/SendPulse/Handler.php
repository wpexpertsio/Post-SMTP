<?php

class PostmanSendpulse extends PostmanServiceRequest{

     /**
     * Success Code
     * 
     * @since 2.7
     * @version 1.0
     */
    private $email_sent_code = "200";

    /**
     * Private Auth Key
     *
     * @since 2.7
     * @version 1.0 
     */

    private $body = array();
    private $api_key = " ";
    private $secret_key = " ";
    private $grant_type = "client_credentials";

    private $token = " "; 

     /**
     * Base URL
     * 
     * @since 2.7
     * @version 1.0
     */

     private $base_url = "https://api.sendpulse.com";


    /**
     * constructor PostmanSendpulse
     * 
     * @param $api_key
     * @since 2.7
     * @version 1.0
     */
    public function __construct( $api_key, $secret_key ) {

        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
        
        parent::__construct( $this->base_url );

    }

     /**
     * header to take token
     * 
     * @since 2.7
     * @version 1.0
     */

    private function auth_headers() {

        return array(
            'Content-Type'        =>  'application/json'
        );

    }

    /**
     * Body to take token
     * 
     * @since 2.7
     * @version 1.0
     */
    private function auth_body(){

        return array(
            'grant_type'        =>  $this->grant_type,
            'client_id'         =>  $this->api_key,
            'client_secret'     =>  $this->secret_key
        );

    }

    /**
     * Authenciation to get Token
     * 
     * @since 2.7
     * @version 1.0
     */

    private function authentication(){

        $content = json_encode( $this->auth_body() );

        $this->auth_response = $this->request(
            'POST',
            '/oauth/access_token',
            $this->auth_headers(),
            $content,
            $this->email_sent_code
        );

    }


    /**
     * Prepares Header for Request
     * 
     * @since 2.7
     * @version 1.0
     */

    private function get_headers() {

        return array(
            'Authorization'       =>  'Bearer' . $this->api_key,
            'Content-Type'        =>  'application/json'
        );

    }

     /**
     * Sends Email using Sendpulse transmissions end point
     * 
     * @param $api_key
     * @since 2.7
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

?>