<?php
/**
 * Mobile App REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Mobile\MobileAppService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MobileAppController extends BaseController {

	private MobileAppService $service;

	public function __construct() {
		$this->service = new MobileAppService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/mobile-app',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getMobileApp' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/mobile-app/regenerate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postRegenerate' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/mobile-app/disconnect',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postDisconnect' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/mobile-app/notifications',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postNotifications' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getMobileApp() {
		return rest_ensure_response( $this->service->getBootstrap() );
	}

	public function postRegenerate() {
		$result = $this->service->regenerate();
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function postDisconnect( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->service->disconnectDevice( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function postNotifications( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->service->updateNotifications( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}
}
