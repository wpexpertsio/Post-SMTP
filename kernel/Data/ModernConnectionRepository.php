<?php
/**
 * Reads credentials from modern postman_connections storage.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModernConnectionRepository implements ConnectionRepositoryInterface {

	public function getConnections(): array {
		$connections = get_option( ConnectionSchema::OPTION_CONNECTIONS, array() );
		return is_array( $connections ) ? $connections : array();
	}

	public function getConnection( $id ): array {
		if ( null === $id || '' === $id ) {
			return array();
		}
		$connections = $this->getConnections();
		return ( isset( $connections[ $id ] ) && is_array( $connections[ $id ] ) ) ? $connections[ $id ] : array();
	}

	public function saveConnections( array $connections ): bool {
		return update_option( ConnectionSchema::OPTION_CONNECTIONS, $connections );
	}

	public function getOptions(): array {
		$options = get_option( ConnectionSchema::OPTION_OPTIONS, array() );
		return is_array( $options ) ? $options : array();
	}

	public function saveOptions( array $options ): bool {
		return update_option( ConnectionSchema::OPTION_OPTIONS, $options );
	}

	public function getPrimaryField( string $field, callable $legacy_getter, string $default = '' ): string {
		$options    = $this->getOptions();
		$primary_id = $options['selected_primary'] ?? $options['primary_connection'] ?? 0;
		$connection = $this->getConnection( $primary_id );

		if ( isset( $connection[ $field ] ) && '' !== (string) $connection[ $field ] ) {
			return (string) $connection[ $field ];
		}

		return $default;
	}
}
