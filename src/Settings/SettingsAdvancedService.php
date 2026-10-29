<?php
/**
 * Advanced settings tab (Figma 672:7495 / 672:7526).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

use PostmanInputSanitizer;
use PostmanLogger;
use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsAdvancedService {

	/**
	 * @return array<string,mixed>
	 */
	public function getAdvanced(): array {
		$options = PostmanOptions::getInstance();

		return array(
			'values'            => $this->readValues( $options ),
			'log_level_choices' => $this->logLevelChoices(),
			'copy'              => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveAdvanced( array $payload ): array {
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
		$headers = $options->getAdditionalHeaders();

		return array(
			'connection_timeout' => (int) $options->getConnectionTimeout(),
			'read_timeout'       => (int) $options->getReadTimeout(),
			'temporary_directory' => (string) $options->getTempDirectory(),
			'log_level'          => (int) $options->getLogLevel(),
			'broken_email_fix'   => (bool) $options->is_php_compatibility_enabled(),
			'custom_headers'     => null !== $headers ? (string) $headers : '',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapPayloadToOptions( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'connection_timeout', $payload ) ) {
			$patch[ PostmanOptions::CONNECTION_TIMEOUT ] = absint( $payload['connection_timeout'] );
		}

		if ( array_key_exists( 'read_timeout', $payload ) ) {
			$patch[ PostmanOptions::READ_TIMEOUT ] = absint( $payload['read_timeout'] );
		}

		if ( array_key_exists( 'temporary_directory', $payload ) ) {
			$patch[ PostmanOptions::TEMPORARY_DIRECTORY ] = sanitize_text_field( (string) $payload['temporary_directory'] );
		}

		if ( array_key_exists( 'log_level', $payload ) ) {
			$level = (int) $payload['log_level'];
			$allowed = array_column( $this->logLevelChoices(), 'value' );
			if ( in_array( $level, $allowed, true ) ) {
				$patch[ PostmanOptions::LOG_LEVEL ] = $level;
			}
		}

		if ( array_key_exists( 'broken_email_fix', $payload ) ) {
			$patch[ PostmanOptions::INCOMPATIBLE_PHP_VERSION ] = ! empty( $payload['broken_email_fix'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'custom_headers', $payload ) ) {
			$patch[ PostmanOptions::ADDITIONAL_HEADERS ] = sanitize_textarea_field( (string) $payload['custom_headers'] );
		}

		return $patch;
	}

	/**
	 * @return list<array{value:int,label:string}>
	 */
	private function logLevelChoices(): array {
		return array(
			array(
				'value' => PostmanLogger::OFF_INT,
				'label' => __( 'Off', 'post-smtp' ),
			),
			array(
				'value' => PostmanLogger::TRACE_INT,
				'label' => __( 'Trace', 'post-smtp' ),
			),
			array(
				'value' => PostmanLogger::DEBUG_INT,
				'label' => __( 'Debug', 'post-smtp' ),
			),
			array(
				'value' => PostmanLogger::INFO_INT,
				'label' => __( 'Info', 'post-smtp' ),
			),
			array(
				'value' => PostmanLogger::WARN_INT,
				'label' => __( 'Warning', 'post-smtp' ),
			),
			array(
				'value' => PostmanLogger::ERROR_INT,
				'label' => __( 'Error', 'post-smtp' ),
			),
		);
	}
}
