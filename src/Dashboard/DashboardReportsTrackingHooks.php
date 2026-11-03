<?php
/**
 * Dashboard “Reports & Tracking” extension points (Pro implements UI + data).
 *
 * @package PostSMTP\Dashboard
 */

namespace PostSMTP\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks for the revamp dashboard Reports & Tracking panel (Figma 346:6649).
 *
 * Filters (Pro):
 * - post_smtp_dashboard_reports_tracking_enabled — bool, default false.
 * - post_smtp_dashboard_reports_tracking_response — REST payload (second arg: context array with `range`).
 * - post_smtp_dashboard_reports_tracking_config — merged into React bootstrap `dashboardReportsTracking`.
 *
 * Actions (Pro):
 * - post_smtp_dashboard_reports_tracking_enqueue — fired when free admin SPA scripts load on dashboard.
 */
final class DashboardReportsTrackingHooks {

	public static function init(): void {
		add_filter( 'post_smtp_admin_config', array( self::class, 'mergeAdminConfig' ), 20 );
		add_action( 'post_smtp_admin_dashboard_enqueue', array( self::class, 'onDashboardEnqueue' ) );
	}

	/**
	 * @param array<string,mixed> $config Admin bootstrap.
	 * @return array<string,mixed>
	 */
	public static function mergeAdminConfig( array $config ): array {
		$defaults = array(
			'enabled' => self::isEnabled(),
		);

		/**
		 * @param array<string,mixed> $panel_config
		 */
		$config['dashboardReportsTracking'] = apply_filters(
			'post_smtp_dashboard_reports_tracking_config',
			$defaults
		);

		return $config;
	}

	public static function isEnabled(): bool {
		/**
		 * Whether the Reports & Tracking dashboard panel should mount (Pro registers the React slot).
		 *
		 * @param bool $enabled Default false.
		 */
		return (bool) apply_filters( 'post_smtp_dashboard_reports_tracking_enabled', false );
	}

	public static function onDashboardEnqueue(): void {
		/**
		 * Pro: enqueue dashboard panel script/styles after `post-smtp-admin-app`.
		 */
		do_action( 'post_smtp_dashboard_reports_tracking_enqueue' );
	}
}
