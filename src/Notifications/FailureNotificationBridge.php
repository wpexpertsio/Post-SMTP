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

	public function __construct( ?NotificationSettingsRepository $settings = null ) {
		$this->settings = $settings ?? new NotificationSettingsRepository();
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

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_ADMIN_EMAIL )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_ADMIN_EMAIL ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					$notifier = new \PostmanMailNotify();
					$notifier->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_SLACK )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_SLACK ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					$notifier = new \PostmanSlackNotify();
					$notifier->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_PUSHOVER )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_PUSHOVER ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					$notifier = new \PostmanPushoverNotify();
					$notifier->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_WEBHOOK )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_WEBHOOK ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					$notifier = new \PostmanWebhookAlertsNotify();
					$notifier->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_TEAMS )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_TEAMS )
			&& class_exists( 'PSP_MSTeam_Notification' ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					( new \PSP_MSTeam_Notification() )->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_TWILIO )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_TWILIO )
			&& class_exists( '\PostSMTPTwilio\Notify' ) ) {
			$this->safeSend(
				static function () use ( $message ) {
					( new \PostSMTPTwilio\Notify() )->send_message( $message );
				}
			);
		}

		if ( $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_CHROME )
			&& $this->settings->isChannelConfigured( NotificationChannelRegistry::SLUG_CHROME ) ) {
			$this->safeSend( array( $this, 'pushChrome' ), $error_message );
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
	 * @param string $message
	 */
	private function pushChrome( $message ): void {
		if ( ! $this->settings->isChannelEnabled( NotificationChannelRegistry::SLUG_CHROME ) ) {
			return;
		}

		$uid = PostmanNotifyOptions::getInstance()->getNotificationChromeUid();
		if ( empty( $uid ) ) {
			return;
		}

		$url = 'https://chrome.postmansmtp.com/' . $uid;

		$args = array(
			'body' => array(
				'message'  => $message,
				'site_url' => get_bloginfo( 'url' ),
			),
		);

		wp_remote_post( $url, $args );
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
