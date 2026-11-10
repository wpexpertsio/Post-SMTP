<?php
/**
 * Modern plugin bootstrap.
 *
 * @package PostSMTP\Bootstrap
 */

namespace PostSMTP\Bootstrap;

use PostSMTP\Api\V2\ApiRegistrar;
use PostSMTP\Connections\ConnectionLegacySendBridge;
use PostSMTP\Connections\RevampProMailerSchemas;
use PostSMTP\Mail\Modern\Register as ModernMailRegister;
use PostSMTP\Connections\GmailConfigurePanelHooks;
use PostSMTP\Connections\Office365ConfigurePanelHooks;
use PostSMTP\Dashboard\DashboardReportsTrackingHooks;
use PostSMTP\Kernel\Libraries\LibraryLoader;
use PostSMTP\Notifications\FailureNotificationBridge;
use PostSMTP\Notifications\NotificationTeamsPanelHooks;
use PostSMTP\Notifications\NotificationTwilioPanelHooks;
use PostSMTP\Settings\SettingsEmailLogsPanelHooks;
use PostSMTP\Settings\SettingsEmailQueuePanelHooks;
use PostSMTP\Settings\SettingsEmailReportingPanelHooks;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/**
	 * @param string $plugin_file Main plugin file path.
	 */
	public static function init( string $plugin_file ): void {
		LegacyBridge::init();
		LibraryLoader::init();
		CohortResolver::detectOnBootstrap();

		add_action(
			'plugins_loaded',
			static function () {
				ApiRegistrar::init();
				RevampProMailerSchemas::init();
				FailureNotificationBridge::init();
				GmailConfigurePanelHooks::init();
				Office365ConfigurePanelHooks::init();
				ModernMailRegister::init();
				if ( ! CohortResolver::usesRevampMailPipeline() ) {
					ConnectionLegacySendBridge::init();
				}
				NotificationTeamsPanelHooks::init();
				NotificationTwilioPanelHooks::init();
				SettingsEmailReportingPanelHooks::init();
				SettingsEmailLogsPanelHooks::init();
				SettingsEmailQueuePanelHooks::init();
				DashboardReportsTrackingHooks::init();
			},
			20
		);

		add_action(
			'init',
			static function () {
				Router::boot();
			},
			5
		);
	}
}
