<?php
/**
 * Validates and sends notification tests.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationDeliveryService {

	/**
	 * @return array{valid:bool,message:string}
	 */
	public function validateChannel( string $slug ): array {
		$filtered = apply_filters( "post_smtp_notification_validate_{$slug}", null );
		if ( is_array( $filtered ) && isset( $filtered['valid'] ) ) {
			return $filtered;
		}

		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_ADMIN_EMAIL:
				$repo     = new NotificationSettingsRepository();
				$settings = $repo->getAdminEmailSettings();
				if ( empty( $settings['enabled'] ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Enable admin email alerts first.', 'post-smtp' ),
					);
				}
				$email = $repo->resolveEffectiveAdminEmail( (string) ( $settings['email'] ?? '' ) );
				if ( ! is_email( $email ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure a valid notification email address.', 'post-smtp' ),
					);
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_CHROME:
				$repo = new NotificationSettingsRepository();
				if ( ! $repo->isChannelEnabled( $slug ) ) {
					return array( 'valid' => false, 'message' => __( 'Enable Chrome extension alerts first.', 'post-smtp' ) );
				}
				if ( empty( PostmanNotifyOptions::getInstance()->getNotificationChromeUid() ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure Chrome Extension UID.', 'post-smtp' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_SLACK:
				if ( empty( PostmanNotifyOptions::getInstance()->getSlackToken() ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure Slack Webhook URL.', 'post-smtp' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_PUSHOVER:
				$opts = PostmanNotifyOptions::getInstance();
				if ( empty( $opts->getPushoverUser() ) || empty( $opts->getPushoverToken() ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure Pushover User Key and App Token.', 'post-smtp' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_WEBHOOK:
				$urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION, array() );
				if ( empty( $urls ) || ! is_array( $urls ) || 0 === count( array_filter( $urls ) ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure at least one Webhook URL.', 'post-smtp' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_TWILIO:
				if ( ! class_exists( '\PostSMTPTwilio\Settings' ) ) {
					return array( 'valid' => false, 'message' => __( 'Twilio extension is not loaded.', 'post-smtp' ) );
				}
				$settings = new \PostSMTPTwilio\Settings();
				if ( empty( $settings->get_sid() ) || empty( $settings->get_token() ) || empty( $settings->get_send_to() ) || empty( $settings->get_msg_service_sid() ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure Twilio credentials.', 'post-smtp' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			case NotificationChannelRegistry::SLUG_TEAMS:
				if ( ! class_exists( 'PSP_MSTeams_Settings' ) ) {
					return array( 'valid' => false, 'message' => __( 'Microsoft Teams extension is not loaded.', 'post-smtp' ) );
				}
				$settings = new \PSP_MSTeams_Settings();
				$url      = $settings->webhook_url();
				if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
					return array( 'valid' => false, 'message' => __( 'Please configure a valid Microsoft Teams Webhook URL.', 'post-smtp-pro' ) );
				}
				return array( 'valid' => true, 'message' => '' );

			default:
				return array(
					'valid'   => false,
					'message' => __( 'Invalid notification channel.', 'post-smtp' ),
				);
		}
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	public function sendTest( string $slug ): array {
		$repo = new NotificationSettingsRepository();
		if ( ! $repo->isChannelEnabled( $slug ) ) {
			return array(
				'success' => false,
				'message' => __( 'Enable this notification channel before sending a test.', 'post-smtp' ),
			);
		}

		$validation = $this->validateChannel( $slug );
		if ( empty( $validation['valid'] ) ) {
			return array(
				'success' => false,
				'message' => $validation['message'] ?: __( 'Configuration is not valid.', 'post-smtp' ),
			);
		}

		$filtered = apply_filters( "post_smtp_notification_test_{$slug}", null );
		if ( is_array( $filtered ) && isset( $filtered['success'] ) ) {
			return $filtered;
		}

		if ( NotificationChannelRegistry::SLUG_CHROME === $slug ) {
			return array(
				'success' => false,
				'message' => __( 'Test notifications are not available for the Chrome extension channel.', 'post-smtp' ),
			);
		}

		$message = __( 'This is a test notification message from Post SMTP.', 'post-smtp' ) . "\n\n";
		$message .= __( 'Your notification configuration is working correctly', 'post-smtp' ) . ' ✅';

		if ( NotificationChannelRegistry::SLUG_ADMIN_EMAIL === $slug ) {
			$config    = $repo->getAdminEmailSettings();
			$recipient = $repo->resolveEffectiveAdminEmail( (string) ( $config['email'] ?? '' ) );
			if ( ! wp_mail( $recipient, __( 'Test Notification – Post SMTP', 'post-smtp' ), $message ) ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to send test notification. Please check your configuration.', 'post-smtp' ),
				);
			}
			return array(
				'success' => true,
				'message' => __( 'Test notification sent. Check your admin inbox.', 'post-smtp' ),
			);
		}

		return $this->dispatchTest( $slug, $message );
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function dispatchTest( string $slug, string $message ): array {
		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_SLACK:
				return $this->testSlack( $message );
			case NotificationChannelRegistry::SLUG_PUSHOVER:
				return $this->testPushover( $message );
			case NotificationChannelRegistry::SLUG_WEBHOOK:
				return $this->testWebhook( $message );
			case NotificationChannelRegistry::SLUG_TWILIO:
				return $this->testTwilio( $message );
			case NotificationChannelRegistry::SLUG_TEAMS:
				return $this->testTeams( $message );
			default:
				return array(
					'success' => false,
					'message' => __( 'Notification service not supported.', 'post-smtp' ),
				);
		}
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function testSlack( string $message ): array {
		$url = PostmanNotifyOptions::getInstance()->getSlackToken();
		if ( empty( $url ) ) {
			return array( 'success' => false, 'message' => __( 'Slack Webhook URL is not configured.', 'post-smtp' ) );
		}
		$result = wp_remote_post(
			$url,
			array(
				'headers' => array( 'content-type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'text' => $message ) ),
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $result ) ) {
			return array( 'success' => false, 'message' => $result->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $result );
		return 200 === $code
			? array( 'success' => true, 'message' => __( 'Test notification sent successfully!', 'post-smtp' ) )
			: array( 'success' => false, 'message' => sprintf( __( 'Slack notification failed (HTTP %d).', 'post-smtp' ), $code ) );
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function testPushover( string $message ): array {
		$opts = PostmanNotifyOptions::getInstance();
		$result = wp_remote_post(
			'https://api.pushover.net/1/messages.json',
			array(
				'body'    => array(
					'token'   => $opts->getPushoverToken(),
					'user'    => $opts->getPushoverUser(),
					'message' => $message,
				),
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $result ) ) {
			return array( 'success' => false, 'message' => $result->get_error_message() );
		}
		$body = json_decode( wp_remote_retrieve_body( $result ), true );
		if ( 200 === wp_remote_retrieve_response_code( $result ) && isset( $body['status'] ) && 1 === (int) $body['status'] ) {
			return array( 'success' => true, 'message' => __( 'Test notification sent successfully!', 'post-smtp' ) );
		}
		$err = isset( $body['errors'] ) ? implode( ', ', $body['errors'] ) : __( 'Unknown error', 'post-smtp' );
		return array( 'success' => false, 'message' => $err );
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function testWebhook( string $message ): array {
		$urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION, array() );
		if ( ! is_array( $urls ) ) {
			return array( 'success' => false, 'message' => __( 'Webhook URLs are not configured.', 'post-smtp' ) );
		}
		$ok = 0;
		foreach ( $urls as $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			$response = wp_remote_post(
				$url,
				array(
					'body'    => wp_json_encode( array( 'message' => $message ) ),
					'headers' => array( 'Content-Type' => 'application/json' ),
					'timeout' => 10,
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$code = wp_remote_retrieve_response_code( $response );
				if ( $code >= 200 && $code < 300 ) {
					++$ok;
				}
			}
		}
		return $ok > 0
			? array( 'success' => true, 'message' => __( 'Test notification sent successfully!', 'post-smtp' ) )
			: array( 'success' => false, 'message' => __( 'All webhook URLs failed.', 'post-smtp' ) );
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function testTwilio( string $message ): array {
		if ( ! class_exists( '\PostSMTPTwilio\Notify' ) ) {
			return array( 'success' => false, 'message' => __( 'Twilio extension is not loaded.', 'post-smtp' ) );
		}
		try {
			( new \PostSMTPTwilio\Notify() )->send_message( $message );
			return array( 'success' => true, 'message' => __( 'Test notification sent successfully!', 'post-smtp' ) );
		} catch ( \Exception $e ) {
			return array( 'success' => false, 'message' => $e->getMessage() );
		}
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	private function testTeams( string $message ): array {
		if ( ! class_exists( 'PSP_MSTeams_Settings' ) ) {
			return array( 'success' => false, 'message' => __( 'Microsoft Teams extension is not loaded.', 'post-smtp-pro' ) );
		}
		$settings = new \PSP_MSTeams_Settings();
		$url      = $settings->webhook_url();
		$site     = get_bloginfo( 'name' );
		$payload  = array(
			'type'        => 'message',
			'attachments' => array(
				array(
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'content'     => array(
						'type'    => 'AdaptiveCard',
						'version' => '1.2',
						'body'    => array(
							array(
								'type' => 'TextBlock',
								'text' => $site,
								'wrap' => true,
							),
							array(
								'type' => 'TextBlock',
								'text' => $message,
								'wrap' => true,
							),
						),
					),
				),
			),
		);
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}
		return 200 === wp_remote_retrieve_response_code( $response )
			? array( 'success' => true, 'message' => __( 'Test notification sent successfully!', 'post-smtp' ) )
			: array( 'success' => false, 'message' => __( 'Microsoft Teams notification failed.', 'post-smtp-pro' ) );
	}
}
