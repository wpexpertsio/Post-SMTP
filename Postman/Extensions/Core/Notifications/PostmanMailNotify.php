<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanMailNotify implements Postman_Notify {

	/** @var bool True while this class sends internal alert mail via PHP mail(). */
	private static $sending = false;

	/**
	 * Pro extensions skip tracking/logging for Post SMTP's own notification emails.
	 */
	public static function is_sending(): bool {
		return self::$sending;
	}

    public function send_message( $message ) {
		self::$sending = true;

		try {
			$notification_emails = PostmanNotifyOptions::getInstance()->get_notification_email();

			/**
			 * Filters The Notification Emails
			 *
			 * @notification_emails String
			 */
			$notification_emails = apply_filters( 'post_smtp_notify_email', $notification_emails );
			$domain              = get_bloginfo( 'url' );
			$notification_emails = explode( ',', $notification_emails );

			foreach ( $notification_emails as $to_email ) {
				mail( $to_email, "{$domain}: " . __( 'Post SMTP email error', 'post-smtp' ), $message, '', "-f{$to_email}" );
			}
		} finally {
			self::$sending = false;
		}
    }
}