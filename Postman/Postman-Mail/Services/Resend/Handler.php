<?php

class PostmanResend extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $email_sent_code = 200;

    /**
     * API Key
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private $base_url = 'https://api.resend.com';

    /**
     * constructor PostmanResend
     * 
     * @param $api_key
     * @since 3.2.0
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 3.2.0
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json'
        );

    }

    /**
     * Sends Email using Resend emails endpoint
     * 
     * @param $content
     * @since 3.2.0
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


    /**
     * Fetch provider logs from Resend API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( $from = '', $to = '' ) {
        if ( empty( $this->api_key ) ) {
            return array();
        }

        $query = array_filter([
            'startDate' => $from,
            'endDate'   => $to,
        ]);

        $endpoint = apply_filters( 'post_smtp_events_endpoint', 'resend' );
	    $url = $this->base_url . $endpoint;

        if ( $query ) {
            $url .= '?' . http_build_query( $query );
        }

        $response = wp_remote_get( $url, [
            'headers' => $this->get_headers(),
            'timeout' => 15,
        ]);

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body   = json_decode( wp_remote_retrieve_body( $response ), true );
        $events = isset( $body['events'] ) ? $body['events'] : array();

        return array_map( static function( $event ) {
            return array(
                'id'      => isset( $event['id'] ) ? $event['id'] : '',
                'subject' => isset( $event['subject'] ) ? $event['subject'] : '',
                'from'    => isset( $event['from'] ) ? $event['from'] : '',
                'to'      => isset( $event['to'] ) ? $event['to'] : '',
                'date'    => isset( $event['date'] ) ? $event['date'] : '',
                'status'  => isset( $event['status'] ) ? $event['status'] : '',
            );
        }, $events );
    }
}
