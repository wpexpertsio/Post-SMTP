<?php
/**
 * Programmatic send when the site still uses PostmanWpMail (classic settings UI).
 *
 * @package PostSMTP\Mail\Classic
 */

namespace PostSMTP\Mail\Classic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WpMailAdapter {

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
			'message' => $result ? __( 'Sent via wp_mail.', 'post-smtp' ) : __( 'Send failed.', 'post-smtp' ),
			'send'    => (bool) $result,
		);
	}
}
