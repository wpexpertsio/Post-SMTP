<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanMailNotify implements Postman_Notify {

	const NOTIFICATION_HEADER = 'X-Post-SMTP-Notification';

	/**
	 * Prevents recursive failure notifications when a notification email fails to send.
	 *
	 * @var bool
	 */
	private static $is_sending = false;

	/**
	 * Whether a notification email is currently being sent.
	 *
	 * @return bool
	 */
	public static function is_sending() {
		return self::$is_sending;
	}

	public function send_message( $message ) {

		if ( self::$is_sending ) {
			return;
		}

		$notification_emails = PostmanNotifyOptions::getInstance()->get_notification_email();

        /**
         * Filters The Notification Emails
         * 
         * @notification_emails String 
         */
        $notification_emails = apply_filters( 'post_smtp_notify_email', $notification_emails );
        $domain = get_bloginfo( 'url' );
        $notification_emails = explode( ',', $notification_emails );

		$options = PostmanOptions::getInstance();
		$envelope_sender = $options->getEnvelopeSender();
		if ( empty( $envelope_sender ) ) {
			$envelope_sender = $options->getMessageSenderEmail();
		}
		$sender_name = $options->getMessageSenderName();

		$subject = "{$domain}: " . __( 'Post SMTP email error', 'post-smtp' );
		$headers = array( self::NOTIFICATION_HEADER . ': true' );

		if ( ! empty( $envelope_sender ) ) {
			if ( ! empty( $sender_name ) ) {
				$headers[] = 'From: ' . sprintf( '%s <%s>', $sender_name, $envelope_sender );
			} else {
				$headers[] = 'From: ' . $envelope_sender;
			}
		}

		self::$is_sending = true;

		try {

        //Lets start informing authorities ;')
        foreach ( $notification_emails as $to_email ) {

			$to_email = trim( $to_email );

			if ( empty( $to_email ) || ! is_email( $to_email ) ) {
				continue;
			}

			$sent = wp_mail( $to_email, $subject, $message, $headers );

			if ( ! $sent && ! empty( $envelope_sender ) ) {
				mail( $to_email, $subject, $message, '', '-f' . $envelope_sender );
			}

        }

		} finally {
			self::$is_sending = false;
		}

    }
}
