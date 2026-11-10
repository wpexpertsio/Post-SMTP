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
			'route_label'     => self::route_label( self::parse_route( $success_raw ) ),
			'sent_at'         => isset( $row['time'] ) ? (int) $row['time'] : 0,
			'sent_at_display' => self::format_timestamp( isset( $row['time'] ) ? (int) $row['time'] : 0 ),
			'status'          => self::parse_status( $success_raw ),
			'status_label'    => self::status_label( self::parse_status( $success_raw ) ),
			'opened'          => self::parse_opened( $row ),
			'has_attachments' => self::has_attachments( $row ),
			'has_body'        => self::row_has_stored_body( $row ),
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
				'initiator'         => self::parse_initiator( $log ),
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
				'attachments'       => self::resolve_detail_attachments( $log ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $log Log row.
	 * @return list<array{name:string,url:string}>
	 */
	private static function resolve_detail_attachments( array $log ): array {
		$log_id = isset( $log['id'] ) ? (int) $log['id'] : 0;
		if ( $log_id <= 0 ) {
			return array();
		}

		return EmailLogAttachmentResolver::listForLog( $log_id );
	}

	/**
	 * Whether the log row has a stored message body (resend / preview).
	 *
	 * @param object|array<string,mixed> $row Row.
	 */
	public static function row_has_stored_body( $row ): bool {
		$row = (array) $row;
		if ( array_key_exists( 'has_stored_body', $row ) ) {
			return (bool) (int) $row['has_stored_body'];
		}
		return ! empty( $row['original_message'] );
	}

	public static function parse_status( string $success_raw ): string {
		if ( self::is_success_raw( $success_raw ) ) {
			return 'success';
		}
		if ( 'In Queue' === $success_raw ) {
			return 'queued';
		}
		return 'failed';
	}

	/**
	 * @param mixed $success_raw Raw DB success column.
	 */
	public static function is_success_raw( $success_raw ): bool {
		if ( '1' === $success_raw || 1 === $success_raw ) {
			return true;
		}
		if ( ! is_string( $success_raw ) || '' === $success_raw ) {
			return false;
		}
		if ( 'Sent ( ** Fallback ** )' === $success_raw || str_starts_with( $success_raw, '( ** Fallback ** )' ) ) {
			return true;
		}
		if ( 0 === strpos( $success_raw, 'Sent ( ** Fallback ** )' ) ) {
			return true;
		}
		if ( false !== stripos( $success_raw, 'delivered' ) ) {
			return true;
		}
		if ( str_starts_with( $success_raw, 'Warning:' ) ) {
			return true;
		}
		if ( str_starts_with( $success_raw, 'Sent' ) ) {
			return true;
		}
		return false;
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
		if ( false !== stripos( $success_raw, '** Fallback **' ) ) {
			return 'fallback';
		}
		return 'primary';
	}

	public static function route_label( string $route ): string {
		return 'fallback' === $route
			? __( 'Fallback', 'post-smtp' )
			: __( 'Primary', 'post-smtp' );
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	public static function parse_mailer_slug( $row ): string {
		$row = (array) $row;
		$uri = isset( $row['transport_uri'] ) ? trim( (string) $row['transport_uri'] ) : '';
		if ( '' === $uri ) {
			$uri = self::transport_uri_from_transcript_prefix( $row );
		}
		if ( '' === $uri ) {
			return 'smtp';
		}

		// Revamp logs store `provider_slug (Connection title)`.
		if ( preg_match( '/^([a-z][a-z0-9_-]*)\s*\(/i', $uri, $m ) ) {
			return self::normalize_mailer_slug_token( $m[1] );
		}

		// Legacy transport URI: `gmail_api:oauth2://host:port` or `smtp:tls:login://host:port`.
		if ( preg_match( '/^([a-z][a-z0-9_-]+):[^:]*:\/\//i', $uri, $m ) ) {
			$slug = self::normalize_mailer_slug_token( $m[1] );
			if ( self::is_known_mailer_slug( $slug ) ) {
				return $slug;
			}
		}

		// Plain provider slug stored without a connection title.
		if ( preg_match( '/^[a-z][a-z0-9_-]+$/i', $uri ) ) {
			$slug = self::normalize_mailer_slug_token( $uri );
			if ( self::is_known_mailer_slug( $slug ) ) {
				return $slug;
			}
		}

		$uri_lower = strtolower( $uri );

		if ( preg_match( '#://([^:/]+)#', $uri, $m ) ) {
			$host = strtolower( $m[1] );
			$from_host = self::slug_from_delivery_host( $host );
			if ( null !== $from_host ) {
				return $from_host;
			}
			return 'smtp';
		}

		foreach ( self::mailer_host_needles() as $needle => $slug ) {
			if ( str_contains( $uri_lower, $needle ) ) {
				return $slug;
			}
		}

		return 'smtp';
	}

	private static function normalize_mailer_slug_token( string $raw ): string {
		$slug = strtolower( str_replace( '-', '_', trim( $raw ) ) );

		$aliases = array(
			'zoho_mail'       => 'zohomail_api',
			'zohomail'        => 'zohomail_api',
			'office365'       => 'office365_api',
			'microsoft_365'   => 'office365_api',
			'amazon_ses'      => 'amazon_ses_api',
			'ses'             => 'amazon_ses_api',
			'sendinblue'      => 'sendinblue_api',
			'brevo'           => 'sendinblue_api',
			'other_smtp'      => 'smtp',
			'phpmailer'       => 'smtp',
			'default'         => 'smtp',
		);

		return $aliases[ $slug ] ?? $slug;
	}

	/**
	 * @return array<string,string> Needle => provider slug.
	 */
	private static function mailer_host_needles(): array {
		return array(
			'googleapis.com'  => 'gmail_api',
			'sendgrid'        => 'sendgrid_api',
			'mailgun'         => 'mailgun_api',
			'amazonaws.com'   => 'amazon_ses_api',
			'email-smtp'      => 'amazon_ses_api',
			'office365'       => 'office365_api',
			'outlook.com'     => 'office365_api',
			'zoho'            => 'zohomail_api',
			'postmark'        => 'postmark_api',
			'mailersend'      => 'mailersend_api',
			'maileroo'        => 'maileroo_api',
			'smtp2go'         => 'smtp2go_api',
			'sparkpost'       => 'sparkpost_api',
			'mandrill'        => 'mandrill_api',
			'elasticemail'    => 'elasticemail_api',
			'resend'          => 'resend_api',
		);
	}

	private static function slug_from_delivery_host( string $host ): ?string {
		foreach ( self::mailer_host_needles() as $needle => $slug ) {
			if ( str_contains( $host, $needle ) ) {
				return $slug;
			}
		}
		if ( str_contains( $host, 'gmail' ) ) {
			return 'gmail_api';
		}

		return null;
	}

	private static function is_known_mailer_slug( string $slug ): bool {
		$slug = self::normalize_mailer_slug_token( $slug );
		if ( isset( self::mailer_slug_labels()[ $slug ] ) ) {
			return true;
		}

		return (bool) preg_match( '/_api$/', $slug ) || 'smtp' === $slug;
	}

	/**
	 * @return array<string,string>
	 */
	private static function mailer_slug_labels(): array {
		return array(
			'gmail_api'            => 'Gmail',
			'sendgrid_api'         => 'SendGrid',
			'amazon_ses_api'       => 'Amazon SES',
			'mailgun_api'          => 'Mailgun',
			'office365_api'        => 'Microsoft 365',
			'zohomail_api'         => 'Zoho Mail',
			'postmark_api'         => 'Postmark',
			'sendinblue_api'       => 'Brevo',
			'smtp'                 => 'SMTP',
			'default'              => 'SMTP',
			'mailtrap_api'         => 'Mailtrap',
			'elasticemail_api'     => 'Elastic Email',
			'smtp2go_api'          => 'SMTP2GO',
			'sparkpost_api'        => 'SparkPost',
			'sendpulse_api'        => 'SendPulse',
			'mandrill_api'         => 'Mandrill',
			'resend_api'           => 'Resend',
			'mailersend_api'       => 'MailerSend',
			'maileroo_api'         => 'Maileroo',
			'emailit_api'          => 'Emailit',
			'sweego_api'           => 'Sweego',
			'smtp_com_api'         => 'SMTP.com',
			'cloudflare_email_api' => 'Cloudflare Email',
		);
	}

	/**
	 * @param object|array<string,mixed> $row Row.
	 */
	public static function parse_mailer_label( $row ): string {
		$row = (array) $row;
		$uri = isset( $row['transport_uri'] ) ? trim( (string) $row['transport_uri'] ) : '';
		if ( '' === $uri ) {
			$uri = self::transport_uri_from_transcript_prefix( $row );
		}

		if ( preg_match( '/^([a-z][a-z0-9_-]*)\s*\(([^)]+)\)\s*$/i', $uri, $m ) ) {
			$connection_title = trim( $m[2] );
			if ( function_exists( 'sanitize_text_field' ) ) {
				$connection_title = sanitize_text_field( $connection_title );
			}

			return self::humanize_mailer_slug( self::normalize_mailer_slug_token( $m[1] ) ) . ' — ' . $connection_title;
		}

		if ( '' !== $uri && ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $uri ) ) {
			return self::humanize_mailer_slug( self::normalize_mailer_slug_token( $uri ) );
		}

		return self::humanize_mailer_slug( self::parse_mailer_slug( $row ) );
	}

	private static function humanize_mailer_slug( string $slug ): string {
		$slug = self::normalize_mailer_slug_token( $slug );
		$labels = self::mailer_slug_labels();
		if ( isset( $labels[ $slug ] ) ) {
			return $labels[ $slug ];
		}

		return ucwords( str_replace( array( '_', '-' ), ' ', $slug ) );
	}

	/**
	 * Logs written before transport_uri was persisted may still have a revamp label on line 1 of the transcript.
	 *
	 * @param array<string,mixed> $row Row.
	 */
	/**
	 * @param array<string,mixed> $log Log row (must include id).
	 * @return array{source?:string,file?:string,line?:string}|null
	 */
	private static function parse_initiator( array $log ): ?array {
		if ( ! class_exists( EmailLogAdvanceSourceQuery::class, false ) || ! EmailLogAdvanceSourceQuery::isEnabled() ) {
			return null;
		}

		$log_id = isset( $log['id'] ) ? (int) $log['id'] : 0;
		if ( $log_id <= 0 || ! function_exists( 'postman_get_log_meta' ) ) {
			return null;
		}

		$source = postman_get_log_meta( $log_id, EmailLogAdvanceSourceQuery::META_SOURCE );
		$file   = postman_get_log_meta( $log_id, 'alf_file' );
		$line   = postman_get_log_meta( $log_id, 'alf_line' );

		if ( '' === (string) $source && '' === (string) $file ) {
			return null;
		}

		$initiator = array();
		if ( '' !== (string) $source ) {
			$initiator['source'] = sanitize_text_field( (string) $source );
		}
		if ( '' !== (string) $file ) {
			$initiator['file'] = sanitize_text_field( (string) $file );
		}
		if ( '' !== (string) $line ) {
			$initiator['line'] = sanitize_text_field( (string) $line );
		}

		return $initiator;
	}

	private static function transport_uri_from_transcript_prefix( array $row ): string {
		$transcript = isset( $row['session_transcript'] ) ? (string) $row['session_transcript'] : '';
		if ( '' === $transcript ) {
			return '';
		}

		$first_line = strtok( $transcript, "\n" );
		if ( false === $first_line ) {
			return '';
		}

		$first_line = trim( $first_line );
		if ( preg_match( '/^([a-z][a-z0-9_-]*)\s*\([^)]+\)\s*$/i', $first_line ) ) {
			return $first_line;
		}

		return '';
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
			$success_raw = isset( $row['success'] ) ? (string) $row['success'] : '';
			if ( self::is_success_raw( $success_raw ) ) {
				return false;
			}
			return null;
		}
		$type = (string) $row['event_type'];
		if ( 'open-email' === $type ) {
			return true;
		}
		$success_raw = isset( $row['success'] ) ? (string) $row['success'] : '';
		if ( self::is_success_raw( $success_raw ) ) {
			return false;
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
		$msg    = (string) $log['original_message'];
		$log_id = isset( $log['id'] ) ? (int) $log['id'] : 0;
		if ( strip_tags( $msg ) !== $msg ) {
			$msg = self::strip_open_tracking_pixels( $msg, $log_id );
			/**
			 * HTML shown in the Email Log preview iframe (tracking pixels should be removed).
			 *
			 * @param string               $html   Sanitized preview HTML.
			 * @param array<string,mixed>  $log    Raw log row.
			 */
			return (string) apply_filters( 'post_smtp_email_log_preview_html', $msg, $log );
		}
		return '<pre>' . esc_html( $msg ) . '</pre>';
	}

	/**
	 * Admin preview must not request the open-tracking pixel (would mark the log as opened).
	 */
	private static function strip_open_tracking_pixels( string $html, int $log_id = 0 ): string {
		$html = preg_replace(
			'#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*post-smtp/v1/t/[^"\']*["\'][^>]*\/?>#i',
			'',
			$html
		);

		if ( $log_id > 0 && function_exists( 'postman_get_log_meta' ) ) {
			$track_code = postman_get_log_meta( $log_id, 'track_code' );
			if ( is_string( $track_code ) && '' !== $track_code ) {
				$html = str_replace( $track_code, '', $html );
			}
		}

		$html = str_replace( '<!-- HTML Ignored -->', '', $html );

		return $html;
	}
}
