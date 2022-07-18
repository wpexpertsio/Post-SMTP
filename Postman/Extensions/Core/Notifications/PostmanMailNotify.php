<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanMailNotify implements Postman_Notify {

    public function send_message($message)
    {
        $to_email = apply_filters( 'post_smtp_notify_email',get_bloginfo( 'admin_email' ) );
        $domain = get_bloginfo( 'url' );

        mail( $to_email, "{$domain}: " .  __( 'Post SMTP email error', 'post-smtp' ), $message , '', "-f{$to_email}" );
    }
}