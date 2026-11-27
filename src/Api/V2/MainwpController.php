<?php
/**
 * MainWP dashboard REST helpers for revamp admin.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\EmailLogs\EmailLogCapabilities;
use PostSMTP\MainWP\MainwpChildSitesService;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MainwpController extends BaseController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/mainwp/child-sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'childSites' ),
					'permission_callback' => array( $this, 'checkEmailLogPermission' ),
				),
			)
		);
	}

	public function checkEmailLogPermission(): bool {
		return EmailLogCapabilities::can_manage();
	}

	public function childSites() {
		$sites = ( new MainwpChildSitesService() )->list_for_rest();
		return rest_ensure_response(
			array(
				'sites' => $sites,
			)
		);
	}
}