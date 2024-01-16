<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanMailNotify implements Postman_Notify {

    public function send_message($message)
    {

		$notification_emails = PostmanNotifyOptions::getInstance()->get_notification_email();

        /**
         * Filters The Notification Emails
         * 
         * @notification_emails String 
         */
        $notification_emails = apply_filters( 'post_smtp_notify_email', $notification_emails );
        $domain = get_bloginfo( 'url' );
        $notification_emails = explode( ',', $notification_emails );

        //Lets start informing authorities ;')
        foreach( $notification_emails as $to_email ) {

            mail( $to_email, "{$domain}: " .  __( 'Post SMTP email error', 'post-smtp' ), $message , '', "-f{$to_email}" );

        }

    }
}