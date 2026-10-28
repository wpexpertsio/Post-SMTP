<?php
/**
 * Diagnostic Report tool (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DiagnosticReportService {

	private DiagnosticReportCollector $collector;

	public function __construct() {
		$this->collector = new DiagnosticReportCollector();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		$cached = $this->collector->getCached();
		$user   = wp_get_current_user();

		return array(
			'defaults' => array(
				'support_email' => apply_filters(
					'post_smtp_diagnostic_support_email',
					'support@wpexperts.io'
				),
				'reporter_email' => $user instanceof \WP_User ? sanitize_email( (string) $user->user_email ) : '',
			),
			'report'   => $cached,
			'copy'     => array(
				'need_help_lead'   => __( 'Email this report to support instead of posting it publicly.', 'post-smtp' ),
				'send_success'     => __( 'Diagnostic report sent successfully.', 'post-smtp' ),
				'send_error'       => __( 'Could not send the diagnostic report.', 'post-smtp' ),
				'empty_report'     => __( 'Run the diagnostic report to collect site details.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function runReport(): array {
		$result = $this->collector->collect();

		return array(
			'success'      => true,
			'rows'         => $result['rows'],
			'plain_text'   => $result['plain_text'],
			'generated_at' => $result['generated_at'],
		);
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array{success:bool,message:string}
	 */
	public function sendReport( array $payload ): array {
		$ticket = sanitize_text_field( (string) ( $payload['ticket'] ?? '' ) );
		$to     = sanitize_email( (string) ( $payload['email'] ?? '' ) );

		if ( '' === $ticket ) {
			return array(
				'success' => false,
				'message' => __( 'Enter your ticket number or forum username.', 'post-smtp' ),
			);
		}

		if ( '' === $to || ! is_email( $to ) ) {
			return array(
				'success' => false,
				'message' => __( 'Enter a valid email address.', 'post-smtp' ),
			);
		}

		$cached = $this->collector->getCached();
		if ( empty( $cached['rows'] ) ) {
			$cached = $this->collector->collect();
		}

		$rows = $cached['rows'] ?? array();
		$table = '<table border="1" cellpadding="10" cellspacing="0">';
		foreach ( $rows as $row ) {
			$table .= sprintf(
				'<tr><td><strong>%s</strong></td><td>%s</td></tr>',
				esc_html( (string) ( $row['label'] ?? '' ) ),
				esc_html( (string) ( $row['value'] ?? '' ) )
			);
		}
		$table .= '</table>';

		$subject = sprintf(
			/* translators: %s: ticket or forum username */
			__( '[Post SMTP] Diagnostic Test Report (%s)', 'post-smtp' ),
			$ticket
		);

		$message  = '<p><strong>' . esc_html__( 'Ticket Number/Forum Username:', 'post-smtp' ) . '</strong> ';
		$message .= esc_html( $ticket ) . '</p>';
		$message .= '<h3>' . esc_html__( 'Diagnostic Test Report', 'post-smtp' ) . '</h3>';
		$message .= $table;

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, $message, $headers );

		if ( ! $sent ) {
			return array(
				'success' => false,
				'message' => __( 'The diagnostic report could not be sent. Try again or copy the report manually.', 'post-smtp' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Diagnostic report sent successfully.', 'post-smtp' ),
		);
	}
}
