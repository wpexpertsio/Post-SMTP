<?php
/**
 * Email Reporting settings tab (Figma 672:7419 / 672:7450).
 *
 * @package PostSMTP\Settings
 */

namespace PostSMTP\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsEmailReportingService {

	/**
	 * @return array<string,mixed>
	 */
	public function getEmailReporting(): array {
		$locked = SettingsEmailReportingPanelHooks::isOpenTrackingLocked();

		$payload = array(
			'values'              => $this->readWeeklyValues(),
			'frequency_choices'   => $this->frequencyChoices(),
			'open_tracking'       => SettingsEmailReportingPanelHooks::getOpenTrackingPanelForApi(),
			'panel_config'        => $this->panelConfig( $locked ),
			'copy'                => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);

		/**
		 * Full Email Reporting settings GET payload.
		 *
		 * @param array<string,mixed>           $payload REST response body.
		 * @param SettingsEmailReportingService $service Service instance.
		 */
		$filtered = apply_filters( 'post_smtp_settings_email_reporting_get', $payload, $this );

		return is_array( $filtered ) ? $filtered : $payload;
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>,open_tracking?:array<string,mixed>}
	 */
	public function saveEmailReporting( array $payload ): array {
		if ( $this->payloadTouchesOpenTracking( $payload ) && SettingsEmailReportingPanelHooks::isOpenTrackingLocked() ) {
			/**
			 * Fires when the client POST includes open-tracking fields while that block is locked.
			 *
			 * @param array<string,mixed> $payload Save payload.
			 */
			do_action( 'post_smtp_settings_email_reporting_open_tracking_locked_save_attempt', $payload );

			return array(
				'success' => false,
				'message' => __( 'Upgrade to Post SMTP Pro to configure open email tracking.', 'post-smtp' ),
			);
		}

		$rat     = get_option( SettingsEmailReportingPanelHooks::RAT_OPTION, array() );
		$rat     = is_array( $rat ) ? $rat : array();
		$rat     = array_merge( $rat, $this->mapWeeklyPayloadToRat( $payload ) );
		update_option( SettingsEmailReportingPanelHooks::RAT_OPTION, $rat );
		$this->rescheduleReportingCron( $rat );

		$result = array(
			'success'       => true,
			'message'       => __( 'Settings saved.', 'post-smtp' ),
			'values'        => $this->readWeeklyValues(),
			'open_tracking' => SettingsEmailReportingPanelHooks::getOpenTrackingPanelForApi(),
		);

		/**
		 * After core weekly report options are saved (Pro may persist open tracking here).
		 *
		 * @param array{success:bool,message:string,values?:array<string,mixed>,open_tracking?:array<string,mixed>} $result  Save response.
		 * @param array<string,mixed>                                                                               $payload Client payload.
		 */
		$filtered = apply_filters( 'post_smtp_settings_email_reporting_save_result', $result, $payload );

		return is_array( $filtered ) ? $filtered : $result;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readWeeklyValues(): array {
		$rat = get_option( SettingsEmailReportingPanelHooks::RAT_OPTION, array() );
		if ( ! is_array( $rat ) ) {
			$rat = array();
		}

		return array(
			'enable_weekly_report' => ! empty( $rat['enable_email_reporting'] ),
			'reporting_interval'   => isset( $rat['reporting_interval'] ) ? (string) $rat['reporting_interval'] : 'w',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapWeeklyPayloadToRat( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'enable_weekly_report', $payload ) ) {
			$patch['enable_email_reporting'] = ! empty( $payload['enable_weekly_report'] ) ? 1 : 0;
		}

		if ( isset( $payload['reporting_interval'] ) ) {
			$interval = sanitize_text_field( (string) $payload['reporting_interval'] );
			$allowed  = array( 'd', 'w', 'm' );
			if ( in_array( $interval, $allowed, true ) ) {
				$patch['reporting_interval'] = $interval;
			}
		}

		return $patch;
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private function payloadTouchesOpenTracking( array $payload ): bool {
		return array_key_exists( 'track_email_opens', $payload );
	}

	/**
	 * @param array<string,mixed> $rat
	 */
	private function rescheduleReportingCron( array $rat ): void {
		wp_clear_scheduled_hook( 'postman_rat_email_report' );

		if ( empty( $rat['enable_email_reporting'] ) ) {
			return;
		}

		$schedules = array(
			'd' => 'daily',
			'w' => 'weekly',
			'm' => 'monthly',
		);

		$interval = isset( $rat['reporting_interval'] ) ? (string) $rat['reporting_interval'] : 'w';
		if ( ! isset( $schedules[ $interval ] ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'postman_rat_email_report' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), $schedules[ $interval ], 'postman_rat_email_report' );
		}
	}

	/**
	 * @return list<array{value:string,label:string}>
	 */
	private function frequencyChoices(): array {
		return array(
			array(
				'value' => 'd',
				'label' => __( 'Daily', 'post-smtp' ),
			),
			array(
				'value' => 'w',
				'label' => __( 'Weekly', 'post-smtp' ),
			),
			array(
				'value' => 'm',
				'label' => __( 'Monthly', 'post-smtp' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function panelConfig( bool $locked ): array {
		/**
		 * Upgrade CTA config for the Email Reporting tab.
		 *
		 * @param array<string,mixed> $config Config fragments.
		 * @param bool                $locked Whether open tracking is locked.
		 */
		$config = apply_filters( 'post_smtp_settings_email_reporting_panel_config', array(), $locked );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		return $config;
	}
}
