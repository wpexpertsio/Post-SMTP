<?php

class PostmanMailNotify implements Postman_Notify {

    public function send_message($message)
    {
        $to_email = get_bloginfo( 'admin_email' );
        $domain = get_bloginfo( 'url' );

        mail( $to_email, "{$domain}: " .  __( 'Post SMTP email error', Postman::TEXT_DOMAIN ), $message , null, "-f{$to_email}" );
    }
}