<?php
/**
 * Security settings tab (Figma 672:7296 / 672:7327).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

use PostmanInputSanitizer;
use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsSecurityService {

	/**
	 * @return array<string,mixed>
	 */
	public function getSecurity(): array {
		$options = PostmanOptions::getInstance();

		return array(
			'values'           => $this->readValues( $options ),
			'delivery_choices' => $this->deliveryChoices(),
			'copy'             => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveSecurity( array $payload ): array {
		$current = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$patch = $this->mapPayloadToOptions( $payload );
		$input = array_merge( $current, $patch );

		$sanitizer = new PostmanInputSanitizer();
		$sanitized = $sanitizer->sanitize( $input );
		if ( ! is_array( $sanitized ) ) {
			return array(
				'success' => false,
				'message' => __( 'Could not save settings.', 'post-smtp' ),
			);
		}

		$merged = array_merge( $current, $sanitized );
		update_option( PostmanOptions::POSTMAN_OPTIONS, $merged );
		PostmanOptions::getInstance()->reload();

		return array(
			'success' => true,
			'message' => __( 'Settings saved.', 'post-smtp' ),
			'values'  => $this->readValues( PostmanOptions::getInstance() ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readValues( PostmanOptions $options ): array {
		$envelope = $options->getEnvelopeSender();

		return array(
			'force_from_email'          => (bool) $options->isPluginSenderEmailEnforced(),
			'force_from_name'             => (bool) $options->isPluginSenderNameEnforced(),
			'disable_email_validation'    => (bool) $options->isEmailValidationDisabled(),
			'mail_delivery'               => (string) $options->getRunMode(),
			'stealth_mode'                => (bool) $options->isStealthModeEnabled(),
			'envelope_sender'             => null !== $envelope ? (string) $envelope : '',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapPayloadToOptions( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'force_from_email', $payload ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ] = ! empty( $payload['force_from_email'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'force_from_name', $payload ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ] = ! empty( $payload['force_from_name'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'disable_email_validation', $payload ) ) {
			$patch[ PostmanOptions::DISABLE_EMAIL_VALIDAITON ] = ! empty( $payload['disable_email_validation'] ) ? 'true' : '';
		}

		if ( isset( $payload['mail_delivery'] ) ) {
			$mode = sanitize_text_field( (string) $payload['mail_delivery'] );
			$allowed = array(
				PostmanOptions::RUN_MODE_PRODUCTION,
				PostmanOptions::RUN_MODE_LOG_ONLY,
				PostmanOptions::RUN_MODE_IGNORE,
			);
			if ( in_array( $mode, $allowed, true ) ) {
				$patch[ PostmanOptions::RUN_MODE ] = $mode;
			}
		}

		if ( array_key_exists( 'stealth_mode', $payload ) ) {
			$patch[ PostmanOptions::STEALTH_MODE ] = ! empty( $payload['stealth_mode'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'envelope_sender', $payload ) ) {
			$patch[ PostmanOptions::ENVELOPE_SENDER ] = sanitize_email( (string) $payload['envelope_sender'] );
		}

		return $patch;
	}

	/**
	 * @return list<array{value:string,label:string}>
	 */
	private function deliveryChoices(): array {
		return array(
			array(
				'value' => PostmanOptions::RUN_MODE_PRODUCTION,
				'label' => __( 'Log email and send', 'post-smtp' ),
			),
			array(
				'value' => PostmanOptions::RUN_MODE_LOG_ONLY,
				'label' => __( 'Log only', 'post-smtp' ),
			),
			array(
				'value' => PostmanOptions::RUN_MODE_IGNORE,
				'label' => __( 'No Action', 'post-smtp' ),
			),
		);
	}
}
