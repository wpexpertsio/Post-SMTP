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
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
