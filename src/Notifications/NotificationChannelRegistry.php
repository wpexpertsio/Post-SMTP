<?php
/**
 * Notification channel metadata for the revamp admin UI.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationChannelRegistry {

	public const SLUG_ADMIN_EMAIL = 'admin_email';
	public const SLUG_CHROME       = 'chrome';
	public const SLUG_SLACK         = 'slack';
	public const SLUG_PUSHOVER      = 'pushover';
	public const SLUG_WEBHOOK       = 'webhook';
	public const SLUG_TEAMS         = 'teams';
	public const SLUG_TWILIO        = 'twilio';

	/**
	 * @return list<array<string,mixed>>
	 */
	public static function all(): array {
		$channels = array(
			array(
				'slug'       => self::SLUG_ADMIN_EMAIL,
				'label'      => __( 'Admin Email', 'post-smtp' ),
				'legacy_key' => 'default',
				'pro_locked' => false,
				'ui_ready'   => true,
			),
			array(
				'slug'       => self::SLUG_CHROME,
				'label'      => __( 'Chrome Extension', 'post-smtp' ),
				'legacy_key' => 'chrome',
				'pro_locked' => false,
				'ui_ready'   => true,
			),
			array(
				'slug'       => self::SLUG_SLACK,
				'label'      => __( 'Slack', 'post-smtp' ),
				'legacy_key' => 'slack',
				'pro_locked' => false,
				'ui_ready'   => true,
			),
			array(
				'slug'       => self::SLUG_PUSHOVER,
				'label'      => __( 'Pushover', 'post-smtp' ),
				'legacy_key' => 'pushover',
				'pro_locked' => false,
				'ui_ready'   => true,
			),
			array(
				'slug'       => self::SLUG_WEBHOOK,
				'label'      => __( 'Webhook', 'post-smtp' ),
				'legacy_key' => 'webhook_alerts',
				'pro_locked' => false,
				'ui_ready'   => true,
			),
		);

		/**
		 * Filter notification channel definitions for the revamp UI.
		 *
		 * Post SMTP Pro adds Microsoft Teams and Twilio channels here when the revamp panels are registered.
		 *
		 * @param list<array<string,mixed>> $channels Channel metadata.
		 */
		return apply_filters( 'post_smtp_notification_channels', $channels );
	}

	public static function isTwilioAvailable(): bool {
		return class_exists( 'PostSMTPTwilio' ) || ( function_exists( 'post_smtp_has_pro' ) && post_smtp_has_pro() );
	}

	public static function isTeamsAvailable(): bool {
		return class_exists( 'PSP_MSTeam_Notification' ) || ( function_exists( 'post_smtp_has_pro' ) && post_smtp_has_pro() );
	}
}
