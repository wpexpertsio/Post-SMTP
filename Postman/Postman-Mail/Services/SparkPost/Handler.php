<?php

class PostmanSparkPost extends PostmanServiceRequest {

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
     * Base URL
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url = 'https://api.sparkpost.com/api/v1';

    /**
     * Base URL for Global Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_us = 'https://api.sparkpost.com/api/v1';

    /**
     * Base URL for Europe Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_eu = 'https://api.eu.sparkpost.com/api/v1';

    /**
     * constructor PostmanSparkPost
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.2
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Authorization' =>  $this->api_key,
            'Content-Type'  =>  'application/json'
        );

    }

    /**
     * Sends Email using SparkPost transmissions end point
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function send( $content ) {

        $content = json_encode( $content );

        return $this->request(
            'POST',
            '/transmissions',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}