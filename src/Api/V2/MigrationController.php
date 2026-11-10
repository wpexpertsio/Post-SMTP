<?php
/**
 * Migration REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Kernel\Migration\ConnectionMigrator;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MigrationController extends BaseController {

	private ConnectionMigrator $migrator;

	public function __construct() {
		$this->migrator = new ConnectionMigrator();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/migration/preview',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'preview' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/migration/start',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/migration/rollback',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rollback' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/migration/rollback-status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rollbackStatus' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/migration/revert-legacy',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'revertLegacy' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function preview() {
		return rest_ensure_response( $this->migrator->preview() );
	}

	public function start( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return rest_ensure_response( $this->migrator->migrate() );
	}

	public function rollback( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return rest_ensure_response( $this->migrator->rollback() );
	}

	public function rollbackStatus() {
		return rest_ensure_response( $this->migrator->rollbackStatus() );
	}

	public function revertLegacy( WP_REST_Request $request ) {
		$nonce_check = $this->requireRestNonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$force = (bool) $request->get_param( 'force_ui_only' );

		return rest_ensure_response( $this->migrator->revertToLegacy( $force ) );
	}
}
