<?php
/**
 * Reports Email Quota REST (data supplied by Pro via filter).
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DashboardReportsEmailQuotaController extends BaseController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/dashboard/reports-email-quota',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getEmailQuota' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getEmailQuota() {
		$payload = array(
			'visible'         => false,
			'emails_used'     => 0,
			'limit'           => 0,
			'duration_key'    => '',
			'duration_label'  => '',
			'percent'         => 0,
			'settings_url'    => '',
			'helper_text'     => __( 'Extra emails wait until the next period.', 'post-smtp' ),
		);

		/**
		 * Reports Email Quota card payload (Pro when Advanced Quota is enabled).
		 *
		 * @param array<string,mixed> $payload
		 */
		$payload = apply_filters( 'post_smtp_dashboard_reports_email_quota_response', $payload );

		return rest_ensure_response( $payload );
	}
}
