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
				'total'     => 0,
				'delivered' => 0,
				'opened'    => 0,
				'failed'    => 0,
			),
			'chart'   => array(
				'labels'    => array(),
				'total'     => array(),
				'delivered' => array(),
				'opened'    => array(),
				'failed'    => array(),
			),
			'by_subject' => array(
				'tracking_available' => false,
				'rows'               => array(),
			),
			'email_quota' => array(
				'visible'        => false,
				'emails_used'    => 0,
				'limit'          => 0,
				'duration_key'   => '',
				'duration_label' => '',
				'percent'        => 0,
				'settings_url'   => '',
				'helper_text'    => '',
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
