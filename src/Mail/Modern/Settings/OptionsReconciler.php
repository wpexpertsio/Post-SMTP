<?php
/**
 * Keeps postman_options aligned with connection index 0 for log UI and classic readers.
 *
 * Outbound send reads postman_connections only; this does not drive Delivery\Orchestrator.
 *
 * @package PostSMTP\Mail\Modern\Settings
 */

namespace PostSMTP\Mail\Modern\Settings;

use PostSMTP\Connections\ConnectionLegacySendBridge;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Rollout\CohortResolver;
use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionsReconciler {

	public static function init(): void {
		add_action(
			'plugins_loaded',
			static function () {
				if ( ! CohortResolver::usesRevampMailPipeline() ) {
					return;
				}
				self::reconcileIfDrifted();
			},
			25
		);
	}

	public static function reconcileIfDrifted(): void {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return;
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		if ( empty( $connections[0] ) || ! is_array( $connections[0] ) ) {
			return;
		}

		$provider = (string) ( $connections[0]['provider'] ?? '' );
		if ( '' === $provider ) {
			return;
		}

		$expected = ConnectionLegacySendBridge::legacyTransportFromProvider( $provider );
		$options  = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$actual = (string) ( $options[ PostmanOptions::TRANSPORT_TYPE ] ?? '' );
		if ( $actual === $expected ) {
			return;
		}

		ConnectionLegacySendBridge::sync_primary_from_connection_index( 0 );
	}
}
