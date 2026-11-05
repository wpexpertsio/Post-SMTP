<?php
/**
 * Primary connection and ordered fallbacks for outbound mail.
 *
 * @package PostSMTP\Mail\Modern\Delivery
 */

namespace PostSMTP\Mail\Modern\Delivery;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Kernel\Data\ConnectionRepositoryInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plan {

	/**
	 * @return array{
	 *   primary: array<string,mixed>|null,
	 *   primary_index: int,
	 *   fallbacks: array<int,array{index:int,connection:array<string,mixed>}>
	 * }
	 */
	public static function resolve( ConnectionRepositoryInterface $repository ): array {
		$connections = $repository->getConnections();
		$options     = $repository->getOptions();
		$service     = new ConnectionService();
		$connections = $service->normalizeOnRead( $connections, $options );

		// Connections UI: index 0 is always the primary mailer slot.
		$primary_index = 0;
		$primary       = $connections[0] ?? null;

		$fallbacks = array();
		foreach ( ConnectionService::getEnabledFallbackIndices( $connections, $options ) as $index ) {
			$index = (int) $index;
			if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
				continue;
			}
			$fallbacks[] = array(
				'index'      => $index,
				'connection' => $connections[ $index ],
			);
		}

		return array(
			'primary'       => is_array( $primary ) ? $primary : null,
			'primary_index' => $primary_index,
			'fallbacks'     => $fallbacks,
		);
	}
}
