<?php
/**
 * Lightweight CLI checks for ConnectionService normalization (no WordPress bootstrap).
 *
 * Usage: php tests/connection-service-normalize.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

require dirname( __DIR__ ) . '/vendor/autoload.php';

use PostSMTP\Connections\ConnectionService;

$service = new ConnectionService();

$connections = array(
	0 => array( 'title' => 'Primary' ),
	1 => array( 'title' => 'Backup' ),
	2 => array( 'title' => 'Idle' ),
);

$options = array(
	'fallback_smtp_enabled' => 'yes',
	'selected_fallback'     => 1,
);

$normalized = $service->normalizeOnRead( $connections, $options );

assert( ! isset( $normalized[0]['fallback_enabled'] ), 'Primary row must not expose fallback_enabled' );
assert( true === $normalized[1]['fallback_enabled'], 'Legacy selected_fallback should enable row 1' );
assert( false === $normalized[2]['fallback_enabled'], 'Non-selected secondary should be disabled' );

$indices = ConnectionService::getEnabledFallbackIndices( $connections, $options );
assert( array( 1 ) === $indices, 'Only enabled fallback indices should be returned' );

$role = ConnectionService::deriveRole( 3, array_merge( $connections, array( 3 => array( 'fallback_enabled' => true ) ) ) );
assert( 'fallback' === $role['role'], 'Fourth row with toggle on should be fallback role' );

echo "connection-service-normalize: OK\n";
