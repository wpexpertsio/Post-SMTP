<?php
/**
 * Programmatic mail send API (connections pipeline or classic wp_mail).
 *
 * @package PostSMTP\Mail
 */

namespace PostSMTP\Mail;

use PostSMTP\Mail\Classic\WpMailAdapter;
use PostSMTP\Mail\Modern\Delivery\Orchestrator;
use PostSMTP\Mail\Modern\Envelope\EnvelopeFactory;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MailPipeline {

	/**
	 * Programmatic send API (tests, REST). Site wp_mail uses Modern\WpMailHandler.
	 *
	 * @param array<string,mixed> $atts wp_mail arguments.
	 * @return array{success:bool,message:string,send:bool}
	 */
	public static function send( array $atts ): array {
		if ( ! CohortResolver::usesRevampMailPipeline() ) {
			return WpMailAdapter::send( $atts );
		}

		$envelope = EnvelopeFactory::fromWpMailArgs( $atts );
		return ( new Orchestrator() )->deliver( $envelope );
	}
}
