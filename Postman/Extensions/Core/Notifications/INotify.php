<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
interface Postman_Notify {
    public function send_message( $message );
}