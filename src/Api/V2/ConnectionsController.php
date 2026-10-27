<?php
/**
 * Connections REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Providers\CatalogProviderSchema;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionsController extends BaseController {

	private ConnectionService $service;

	public function __construct() {
		$this->service = new ConnectionService();
	}

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
					'callback'            => array( $this, 'createConnection' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/connections/order',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reorderConnections' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/connections/(?P<index>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'updateConnection' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'deleteConnection' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/connections/(?P<index>\d+)/fallback',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'patchFallback' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/connections/bulk',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'saveConnectionsBulk' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/mailer-catalog',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getMailerCatalog' ),
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
		return rest_ensure_response( $this->service->listForAdmin() );
	}

	public function getMailerCatalog() {
		return rest_ensure_response(
			array(
				'items'  => MailerCatalog::all(),
				'limits' => \PostSMTP\Connections\ConnectionLimits::get(),
			)
		);
	}

	public function createConnection( WP_REST_Request $request ) {
		$row = $request->get_json_params();
		if ( ! is_array( $row ) ) {
			$row = array();
		}
		$result = $this->service->create( $row );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'connection_create_failed', $result['message'] ?? '', array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function updateConnection( WP_REST_Request $request ) {
		$index = (int) $request['index'];
		$row   = $request->get_json_params();
		if ( ! is_array( $row ) ) {
			$row = array();
		}
		$result = $this->service->update( $index, $row );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'connection_update_failed', $result['message'] ?? '', array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function deleteConnection( WP_REST_Request $request ) {
		$index  = (int) $request['index'];
		$result = $this->service->delete( $index );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'connection_delete_failed', $result['message'] ?? '', array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function reorderConnections( WP_REST_Request $request ) {
		$body  = $request->get_json_params();
		$order = isset( $body['order'] ) && is_array( $body['order'] ) ? $body['order'] : array();
		$result = $this->service->reorder( $order );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'connection_reorder_failed', $result['message'] ?? '', array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	public function patchFallback( WP_REST_Request $request ) {
		$index   = (int) $request['index'];
		$body    = $request->get_json_params();
		$enabled = ! empty( $body['enabled'] );
		$result  = $this->service->setFallbackEnabled( $index, $enabled );
		if ( empty( $result['success'] ) ) {
			return new \WP_Error( 'connection_fallback_failed', $result['message'] ?? '', array( 'status' => 400 ) );
		}
		return rest_ensure_response( $result );
	}

	/** @deprecated Bulk save — prefer granular endpoints. */
	public function saveConnectionsBulk( WP_REST_Request $request ) {
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

		return rest_ensure_response( $this->service->listForAdmin() );
	}

	public function getProviders() {
		$registry = new ProviderRegistry();
		$schemas  = $registry->schemas();
		foreach ( MailerCatalog::all() as $item ) {
			$slug = (string) ( $item['slug'] ?? '' );
			if ( $slug && ! isset( $schemas[ $slug ] ) ) {
				$schemas[ $slug ] = CatalogProviderSchema::forSlug( $slug );
			}
		}
		return rest_ensure_response( $schemas );
	}
}
