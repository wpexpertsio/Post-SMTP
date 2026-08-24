<?php
/**
 * Registers post-smtp/v2 REST routes.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApiRegistrar {

	public static function init(): void {
		add_action(
			'rest_api_init',
			static function () {
				( new ConnectionsController() )->register_routes();
				( new MigrationController() )->register_routes();
			}
		);
	}
}
