<?php
/**
 * Shared Advanced Email Delivery & Logs option (`ps_aedl_option`).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AedlOptions {

	public const OPTION = 'ps_aedl_option';

	private const QUOTA_DURATIONS = array( 'h', 'd', 'w', 'm' );

	/**
	 * Raw stored option (may be empty).
	 *
	 * @return array<string,mixed>
	 */
	public static function readRaw(): array {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Defaults for keys missing from storage (does not write to DB).
	 *
	 * @return array<string,mixed>
	 */
	public static function defaultsForNewSite(): array {
		return array(
			'enable_advance_delivery'  => 1,
			'emails_per_batch'         => 10,
			'batch_duration'           => 5,
			'enable_quota'             => 0,
			'advanced_quota_settings'  => 0,
			'quota_value'              => 500,
			'quota_duration'           => 'd',
			'enable_auto_retry'        => 1,
			'times_to_retry'           => 3,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function mergeWithDefaults( array $raw ): array {
		return array_merge( self::defaultsForNewSite(), $raw );
	}

	/**
	 * API-shaped queue settings for React.
	 *
	 * @return array<string,mixed>
	 */
	public static function valuesForQueueApi(): array {
		$pro = self::mergeWithDefaults( self::readRaw() );

		return array(
			'enable_advance_delivery'  => ! empty( $pro['enable_advance_delivery'] ),
			'emails_per_batch'         => isset( $pro['emails_per_batch'] ) ? absint( $pro['emails_per_batch'] ) : 10,
			'batch_duration'           => isset( $pro['batch_duration'] ) ? absint( $pro['batch_duration'] ) : 5,
			'enable_quota'             => ! empty( $pro['enable_quota'] ),
			'advanced_quota_settings'  => ! empty( $pro['advanced_quota_settings'] ),
			'quota_value'              => isset( $pro['quota_value'] ) ? absint( $pro['quota_value'] ) : 500,
			'quota_duration'           => self::sanitizeQuotaDuration( (string) ( $pro['quota_duration'] ?? 'd' ) ),
			'auto_retry_enabled'       => ! empty( $pro['enable_auto_retry'] ),
			'auto_retry_max_attempts'  => isset( $pro['times_to_retry'] ) ? absint( $pro['times_to_retry'] ) : 3,
		);
	}

	/**
	 * Auto-retry slice for Email Logs API (same storage keys).
	 *
	 * @return array{enable_auto_retry:int,times_to_retry:int}
	 */
	public static function autoRetryForLogsApi( bool $pro_active ): array {
		if ( ! $pro_active ) {
			return array(
				'enable_auto_retry' => 0,
				'times_to_retry'    => 3,
			);
		}

		$pro = self::mergeWithDefaults( self::readRaw() );

		return array(
			'enable_auto_retry' => ! empty( $pro['enable_auto_retry'] ) ? 1 : 0,
			'times_to_retry'    => isset( $pro['times_to_retry'] ) ? absint( $pro['times_to_retry'] ) : 3,
		);
	}

	/**
	 * @param array<string,mixed> $payload REST body (queue or logs field names).
	 * @return array{success:bool,message?:string,patch?:array<string,mixed>}
	 */
	public static function validateAndBuildPatch( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'enable_advance_delivery', $payload ) ) {
			$patch['enable_advance_delivery'] = ! empty( $payload['enable_advance_delivery'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'emails_per_batch', $payload ) ) {
			$value = absint( $payload['emails_per_batch'] );
			if ( $value < 1 ) {
				return array(
					'success' => false,
					'message' => __( 'Emails per batch must be at least 1.', 'post-smtp' ),
				);
			}
			$patch['emails_per_batch'] = $value;
		}

		if ( array_key_exists( 'batch_duration', $payload ) ) {
			$value = absint( $payload['batch_duration'] );
			if ( $value < 1 ) {
				return array(
					'success' => false,
					'message' => __( 'Duration between batches must be at least 1 second.', 'post-smtp' ),
				);
			}
			$patch['batch_duration'] = $value;
		}

		if ( array_key_exists( 'enable_quota', $payload ) ) {
			$patch['enable_quota'] = ! empty( $payload['enable_quota'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'advanced_quota_settings', $payload ) ) {
			$patch['advanced_quota_settings'] = ! empty( $payload['advanced_quota_settings'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'quota_value', $payload ) ) {
			$value = absint( $payload['quota_value'] );
			if ( $value < 1 ) {
				return array(
					'success' => false,
					'message' => __( 'Email quota value must be at least 1.', 'post-smtp' ),
				);
			}
			$patch['quota_value'] = $value;
		}

		if ( array_key_exists( 'quota_duration', $payload ) ) {
			$duration = self::sanitizeQuotaDuration( (string) $payload['quota_duration'] );
			if ( ! in_array( $duration, self::QUOTA_DURATIONS, true ) ) {
				return array(
					'success' => false,
					'message' => __( 'Email quota duration is invalid.', 'post-smtp' ),
				);
			}
			$patch['quota_duration'] = $duration;
		}

		if ( array_key_exists( 'auto_retry_enabled', $payload ) ) {
			$patch['enable_auto_retry'] = ! empty( $payload['auto_retry_enabled'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'auto_retry_max_attempts', $payload ) ) {
			$attempts = absint( $payload['auto_retry_max_attempts'] );
			if ( $attempts < 1 ) {
				return array(
					'success' => false,
					'message' => __( 'Times to retry must be at least 1.', 'post-smtp' ),
				);
			}
			if ( $attempts > 20 ) {
				$attempts = 20;
			}
			$patch['times_to_retry'] = $attempts;
		}

		return array(
			'success' => true,
			'patch'   => $patch,
		);
	}

	/**
	 * @param array<string,mixed> $patch
	 * @return array<string,mixed> merged stored option
	 */
	public static function savePatch( array $patch ): array {
		$settings = self::readRaw();
		$merged   = array_merge( $settings, $patch );

		if ( empty( $merged['quota_duration'] ) ) {
			$merged['quota_duration'] = 'd';
		}

		update_option( self::OPTION, $merged );

		return $merged;
	}

	public static function wasAdvanceDeliveryEnabled(): bool {
		$raw = self::readRaw();
		return ! empty( $raw['enable_advance_delivery'] );
	}

	public static function sanitizeQuotaDuration( string $duration ): string {
		$key = sanitize_key( $duration );
		return in_array( $key, self::QUOTA_DURATIONS, true ) ? $key : 'd';
	}

	/**
	 * Free-tier preview sections for Email Queue GET.
	 *
	 * @return list<array{id:string,title:string,description:string,image_url:string}>
	 */
	public static function previewSections(): array {
		$base = defined( 'POST_SMTP_URL' ) ? POST_SMTP_URL . '/admin-app/build/' : '';

		/**
		 * @param list<array{id:string,title:string,description:string,image_url:string}> $sections
		 */
		return apply_filters(
			'post_smtp_settings_email_queue_preview_sections',
			array(
				array(
					'id'          => 'advanced_delivery',
					'title'       => __( 'Advanced Delivery', 'post-smtp' ),
					'description' => __( 'Queue outgoing emails and send them automatically in the background to support higher email volumes.', 'post-smtp' ),
					'image_url'   => $base . 'email-queue-preview-advanced.png',
				),
				array(
					'id'          => 'batch_settings',
					'title'       => __( 'Batch Settings', 'post-smtp' ),
					'description' => __( 'Control how many emails are sent in each batch and the waiting time between batches to manage server load.', 'post-smtp' ),
					'image_url'   => $base . 'email-queue-preview-batch.png',
				),
				array(
					'id'          => 'quota_settings',
					'title'       => __( 'Quota Settings', 'post-smtp' ),
					'description' => __( 'Set email sending limits for an hour, day, week, or month. Additional queued emails wait until the next available quota period.', 'post-smtp' ),
					'image_url'   => $base . 'email-queue-preview-quota.png',
				),
				array(
					'id'          => 'auto_retry',
					'title'       => __( 'Auto Retry', 'post-smtp' ),
					'description' => __( 'Automatically retry failed email sends using a configurable retry limit to improve delivery reliability.', 'post-smtp' ),
					'image_url'   => $base . 'email-queue-preview-retry.png',
				),
			)
		);
	}
}
