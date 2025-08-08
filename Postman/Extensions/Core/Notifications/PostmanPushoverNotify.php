<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanPushoverNotify implements Postman_Notify {

    public function send_message($message)
    {
        $options = PostmanNotifyOptions::getInstance();

        $api_url = "https://api.pushover.net/1/messages.json";
        $existing_db_version = get_option( 'postman_db_version' );
        if ( $existing_db_version != POST_SMTP_DB_VERSION ) {
            $app_token = base64_decode( $options->getPushoverToken() );
            $user_key  = base64_decode( $options->getPushoverUser() ); 
        }else{
            $app_token = $options->getPushoverToken();
            $user_key = $options->getPushoverUser();
        }

        $args = array(
            'body' => array(
                "token" => $app_token,
                "user" => $user_key,
                "message" => $message,
            )
        );

        $result = wp_remote_post( $api_url, $args );

        if ( is_wp_error($result) ) {
            error_log( __CLASS__ . ': ' . $result->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $result ), true );
        if ( $body['status'] == 0 ) {
            error_log( __CLASS__ . ': ' . print_r( $body, true ) );
        }
    }
}