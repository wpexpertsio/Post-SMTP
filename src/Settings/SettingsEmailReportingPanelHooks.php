<?php
/**
 * Email Reporting settings panel extension points (free vs Pro).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers default Email Reporting panel behavior and documents Pro extension points.
 *
 * Filters:
 * - post_smtp_settings_email_reporting_open_tracking_locked — bool, default ! class_exists( 'Post_SMTP_Report_And_Tracking' )
 * - post_smtp_settings_email_reporting_panel_config — upgrade_url, upgrade_label (second arg: open_tracking_locked)
 * - post_smtp_settings_email_reporting_open_tracking_panel — open-tracking UI block for REST (second arg: locked)
 * - post_smtp_settings_email_reporting_get — full GET payload (second arg: service instance)
 * - post_smtp_settings_email_reporting_save_result — after core weekly save (second arg: payload)
 *
 * Actions:
 * - post_smtp_settings_email_reporting_open_tracking_locked_save_attempt — when save includes pro fields while locked
 */
final class SettingsEmailReportingPanelHooks {

	public const RAT_OPTION = 'postman_rat';

	public static function init(): void {
		add_filter( 'post_smtp_settings_email_reporting_panel_config', array( self::class, 'defaultPanelConfig' ), 5, 2 );
		add_filter( 'post_smtp_settings_email_reporting_open_tracking_panel', array( self::class, 'defaultOpenTrackingPanel' ), 5, 2 );
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

	public static function isOpenTrackingLocked(): bool {
		/**
		 * Whether the Open Email Tracking block is locked in the revamp settings UI.
		 *
		 * @param bool $locked Default true when Report and Tracking extension is unavailable.
		 */
		return (bool) apply_filters(
			'post_smtp_settings_email_reporting_open_tracking_locked',
			! class_exists( 'Post_SMTP_Report_And_Tracking' )
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function getOpenTrackingPanelForApi(): array {
		$locked = self::isOpenTrackingLocked();

		$defaults = array(
			'locked'             => $locked,
			'title'              => __( 'Open Email Tracking', 'post-smtp' ),
			'description'        => __( 'See who opened your emails.', 'post-smtp' ),
			'show_pro_tag'       => true,
			'show_upgrade_cta'   => $locked,
			'toggle'             => array(
				'label'    => __( 'Track email opens', 'post-smtp' ),
				'hint'     => __( 'Add a small hidden image to your emails.', 'post-smtp' ),
				'checked'  => false,
				'disabled' => $locked,
			),
		);

		/**
		 * Open Email Tracking section for GET /settings/email-reporting.
		 *
		 * @param array<string,mixed> $panel  Panel payload.
		 * @param bool                $locked Whether open tracking is locked.
		 */
		$panel = apply_filters( 'post_smtp_settings_email_reporting_open_tracking_panel', $defaults, $locked );
		if ( ! is_array( $panel ) ) {
			$panel = $defaults;
		}

		return array_merge( $defaults, $panel );
	}

	/**
	 * @param array<string,mixed> $panel
	 * @return array<string,mixed>
	 */
	public static function defaultOpenTrackingPanel( array $panel, bool $locked ): array {
		$defaults = array(
			'locked'             => $locked,
			'title'              => __( 'Open Email Tracking', 'post-smtp' ),
			'description'        => __( 'See who opened your emails.', 'post-smtp' ),
			'show_pro_tag'       => true,
			'show_upgrade_cta'   => $locked,
			'toggle'             => array(
				'label'    => __( 'Track email opens', 'post-smtp' ),
				'hint'     => __( 'Add a small hidden image to your emails.', 'post-smtp' ),
				'checked'  => false,
				'disabled' => $locked,
			),
		);

		return array_merge( $defaults, $panel );
	}
}
