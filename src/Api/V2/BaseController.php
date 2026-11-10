<?php
/**
 * REST API base controller.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use WP_REST_Controller;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class BaseController extends WP_REST_Controller {

	protected $namespace = 'post-smtp/v2';

	public function checkPermission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function checkNonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * CSRF guard for state-changing REST handlers (admin cookie auth).
	 *
	 * @return true|\WP_Error
	 */
	protected function requireRestNonce( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Invalid nonce.', 'post-smtp' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
