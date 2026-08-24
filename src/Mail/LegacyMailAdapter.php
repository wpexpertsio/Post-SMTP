<?php
/**
 * Delegates mail sending to legacy PostmanWpMail.
 *
 * @package PostSMTP\Mail
 */

namespace PostSMTP\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyMailAdapter {

	/**
	 * @param array<string,mixed> $atts wp_mail arguments.
	 * @return array{success:bool,message:string,send:bool}
	 */
	public static function send( array $atts ): array {
		$result = wp_mail(
			$atts['to'] ?? '',
			$atts['subject'] ?? '',
			$atts['message'] ?? '',
			$atts['headers'] ?? '',
			$atts['attachments'] ?? array()
		);

		return array(
			'success' => (bool) $result,
			'message' => $result ? __( 'Sent via legacy mail adapter.', 'post-smtp' ) : __( 'Legacy send failed.', 'post-smtp' ),
			'send'    => (bool) $result,
		);
	}
}
