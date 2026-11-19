<?php
/**
 * Send via legacy PostmanWpMail while revamp holds post_smtp_revamp_mail_in_progress.
 *
 * @package PostSMTP\Mail\Modern\Legacy
 */

namespace PostSMTP\Mail\Modern\Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RevampLegacyPostmanSend {

	/**
	 * @param array<string,mixed> $atts wp_mail-style args (to, subject, message, headers, attachments).
	 * @param string              $failure_default Message when send fails without exception detail.
	 * @return array{success:bool,message:string,send:bool,transcript?:string}
	 */
	public static function send( array $atts, string $failure_default ): array {
		if ( ! class_exists( '\PostmanWpMail', false ) ) {
			if ( defined( 'POST_SMTP_PATH' ) && is_readable( POST_SMTP_PATH . 'Postman/PostmanWpMail.php' ) ) {
				require_once POST_SMTP_PATH . 'Postman/PostmanWpMail.php';
			} elseif ( function_exists( 'post_setupPostman' ) ) {
				post_setupPostman();
			}
		}

		if ( ! class_exists( '\PostmanWpMail', false ) ) {
			return array(
				'success' => false,
				'message' => __( 'Post SMTP mail engine is not available.', 'post-smtp' ),
				'send'    => false,
			);
		}

		$skip_fallback = static function () {
			return true;
		};
		add_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );

		$headers     = $atts['headers'] ?? '';
		$attachments = $atts['attachments'] ?? array();

		$mailer  = new \PostmanWpMail();
		$success = $mailer->send(
			$atts['to'] ?? '',
			$atts['subject'] ?? '',
			$atts['message'] ?? '',
			$headers,
			$attachments
		);

		remove_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );

		$result = apply_filters( 'postman_wp_mail_result', null );
		if ( ! is_array( $result ) ) {
			$result = array();
		}

		$message = $success
			? __( 'Email sent successfully.', 'post-smtp' )
			: $failure_default;

		if ( ! $success && isset( $result['exception'] ) && $result['exception'] instanceof \Exception ) {
			$message = sanitize_text_field( $result['exception']->getMessage() );
		}

		$payload = array(
			'success' => (bool) $success,
			'message' => $message,
			'send'    => (bool) $success,
		);

		if ( isset( $result['transcript'] ) && '' !== (string) $result['transcript'] ) {
			$payload['transcript'] = (string) $result['transcript'];
		}

		return $payload;
	}
}