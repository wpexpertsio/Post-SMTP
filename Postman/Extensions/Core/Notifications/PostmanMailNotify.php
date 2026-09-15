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
		$was_fallback = $options->is_fallback;
		$options->is_fallback = false;

		$envelope_sender = $options->getEnvelopeSender();
		if ( empty( $envelope_sender ) ) {
			$envelope_sender = $options->getMessageSenderEmail();
		}
		if ( empty( $envelope_sender ) || ! is_email( $envelope_sender ) ) {
			$envelope_sender = get_option( 'admin_email' );
		}
		$sender_name = $options->getMessageSenderName();

		$subject = "{$domain}: " . __( 'Post SMTP email error', 'post-smtp' );

		self::$is_sending = true;
		$GLOBALS['post_smtp_suppress_logging'] = true;

		try {

        foreach ( $notification_emails as $to_email ) {

			$to_email = trim( $to_email );

			if ( empty( $to_email ) || ! is_email( $to_email ) ) {
				continue;
			}

			$this->send_notification_email( $to_email, $subject, $message, $envelope_sender, $sender_name );

        }

		} finally {
			$options->is_fallback = $was_fallback;
			self::$is_sending = false;
			unset( $GLOBALS['post_smtp_suppress_logging'] );
		}

    }

	/**
	 * Send a failure notification via standalone PHPMailer (PHP mail transport).
	 *
	 * Does not use wp_mail() or the Post SMTP mailer.
	 *
	 * @param string $to_email
	 * @param string $subject
	 * @param string $body
	 * @param string $from_email
	 * @param string $from_name
	 */
	private function send_notification_email( $to_email, $subject, $body, $from_email, $from_name ) {
		$mail = $this->create_phpmailer();

		try {
			$mail->isMail();
			$mail->CharSet = get_bloginfo( 'charset' );

			if ( ! empty( $from_email ) && is_email( $from_email ) ) {
				$mail->setFrom( $from_email, $from_name );
			}

			$mail->addAddress( $to_email );
			$mail->Subject = $subject;
			$mail->Body    = $body;
			$mail->addCustomHeader( self::NOTIFICATION_HEADER, 'true' );

			$mail->send();
		} catch ( Exception $e ) {
			// Notification delivery failed; do not route through Post SMTP mailer.
		}
	}

	/**
	 * Create a standalone PHPMailer instance that does not use Post SMTP transport.
	 *
	 * @return PHPMailer
	 */
	private function create_phpmailer() {
		if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '>=' ) ) {
			if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer', false ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			}

			return new PHPMailer\PHPMailer\PHPMailer( true );
		}

		if ( ! class_exists( 'PHPMailer', false ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
		}

		return new PHPMailer( true );
	}
}
