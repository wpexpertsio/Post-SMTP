<?php
/**
 * Email Queue settings tab (Figma 672:7781) — Pro Advanced Delivery & Logs.
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsEmailQueueService {

	/**
	 * @return array<string,mixed>
	 */
	public function getEmailQueue(): array {
		$payload = array(
			'is_pro'                 => SettingsEmailQueuePanelHooks::isProForApi(),
			'panel_locked'           => SettingsEmailQueuePanelHooks::isPanelLocked(),
			'panel_config'           => SettingsEmailQueuePanelHooks::panelConfig(),
			'values'                 => AedlOptions::valuesForQueueApi(),
			'preview_sections'       => SettingsEmailQueuePanelHooks::isPanelLocked() ? AedlOptions::previewSections() : array(),
			'quota_duration_choices' => $this->quotaDurationChoices(),
			'retry_attempt_choices'  => $this->retryAttemptChoices(),
			'copy'                   => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);

		/**
		 * Full Email Queue settings GET payload.
		 *
		 * @param array<string,mixed>       $payload REST response body.
		 * @param SettingsEmailQueueService $service Service instance.
		 */
		$filtered = apply_filters( 'post_smtp_settings_email_queue_get', $payload, $this );

		return is_array( $filtered ) ? $filtered : $payload;
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveEmailQueue( array $payload ): array {
		if ( SettingsEmailQueuePanelHooks::isPanelLocked() ) {
			do_action( 'post_smtp_settings_email_queue_locked_save_attempt', $payload );

			return array(
				'success' => false,
				'message' => __( 'Email Queue requires Post SMTP Pro.', 'post-smtp' ),
			);
		}

		$was_enabled = AedlOptions::wasAdvanceDeliveryEnabled();

		$validation = AedlOptions::validateAndBuildPatch( $payload );
		if ( empty( $validation['success'] ) ) {
			return array(
				'success' => false,
				'message' => $validation['message'] ?? __( 'Could not save settings.', 'post-smtp' ),
			);
		}

		$patch  = $validation['patch'] ?? array();
		$merged = AedlOptions::savePatch( $patch );

		$now_enabled = ! empty( $merged['enable_advance_delivery'] );
		if ( $was_enabled && ! $now_enabled ) {
			do_action( 'post_smtp_aedl_advance_delivery_disabled' );
		}

		$result = array(
			'success' => true,
			'message' => __( 'Settings saved.', 'post-smtp' ),
			'values'  => AedlOptions::valuesForQueueApi(),
		);

		$filtered = apply_filters( 'post_smtp_settings_email_queue_save_result', $result, $payload );

		return is_array( $filtered ) ? $filtered : $result;
	}

	/**
	 * @return list<array{value:string,label:string}>
	 */
	private function quotaDurationChoices(): array {
		return array(
			array(
				'value' => 'h',
				'label' => __( 'Per Hour', 'post-smtp' ),
			),
			array(
				'value' => 'd',
				'label' => __( 'Per Day', 'post-smtp' ),
			),
			array(
				'value' => 'w',
				'label' => __( 'Per Week', 'post-smtp' ),
			),
			array(
				'value' => 'm',
				'label' => __( 'Per Month', 'post-smtp' ),
			),
		);
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
