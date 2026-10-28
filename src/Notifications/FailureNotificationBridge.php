<?php
/**
 * Delivers failure alerts to all enabled notification channels (revamp model).
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FailureNotificationBridge {

	private NotificationSettingsRepository $settings;

	private NotificationDeliveryService $delivery;

	public function __construct(
		?NotificationSettingsRepository $settings = null,
		?NotificationDeliveryService $delivery = null
	) {
		$this->settings = $settings ?? new NotificationSettingsRepository();
		$this->delivery = $delivery ?? new NotificationDeliveryService();
	}

	public static function init(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		$bridge = new self();
		add_action( 'post_smtp_on_failed', array( $bridge, 'dispatch' ), 8, 5 );
	}

	/**
	 * @param mixed $log
	 * @param mixed $postman_message
	 * @param mixed $transcript
	 * @param mixed $transport
	 * @param string $error_message
	 */
	public function dispatch( $log, $postman_message, $transcript, $transport, $error_message ): void {
		if ( empty( $error_message ) ) {
			return;
		}

		$message  = __( 'You getting this message because an error detected while delivered your email.', 'post-smtp' );
		$message .= "\r\n" . sprintf( __( 'For the domain: %1$s', 'post-smtp' ), get_bloginfo( 'url' ) );
		$message .= "\r\n" . __( 'The log to paste when you open a support issue:', 'post-smtp' ) . "\r\n";
		$message .= $error_message;

		$channels = array(
			NotificationChannelRegistry::SLUG_ADMIN_EMAIL,
			NotificationChannelRegistry::SLUG_SLACK,
			NotificationChannelRegistry::SLUG_PUSHOVER,
			NotificationChannelRegistry::SLUG_WEBHOOK,
			NotificationChannelRegistry::SLUG_TEAMS,
			NotificationChannelRegistry::SLUG_TWILIO,
		);

		foreach ( $channels as $slug ) {
			if ( ! $this->settings->isChannelEnabled( $slug ) || ! $this->settings->isChannelConfigured( $slug ) ) {
				continue;
			}
			$this->safeSend(
				function () use ( $slug, $message ) {
					$this->delivery->deliverMessage( $slug, $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_CHROME )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_CHROME ) ) {
			$this->safeSend(
				function () use ( $error_message ) {
					$this->delivery->deliverMessage( NotificationChannelRegistry::SLUG_CHROME, $error_message );
				}
			);
		}

		$extra = apply_filters( 'post_smtp_notification_dispatch_channels', array(), $message, $error_message );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $callback ) {
				if ( is_callable( $callback ) ) {
					$this->safeSend( $callback );
				}
			}
		}
	}

	/**
	 * @param callable $callback
	 * @param mixed    ...$args
	 */
	private function safeSend( callable $callback, ...$args ): void {
		try {
			$callback( ...$args );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Post SMTP notification channel failed: ' . $e->getMessage() );
			}
		}
	}
}
