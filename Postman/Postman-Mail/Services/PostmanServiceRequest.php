<?php

class PostmanServiceRequest {


    /**
     * Base URL
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url = '';

    /**
     * Additional Args
     * 
     * @since 2.2
     * @version 1.0
     */
    private $additional_args = array();

    /**
     * Request Response
     * 
     * @since 2.2
     * @version 1.0
     */
    private $response = array();

    /**
     * constructor PostmanServiceRequest
     * 
     * @param $base_url
     * @since 2.2
     * @version 1.0
     */
    public function __construct( $base_url ) {
        
        $this->base_url = $base_url;

    }

    /**
     * Set Additional Args
     * 
     * @param $args
     * @since 2.2
     * @version 1.0
     */
    public function set_additional_args( $args ) {

        $this->additional_args = $args;

    }

    /**
     * Makes Remote Request
     * 
     * @param $method
     * @param $end_point
     * @param $headers
     * @param $body
     * @param $success_code
     * @since 2.2
     * @version 1.0
     */
    public function request( $method, $end_point, $headers = array(), $body = array(), $success_code = 200 ) {

        $url = "{$this->base_url}{$end_point}";
        $args = array(
            'method'    =>  $method,
            'headers'   =>  $headers,
            'body'      =>  $body
        );

        //Set Additional Args (If Set)
        if( !empty( $this->additional_args ) ) {
            
            $args = array_merge( $this->additional_args, $args );

        }

        $this->response = wp_remote_post(
            $url,
            $args
        );

        $response_code = $this->get_response_code();

        if( $response_code == $success_code ) {

            return $this->response;

        }
        else {

            $this->exception();

        }

    }

    /**
     * Gets Reponse Code
     * 
     * @since 2.2
     * @version 1.0
     */
    public function get_response_code() {

        return wp_remote_retrieve_response_code( $this->response );

    }

    /**
     * Gets Response message
     * 
     * @since 2.2
     * @version 1.0
     */
    public function get_response_message() {

        return wp_remote_retrieve_response_message( $this->response );

    }

    /**
     * Gets Response Body
     * 
     * @since 2.2
     * @version 1.0
     */
    public function get_response_body() {

        return  wp_remote_retrieve_body( $this->response );

    }


    /**
     * Create and throw Exception
     * 
     * @since 2.2
     * @version 1.0
     */
    public function exception() {

        $message = "Code: {$this->get_response_code()}, Message: {$this->get_response_message()}, Body: {$this->get_response_body()}";

        throw new Exception( $message );

    }

}