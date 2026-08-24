<?php
/**
 * Mail send pipeline with legacy fallback.
 *
 * @package PostSMTP\Mail
 */

namespace PostSMTP\Mail;

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

		$registry = new ProviderRegistry();
		$provider = $registry->create( (string) ( $connection['provider'] ?? 'smtp' ) );

		$processed = array(
			'to' => array(
				array(
					'email' => is_string( $atts['to'] ?? '' ) ? $atts['to'] : '',
					'name'  => '',
				),
			),
		);

		return $provider->send( $atts, null, $connection, $processed );
	}
}
