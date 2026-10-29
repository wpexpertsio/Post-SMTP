<?php
/**
 * Settings REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Settings\SettingsAdvancedService;
use PostSMTP\Settings\SettingsEmailLogsService;
use PostSMTP\Settings\SettingsEmailQueueService;
use PostSMTP\Settings\SettingsEmailReportingService;
use PostSMTP\Settings\SettingsGeneralService;
use PostSMTP\Settings\SettingsSecurityService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsController extends BaseController {

	private SettingsGeneralService $general;

	private SettingsEmailLogsService $email_logs;

	private SettingsEmailQueueService $email_queue;

	private SettingsSecurityService $security;

	private SettingsAdvancedService $advanced;

	private SettingsEmailReportingService $email_reporting;

	public function __construct() {
		$this->general          = new SettingsGeneralService();
		$this->email_logs       = new SettingsEmailLogsService();
		$this->email_queue      = new SettingsEmailQueueService();
		$this->security         = new SettingsSecurityService();
		$this->advanced         = new SettingsAdvancedService();
		$this->email_reporting  = new SettingsEmailReportingService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/settings/general',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getGeneral' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveGeneral' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/email-logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getEmailLogs' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveEmailLogs' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/email-queue',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getEmailQueue' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveEmailQueue' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/security',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getSecurity' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveSecurity' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/advanced',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getAdvanced' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveAdvanced' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/email-reporting',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getEmailReporting' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveEmailReporting' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getGeneral() {
		return rest_ensure_response( $this->general->getGeneral() );
	}

	public function saveGeneral( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body   = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result = $this->general->saveGeneral( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	public function getEmailLogs() {
		return rest_ensure_response( $this->email_logs->getEmailLogs() );
	}

	public function saveEmailLogs( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body    = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result  = $this->email_logs->saveEmailLogs( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	public function getEmailQueue() {
		return rest_ensure_response( $this->email_queue->getEmailQueue() );
	}

	public function saveEmailQueue( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body    = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result  = $this->email_queue->saveEmailQueue( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	public function getSecurity() {
		return rest_ensure_response( $this->security->getSecurity() );
	}

	public function saveSecurity( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body    = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result  = $this->security->saveSecurity( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	public function getAdvanced() {
		return rest_ensure_response( $this->advanced->getAdvanced() );
	}

	public function saveAdvanced( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body    = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result  = $this->advanced->saveAdvanced( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	public function getEmailReporting() {
		return rest_ensure_response( $this->email_reporting->getEmailReporting() );
	}

	public function saveEmailReporting( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$body    = $request->get_json_params();
		$payload = is_array( $body ) ? $body : array();
		$result  = $this->email_reporting->saveEmailReporting( $payload );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_settings_save_failed',
				$result['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}
}
