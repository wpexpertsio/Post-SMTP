<?php
/**
 * Handles wp_mail for sites on the connections UI (PostmanWpMail is not used).
 *
 * @package PostSMTP\Mail\Modern
 */

namespace PostSMTP\Mail\Modern;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Mail\Modern\Delivery\Orchestrator;
use PostSMTP\Mail\Modern\Delivery\Plan;
use PostSMTP\Mail\Modern\EmailLog\Events;
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

		if ( RunModeGate::isIgnore() ) {
			return true;
		}

		if ( self::shouldForceSend( $atts ) ) {
			return self::deliverNow( $atts );
		}

		if ( self::shouldQueue( $atts ) ) {
			return self::queueMail( $atts );
		}

		if ( RunModeGate::isLogOnly() ) {
			return self::logOnly( $atts );
		}

		return self::deliverNow( $atts );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	private static function shouldForceSend( array $atts ): bool {
		if ( ! empty( $GLOBALS['post_smtp_aedl_batch_send'] ) ) {
			return true;
		}

		/**
		 * @param bool                $force Send immediately (batch/cron), do not re-queue.
		 * @param array<string,mixed> $atts  wp_mail arguments.
		 */
		return (bool) apply_filters( 'post_smtp_revamp_force_send', false, $atts );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	private static function shouldQueue( array $atts ): bool {
		if ( ! RunModeGate::allowsOutboundDelivery() ) {
			return false;
		}

		/**
		 * @param bool                $queue Whether to queue instead of sending.
		 * @param array<string,mixed> $atts  wp_mail arguments.
		 */
		return (bool) apply_filters( 'post_smtp_revamp_should_queue_mail', false, $atts );
	}

	/**
	 * @param array<string,mixed> $atts
	 * @return bool
	 */
	private static function deliverNow( array $atts ) {
		$GLOBALS['post_smtp_revamp_mail_in_progress'] = true;

		try {
			$envelope = EnvelopeFactory::fromWpMailArgs( $atts );
			$result   = ( new Orchestrator() )->deliver( $envelope );
			return ! empty( $result['success'] );
		} finally {
			unset( $GLOBALS['post_smtp_revamp_mail_in_progress'] );
		}
	}

	/**
	 * @param array<string,mixed> $atts
	 * @return bool
	 */
	private static function logOnly( array $atts ) {
		if ( ! class_exists( '\PostmanOptions', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanOptions.php';
		}

		if ( ! \PostmanOptions::getInstance()->isMailLoggingEnabled() ) {
			return true;
		}

		$repo       = ConnectionRepositoryFactory::make();
		$plan       = Plan::resolve( $repo );
		$connection = $plan['primary'] ?? array();
		$envelope   = EnvelopeFactory::fromWpMailArgs( $atts );

		Events::recordAttempt(
			$atts,
			$connection,
			array(
				'success'    => true,
				'message'    => __( 'Log only mode — email not sent.', 'post-smtp' ),
				'transcript' => '',
			),
			false,
			$envelope->processed,
			null
		);

		return true;
	}

	/**
	 * @param array<string,mixed> $atts
	 * @return bool
	 */
	private static function queueMail( array $atts ) {
		$repo       = ConnectionRepositoryFactory::make();
		$plan       = Plan::resolve( $repo );
		$connection = $plan['primary'] ?? array();
		$envelope   = EnvelopeFactory::fromWpMailArgs( $atts );

		Events::persistQueued( $atts, $connection, $envelope->processed );

		return true;
	}
}
