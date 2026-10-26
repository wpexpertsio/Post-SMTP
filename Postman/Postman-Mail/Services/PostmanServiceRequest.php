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
            'body'      =>  $body,
            'timeout'   =>  PostmanOptions::getInstance()->getConnectionTimeout(),
        );

        //Set Additional Args (If Set)
        if( !empty( $this->additional_args ) ) {
            
            $args = array_merge( $this->additional_args, $args );

        }

        $this->response = wp_remote_post(
            $url,
            $args
        );

        if ( is_wp_error( $this->response ) ) {
            throw new Exception( 'Error executing wp_remote_post: ' . $this->response->get_error_message() );
        }

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

        $code = $this->get_response_code();
        $status_message = $this->get_response_message();
        $body = $this->get_response_body();

        if ( is_array( $body ) || is_object( $body ) ) {
            $body = wp_json_encode( $body );
        }

        $parsed = json_decode( $body, true );
        if ( is_array( $parsed ) ) {
            if ( ! empty( $parsed['ErrorMessage'] ) ) {
                throw new Exception( sprintf( 'Code: %s, Message: %s, Body: %s', $code, $parsed['ErrorMessage'], $body ) );
            }
            if ( ! empty( $parsed['Messages'] ) && is_array( $parsed['Messages'] ) ) {
                foreach ( $parsed['Messages'] as $mailjet_message ) {
                    if ( ! empty( $mailjet_message['Errors'] ) ) {
                        $error_details = wp_json_encode( $mailjet_message['Errors'] );
                        throw new Exception( sprintf( 'Code: %s, Message: %s, Body: %s', $code, $error_details, $body ) );
                    }
                }
            }
        }

        if ( '' === (string) $code && '' === (string) $status_message && '' === (string) $body ) {
            throw new Exception( 'Error executing wp_remote_post: empty HTTP response (request may have been blocked by a security plugin or server firewall).' );
        }

        $message = "Code: {$code}, Message: {$status_message}, Body: {$body}";

        throw new Exception( $message );

    }

}