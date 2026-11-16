<?php
/**
 * Reads credentials from legacy postman_options blob.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyConnectionRepository implements ConnectionRepositoryInterface {

	public function getConnections(): array {
		return ConnectionSchema::readConnectionsOption();
	}

	public function getConnection( $id ): array {
		if ( null === $id || '' === $id ) {
			return array();
		}

		$connections = $this->getConnections();

		return ( isset( $connections[ $id ] ) && is_array( $connections[ $id ] ) ) ? $connections[ $id ] : array();
	}

	public function saveConnections( array $connections ): bool {
		return ConnectionSchema::writeConnectionsOption( $connections );
	}

	public function getOptions(): array {
		$options = get_option( ConnectionSchema::OPTION_OPTIONS, array() );
		return is_array( $options ) ? $options : array();
	}

	public function saveOptions( array $options ): bool {
		return update_option( ConnectionSchema::OPTION_OPTIONS, $options );
	}

	public function getPrimaryField( string $field, callable $legacy_getter, string $default = '' ): string {
		if ( is_callable( $legacy_getter ) ) {
			$value = call_user_func( $legacy_getter );
			return null === $value ? $default : (string) $value;
		}
		return $default;
	}
}
