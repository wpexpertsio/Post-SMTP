<?php

class PostmanSendinblue extends PostmanServiceRequest {

    /**
     * Success Code
     * 
     * @since 2.3
     * @version 1.0
     */
    private $email_sent_code = 201;

    /**
     * API Key
     * 
     * @since 2.3
     * @version 1.0
     */
    private $api_key = '';

    /**
     * Base URL
     * 
     * @since 2.3
     * @version 1.0
     */
    private $base_url = 'https://api.brevo.com/v3/smtp';

    /**
     * constructor PostmanSendinblue
     * 
     * @param $api_key
     * @since 2.3
     * @version 1.0
     */
    public function __construct( $api_key ) {

        $this->api_key = $api_key;
        
        parent::__construct( $this->base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.3
     * @version 1.0
     */
    private function get_headers() {

        return array(
            'Api-Key'       =>  $this->api_key,
            'Content-Type'  =>  'application/json',
            'Accept'        =>  'application/json'
        );

    }

    /**
     * Sends Email using Sendinblue transmissions end point
     * 
     * @param $api_key
     * @since 2.3
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

    /**
     * Fetch provider logs from Brevo API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( string $from = '', string $to = '' ) {
        if ( empty( $this->api_key ) ) {
            return [];
        }

        $query = array_filter([
            'startDate' => $from,
            'endDate'   => $to,
        ]);

		$endpoint = apply_filters( 'post_smtp_events_endpoint', 'brevo' );
	    $url = $this->base_url . $endpoint;
        if ( $query ) {
            $url .= '?' . http_build_query( $query );
        }

        $response = wp_remote_get( $url, [
            'headers' => [
                'api-key' => $this->api_key,
                'accept'  => 'application/json',
            ],
            'timeout' => 15,
        ]);

        if ( is_wp_error( $response ) ) {
            return [];
        }

        $body   = json_decode( wp_remote_retrieve_body( $response ), true );
        $events = $body['events'] ?? [];

        return array_map( static function( $event ) {
            return array(
                'id'      => $event['messageId'] ?? '',
                'subject' => $event['subject'] ?? '',
                'from'    => $event['from'] ?? '',
                'to'      => $event['email'] ?? '',
                'date'    => $event['date'] ?? '',
                'status'  => $event['event'] ?? '',
            );
        }, $events );
    }

}