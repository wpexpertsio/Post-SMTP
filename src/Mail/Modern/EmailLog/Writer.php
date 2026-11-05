<?php
/**
 * Email log rows for primary + fallback send attempts.
 *
 * @package PostSMTP\Mail\Modern\EmailLog
 */

namespace PostSMTP\Mail\Modern\EmailLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Writer {

	/**
	 * @param int|null $primary_log_id Staged log ID from Report & Tracking (primary attempt only).
	 */
	public static function persistAttempt(
		\PostmanEmailLog $log,
		\PostmanMessage $message,
		bool $success,
		string $status_message,
		string $transcript,
		bool $is_fallback,
		?int $primary_log_id = null
	): void {
		if ( ! class_exists( '\PostmanEmailLogService', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailLogService.php';
		}
		if ( ! class_exists( '\PostmanOptions', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanOptions.php';
		}

		\PostmanEmailLogService::getInstance();

		if ( ! \PostmanOptions::getInstance()->isMailLoggingEnabled() ) {
			return;
		}

		$options              = \PostmanOptions::getInstance();
		$options->is_fallback = $is_fallback;

		self::configureLogUpdateFilter( $is_fallback, $primary_log_id );

		$service = \PostmanEmailLogService::getInstance();
		if ( $success ) {
			$service->writeSuccessLog( $log, $message, $transcript, null );
		} else {
			$service->writeFailureLog( $log, $transcript, $status_message, null, $message );
		}

		$options->is_fallback = false;
	}

	/**
	 * Primary failure must UPDATE the staged row; fallback must INSERT a new row.
	 *
	 * @param int|null $primary_log_id Staged log ID.
	 */
	private static function configureLogUpdateFilter( bool $is_fallback, ?int $primary_log_id ): void {
		remove_all_filters( 'post_smtp_update_email_log_id' );

		if ( $is_fallback ) {
			return;
		}

		if ( null !== $primary_log_id && $primary_log_id > 0 ) {
			add_filter(
				'post_smtp_update_email_log_id',
				static function () use ( $primary_log_id ) {
					return $primary_log_id;
				},
				1
			);
			return;
		}

		add_filter(
			'post_smtp_update_email_log_id',
			static function ( $log_id ) {
				$staged = apply_filters( 'post_smtp_current_email_id', false );
				return is_numeric( $staged ) ? (int) $staged : $log_id;
			},
			1
		);
	}

	/**
	 * Call before fallback provider send so success cannot overwrite the failed primary row.
	 */
	public static function detachSharedLogUpdateFilters(): void {
		remove_all_filters( 'post_smtp_update_email_log_id' );
	}
}
