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


    /**
     * Fetch provider logs from SparkPost API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( $from = '', $to = '' ) {
        $endpoint = apply_filters( 'post_smtp_events_endpoint', 'sparkpost' );
        
        $url = $this->base_url . $endpoint;
        
        $query = array();
        if ( $from ) {
            $query['from'] = $from;
        }
        
        if ( $to ) {
            $query['to'] = $to;
        }
        
        $args = array(
            'headers' => $this->get_headers(),
            'timeout' => 15,
        );
        
        $response = wp_remote_get( add_query_arg( $query, $url ), $args );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['results'] ) || ! is_array( $body['results'] ) ) {
            return array();
        }
        
        $events = $body['results'];
        return array_map( static function( $event ) {
            return array(
                'id'      => $event['message_id'] ?? '',
                'subject' => $event['subject'] ?? '',
                'from'    => $event['friendly_from'] ?? '',
                'to'      => $event['rcpt_to'] ?? '',
                'date'    => $event['timestamp'] ?? '',
                'status'  => $event['type'] ?? '',
            );
        }, $events );
    }
}