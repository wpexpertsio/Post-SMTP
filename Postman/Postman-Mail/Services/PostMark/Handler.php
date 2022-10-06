<?php

class PostmanPostMark extends PostmanServiceRequest {

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
    private $base_url = 'https://api.postmarkapp.com';

    /**
     * constructor PostmanPostMark
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
            'X-Postmark-Server-Token'   => $this->api_key,
            'Content-Type'              => 'application/json',
            'Accept'                    => 'application/json',
        );

    }

    /**
     * Sends Email using PostMark email end point
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function send( $content ) {
        $content = json_encode( $content );
         
        return $this->request(
            'POST',
            '/email',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }

}