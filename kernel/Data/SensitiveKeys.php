<?php
/**
 * Canonical sensitive credential keys for Post SMTP connection storage.
 *
 * @package PostSMTP\Kernel\Data
 */

namespace PostSMTP\Kernel\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SensitiveKeys {

	/**
	 * Keys stripped from postman_options after migration.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			'basic_auth_password',
			'mandrill_api_key',
			'sendgrid_api_key',
			'sendinblue_api_key',
			'postmark_api_key',
			'sendpulse_api_key',
			'sendpulse_secret_key',
			'sparkpost_api_key',
			'elasticemail_api_key',
			'smtp2go_api_key',
			'mailersend_api_key',
			'mailjet_api_key',
			'mailjet_secret_key',
			'emailit_api_key',
			'maileroo_api_key',
			'sweego_api_key',
			'resend_api_key',
			'mailtrap_api_key',
			'mailgun_api_key',
			'oauth_client_secret',
			'office365_app_id',
			'office365_app_password',
			'zohomail_client_secret',
			'ses_secret_access_key',
		);
	}

	/**
	 * Legacy postman_options keys stored as base64.
	 *
	 * @return string[]
	 */
	public static function base64OptionKeys(): array {
		return array(
			'basic_auth_password',
			'fallback_smtp_password',
			'mandrill_api_key',
			'sendgrid_api_key',
			'sendinblue_api_key',
			'postmark_api_key',
			'sendpulse_api_key',
			'sendpulse_secret_key',
			'sparkpost_api_key',
			'elasticemail_api_key',
			'smtp2go_api_key',
			'mailersend_api_key',
			'mailjet_api_key',
			'mailjet_secret_key',
			'emailit_api_key',
			'maileroo_api_key',
			'sweego_api_key',
			'resend_api_key',
			'mailtrap_api_key',
			'mailgun_api_key',
			'zohomail_client_secret',
			'ses_secret_access_key',
		);
	}

	/**
	 * Legacy postman_options keys stored as plaintext.
	 *
	 * @return string[]
	 */
	public static function plaintextOptionKeys(): array {
		return array(
			'office365_app_id',
			'office365_app_password',
		);
	}
}
