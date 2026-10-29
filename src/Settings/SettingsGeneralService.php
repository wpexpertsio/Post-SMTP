<?php
/**
 * General settings tab (Figma 672:7182).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

use PostmanInputSanitizer;
use PostmanOptions;
use PostSMTP\Connections\ConnectionService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsGeneralService {

	/**
	 * @return array<string,mixed>
	 */
	public function getGeneral(): array {
		$options = PostmanOptions::getInstance();

		return array(
			'values'         => $this->readValues( $options ),
			'mail_handlers'  => $this->mailHandlerChoices(),
			'primary_label'  => $this->primaryConnectionLabel(),
			'copy'           => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveGeneral( array $payload ): array {
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
		return array(
			'mail_handler'              => (string) $options->getRunMode(),
			'from_name'                 => (string) ( $options->getMessageSenderName() ?? '' ),
			'from_email'                => (string) ( $options->getMessageSenderEmail() ?? '' ),
			'enforce_from_name'         => (bool) $options->isPluginSenderNameEnforced(),
			'enforce_from_email'        => (bool) $options->isPluginSenderEmailEnforced(),
			'reply_to_name'             => '',
			'reply_to_email'            => (string) ( $options->getReplyTo() ?? '' ),
			'forced_to'                 => (string) ( $options->getForcedToRecipients() ?? '' ),
			'forced_cc'                 => (string) ( $options->getForcedCcRecipients() ?? '' ),
			'forced_bcc'                => (string) ( $options->getForcedBccRecipients() ?? '' ),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapPayloadToOptions( array $payload ): array {
		$patch = array();

		if ( isset( $payload['mail_handler'] ) ) {
			$mode = sanitize_text_field( (string) $payload['mail_handler'] );
			$allowed = array(
				PostmanOptions::RUN_MODE_PRODUCTION,
				PostmanOptions::RUN_MODE_LOG_ONLY,
				PostmanOptions::RUN_MODE_IGNORE,
			);
			if ( in_array( $mode, $allowed, true ) ) {
				$patch[ PostmanOptions::RUN_MODE ] = $mode;
			}
		}

		if ( array_key_exists( 'from_name', $payload ) ) {
			$patch[ PostmanOptions::MESSAGE_SENDER_NAME ] = sanitize_text_field( (string) $payload['from_name'] );
		}

		if ( array_key_exists( 'from_email', $payload ) ) {
			$patch[ PostmanOptions::MESSAGE_SENDER_EMAIL ] = sanitize_email( (string) $payload['from_email'] );
		}

		if ( array_key_exists( 'enforce_from_name', $payload ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ] = ! empty( $payload['enforce_from_name'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'enforce_from_email', $payload ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ] = ! empty( $payload['enforce_from_email'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'reply_to_email', $payload ) ) {
			$patch[ PostmanOptions::REPLY_TO ] = sanitize_text_field( (string) $payload['reply_to_email'] );
		}

		if ( array_key_exists( 'forced_to', $payload ) ) {
			$patch[ PostmanOptions::FORCED_TO_RECIPIENTS ] = sanitize_text_field( (string) $payload['forced_to'] );
		}

		if ( array_key_exists( 'forced_cc', $payload ) ) {
			$patch[ PostmanOptions::FORCED_CC_RECIPIENTS ] = sanitize_text_field( (string) $payload['forced_cc'] );
		}

		if ( array_key_exists( 'forced_bcc', $payload ) ) {
			$patch[ PostmanOptions::FORCED_BCC_RECIPIENTS ] = sanitize_text_field( (string) $payload['forced_bcc'] );
		}

		return $patch;
	}

	/**
	 * @return list<array{value:string,label:string}>
	 */
	private function mailHandlerChoices(): array {
		return array(
			array(
				'value' => PostmanOptions::RUN_MODE_PRODUCTION,
				'label' => __( 'Post SMTP', 'post-smtp' ),
			),
			array(
				'value' => PostmanOptions::RUN_MODE_LOG_ONLY,
				'label' => __( 'Log emails only', 'post-smtp' ),
			),
			array(
				'value' => PostmanOptions::RUN_MODE_IGNORE,
				'label' => __( 'WordPress default', 'post-smtp' ),
			),
		);
	}

	private function primaryConnectionLabel(): string {
		try {
			$service = new ConnectionService();
			$list    = $service->listForAdmin();
			foreach ( $list['connections'] ?? array() as $row ) {
				if ( ! empty( $row['is_primary'] ) || ( isset( $row['role'] ) && 'primary' === $row['role'] ) ) {
					$label = isset( $row['label'] ) ? (string) $row['label'] : '';
					$mailer = isset( $row['mailer_label'] ) ? (string) $row['mailer_label'] : '';
					if ( '' !== $label ) {
						return $label;
					}
					if ( '' !== $mailer ) {
						return $mailer;
					}
				}
			}
		} catch ( \Throwable $e ) {
			return '';
		}

		return '';
	}
}
