<?php
/**
 * Reads legacy postman_options / webhook storage for the revamp Notifications module.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationLegacyOptions {

	public const LEGACY_OPTION = 'postman_options';

	public const WEBHOOK_URLS_OPTION = 'post_smtp_webhook_urls';

	public const KEY_NOTIFICATION_SERVICE   = 'notification_service';
	public const KEY_NOTIFICATION_USE_CHROME  = 'notification_use_chrome';
	public const KEY_NOTIFICATION_CHROME_UID  = 'notification_chrome_uid';
	public const KEY_PUSHOVER_USER            = 'pushover_user';
	public const KEY_PUSHOVER_TOKEN           = 'pushover_token';
	public const KEY_SLACK_TOKEN              = 'slack_token';
	public const KEY_NOTIFICATION_EMAIL       = 'notification_email';

	public const DEFAULT_NOTIFICATION_SERVICE = 'default';

	/**
	 * @return array<string,mixed>
	 */
	public static function getLegacyOptions(): array {
		$options = get_option( self::LEGACY_OPTION, array() );
		return is_array( $options ) ? $options : array();
	}

	public static function getNotificationService(): string {
		$legacy = self::getLegacyOptions();
		if ( isset( $legacy[ self::KEY_NOTIFICATION_SERVICE ] ) ) {
			return (string) $legacy[ self::KEY_NOTIFICATION_SERVICE ];
		}
		return self::DEFAULT_NOTIFICATION_SERVICE;
	}

	public static function getChromeUid(): string {
		return self::decodeOptionSecret( self::KEY_NOTIFICATION_CHROME_UID );
	}

	public static function getSlackWebhookUrl(): string {
		return self::decodeOptionSecret( self::KEY_SLACK_TOKEN );
	}

	public static function getPushoverUser(): string {
		return self::decodeOptionSecret( self::KEY_PUSHOVER_USER );
	}

	public static function getPushoverToken(): string {
		return self::decodeOptionSecret( self::KEY_PUSHOVER_TOKEN );
	}

	/**
	 * @return list<string>
	 */
	public static function getWebhookUrls(): array {
		$urls = get_option( self::WEBHOOK_URLS_OPTION, array() );
		if ( ! is_array( $urls ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $urls ) ) );
	}

	public static function getConfiguredNotificationEmail(): string {
		$legacy = self::getLegacyOptions();
		if ( isset( $legacy[ self::KEY_NOTIFICATION_EMAIL ] ) && '' !== (string) $legacy[ self::KEY_NOTIFICATION_EMAIL ] ) {
			return sanitize_text_field( (string) $legacy[ self::KEY_NOTIFICATION_EMAIL ] );
		}
		return '';
	}

	private static function decodeOptionSecret( string $key ): string {
		$legacy = self::getLegacyOptions();
		if ( ! isset( $legacy[ $key ] ) || ! is_string( $legacy[ $key ] ) || '' === $legacy[ $key ] ) {
			return '';
		}
		$decoded = base64_decode( $legacy[ $key ], true );
		if ( false === $decoded || '' === $decoded ) {
			return $legacy[ $key ];
		}
		return $decoded;
	}
}
