<?php
/**
 * Fallback send chain for multi-connection mail delivery.
 *
 * @package PostSMTP\Mail
 */

namespace PostSMTP\Mail;

use PostSMTP\Kernel\Data\ConnectionRepositoryInterface;
use PostSMTP\Kernel\Providers\ProviderRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FallbackChain {

	private ConnectionRepositoryInterface $repository;
	private ProviderRegistry $registry;

	public function __construct( ConnectionRepositoryInterface $repository, ProviderRegistry $registry ) {
		$this->repository = $repository;
		$this->registry   = $registry;
	}

	/**
	 * @param array<string,mixed> $atts           wp_mail attributes.
	 * @param array<string,mixed> $processed_data Normalized email data.
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string}
	 */
	public function send( array $atts, array $processed_data ): array {
		$connections = $this->repository->getConnections();
		if ( empty( $connections ) ) {
			return LegacyMailAdapter::send( $atts );
		}

		$last_result = array(
			'success' => false,
			'message' => __( 'No connection could send the email.', 'post-smtp' ),
			'send'    => false,
		);

		foreach ( $connections as $connection ) {
			if ( ! is_array( $connection ) || empty( $connection['provider'] ) ) {
				continue;
			}

			$provider = $this->registry->create( (string) $connection['provider'] );
			$result   = $provider->send( $atts, null, $connection, $processed_data );
			if ( ! empty( $result['success'] ) ) {
				return $result;
			}
			$last_result = $result;
		}

		return $last_result;
	}
}
