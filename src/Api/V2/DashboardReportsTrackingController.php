<?php
/**
 * Dashboard Reports & Tracking REST (data supplied by Pro via filter).
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DashboardReportsTrackingController extends BaseController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/dashboard/reports-tracking',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getReportsTracking' ),
					'permission_callback' => array( $this, 'checkPermission' ),
					'args'                => array(
						'range' => array(
							'type'              => 'string',
							'default'           => '7',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);
	}

	public function getReportsTracking( WP_REST_Request $request ) {
		$range   = (string) $request->get_param( 'range' );
		$allowed = array( '7', '14', '30' );
		if ( ! in_array( $range, $allowed, true ) ) {
			$range = '7';
		}

		$context = array(
			'range' => $range,
		);

		$payload = array(
			'enabled' => false,
			'range'   => $range,
			'totals'  => array(
				'delivered' => 0,
				'opened'    => 0,
				'failed'    => 0,
			),
			'chart'   => array(
				'labels'    => array(),
				'delivered' => array(),
				'opened'    => array(),
				'failed'    => array(),
			),
		);

		/**
		 * Dashboard Reports & Tracking API payload (Pro fills when extension is active).
		 *
		 * @param array<string,mixed> $payload
		 * @param array<string,mixed> $context  Keys: range (7|14|30).
		 */
		$payload = apply_filters( 'post_smtp_dashboard_reports_tracking_response', $payload, $context );

		return rest_ensure_response( $payload );
	}
}
