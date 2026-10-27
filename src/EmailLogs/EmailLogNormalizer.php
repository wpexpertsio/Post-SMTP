<?php
/**
 * Normalize email log rows for REST / SPA.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogNormalizer {

	/**
	 * @param object|array<string,mixed> $row Raw DB row.
	 * @return array<string,mixed>
	 */
	public static function normalize_list_row( $row ): array {
		$row = (array) $row;
		$success_raw = isset( $row['success'] ) ? (string) $row['success'] : '';

		return array(
			'id'              => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'subject'         => isset( $row['original_subject'] ) ? (string) $row['original_subject'] : '',
			'recipients'      => self::parse_recipients( $row ),
			'recipients_short'=> self::short_recipients( $row ),
			'mailer'          => self::parse_mailer_slug( $row ),
			'mailer_label'    => self::parse_mailer_label( $row ),
			'route'           => self::parse_route( $success_raw ),
			'sent_at'         => isset( $row['time'] ) ? (int) $row['time'] : 0,
			'sent_at_display' => self::format_timestamp( isset( $row['time'] ) ? (int) $row['time'] : 0 ),
			'status'          => self::parse_status( $success_raw ),
			'status_label'    => self::status_label( self::parse_status( $success_raw ) ),
			'opened'          => self::parse_opened( $row ),
			'has_attachments' => self::has_attachments( $row ),
			'has_body'        => ! empty( $row['original_message'] ),
			'form'            => isset( $row['event_type'] ) ? (string) $row['event_type'] : '',
		);
	}

	/**
	 * @param array<string,mixed> $log Full log row.
	 * @return array<string,mixed>
	 */
	public static function normalize_detail( array $log ): array {
		$list = self::normalize_list_row( $log );

		return array_merge(
			$list,
			array(
				'from'              => isset( $log['from_header'] ) ? (string) $log['from_header'] : '',
				'message_id'        => self::extract_message_id( $log ),
				'to'                => self::split_emails( isset( $log['to_header'] ) ? (string) $log['to_header'] : '' ),
				'cc'                => self::split_emails( isset( $log['cc_header'] ) ? (string) $log['cc_header'] : '' ),
				'bcc'               => self::split_emails( isset( $log['bcc_header'] ) ? (string) $log['bcc_header'] : '' ),
				'headers'           => self::redact_headers( isset( $log['original_headers'] ) ? $log['original_headers'] : '' ),
				'transcript'        => self::redact_transcript( isset( $log['session_transcript'] ) ? (string) $log['session_transcript'] : '' ),
				'server_response'   => self::server_response( $log ),
				'preview_html'      => self::preview_html( $log ),
				'preview_available' => ! empty( $log['original_message'] ),
				'resend_available'  => ! empty( $log['original_message'] ),
				'attachments'       => array(),
			)
		);
	}

	public static function parse_status( string $success_raw ): string {
		if ( '1' === $success_raw || 1 === $success_raw || 'Sent ( ** Fallback ** )' === $success_raw || str_starts_with( $success_raw, '( ** Fallback ** )' ) ) {
			return 'success';
		}
		if ( 'In Queue' === $success_raw ) {
			return 'queued';
		}
		return 'failed';
	}

	public static function status_label( string $status ): string {
		switch ( $status ) {
			case 'success':
				return __( 'Success', 'post-smtp' );
			case 'queued':
				return __( 'Queued', 'post-smtp' );
			default:
				return __( 'Failed', 'post-smtp' );
		}
	}

	public static function parse_route( string $success_raw ): string {
		if ( 'Sent ( ** Fallback ** )' === $success_raw || str_starts_with( $success_raw, '( ** Fallback ** )' ) ) {
			return 'fallback';
		}
		return 'primary';
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	public static function parse_mailer_slug( $row ): string {
		$row = (array) $row;
		$uri = isset( $row['transport_uri'] ) ? (string) $row['transport_uri'] : '';
		if ( preg_match( '#://([^:/]+)#', $uri, $m ) ) {
			$host = strtolower( $m[1] );
			if ( str_contains( $host, 'gmail' ) ) {
				return 'gmail_api';
			}
			if ( str_contains( $host, 'sendgrid' ) ) {
				return 'sendgrid_api';
			}
			if ( str_contains( $host, 'amazonaws' ) || str_contains( $host, 'ses' ) ) {
				return 'amazon_ses_api';
			}
			return 'smtp';
		}
		if ( str_contains( strtolower( $uri ), 'sendgrid' ) ) {
			return 'sendgrid_api';
		}
		if ( str_contains( strtolower( $uri ), 'mailgun' ) ) {
			return 'mailgun_api';
		}
		return 'smtp';
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	public static function parse_mailer_label( $row ): string {
		$slug = self::parse_mailer_slug( $row );
		$labels = array(
			'gmail_api'      => 'Gmail',
			'sendgrid_api'   => 'SendGrid',
			'amazon_ses_api' => 'Amazon SES',
			'mailgun_api'    => 'Mailgun',
			'smtp'           => 'SMTP',
		);
		return $labels[ $slug ] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * @param array<string,mixed> $args Query args.
	 * @return array{from?:int,to?:int}
	 */
	public static function date_preset_to_range( string $preset ): array {
		$now = time();
		switch ( $preset ) {
			case 'last_30_days':
				return array(
					'from' => $now - ( 30 * DAY_IN_SECONDS ),
					'to'   => $now,
				);
			case 'last_90_days':
				return array(
					'from' => $now - ( 90 * DAY_IN_SECONDS ),
					'to'   => $now,
				);
			case 'all':
				return array();
			default:
				return array(
					'from' => $now - ( 7 * DAY_IN_SECONDS ),
					'to'   => $now,
				);
		}
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 * @return list<string>
	 */
	private static function parse_recipients( $row ): array {
		$row = (array) $row;
		$raw = '';
		if ( ! empty( $row['to_header'] ) ) {
			$raw = (string) $row['to_header'];
		} elseif ( ! empty( $row['original_to'] ) ) {
			$raw = (string) $row['original_to'];
		}
		return self::split_emails( $raw );
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	private static function short_recipients( $row ): string {
		$list = self::parse_recipients( $row );
		if ( empty( $list ) ) {
			return '';
		}
		if ( count( $list ) === 1 ) {
			return $list[0];
		}
		return $list[0] . ' +' . ( count( $list ) - 1 );
	}

	/**
	 * @return list<string>
	 */
	private static function split_emails( string $raw ): array {
		if ( '' === trim( $raw ) ) {
			return array();
		}
		$parts = preg_split( '/[,;]/', $raw ) ?: array();
		$out   = array();
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( '' === $part ) {
				continue;
			}
			if ( preg_match( '/<([^>]+)>/', $part, $m ) ) {
				$out[] = $m[1];
			} else {
				$out[] = $part;
			}
		}
		return array_values( array_unique( $out ) );
	}

	private static function format_timestamp( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '';
		}
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		return date_i18n( "{$date_format} {$time_format}", $timestamp );
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	private static function parse_opened( $row ): ?bool {
		$row = (array) $row;
		if ( ! isset( $row['event_type'] ) ) {
			return null;
		}
		$type = (string) $row['event_type'];
		if ( 'open-email' === $type ) {
			return true;
		}
		return null;
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	private static function has_attachments( $row ): bool {
		$row = (array) $row;
		return ! empty( $row['has_attachments'] );
	}

	/**
	 * @param array<string,mixed> $log Log.
	 */
	private static function extract_message_id( array $log ): string {
		$headers = isset( $log['original_headers'] ) ? $log['original_headers'] : '';
		if ( is_string( $headers ) && preg_match( '/Message-ID:\s*(.+)/i', $headers, $m ) ) {
			return trim( $m[1] );
		}
		return '';
	}

	/**
	 * @param mixed $headers Serialized or string headers.
	 */
	private static function redact_headers( $headers ): string {
		if ( is_string( $headers ) && is_serialized( $headers ) ) {
			$un = @unserialize( $headers );
			if ( is_array( $un ) ) {
				$headers = implode( "\n", $un );
			}
		}
		$text = is_string( $headers ) ? $headers : '';
		return self::redact_secrets( $text );
	}

	private static function redact_transcript( string $text ): string {
		return self::redact_secrets( $text );
	}

	private static function redact_secrets( string $text ): string {
		$patterns = array(
			'/Authorization:\s*Basic\s+[^\s]+/i' => 'Authorization: Basic [redacted]',
			'/password[=:]\s*[^\s]+/i'          => 'password=[redacted]',
			'/api[_-]?key[=:]\s*[^\s]+/i'       => 'api_key=[redacted]',
		);
		foreach ( $patterns as $pattern => $replacement ) {
			$text = preg_replace( $pattern, $replacement, $text ) ?? $text;
		}
		return $text;
	}

	/**
	 * @param array<string,mixed> $log Log.
	 */
	private static function server_response( array $log ): string {
		$success = isset( $log['success'] ) ? (string) $log['success'] : '';
		if ( '1' === $success || 1 === $success || 'Sent ( ** Fallback ** )' === $success ) {
			return __( 'Message accepted by the mailer.', 'post-smtp' );
		}
		if ( 'In Queue' === $success ) {
			return __( 'Queued for delivery.', 'post-smtp' );
		}
		return $success;
	}

	/**
	 * @param array<string,mixed> $log Log.
	 */
	private static function preview_html( array $log ): string {
		if ( empty( $log['original_message'] ) ) {
			return '';
		}
		$msg = (string) $log['original_message'];
		if ( strip_tags( $msg ) !== $msg ) {
			return $msg;
		}
		return '<pre>' . esc_html( $msg ) . '</pre>';
	}
}
