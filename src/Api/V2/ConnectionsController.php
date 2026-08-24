<?php
/**
 * Connections REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionsController extends BaseController {

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/connections',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getConnections' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'saveConnections' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/providers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getProviders' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getConnections() {
		$repo = ConnectionRepositoryFactory::make();
		return rest_ensure_response(
			array(
				'connections' => $repo->getConnections(),
				'options'     => $repo->getOptions(),
				'is_legacy'   => ConnectionRepositoryFactory::isLegacyMode(),
			)
		);
	}

	public function saveConnections( WP_REST_Request $request ) {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $request->get_param( 'connections' );

		if ( ! is_array( $connections ) ) {
			return new \WP_Error( 'invalid_connections', __( 'Invalid connections payload.', 'post-smtp' ), array( 'status' => 400 ) );
		}

		$sanitized = array_map(
			static function ( $row ) {
				return ConnectionSchema::sanitizeRow( (array) $row );
			},
			$connections
		);

		$registry = new ProviderRegistry();
		foreach ( $sanitized as $row ) {
			$slug     = (string) ( $row['provider'] ?? '' );
			$provider = $registry->create( $slug );
			$result   = $provider->authenticate( $row );
			if ( empty( $result['success'] ) ) {
				return new \WP_Error(
					'provider_auth_failed',
					$result['message'] ?? __( 'Provider authentication failed.', 'post-smtp' ),
					array( 'status' => 400 )
				);
			}
		}

		$repo->saveConnections( $sanitized );

		return rest_ensure_response(
			array(
				'success'     => true,
				'connections' => $repo->getConnections(),
			)
		);
	}

	public function getProviders() {
		$registry = new ProviderRegistry();
		return rest_ensure_response( $registry->schemas() );
	}
}
