<?php
/**
 * Handles wp_mail for sites on the connections UI (PostmanWpMail is not used).
 *
 * @package PostSMTP\Mail\Modern
 */

namespace PostSMTP\Mail\Modern;

use PostSMTP\Mail\Modern\Delivery\Orchestrator;
use PostSMTP\Mail\Modern\Envelope\EnvelopeFactory;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WpMailHandler {

	public static function init(): void {
		add_filter( 'pre_wp_mail', array( self::class, 'handle' ), 1, 2 );
	}

	/**
	 * @param null|bool $short_circuit Existing short-circuit value.
	 * @param array     $atts          wp_mail arguments.
	 * @return null|bool
	 */
	public static function handle( $short_circuit, array $atts ) {
		if ( null !== $short_circuit ) {
			return $short_circuit;
		}

		if ( ! CohortResolver::usesRevampMailPipeline() ) {
			return null;
		}

		if ( ! empty( $GLOBALS['post_smtp_revamp_mail_in_progress'] ) ) {
			return null;
		}

		$GLOBALS['post_smtp_revamp_mail_in_progress'] = true;

		try {
			$envelope = EnvelopeFactory::fromWpMailArgs( $atts );
			$result   = ( new Orchestrator() )->deliver( $envelope );
			return ! empty( $result['success'] );
		} finally {
			unset( $GLOBALS['post_smtp_revamp_mail_in_progress'] );
		}
	}
}
