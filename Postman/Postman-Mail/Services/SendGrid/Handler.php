<?php

class PostmanSendGrid extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.4
     * @version 1.0
     */
    private $email_sent_code = 202;

    /**
     * API Key
     * 
     * @since 2.4
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 2.4
     * @version 1.0
     */
    private $base_url = 'https://api.sendgrid.com/v3/mail';

     /**
     * Options instance
     * 
     * @var PostmanOptions
     */
    private $options;

    /**
     * constructor PostmanSendGrid
     * 
     * @param $api_key
     * @since 2.4
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        $this->options = PostmanOptions::getInstance();
        $region = $this->options->getSendGridRegion();

        if ( 'EU' === $region || apply_filters( 'post_smtp_enable_sendgrid_eu', false ) ) {
            $this->base_url = 'https://api.eu.sendgrid.com/v3/mail';
        }

        $this->base_url = apply_filters( 'post_smtp_sendgrid_base_url', $this->base_url, $region );
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.4
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Content-Type'  =>  'application/json',
            'Authorization' =>  'Bearer ' . $this->api_key
        );

    }

    /**
     * Sends Email using SendGrid email end point
     * 
     * @param $api_key
     * @since 2.4
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


    /**
     * Fetch provider logs from SendGrid API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( $from = '', $to = '' ) {
        $baseurl = 'https://api.sendgrid.com/v3';
        $endpoint = apply_filters( 'post_smtp_events_endpoint', 'sendgrid' );
        $url = $baseurl . $endpoint;
        $query = array( 'limit' => 50 );
        if ( $from ) {
            $query['start_time'] = strtotime( $from );
        }
        if ( $to ) {
            $query['end_time'] = strtotime( $to );
        }
        $args = array(
            'headers' => $this->get_headers(),
            'timeout' => 30,
        );
        $response = wp_remote_get( add_query_arg( $query, $url ), $args );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['messages'] ) || ! is_array( $body['messages'] ) ) {
            return array();
        }
        return array_map( static function( $message ) {
            return array(
                'id'      => $message['msg_id'] ?? '',
                'subject' => $message['subject'] ?? '',
                'from'    => $message['from_email'] ?? '',
                'to'      => $message['to_email'] ?? '',
                'date'    => $message['last_event_time'] ?? '',
                'status'  => $message['status'] ?? '',
            );
        }, $body['messages'] );
    }
}