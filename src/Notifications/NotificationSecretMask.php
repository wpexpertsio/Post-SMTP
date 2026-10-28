<?php
/**
 * Mask sensitive notification credentials for REST responses.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationSecretMask {

	public static function mask( string $value, int $visible = 4 ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		/**
		 * Filter masked secret display for notification settings API.
		 *
		 * @param string $masked  Masked value.
		 * @param string $raw     Raw secret (do not log).
		 */
		return apply_filters(
			'post_smtp_notification_secrets_mask',
			str_repeat( '•', max( 8, strlen( $value ) - $visible ) ) . substr( $value, - $visible ),
			$value
		);
	}

	public static function isMaskedPlaceholder( string $value ): bool {
		return '' === $value || preg_match( '/^[•*]+$/u', $value ) || preg_match( '/^\*+$/', $value );
	}
}
