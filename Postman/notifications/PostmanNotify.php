<?php
require_once 'INotify.php';
require_once 'PostmanMailNotify.php';
require_once 'PostmanPushoverNotify.php';
require_once 'PostmanSlackNotify.php';

class PostmanNotify {
    private $notify;

    public function __construct( Postman_Notify $notify ) {
        $this->notify = $notify;
    }

    public function send( $message, $log ) {
        $this->notify->send_message( $message );
    }

    public function push_to_chrome($message) {
        $push_chrome = PostmanOptions::getInstance()->useChromeExtension();

        if ( $push_chrome ) {
            $uid = PostmanOptions::getInstance()->getNotificationChromeUid();

            if ( empty( $uid ) ) {
                return;
            }

            $url = 'https://postmansmtp.com/chrome/' . $uid;

            $args = array(
                'body' => array(
                    'message' => $message
                )
            );

            $response = wp_remote_post( $url , $args );
        }
    }
}