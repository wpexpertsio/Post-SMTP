<?php
/**
 * Tools REST endpoints (Send Test Email first).
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Tools\CheckConnectionService;
use PostSMTP\Tools\DiagnosticReportService;
use PostSMTP\Tools\ImportExportSettingsService;
use PostSMTP\Tools\ResetPluginService;
use PostSMTP\Tools\TestEmailService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ToolsController extends BaseController {

	private TestEmailService $service;

	private CheckConnectionService $checkService;

	private DiagnosticReportService $diagnosticService;

	private ImportExportSettingsService $importExportService;

	private ResetPluginService $resetService;

	public function __construct() {
		$this->service             = new TestEmailService();
		$this->checkService        = new CheckConnectionService();
		$this->diagnosticService   = new DiagnosticReportService();
		$this->importExportService = new ImportExportSettingsService();
		$this->resetService        = new ResetPluginService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/tools/send-test-email',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getSendTestEmail' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postSendTestEmail' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/check-connection',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getCheckConnection' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/check-connection/(?P<connection_id>\d+)/run',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postCheckConnectionRun' ),
					'permission_callback' => array( $this, 'checkPermission' ),
					'args'                => array(
						'connection_id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/diagnostic-report',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getDiagnosticReport' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postDiagnosticReportRun' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/diagnostic-report/send',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postDiagnosticReportSend' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/import-export',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getImportExport' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/import-export/export',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postImportExportExport' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/import-export/import',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postImportExportImport' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/reset-plugin',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getResetPlugin' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/reset-plugin/reset',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postResetPlugin' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/send-test-email/preview',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'postPreview' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function checkPermission(): bool {
		if ( class_exists( '\Postman' ) ) {
			return current_user_can( \Postman::MANAGE_POSTMAN_CAPABILITY_NAME );
		}

		return current_user_can( 'manage_options' );
	}

	public function getSendTestEmail() {
		return rest_ensure_response( $this->service->getBootstrap() );
	}

	public function postSendTestEmail( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->service->send( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function getCheckConnection() {
		return rest_ensure_response( $this->checkService->getBootstrap() );
	}

	public function postCheckConnectionRun( WP_REST_Request $request ) {
		$connection_id = (int) $request['connection_id'];
		$result        = $this->checkService->runCheck( $connection_id );

		if ( empty( $result['success'] ) ) {
			$status = 'already_running' === ( $result['code'] ?? '' ) ? 409 : 400;
			return new \WP_REST_Response( $result, $status );
		}

		return rest_ensure_response( $result );
	}

	public function getDiagnosticReport() {
		return rest_ensure_response( $this->diagnosticService->getBootstrap() );
	}

	public function postDiagnosticReportRun() {
		return rest_ensure_response( $this->diagnosticService->runReport() );
	}

	public function postDiagnosticReportSend( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->diagnosticService->sendReport( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function postPreview( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->service->preview( is_array( $body ) ? $body : array() );

		return rest_ensure_response( $result );
	}

	public function getImportExport() {
		return rest_ensure_response( $this->importExportService->getBootstrap() );
	}

	public function postImportExportExport() {
		$result = $this->importExportService->exportSettings();
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function postImportExportImport( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->importExportService->importSettings( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}

	public function getResetPlugin() {
		return rest_ensure_response( $this->resetService->getBootstrap() );
	}

	public function postResetPlugin( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$result = $this->resetService->reset( is_array( $body ) ? $body : array() );
		$status = ! empty( $result['success'] ) ? 200 : 400;

		return new \WP_REST_Response( $result, $status );
	}
}
