<?php

class PostmanMailGun extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.2
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 2.2
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL US Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_us = 'https://api.mailgun.net/v3/';
    /**
     * Base URL EU Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_eu = 'https://api.eu.mailgun.net/v3/';

    /**
     * constructor PostmanMailGun
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function __construct( $api_key, $region, $domain ) {
        $base_url = ! is_null( $region ) ? $this->base_url_eu : $this->base_url_us;
        $base_url .= $domain;
        
        $this->api_key = $api_key;
        parent::__construct( $base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.2
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Authorization'             => 'Basic ' . base64_encode('api:' . $this->api_key),
            'Content-Type'              => 'application/x-www-form-urlencoded',
        );

    }

    /**
     * Sends Email using MailGun email end point
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function send( $content ) {
        // $content = json_encode( $content );
         
        return $this->request(
            'POST',
            '/messages',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}