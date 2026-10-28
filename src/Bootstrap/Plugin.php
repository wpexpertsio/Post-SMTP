<?php
/**
 * Modern plugin bootstrap.
 *
 * @package PostSMTP\Bootstrap
 */

namespace PostSMTP\Bootstrap;

use PostSMTP\Api\V2\ApiRegistrar;
use PostSMTP\Connections\GmailConfigurePanelHooks;
use PostSMTP\Kernel\Libraries\LibraryLoader;
use PostSMTP\Notifications\FailureNotificationBridge;
use PostSMTP\Notifications\NotificationTeamsPanelHooks;
use PostSMTP\Notifications\NotificationTwilioPanelHooks;
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
				FailureNotificationBridge::init();
				GmailConfigurePanelHooks::init();
				NotificationTeamsPanelHooks::init();
				NotificationTwilioPanelHooks::init();
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
