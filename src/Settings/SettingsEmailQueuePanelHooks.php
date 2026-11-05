<?php
/**
 * Email Queue settings panel extension points (Pro Advanced Delivery & Logs).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters:
 * - post_smtp_settings_email_queue_is_pro — bool for REST `is_pro`
 * - post_smtp_settings_email_queue_locked — entire Email Queue tab locked without Pro
 * - post_smtp_settings_email_queue_panel_config — upgrade_url, upgrade_label (second arg: locked)
 * - post_smtp_settings_email_queue_get — full GET payload (second arg: service instance)
 * - post_smtp_settings_email_queue_save_result — short-circuit or extend save (second arg: payload)
 *
 * Actions:
 * - post_smtp_settings_email_queue_locked_save_attempt — save while panel locked
 *
 * Revamp mail queue (Pro):
 * - post_smtp_revamp_should_queue_mail — bool, second arg wp_mail $atts
 * - post_smtp_revamp_queue_log_status — string status, second arg $atts
 */
final class SettingsEmailQueuePanelHooks {

	public static function init(): void {
		add_filter( 'post_smtp_settings_email_queue_panel_config', array( self::class, 'defaultPanelConfig' ), 5, 2 );
	}

	public static function isProForApi(): bool {
		/**
		 * @param bool $is_pro Default Pro detection for Email Queue settings.
		 */
		return (bool) apply_filters( 'post_smtp_settings_email_queue_is_pro', self::defaultIsPro() );
	}

	public static function isPanelLocked(): bool {
		/**
		 * @param bool $locked Whether the Email Queue settings tab is Pro-only.
		 */
		return (bool) apply_filters(
			'post_smtp_settings_email_queue_locked',
			! self::isProForApi()
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function panelConfig(): array {
		$locked = self::isPanelLocked();

		/**
		 * @param array<string,mixed> $config Panel config for React.
		 * @param bool                $locked Panel locked state.
		 */
		return apply_filters(
			'post_smtp_settings_email_queue_panel_config',
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
