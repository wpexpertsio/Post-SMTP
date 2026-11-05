<?php
/**
 * Email Logs settings panel extension points (free vs Pro).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters:
 * - post_smtp_settings_email_logs_is_pro — bool for REST `is_pro` (Pro unlocks auto-retry UI)
 * - post_smtp_settings_email_logs_auto_retry_locked — bool, default when not Pro
 * - post_smtp_settings_email_logs_panel_config — upgrade_url, upgrade_label (second arg: auto_retry_locked)
 * - post_smtp_settings_email_logs_get — full GET payload (second arg: service instance)
 * - post_smtp_settings_email_logs_save_result — after core save (second arg: payload)
 *
 * Actions:
 * - post_smtp_settings_email_logs_auto_retry_locked_save_attempt — save included auto-retry while locked
 */
final class SettingsEmailLogsPanelHooks {

	public static function init(): void {
		add_filter( 'post_smtp_settings_email_logs_panel_config', array( self::class, 'defaultPanelConfig' ), 5, 2 );
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
