<?php
/**
 * postman_options keys that belong to mailer / connection configuration (multisite sync).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MailerOptionKeys {

	/**
	 * @return string[]
	 */
	public static function keys(): array {
		$keys = array(
			'selected_primary',
			'primary_connection',
			\PostmanOptions::TRANSPORT_TYPE,
			\PostmanOptions::AUTHENTICATION_TYPE,
			\PostmanOptions::HOSTNAME,
			\PostmanOptions::PORT,
			\PostmanOptions::SECURITY_TYPE,
			\PostmanOptions::BASIC_AUTH_USERNAME,
			\PostmanOptions::BASIC_AUTH_PASSWORD,
			\PostmanOptions::MESSAGE_SENDER_EMAIL,
			\PostmanOptions::MESSAGE_SENDER_NAME,
			\PostmanOptions::ENVELOPE_SENDER,
			\PostmanOptions::REPLY_TO,
			\PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE,
			\PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE,
			\PostmanOptions::CLIENT_ID,
			\PostmanOptions::CLIENT_SECRET,
			\PostmanOptions::MANDRILL_API_KEY,
			\PostmanOptions::SENDGRID_API_KEY,
			\PostmanOptions::SENDGRID_REGION,
			\PostmanOptions::SENDINBLUE_API_KEY,
			\PostmanOptions::MAILTRAP_API_KEY,
			\PostmanOptions::RESEND_API_KEY,
			\PostmanOptions::MAILJET_API_KEY,
			\PostmanOptions::MAILJET_SECRET_KEY,
			\PostmanOptions::SENDPULSE_API_KEY,
			\PostmanOptions::SENDPULSE_SECRET_KEY,
			\PostmanOptions::POSTMARK_API_KEY,
			\PostmanOptions::SPARKPOST_API_KEY,
			\PostmanOptions::MAILGUN_API_KEY,
			\PostmanOptions::MAILGUN_DOMAIN_NAME,
			\PostmanOptions::MAILGUN_REGION,
			\PostmanOptions::ELASTICEMAIL_API_KEY,
			\PostmanOptions::MAILERSEND_API_KEY,
			\PostmanOptions::EMAILIT_API_KEY,
			\PostmanOptions::MAILEROO_API_KEY,
			\PostmanOptions::SWEEGO_API_KEY,
			\PostmanOptions::FALLBACK_SMTP_ENABLED,
			\PostmanOptions::FALLBACK_SMTP_HOSTNAME,
			\PostmanOptions::FALLBACK_SMTP_PORT,
			\PostmanOptions::FALLBACK_SMTP_SECURITY,
			\PostmanOptions::FALLBACK_FROM_EMAIL,
			\PostmanOptions::FALLBACK_SMTP_USE_AUTH,
			\PostmanOptions::FALLBACK_SMTP_USERNAME,
			\PostmanOptions::FALLBACK_SMTP_PASSWORD,
			'office365_app_id',
			'office365_app_password',
			'ses_access_key_id',
			'ses_secret_access_key',
			'ses_region',
			'smtp_mailers',
			\PostmanOptions::SMTP2GO_API_KEY,
		);

		return array_values( array_unique( $keys ) );
	}

	/**
	 * @param array<string,mixed> $source Full options array from main site.
	 * @return array<string,mixed>
	 */
	public static function extractMailerValues( array $source ): array {
		$patch = array();
		foreach ( self::keys() as $key ) {
			if ( array_key_exists( $key, $source ) ) {
				$patch[ $key ] = $source[ $key ];
			}
		}

		return $patch;
	}

	/**
	 * @param array<string,mixed>|mixed $old Previous option value.
	 * @param array<string,mixed>|mixed $new Incoming option value.
	 */
	public static function patchTouchesMailerKeys( $old, $new ): bool {
		if ( ! is_array( $new ) ) {
			return true;
		}

		$old = is_array( $old ) ? $old : array();
		foreach ( self::keys() as $key ) {
			if ( ! array_key_exists( $key, $new ) ) {
				continue;
			}
			if ( ! array_key_exists( $key, $old ) || $old[ $key ] !== $new[ $key ] ) {
				return true;
			}
		}

		return false;
	}
}