<?php
/**
 * Email logs REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\EmailLogs\EmailLogCapabilities;
use PostSMTP\EmailLogs\EmailLogService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmailLogsController extends BaseController {

	private EmailLogService $service;

	public function __construct() {
		$this->service = new EmailLogService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/email-logs/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getSettings' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/email-logs/export',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'exportLogs' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/email-logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'listLogs' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'deleteLogs' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/email-logs/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getLog' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/email-logs/(?P<id>\d+)/resend',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'resendLog' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function checkPermission(): bool {
		return EmailLogCapabilities::can_manage();
	}

	public function listLogs( WP_REST_Request $request ) {
		$params = array(
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
			'search'   => $request->get_param( 'search' ) ?? $request->get_param( 'q' ),
			'status'   => $request->get_param( 'status' ),
			'mailer'   => $request->get_param( 'mailer' ),
			'form'     => $request->get_param( 'form' ),
			'date'     => $request->get_param( 'date' ),
		);

		return rest_ensure_response( $this->service->list_logs( $params ) );
	}

	public function getLog( WP_REST_Request $request ) {
		$id  = (int) $request->get_param( 'id' );
		$log = $this->service->get_log( $id );
		if ( null === $log ) {
			return new \WP_Error( 'post_smtp_log_not_found', __( 'Log not found.', 'post-smtp' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $log );
	}

	public function deleteLogs( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		$ids  = is_array( $body ) && isset( $body['ids'] ) ? $body['ids'] : $request->get_param( 'ids' );
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		$result = $this->service->delete_logs( $ids );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'post_smtp_delete_failed', $result['message'] ?? __( 'Delete failed.', 'post-smtp' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function exportLogs( WP_REST_Request $request ) {
		$params = array(
			'format'   => $request->get_param( 'format' ),
			'ids'      => $request->get_param( 'ids' ),
			'search'   => $request->get_param( 'search' ) ?? $request->get_param( 'q' ),
			'status'   => $request->get_param( 'status' ),
			'mailer'   => $request->get_param( 'mailer' ),
			'form'     => $request->get_param( 'form' ),
			'date'     => $request->get_param( 'date' ),
		);

		$result = $this->service->export_logs( $params );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'post_smtp_export_failed', $result['message'] ?? __( 'Export failed.', 'post-smtp' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function resendLog( WP_REST_Request $request ) {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->resend( $id );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'post_smtp_resend_failed', $result['message'] ?? __( 'Resend failed.', 'post-smtp' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function getSettings() {
		return rest_ensure_response( $this->service->get_settings() );
	}
}
