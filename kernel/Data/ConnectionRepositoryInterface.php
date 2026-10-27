<?php
/**
 * Connection repository contract.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ConnectionRepositoryInterface {

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function getConnections(): array;

	/**
	 * @param int|string $id Connection index.
	 * @return array<string,mixed>
	 */
	public function getConnection( $id ): array;

	/**
	 * @param array<int,array<string,mixed>> $connections Connection rows.
	 */
	public function saveConnections( array $connections ): bool;

	/**
	 * @return array<string,mixed>
	 */
	public function getOptions(): array;

	/**
	 * @param array<string,mixed> $options Options array.
	 */
	public function saveOptions( array $options ): bool;

	/**
	 * Resolve a credential field for the active primary connection.
	 *
	 * @param string   $field         Field name.
	 * @param callable $legacy_getter Legacy getter when in legacy mode.
	 * @param string   $default       Default value.
	 */
	public function getPrimaryField( string $field, callable $legacy_getter, string $default = '' ): string;
}
