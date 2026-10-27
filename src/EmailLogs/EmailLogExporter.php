<?php
/**
 * Export email logs as CSV or JSON.
 *
 * @package PostSMTP\EmailLogs
 */

namespace PostSMTP\EmailLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogExporter {

	/**
	 * @param list<object> $rows Raw DB rows.
	 */
	public static function to_csv( array $rows ): string {
		$fp = fopen( 'php://temp', 'r+' );
		if ( false === $fp ) {
			return '';
		}

		$headers = array(
			'solution',
			'response',
			'from_header',
			'to_header',
			'cc_header',
			'bcc_header',
			'reply_to_header',
			'transport_uri',
			'original_to',
			'original_subject',
			'original_message',
			'original_headers',
			'session_transcript',
			'delivery_time',
		);
		fputcsv( $fp, $headers );

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		foreach ( $rows as $log ) {
			$log = (array) $log;
			fputcsv(
				$fp,
				array(
					$log['solution'] ?? '',
					( isset( $log['success'] ) && ( 1 === $log['success'] || '1' === (string) $log['success'] ) ) ? 'Sent' : ( $log['success'] ?? '' ),
					$log['from_header'] ?? '',
					$log['to_header'] ?? '',
					$log['cc_header'] ?? '',
					$log['bcc_header'] ?? '',
					$log['reply_to_header'] ?? '',
					$log['transport_uri'] ?? '',
					$log['original_to'] ?? '',
					$log['original_subject'] ?? '',
					$log['original_message'] ?? '',
					$log['original_headers'] ?? '',
					$log['session_transcript'] ?? '',
					isset( $log['time'] ) ? date_i18n( "{$date_format} {$time_format}", (int) $log['time'] ) : '',
				)
			);
		}

		rewind( $fp );
		$csv = stream_get_contents( $fp );
		fclose( $fp );
		return is_string( $csv ) ? $csv : '';
	}

	/**
	 * @param list<array<string,mixed>> $normalized Normalized rows.
	 */
	public static function to_json( array $normalized ): string {
		return wp_json_encode( $normalized, JSON_PRETTY_PRINT ) ?: '[]';
	}
}
