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


    /**
     * Fetch provider logs from Elastic Email API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( $from = '', $to = '' ) {
        if ( empty( $this->api_key ) ) {
            return array();
        }

        $query = array_filter(
			array(
				'from' => $from,
				'to'   => $to,
				'orderBy' => 'DateDescending',
			)
		);
		
        $endpoint = apply_filters( 'post_smtp_events_endpoint', 'elasticemail' );
        $url = 'https://api.elasticemail.com/v4' . $endpoint;
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

        $events = isset( $body) ? $body : array();
        return array_map( static function( $event ) {
            return array(
                'id'      => $event['TransactionID'] ?? $event['MsgID'] ?? '',
                'subject' => $event['Subject'] ?? '',
                'from'    => $event['FromEmail'] ?? '',
                'to'      => $event['To'] ?? '',
                'date'    => $event['EventDate'] ?? '',
                'status'  => $event['EventType'] ?? '',
            );
        }, $events );
    }
}