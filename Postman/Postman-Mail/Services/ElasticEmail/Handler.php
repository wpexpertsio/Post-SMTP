<?php

class PostmanElasticEmail extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.6.0
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 2.6.0
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 2.6.0
     * @version 1.0
     */
    private $base_url = 'https://api.elasticemail.com/v4/emails';

    /**
     * constructor ElasticEmail
     * 
     * @param $api_key
     * @since 2.6.0
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.6.0
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'X-ElasticEmail-ApiKey' =>  $this->api_key
        );

    }

    /**
     * Sends Email using ElasticEmail transactional end point
     * 
     * @param $api_key
     * @since 2.6.0
     * @version 1.0
     */
    public function send( $content ) {

        $content = json_encode( $content );

        return $this->request(
            'POST',
            '/transactional',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}