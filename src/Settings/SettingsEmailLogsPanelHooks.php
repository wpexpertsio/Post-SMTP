<?php
/**
 * Email Logs settings panel extension points (free vs Pro).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters:
 * - post_smtp_settings_email_logs_is_pro — bool for REST `is_pro` (Pro unlocks auto-retry UI)
 * - post_smtp_settings_email_logs_auto_retry_locked — bool, default when not Pro
 * - post_smtp_settings_email_logs_pro_features_locked — bool (attachments, filters, retention, export)
 * - post_smtp_settings_email_logs_panel_config — upgrade_url, upgrade_label (second arg: auto_retry_locked)
 * - post_smtp_settings_email_logs_get — full GET payload (second arg: service instance)
 * - post_smtp_settings_email_logs_save_result — after core save (second arg: payload)
 *
 * Actions:
 * - post_smtp_settings_email_logs_auto_retry_locked_save_attempt — save included auto-retry while locked
 */
final class SettingsEmailLogsPanelHooks {

	public const SAVE_ATTACHMENTS_OPTION = 'post_smtp_log_save_attachments';

	public const ADVANCED_LOG_FILTER_OPTION = 'postman_advanced_log_filter';

	public static function init(): void {
		add_filter( 'post_smtp_settings_email_logs_panel_config', array( self::class, 'defaultPanelConfig' ), 5, 2 );
		add_filter( 'post_smtp_log_save_attachments_enabled', array( self::class, 'filterSaveAttachmentsEnabled' ), 5 );
	}

	public static function filterSaveAttachmentsEnabled( bool $enabled ): bool {
		unset( $enabled );

		if ( self::isProFeaturesLocked() ) {
			return false;
		}

		$stored = get_option( self::SAVE_ATTACHMENTS_OPTION, null );
		if ( null === $stored ) {
			return true;
		}

		return (bool) $stored;
	}

	/**
	 * Pro-only email log fields for REST when the panel is unlocked.
	 *
	 * @return array<string,mixed>
	 */
	public static function proFeatureValuesForApi(): array {
		if ( self::isProFeaturesLocked() ) {
			return array();
		}

		$aedl = AedlOptions::mergeWithDefaults( AedlOptions::readRaw() );
		$attachments_stored = get_option( self::SAVE_ATTACHMENTS_OPTION, null );

		return array(
			'save_attachments'    => null === $attachments_stored ? true : (bool) $attachments_stored,
			'advanced_log_filter' => (bool) get_option( self::ADVANCED_LOG_FILTER_OPTION, 0 ),
			'delete_logs_days'      => isset( $aedl['delete_logs_days'] ) ? (int) $aedl['delete_logs_days'] : 0,
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public static function saveProFeatureFields( array $payload ): void {
		if ( self::isProFeaturesLocked() ) {
			return;
		}

		if ( array_key_exists( 'save_attachments', $payload ) ) {
			update_option(
				self::SAVE_ATTACHMENTS_OPTION,
				! empty( $payload['save_attachments'] ) ? 1 : 0
			);
		}

		if ( array_key_exists( 'advanced_log_filter', $payload ) ) {
			update_option(
				self::ADVANCED_LOG_FILTER_OPTION,
				! empty( $payload['advanced_log_filter'] ) ? 1 : 0
			);
		}

		if ( array_key_exists( 'delete_logs_days', $payload ) ) {
			$validation = AedlOptions::validateAndBuildPatch(
				array(
					'delete_logs_days' => $payload['delete_logs_days'],
				)
			);
			if ( ! empty( $validation['success'] ) && ! empty( $validation['patch'] ) ) {
				AedlOptions::savePatch( $validation['patch'] );
			}
		}
	}

	public static function isProForApi(): bool {
		/**
		 * @param bool $is_pro Default Pro detection for Email Logs settings.
		 */
		return (bool) apply_filters( 'post_smtp_settings_email_logs_is_pro', self::defaultIsPro() );
	}

	public static function isAutoRetryLocked(): bool {
		/**
		 * @param bool $locked Whether auto-retry fields are read-only / upsell.
		 */
		return (bool) apply_filters(
			'post_smtp_settings_email_logs_auto_retry_locked',
			! self::isProForApi()
		);
	}

	public static function isProFeaturesLocked(): bool {
		$default_locked = ! self::isProForApi();
		if (
			$default_locked
			&& defined( 'POST_SMTP_PRO_VERSION' )
			&& class_exists( CohortResolver::class, false )
			&& CohortResolver::usesReactAdmin()
		) {
			$default_locked = false;
		}

		/**
		 * @param bool $locked Pro-only log features (attachments, advanced filter, retention, export).
		 */
		return (bool) apply_filters(
			'post_smtp_settings_email_logs_pro_features_locked',
			$default_locked
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function panelConfig(): array {
		$locked = self::isAutoRetryLocked();

		/**
		 * @param array<string,mixed> $config Panel config for React.
		 * @param bool                $locked Auto-retry locked state.
		 */
		return apply_filters(
			'post_smtp_settings_email_logs_panel_config',
			array(),
			$locked
		);
	}

	/**
	 * @param array<string,mixed> $config
	 * @return array<string,mixed>
	 */
	public static function defaultPanelConfig( array $config, bool $locked ): array {
		unset( $locked );

		return array_merge(
			array(
				'upgrade_url'   => 'https://postmansmtp.com/pricing/',
				'upgrade_label' => __( 'Upgrade to Pro', 'post-smtp' ),
			),
			$config
		);
	}

	private static function defaultIsPro(): bool {
		return defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );
	}
}
