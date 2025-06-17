<?php

class PostmanMailGun extends PostmanServiceRequest {

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
     * Base URL US Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_us = 'https://api.mailgun.net/v3/';
    /**
     * Base URL EU Region
     * 
     * @since 2.2
     * @version 1.0
     */
    private $base_url_eu = 'https://api.eu.mailgun.net/v3/';

    /**
     * Content
     * 
     * @since 2.2
     * @version 1.0
     */
    private $content = array();

    /**
     * constructor PostmanMailGun
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function __construct( $api_key, $region, $domain ) {
        $base_url = ! is_null( $region ) ? $this->base_url_eu : $this->base_url_us;
        $base_url .= $domain;
        
        $this->api_key = $api_key;
        parent::__construct( $base_url );

    }

    /**
     * Prepares Header for Request
     * 
     * @since 2.2
     * @version 1.0
     */
    private function get_headers() {

        $headers = array();
        $headers['Authorization'] = 'Basic ' . base64_encode('api:' . $this->api_key);


        if( isset( $this->content['attachment'] ) ) {

            //Remove attachment from content, to manage it separately
            $attachments = $this->content['attachment'];
            unset( $this->content['attachment'] );

            //Let's create the boundary string. It must be unique
            //so we use the MD5 algorithm to generate a random hash
            $boundary = md5( date( 'r', time() ) );
            $headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
            $payload = '';

            foreach( $this->content as $key => $value ) {
               
                if ( is_array( $value ) ) {

					foreach ( $value as $child_value ) {

						$payload .= '--' . $boundary;
						$payload .= "\r\n";
						$payload .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
						$payload .= $child_value;
						$payload .= "\r\n";

					}

				} else {

					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
					$payload .= $value;
					$payload .= "\r\n";

				}
                
            }

            //Add attachments
            foreach( $attachments as $key => $attachment ) { 
 
                $payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="attachment[' . $key . ']"; filename="' . $attachment['filePath'] . '"' . "\r\n\r\n";
				$payload .= file_get_contents( $attachment['filePath'] );
				$payload .= "\r\n";

            }

            $payload .= '--' . $boundary . '--';

            //Overwrite body with payload
            $this->content = $payload;

        }
        else {

            $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        }

        return $headers;

    }

    /**
     * Sends Email using MailGun email end point
     * 
     * @param $api_key
     * @since 2.2
     * @version 1.0
     */
    public function send( $content ) {

        $this->content = $content;
         
        return $this->request(
            'POST',
            '/messages',
            $this->get_headers(),
            $this->content,
            $this->email_sent_code
        );

    }

}