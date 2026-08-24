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

	public function register_routes(): void {
		$migrator = new ConnectionMigrator();

		register_rest_route(
			$this->namespace,
			'/migration/preview',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => static function () use ( $migrator ) {
						return rest_ensure_response( $migrator->preview() );
					},
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
					'callback'            => static function () use ( $migrator ) {
						return rest_ensure_response( $migrator->migrate() );
					},
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
					'callback'            => static function () use ( $migrator ) {
						return rest_ensure_response( $migrator->rollback() );
					},
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
					'callback'            => static function () use ( $migrator ) {
						return rest_ensure_response( $migrator->rollbackStatus() );
					},
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
					'callback'            => static function ( WP_REST_Request $request ) use ( $migrator ) {
						$force = (bool) $request->get_param( 'force_ui_only' );
						return rest_ensure_response( $migrator->revertToLegacy( $force ) );
					},
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}
}
