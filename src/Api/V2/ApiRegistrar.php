<?php
/**
 * Registers post-smtp/v2 REST routes.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\EmailLogs\EmailLogService;
use PostSMTP\Settings\SettingsEmailLogsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApiRegistrar {

	public static function init(): void {
		SettingsEmailLogsService::init();
		EmailLogService::init();

		add_action(
			'rest_api_init',
			static function () {
				( new ConnectionsController() )->register_routes();
				( new EmailLogsController() )->register_routes();
				( new MigrationController() )->register_routes();
				( new NotificationsController() )->register_routes();
				( new ToolsController() )->register_routes();
				( new MobileAppController() )->register_routes();
				( new SettingsController() )->register_routes();
				( new DashboardReportsTrackingController() )->register_routes();
				( new DashboardReportsEmailQuotaController() )->register_routes();
			}
		);
	}
}
