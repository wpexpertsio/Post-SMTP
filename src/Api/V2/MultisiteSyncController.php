<?php
/**
 * Multisite sync REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Multisite\MultisiteSyncService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteSyncController extends BaseController {

	private MultisiteSyncService $service;

	public function __construct() {
		$this->service = new MultisiteSyncService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/multisite/sync/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getStatus' ),
					'permission_callback' => array( $this, 'checkNetworkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/multisite/sync',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postSync' ),
					'permission_callback' => array( $this, 'checkNetworkPermission' ),
				),
			)
		);
	}

	public function checkNetworkPermission(): bool {
		return current_user_can( 'manage_network' );
	}

	public function getStatus() {
		return rest_ensure_response( $this->service->getStatus() );
	}

	public function postSync( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		if ( ! is_multisite() ) {
			return new \WP_Error(
				'multisite_required',
				__( 'Multisite is not enabled.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->service->syncAllChildSites();
		$status = empty( $result['success'] ) && isset( $result['failed_count'] ) && (int) $result['failed_count'] > 0 ? 207 : 200;
		if ( empty( $result['success'] ) && ! isset( $result['success_count'] ) ) {
			$status = 400;
		}

		return new \WP_REST_Response( $result, $status );
	}
}