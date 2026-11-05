<?php
/**
 * Builds Envelope from wp_mail arguments.
 *
 * @package PostSMTP\Mail\Modern\Envelope
 */

namespace PostSMTP\Mail\Modern\Envelope;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EnvelopeFactory {

	/**
	 * @param array<string,mixed> $atts wp_mail arguments.
	 */
	public static function fromWpMailArgs( array $atts ): Envelope {
		$atts = apply_filters( 'wp_mail', $atts );

		$to = $atts['to'] ?? '';
		if ( is_array( $to ) ) {
			$to = implode( ',', array_map( 'strval', $to ) );
		}

		$recipients = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) $to ) ) ) as $address ) {
			$recipients[] = array(
				'email' => sanitize_email( self::extractEmail( $address ) ),
				'name'  => sanitize_text_field( self::extractName( $address ) ),
			);
		}

		if ( empty( $recipients ) ) {
			$recipients[] = array(
				'email' => sanitize_email( (string) $to ),
				'name'  => '',
			);
		}

		$processed = array(
			'to'          => $recipients,
			'subject'     => (string) ( $atts['subject'] ?? '' ),
			'message'     => (string) ( $atts['message'] ?? '' ),
			'headers'     => $atts['headers'] ?? '',
			'attachments' => $atts['attachments'] ?? array(),
		);

		return new Envelope( $atts, $processed );
	}

	private static function extractEmail( string $formatted ): string {
		if ( preg_match( '/<([^>]+)>/', $formatted, $matches ) ) {
			return $matches[1];
		}
		return $formatted;
	}

	private static function extractName( string $formatted ): string {
		if ( preg_match( '/^(.+?)<[^>]+>$/', trim( $formatted ), $matches ) ) {
			return trim( $matches[1], '"\' ' );
		}
		return '';
	}
}
