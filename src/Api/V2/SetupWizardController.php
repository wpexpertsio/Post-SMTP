<?php
/**
 * Setup wizard REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\SetupWizard\SetupWizardService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SetupWizardController extends BaseController {

	private SetupWizardService $service;

	public function __construct() {
		$this->service = new SetupWizardService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/setup-wizard',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getBootstrap' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/setup-wizard/validate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postValidate' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/setup-wizard/draft',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'putDraft' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'deleteDraft' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/setup-wizard/test-email',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postTestEmail' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/setup-wizard/domain-auth',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postDomainAuth' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/setup-wizard/complete',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postComplete' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getBootstrap() {
		return rest_ensure_response( $this->service->bootstrap() );
	}

	public function postValidate( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$result = $this->service->validate( $body );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'setup_wizard_validate_failed',
				$result['message'] ?? __( 'Validation failed.', 'post-smtp' ),
				array( 'status' => 400, 'data' => $result )
			);
		}

		return rest_ensure_response( $result );
	}

	public function putDraft( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$result = $this->service->saveDraft( $body );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'setup_wizard_draft_failed',
				$result['message'] ?? __( 'Could not save draft.', 'post-smtp' ),
				array( 'status' => 400, 'data' => $result )
			);
		}

		return rest_ensure_response( $result );
	}

	public function deleteDraft( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return rest_ensure_response( $this->service->discardDraft() );
	}

	public function postTestEmail( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}

		$result = $this->service->sendTestEmail( $body );
		$status = empty( $result['success'] ) ? 400 : 200;

		return new \WP_REST_Response( $result, $status );
	}

	public function postDomainAuth( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$result = $this->service->domainAuth();
		$status = empty( $result['success'] ) ? 400 : 200;

		return new \WP_REST_Response( $result, $status );
	}

	public function postComplete( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$result = $this->service->complete();
		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'setup_wizard_complete_failed',
				$result['message'] ?? __( 'Could not complete setup.', 'post-smtp' ),
				array( 'status' => 400, 'data' => $result )
			);
		}

		return rest_ensure_response( $result );
	}
}