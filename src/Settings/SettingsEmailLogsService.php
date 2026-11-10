<?php
/**
 * Email Logs settings tab (Figma 672:7610).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsEmailLogsService {

	public const SAVE_MESSAGE_OPTION = 'mail_log_save_message';

	public static function init(): void {
		add_filter( 'post_smtp_email_log_save_data', array( self::class, 'filterLogSaveData' ), 10, 1 );
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public static function filterLogSaveData( array $data ): array {
		if ( self::isSaveMessageBodyEnabled() ) {
			return $data;
		}

		$data['original_message'] = '';

		return $data;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getEmailLogs(): array {
		$options = PostmanOptions::getInstance();
		$pro     = AedlOptions::autoRetryForLogsApi( SettingsEmailLogsPanelHooks::isProForApi() );

		$payload = array(
			'values'                => $this->readValues( $options, $pro ),
			'max_entries_choices'   => $this->maxEntriesChoices(),
			'transcript_choices'    => $this->transcriptChoices(),
			'retry_attempt_choices' => $this->retryAttemptChoices(),
			'is_pro'                => SettingsEmailLogsPanelHooks::isProForApi(),
			'auto_retry_locked'      => SettingsEmailLogsPanelHooks::isAutoRetryLocked(),
			'pro_features_locked'    => SettingsEmailLogsPanelHooks::isProFeaturesLocked(),
			'delete_logs_choices'    => $this->deleteLogsDayChoices(),
			'export_duration_choices'=> $this->exportDurationChoices(),
			'panel_config'          => SettingsEmailLogsPanelHooks::panelConfig(),
			'logging_allowed'       => $options->isMailLoggingAllowed(),
			'copy'                  => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);

		/**
		 * Full Email Logs settings GET payload.
		 *
		 * @param array<string,mixed>     $payload REST response body.
		 * @param SettingsEmailLogsService $service Service instance.
		 */
		$filtered = apply_filters( 'post_smtp_settings_email_logs_get', $payload, $this );

		return is_array( $filtered ) ? $filtered : $payload;
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveEmailLogs( array $payload ): array {
		$current = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$patch = $this->mapPayloadToPostmanOptions( $payload );
		$sanitized_patch = $this->sanitizeEmailLogsPatch( $patch, PostmanOptions::getInstance() );
		if ( null === $sanitized_patch ) {
			return array(
				'success' => false,
				'message' => __( 'Could not save settings.', 'post-smtp' ),
			);
		}

		$merged = array_merge( $current, $sanitized_patch );

		update_option( PostmanOptions::POSTMAN_OPTIONS, $merged );
		PostmanOptions::getInstance()->reload();

		if ( $this->mapPayloadHasProOnlyFields( $payload ) && SettingsEmailLogsPanelHooks::isProFeaturesLocked() ) {
			do_action( 'post_smtp_settings_email_logs_pro_locked_save_attempt', $payload );
		}

		if ( $this->mapPayloadHasAutoRetry( $payload ) && SettingsEmailLogsPanelHooks::isAutoRetryLocked() ) {
			/**
			 * Fires when the client POST includes auto-retry fields while that block is locked.
			 *
			 * @param array<string,mixed> $payload Save payload.
			 */
			do_action( 'post_smtp_settings_email_logs_auto_retry_locked_save_attempt', $payload );
		} elseif ( SettingsEmailLogsPanelHooks::isProForApi() && $this->mapPayloadHasAutoRetry( $payload ) ) {
			$validation = AedlOptions::validateAndBuildPatch( $payload );
			if ( empty( $validation['success'] ) ) {
				return array(
					'success' => false,
					'message' => $validation['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
				);
			}
			if ( ! empty( $validation['patch'] ) ) {
				AedlOptions::savePatch( $validation['patch'] );
			}
		}

		$pro = AedlOptions::autoRetryForLogsApi( SettingsEmailLogsPanelHooks::isProForApi() );

		$result = array(
			'success' => true,
			'message' => __( 'Settings saved.', 'post-smtp' ),
			'values'  => $this->readValues( PostmanOptions::getInstance(), $pro ),
		);

		/**
		 * After core log options are saved (Pro may extend the response).
		 *
		 * @param array{success:bool,message:string,values?:array<string,mixed>} $result  Save response.
		 * @param array<string,mixed>                                             $payload Client payload.
		 */
		$filtered = apply_filters( 'post_smtp_settings_email_logs_save_result', $result, $payload );

		return is_array( $filtered ) ? $filtered : $result;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readValues( PostmanOptions $options, array $pro ): array {
		$raw = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		return array(
			'logging_enabled'        => (bool) $options->isMailLoggingEnabled(),
			'save_message_body'      => self::isSaveMessageBodyEnabled(),
			'max_log_entries'        => (int) $options->getMailLoggingMaxEntries(),
			'transcript_size_kb'     => (int) $options->getTranscriptSize(),
			'auto_retry_enabled'     => ! empty( $pro['enable_auto_retry'] ),
			'auto_retry_max_attempts' => (int) $pro['times_to_retry'],
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapPayloadToPostmanOptions( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'logging_enabled', $payload ) ) {
			$patch[ PostmanOptions::MAIL_LOG_ENABLED_OPTION ] = ! empty( $payload['logging_enabled'] )
				? PostmanOptions::MAIL_LOG_ENABLED_OPTION_YES
				: PostmanOptions::MAIL_LOG_ENABLED_OPTION_NO;
		}

		if ( array_key_exists( 'save_message_body', $payload ) ) {
			$patch[ self::SAVE_MESSAGE_OPTION ] = ! empty( $payload['save_message_body'] ) ? 'true' : '';
		}

		if ( array_key_exists( 'max_log_entries', $payload ) ) {
			$patch[ PostmanOptions::MAIL_LOG_MAX_ENTRIES ] = absint( $payload['max_log_entries'] );
		}

		if ( array_key_exists( 'transcript_size_kb', $payload ) ) {
			$patch[ PostmanOptions::TRANSCRIPT_SIZE ] = absint( $payload['transcript_size_kb'] );
		}

		return $patch;
	}

	/**
	 * Patch only email-log keys — avoids legacy PostmanInputSanitizer (needs PostmanSession).
	 *
	 * @param array<string,mixed> $patch
	 * @return array<string,mixed>|null
	 */
	private function sanitizeEmailLogsPatch( array $patch, PostmanOptions $options ) {
		$out = array();

		if ( array_key_exists( PostmanOptions::MAIL_LOG_ENABLED_OPTION, $patch ) ) {
			$value = (string) $patch[ PostmanOptions::MAIL_LOG_ENABLED_OPTION ];
			if ( PostmanOptions::MAIL_LOG_ENABLED_OPTION_YES !== $value
				&& PostmanOptions::MAIL_LOG_ENABLED_OPTION_NO !== $value ) {
				return null;
			}
			if ( PostmanOptions::MAIL_LOG_ENABLED_OPTION_YES === $value && ! $options->isMailLoggingAllowed() ) {
				$value = PostmanOptions::MAIL_LOG_ENABLED_OPTION_NO;
			}
			$out[ PostmanOptions::MAIL_LOG_ENABLED_OPTION ] = $value;
		}

		if ( array_key_exists( self::SAVE_MESSAGE_OPTION, $patch ) ) {
			$out[ self::SAVE_MESSAGE_OPTION ] = 'true' === (string) $patch[ self::SAVE_MESSAGE_OPTION ] ? 'true' : '';
		}

		if ( array_key_exists( PostmanOptions::MAIL_LOG_MAX_ENTRIES, $patch ) ) {
			$max = absint( $patch[ PostmanOptions::MAIL_LOG_MAX_ENTRIES ] );
			if ( $max <= 0 ) {
				return null;
			}
			$out[ PostmanOptions::MAIL_LOG_MAX_ENTRIES ] = $max;
		}

		if ( array_key_exists( PostmanOptions::TRANSCRIPT_SIZE, $patch ) ) {
			$out[ PostmanOptions::TRANSCRIPT_SIZE ] = absint( $patch[ PostmanOptions::TRANSCRIPT_SIZE ] );
		}

		return $out;
	}

	private function mapPayloadHasAutoRetry( array $payload ): bool {
		return array_key_exists( 'auto_retry_enabled', $payload )
			|| array_key_exists( 'auto_retry_max_attempts', $payload );
	}

	private function mapPayloadHasProOnlyFields( array $payload ): bool {
		return array_key_exists( 'save_attachments', $payload )
			|| array_key_exists( 'advanced_log_filter', $payload )
			|| array_key_exists( 'delete_logs_days', $payload );
	}

	/**
	 * @return list<array{value:int,label:string}>
	 */
	private function deleteLogsDayChoices(): array {
		$values = array( 0, 7, 14, 30, 60, 90, 180, 365 );
		$out    = array();
		foreach ( $values as $value ) {
			$out[] = array(
				'value' => $value,
				'label' => 0 === $value
					? __( 'Never', 'post-smtp' )
					: sprintf(
						/* translators: %d: number of days */
						__( '%d days', 'post-smtp' ),
						$value
					),
			);
		}
		return $out;
	}

	/**
	 * @return list<array{value:string,label:string}>
	 */
	private function exportDurationChoices(): array {
		return array(
			array(
				'value' => '7',
				'label' => __( 'Last 7 days', 'post-smtp' ),
			),
			array(
				'value' => '30',
				'label' => __( 'Last 30 days', 'post-smtp' ),
			),
			array(
				'value' => '90',
				'label' => __( 'Last 90 days', 'post-smtp' ),
			),
			array(
				'value' => 'all',
				'label' => __( 'All time', 'post-smtp' ),
			),
		);
	}

	public static function isSaveMessageBodyEnabled(): bool {
		$raw = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $raw ) ) {
			return true;
		}

		if ( ! array_key_exists( self::SAVE_MESSAGE_OPTION, $raw ) ) {
			return true;
		}

		return 'true' === $raw[ self::SAVE_MESSAGE_OPTION ];
	}

	/**
	 * @return list<array{value:int,label:string}>
	 */
	private function maxEntriesChoices(): array {
		$values = array( 50, 100, 250, 500, 1000, 2500 );
		$out    = array();
		foreach ( $values as $value ) {
			$out[] = array(
				'value' => $value,
				'label' => (string) $value,
			);
		}

		return $out;
	}

	/**
	 * @return list<array{value:int,label:string}>
	 */
	private function transcriptChoices(): array {
		$values = array( 64, 128, 256, 512, 1024 );
		$out    = array();
		foreach ( $values as $value ) {
			$out[] = array(
				'value' => $value,
				'label' => sprintf(
					/* translators: %d: kilobytes */
					__( '%d KB', 'post-smtp' ),
					$value
				),
			);
		}

		return $out;
	}

	/**
	 * @return list<array{value:int,label:string}>
	 */
	private function retryAttemptChoices(): array {
		$out = array();
		for ( $i = 1; $i <= 10; $i++ ) {
			$out[] = array(
				'value' => $i,
				'label' => (string) $i,
			);
		}

		return $out;
	}
}
