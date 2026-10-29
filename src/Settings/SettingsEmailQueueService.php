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

	private const PRO_OPTION = 'ps_aedl_option';

	/**
	 * @return array<string,mixed>
	 */
	public function getEmailQueue(): array {
		$pro = $this->readProSettings();

		return array(
			'is_pro'                 => $this->isProActive(),
			'values'                 => $this->readValues( $pro ),
			'quota_duration_choices' => $this->quotaDurationChoices(),
			'copy'                   => array(
				'saved' => __( 'Settings saved.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,values?:array<string,mixed>}
	 */
	public function saveEmailQueue( array $payload ): array {
		if ( ! $this->isProActive() ) {
			return array(
				'success' => false,
				'message' => __( 'Email Queue requires Post SMTP Pro.', 'post-smtp' ),
			);
		}

		$settings = get_option( self::PRO_OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$patch = $this->mapPayloadToProSettings( $payload );
		$merged = array_merge( $settings, $patch );

		if ( empty( $merged['quota_duration'] ) ) {
			$merged['quota_duration'] = 'd';
		}

		update_option( self::PRO_OPTION, $merged );

		return array(
			'success' => true,
			'message' => __( 'Settings saved.', 'post-smtp' ),
			'values'  => $this->readValues( $merged ),
		);
	}

	/**
	 * @param array<string,mixed> $pro
	 * @return array<string,mixed>
	 */
	private function readValues( array $pro ): array {
		return array(
			'enable_advance_delivery'  => ! empty( $pro['enable_advance_delivery'] ),
			'emails_per_batch'         => isset( $pro['emails_per_batch'] ) ? absint( $pro['emails_per_batch'] ) : 20,
			'batch_duration'           => isset( $pro['batch_duration'] ) ? absint( $pro['batch_duration'] ) : 60,
			'enable_quota'             => ! empty( $pro['enable_quota'] ),
			'advanced_quota_settings'  => ! empty( $pro['advanced_quota_settings'] ),
			'quota_value'              => isset( $pro['quota_value'] ) ? absint( $pro['quota_value'] ) : 100,
			'quota_duration'           => isset( $pro['quota_duration'] ) ? sanitize_key( (string) $pro['quota_duration'] ) : 'd',
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readProSettings(): array {
		$settings = get_option( self::PRO_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function mapPayloadToProSettings( array $payload ): array {
		$patch = array();

		if ( array_key_exists( 'enable_advance_delivery', $payload ) ) {
			$patch['enable_advance_delivery'] = ! empty( $payload['enable_advance_delivery'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'emails_per_batch', $payload ) ) {
			$patch['emails_per_batch'] = max( 1, absint( $payload['emails_per_batch'] ) );
		}

		if ( array_key_exists( 'batch_duration', $payload ) ) {
			$patch['batch_duration'] = max( 0, absint( $payload['batch_duration'] ) );
		}

		if ( array_key_exists( 'enable_quota', $payload ) ) {
			$patch['enable_quota'] = ! empty( $payload['enable_quota'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'advanced_quota_settings', $payload ) ) {
			$patch['advanced_quota_settings'] = ! empty( $payload['advanced_quota_settings'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'quota_value', $payload ) ) {
			$patch['quota_value'] = max( 1, absint( $payload['quota_value'] ) );
		}

		if ( array_key_exists( 'quota_duration', $payload ) ) {
			$duration = sanitize_key( (string) $payload['quota_duration'] );
			$allowed  = array( 'h', 'd', 'w', 'm' );
			if ( in_array( $duration, $allowed, true ) ) {
				$patch['quota_duration'] = $duration;
			}
		}

		return $patch;
	}

	private function isProActive(): bool {
		return defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );
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
}
