<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PostmanWebhookAlertsNotify implements Postman_Notify {

    const WEBHOOK_OPTION = 'post_smtp_webhook_urls';

    /**
     * Broadcasts a message to the webhook
     * 
     * @param string $message
     * 
     * @return void
     * 
     * @since 3.1.0
     */
    public function send_message( $message ) {

        $webhook_urls = get_option( self::WEBHOOK_OPTION );

        foreach ( $webhook_urls as $webhook_url ) {
            
            /**
             * Filter to validate the webhook URL
             * 
             * @param bool $validate
             * @param string $webhook_url
             * 
             * @since 3.1.0
             */
            $validate = apply_filters( 'post_smtp_validate_webhook_url', true, $webhook_url );

            if( !$validate || filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {

                $response = wp_remote_post( $webhook_url, array(
                    'body' => json_encode( array(
                        'message' => $message
                    ) ),
                    'headers' => array(
                        'Content-Type' => 'application/json'
                    )
                ) );

            }

        }

    }

}