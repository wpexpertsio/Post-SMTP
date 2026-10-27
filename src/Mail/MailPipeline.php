<?php
/**
 * Mail send pipeline with legacy fallback.
 *
 * @package PostSMTP\Mail
 */

namespace PostSMTP\Mail;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use PostSMTP\Rollout\FeatureFlags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MailPipeline {

	/**
	 * @param array<string,mixed> $atts wp_mail arguments.
	 * @return array{success:bool,message:string,send:bool}
	 */
	public static function send( array $atts ): array {
		if ( ! FeatureFlags::enabled( 'new_mail_pipeline' ) ) {
			return LegacyMailAdapter::send( $atts );
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		$options     = $repo->getOptions();
		$primary_id  = $options['selected_primary'] ?? $options['primary_connection'] ?? 0;
		$connection  = $repo->getConnection( $primary_id );

		if ( empty( $connection ) && ! empty( $connections[0] ) ) {
			$connection = $connections[0];
		}

		if ( empty( $connection ) ) {
			return LegacyMailAdapter::send( $atts );
		}

		$processed = self::buildProcessedData( $atts );
		$result    = self::sendViaConnection( $connection, $atts, $processed );
		if ( ! empty( $result['success'] ) ) {
			return $result;
		}

		$fallback_indices = ConnectionService::getEnabledFallbackIndices( $connections, $options );
		foreach ( $fallback_indices as $fallback_id ) {
			$fallback_connection = $repo->getConnection( $fallback_id );
			if ( empty( $fallback_connection ) ) {
				continue;
			}
			$result = self::sendViaConnection( $fallback_connection, $atts, $processed );
			if ( ! empty( $result['success'] ) ) {
				return $result;
			}
		}

		return $result;
	}

	/**
	 * @param array<string,mixed> $atts wp_mail arguments.
	 * @return array{to:array<int,array{email:string,name:string}>}
	 */
	private static function buildProcessedData( array $atts ): array {
		return array(
			'to' => array(
				array(
					'email' => is_string( $atts['to'] ?? '' ) ? $atts['to'] : '',
					'name'  => '',
				),
			),
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @param array<string,mixed> $atts       wp_mail arguments.
	 * @param array<string,mixed> $processed  Normalized recipients.
	 * @return array{success:bool,message:string,send:bool}
	 */
	private static function sendViaConnection( array $connection, array $atts, array $processed ): array {
		$registry = new ProviderRegistry();
		$provider = $registry->create( (string) ( $connection['provider'] ?? 'smtp' ) );
		return $provider->send( $atts, null, $connection, $processed );
	}
}
