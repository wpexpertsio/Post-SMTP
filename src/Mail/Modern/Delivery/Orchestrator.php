<?php
/**
 * Primary + fallback delivery using one provider class per mailer.
 *
 * @package PostSMTP\Mail\Modern\Delivery
 */

namespace PostSMTP\Mail\Modern\Delivery;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use PostSMTP\Mail\Modern\EmailLog\Events;
use PostSMTP\Mail\Modern\Envelope\Envelope;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Orchestrator {

	private ProviderRegistry $registry;

	public function __construct( ?ProviderRegistry $registry = null ) {
		$this->registry = $registry ?? new ProviderRegistry();
	}

	/**
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string}
	 */
	public function deliver( Envelope $envelope ): array {
		$repo = ConnectionRepositoryFactory::make();
		$plan = Plan::resolve( $repo );

		if ( empty( $plan['primary'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Primary connection is missing.', 'post-smtp' ),
				'send'    => false,
			);
		}

		$staged_log_id  = apply_filters( 'post_smtp_current_email_id', false );
		$primary_log_id = is_numeric( $staged_log_id ) ? (int) $staged_log_id : null;

		$result = $this->attempt(
			$envelope,
			$plan['primary'],
			$primary_log_id,
			false,
			(int) $plan['primary_index']
		);

		if ( ! empty( $result['success'] ) ) {
			return $result;
		}

		if ( empty( $plan['fallbacks'] ) ) {
			return $this->finalizeAllFailed( $envelope, $result );
		}

		/**
		 * @param array{success:bool,message:string,send:bool} $primary_result Primary failure result.
		 * @param array<string,mixed>                          $atts           wp_mail arguments.
		 */
		if ( ! apply_filters( 'post_smtp_should_try_fallback', true, $result, $envelope->atts ) ) {
			return $this->finalizeAllFailed( $envelope, $result );
		}

		foreach ( $plan['fallbacks'] as $fallback_row ) {
			Events::beforeFallback( $envelope->atts );

			$result = $this->attempt(
				$envelope,
				$fallback_row['connection'],
				null,
				true,
				(int) $fallback_row['index']
			);

			if ( ! empty( $result['success'] ) ) {
				return $result;
			}
		}

		return $this->finalizeAllFailed( $envelope, $result );
	}

	/**
	 * @param array{success:bool,message:string,send:bool} $result Last failure result.
	 * @return array{success:bool,message:string,send:bool}
	 */
	private function finalizeAllFailed( Envelope $envelope, array $result ): array {
		$mail_error_data = array(
			'to'          => $envelope->atts['to'] ?? '',
			'subject'     => $envelope->atts['subject'] ?? '',
			'message'     => $envelope->atts['message'] ?? '',
			'headers'     => $envelope->atts['headers'] ?? '',
			'attachments' => $envelope->atts['attachments'] ?? array(),
		);
		do_action(
			'wp_mail_failed',
			new \WP_Error(
				'wp_mail_failed',
				(string) ( $result['message'] ?? __( 'All connections failed.', 'post-smtp' ) ),
				$mail_error_data
			)
		);

		return $result;
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @param int|null            $log_id         Staged email log ID (primary attempt only).
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string}
	 */
	private function attempt(
		Envelope $envelope,
		array $connection,
		?int $log_id,
		bool $is_fallback,
		int $connection_index
	): array {
		$provider_slug = (string) ( $connection['provider'] ?? 'smtp' );
		$provider      = $this->registry->create( $provider_slug );

		/**
		 * @param array<string,mixed> $connection       Connection row.
		 * @param bool                $is_fallback      Fallback attempt.
		 * @param int                 $connection_index Connection index in postman_connections.
		 */
		do_action( 'post_smtp_revamp_before_provider_send', $connection, $is_fallback, $connection_index );

		$result = $provider->send(
			$envelope->atts,
			$log_id,
			$connection,
			$envelope->processed
		);

		Events::recordAttempt(
			$envelope->atts,
			$connection,
			$result,
			$is_fallback,
			$envelope->processed,
			$is_fallback ? null : $log_id
		);

		/**
		 * @param array<string,mixed> $result           Provider result.
		 * @param array<string,mixed> $connection       Connection row.
		 * @param bool                $is_fallback      Fallback attempt.
		 * @param int                 $connection_index Connection index.
		 */
		do_action( 'post_smtp_revamp_after_provider_send', $result, $connection, $is_fallback, $connection_index );

		return $result;
	}
}
