<?php

class PostmanSlackNotify implements Postman_Notify {

    public function send_message($message)
    {
        $options = PostmanOptions::getInstance();

        $api_url = $options->getSlackToken();

        $headers = array(
            'content-type' => 'application/json'
        );

        $body = array(
            'text' => $message
        );

        $args = array(
            'headers' => $headers,
            'body' => json_encode($body)
        );

        $result = wp_remote_post( $api_url, $args );

        if ( is_wp_error($result) ) {
            error_log( __CLASS__ . ': ' . $result->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $result );
        $message = wp_remote_retrieve_response_message( $result );

        if ( $code != 200 && $message !== 'OK' ) {
            error_log( __CLASS__ . ': ' . $message );
        }
    }
}