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
}