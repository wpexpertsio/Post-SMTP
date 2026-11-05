<?php
/**
 * Email log persistence and fallback hooks (PostmanEmailLogService integration).
 *
 * @package PostSMTP\Mail\Modern\EmailLog
 */

namespace PostSMTP\Mail\Modern\EmailLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Events {

	private static function ensurePostmanClasses(): void {
		if ( ! class_exists( '\PostmanEmailAddress', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/Postman-Mail/PostmanEmailAddress.php';
		}
		if ( ! class_exists( '\PostmanMessage', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/Postman-Mail/PostmanMessage.php';
		}
		if ( ! class_exists( '\PostmanOptions', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanOptions.php';
		}
	}

	/**
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param array<string,mixed> $connection     Connection row.
	 * @param array<string,mixed> $result         Provider send result.
	 * @param bool                $is_fallback    Whether this attempt used a fallback connection.
	 * @param array<string,mixed> $processed_data Normalized envelope data.
	 * @param int|null            $primary_log_id Staged log row for the primary attempt.
	 */
	public static function recordAttempt(
		array $atts,
		array $connection,
		array $result,
		bool $is_fallback,
		array $processed_data = array(),
		?int $primary_log_id = null
	): void {
		self::ensurePostmanClasses();

		$log        = self::buildLog( $atts, $connection, $processed_data );
		$message    = self::buildMessage( $atts, $connection, $processed_data );
		$transcript = (string) ( $result['transcript'] ?? $result['message'] ?? '' );
		$success    = ! empty( $result['success'] );
		$status     = (string) ( $result['message'] ?? __( 'Send failed.', 'post-smtp' ) );

		Writer::persistAttempt(
			$log,
			$message,
			$success,
			$status,
			$transcript,
			$is_fallback,
			$is_fallback ? null : $primary_log_id
		);

		if ( ! $success && ! $is_fallback ) {
			Writer::detachSharedLogUpdateFilters();
		}
	}

	/**
	 * Primary failed — detach log-row reuse so fallback writes a new row (Pro Report & Tracking).
	 *
	 * @param array<string,mixed> $atts wp_mail arguments.
	 */
	public static function beforeFallback( array $atts ): void {
		self::ensurePostmanClasses();
		Writer::detachSharedLogUpdateFilters();

		$log     = self::buildLog( $atts, array(), array() );
		$message = self::buildMessage( $atts, array(), array() );
		do_action( 'post_smtp_before_fallback_delivery', $log, $message );
	}

	/**
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param array<string,mixed> $connection     Connection row.
	 * @param array<string,mixed> $processed_data Normalized data.
	 */
	private static function buildLog( array $atts, array $connection, array $processed_data ): \PostmanEmailLog {
		if ( ! class_exists( '\PostmanEmailLog', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailLogService.php';
		}

		$log                  = new \PostmanEmailLog();
		$log->originalTo      = self::formatToForLog( $atts, $processed_data );
		$log->originalSubject = (string) ( $atts['subject'] ?? '' );
		$log->originalMessage = (string) ( $atts['message'] ?? '' );
		$log->originalHeaders = is_array( $atts['headers'] ?? '' ) ? implode( "\r\n", $atts['headers'] ) : (string) ( $atts['headers'] ?? '' );
		$log->transportUri    = self::transportLabel( $connection );
		$log->toRecipients    = $log->originalTo;
		$log->sender          = sanitize_email( (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ) );
		return $log;
	}

	/**
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param array<string,mixed> $connection     Connection row.
	 * @param array<string,mixed> $processed_data Normalized data.
	 */
	private static function buildMessage( array $atts, array $connection, array $processed_data ): \PostmanMessage {
		self::ensurePostmanClasses();
		$message = new \PostmanMessage();
		$message->setSubject( (string) ( $atts['subject'] ?? '' ) );
		$message->setBody( (string) ( $atts['message'] ?? '' ) );

		$from_email = sanitize_email( (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ) );
		$from_name  = sanitize_text_field( (string) ( $connection['sender_name'] ?? $connection['from_name'] ?? '' ) );
		if ( $from_email ) {
			$message->setFrom( $from_email, $from_name );
		}

		foreach ( (array) ( $processed_data['to'] ?? array() ) as $recipient ) {
			if ( empty( $recipient['email'] ) ) {
				continue;
			}
			$formatted = ! empty( $recipient['name'] )
				? sprintf( '%s <%s>', $recipient['name'], $recipient['email'] )
				: $recipient['email'];
			$message->addTo( $formatted );
		}

		if ( empty( $processed_data['to'] ) ) {
			$to_raw = $atts['to'] ?? '';
			if ( is_array( $to_raw ) ) {
				$to_raw = implode( ',', $to_raw );
			}
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) $to_raw ) ) ) as $address ) {
				$message->addTo( $address );
			}
		}

		return $message;
	}

	/**
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param array<string,mixed> $processed_data Normalized data.
	 */
	private static function formatToForLog( array $atts, array $processed_data ): string {
		$parts = array();
		foreach ( (array) ( $processed_data['to'] ?? array() ) as $recipient ) {
			if ( empty( $recipient['email'] ) ) {
				continue;
			}
			$parts[] = ! empty( $recipient['name'] )
				? sprintf( '%s <%s>', $recipient['name'], $recipient['email'] )
				: $recipient['email'];
		}
		if ( ! empty( $parts ) ) {
			return implode( ', ', $parts );
		}

		$to = $atts['to'] ?? '';
		return is_array( $to ) ? implode( ', ', $to ) : (string) $to;
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 */
	private static function transportLabel( array $connection ): string {
		if ( empty( $connection ) ) {
			return '';
		}
		$provider = (string) ( $connection['provider'] ?? 'smtp' );
		$title    = (string) ( $connection['connection_title'] ?? $connection['title'] ?? '' );
		return $title ? sprintf( '%s (%s)', $provider, $title ) : $provider;
	}

	/**
	 * Log wp_mail payload as queued (Advanced Delivery) without provider send.
	 *
	 * @param array<string,mixed> $atts           wp_mail arguments.
	 * @param array<string,mixed> $connection     Primary connection row.
	 * @param array<string,mixed> $processed_data Envelope processed data.
	 */
	public static function persistQueued( array $atts, array $connection, array $processed_data = array() ): void {
		self::ensurePostmanClasses();

		$log     = self::buildLog( $atts, $connection, $processed_data );
		$message = self::buildMessage( $atts, $connection, $processed_data );

		/**
		 * @param string              $status Default log status for queued mail.
		 * @param array<string,mixed> $atts   wp_mail arguments.
		 */
		$status = apply_filters( 'post_smtp_revamp_queue_log_status', 'In Queue', $atts );

		Writer::persistQueued( $log, $message, (string) $status );
	}
}
