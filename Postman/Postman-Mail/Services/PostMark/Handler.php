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

        /**
         * Filters the content before sending
         * 
         * @since 2.9.2
         */
        $content = json_encode( apply_filters( 'post_smtp_postmark_content' , $content ) );
         
        return $this->request(
            'POST',
            '/email',
            $this->get_headers(),
            $content,
            $this->email_sent_code
        );

    }


    /**
     * Fetch provider logs from Postmark API
     *
     * @param string $from Optional start date (YYYY-MM-DD)
     * @param string $to   Optional end date (YYYY-MM-DD)
     * @return array       List of logs with id, subject, from, to, date, and status.
     */
    public function get_logs( $from = '', $to = '' ) {
        if ( empty( $this->api_key ) ) {
            return array();
        }

		 $query = array(
			'count' => 50,
			 'offset' => 0
		);
        if ( $from ) {
            $query['fromdate'] = $from;
        }
        if ( $to ) {
            $query['todate'] = $to;
        }
        
        $endpoint = apply_filters( 'post_smtp_events_endpoint', 'postmark' );
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

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['Messages'] ) || ! is_array( $body['Messages'] ) ) {
            return array();
        }

        $logs = array();
        foreach ( $body['Messages'] as $message ) {
            if ( ! is_array( $message ) ) {
                continue;
            }
            $to_emails = array();
            if ( ! empty( $message['To'] ) && is_array( $message['To'] ) ) {
                foreach ( $message['To'] as $recipient ) {
                    if ( ! empty( $recipient['Email'] ) ) {
                        $to_emails[] = $recipient['Email'];
                    }
                }
            }
            $logs[] = array(
                'id'      => isset($message['MessageID']) ? $message['MessageID'] : '',
                'subject' => isset($message['Subject']) ? $message['Subject'] : '',
                'from'    => isset($message['From']) ? $message['From'] : '',
                'to'      => implode( ', ', $to_emails ),
                'date'    => isset($message['ReceivedAt']) ? $message['ReceivedAt'] : '',
                'status'  => isset($message['Status']) ? $message['Status'] : '',
            );
        }
        return $logs;
    }
}