<?php
/**
 * Mobile App admin module (QR connect, devices).
 *
 * @package PostSMTP\Mobile
 */

namespace PostSMTP\Mobile;

use PostmanPreRequisitesCheck;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MobileAppService {

	private const AUTH_TRANSIENT = 'post_smtp_auth_nonce';

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		$gd_ok     = function_exists( 'ImageCreate' );
		$connected = get_option( 'post_smtp_mobile_app_connection', array() );
		$devices   = is_array( $connected ) ? $this->normalizeDevices( $connected ) : array();

		$payload = array(
			'gd_enabled'       => $gd_ok,
			'is_connected'     => ! empty( $devices ),
			'devices'          => $devices,
			'store_links'      => array(
				'google_play' => 'https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Dsettings%26anid%3Dadmob',
				'app_store'   => 'https://apps.apple.com/us/app/post-smtp/id6473368559?utm_source=plugin&utm_medium=dashboard&utm_campaign=plugin',
			),
			'docs_url'         => 'https://postmansmtp.com/documentation/post-smtp-mobile-app/download-the-app-and-connect-with-plugin/?utm_source=plugin&utm_medium=settings',
			'promo_features'   => array(
				__( 'Support multiple sites', 'post-smtp' ),
				__( 'Easy email tracking & previews', 'post-smtp' ),
				__( 'Quick error details', 'post-smtp' ),
				__( 'Resend failed emails on the go', 'post-smtp' ),
			),
			'benefits'         => array(
				__( 'Delivery at a glance', 'post-smtp' ),
				__( 'Failure alerts', 'post-smtp' ),
				__( 'Resend failed emails', 'post-smtp' ),
			),
			'qr'               => null,
			'secret_key'       => '',
			'connect_url'      => '',
			'qr_error'         => '',
		);

		if ( ! $gd_ok ) {
			$payload['qr_error'] = __(
				'Your server does not have the GD library enabled. Contact your host to enable it before connecting the mobile app.',
				'post-smtp'
			);
			return $payload;
		}

		if ( empty( $devices ) ) {
			$qr = $this->buildQrPayload();
			if ( empty( $qr['qr_data_url'] ) ) {
				$payload['qr_error'] = ! empty( $qr['error'] )
					? (string) $qr['error']
					: __(
						'QR code is unavailable because another plugin loaded a conflicting QR library.',
						'post-smtp'
					);
			} else {
				$payload['qr']          = $qr['qr_data_url'];
				$payload['secret_key']  = $qr['secret_key'];
				$payload['connect_url'] = $qr['connect_url'];
			}
		}

		return $payload;
	}

	/**
	 * @return array{success:bool,message?:string,bootstrap?:array<string,mixed>}
	 */
	public function regenerate(): array {
		if ( ! function_exists( 'ImageCreate' ) ) {
			return array(
				'success' => false,
				'message' => __( 'GD library is not available on this server.', 'post-smtp' ),
			);
		}

		delete_transient( self::AUTH_TRANSIENT );

		return array(
			'success'   => true,
			'message'   => __( 'QR code and key regenerated.', 'post-smtp' ),
			'bootstrap' => $this->getBootstrap(),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,bootstrap?:array<string,mixed>}
	 */
	public function disconnectDevice( array $payload ): array {
		$token = isset( $payload['fcm_token'] ) ? sanitize_text_field( (string) $payload['fcm_token'] ) : '';
		if ( '' === $token ) {
			return array(
				'success' => false,
				'message' => __( 'Device token is required.', 'post-smtp' ),
			);
		}

		$connected = get_option( 'post_smtp_mobile_app_connection', array() );
		if ( ! is_array( $connected ) || ! isset( $connected[ $token ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Device not found.', 'post-smtp' ),
			);
		}

		$device     = $connected[ $token ];
		$auth_key   = $device['auth_key'] ?? '';
		$server_url = get_option( 'post_smtp_server_url' );

		if ( $server_url && $auth_key ) {
			$response = wp_remote_post(
				trailingslashit( (string) $server_url ) . 'disconnect-app',
				array(
					'method'  => 'PUT',
					'headers' => array(
						'Content-Type' => 'application/json',
						'Auth-Key'     => $auth_key,
						'FCM-Token'    => $token,
					),
					'timeout' => 15,
				)
			);

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				return array(
					'success' => false,
					'message' => __( 'Could not disconnect the device from the mobile service.', 'post-smtp' ),
				);
			}
		}

		unset( $connected[ $token ] );
		if ( empty( $connected ) ) {
			delete_option( 'post_smtp_mobile_app_connection' );
			delete_option( 'post_smtp_server_url' );
		} else {
			update_option( 'post_smtp_mobile_app_connection', $connected, false );
		}

		return array(
			'success'   => true,
			'message'   => __( 'Device disconnected.', 'post-smtp' ),
			'bootstrap' => $this->getBootstrap(),
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,bootstrap?:array<string,mixed>}
	 */
	public function updateNotifications( array $payload ): array {
		$enabled_tokens = array();
		if ( ! empty( $payload['enabled_tokens'] ) && is_array( $payload['enabled_tokens'] ) ) {
			foreach ( $payload['enabled_tokens'] as $token ) {
				$enabled_tokens[] = sanitize_text_field( (string) $token );
			}
		}

		$connected = get_option( 'post_smtp_mobile_app_connection', array() );
		if ( ! is_array( $connected ) || empty( $connected ) ) {
			return array(
				'success' => false,
				'message' => __( 'No connected devices.', 'post-smtp' ),
			);
		}

		foreach ( $connected as $token => $device ) {
			$connected[ $token ]['enable_notification'] = in_array( (string) $token, $enabled_tokens, true ) ? 1 : 0;
		}

		update_option( 'post_smtp_mobile_app_connection', $connected, false );

		return array(
			'success'   => true,
			'message'   => __( 'Notification preferences saved.', 'post-smtp' ),
			'bootstrap' => $this->getBootstrap(),
		);
	}

	/**
	 * @param array<string,mixed> $connected
	 * @return list<array<string,mixed>>
	 */
	private function normalizeDevices( array $connected ): array {
		$list = array();
		foreach ( $connected as $token => $device ) {
			if ( ! is_array( $device ) ) {
				continue;
			}
			$list[] = array(
				'fcm_token'            => (string) $token,
				'device'               => sanitize_text_field( (string) ( $device['device'] ?? __( 'Mobile device', 'post-smtp' ) ) ),
				'enable_notification'  => ! empty( $device['enable_notification'] ),
			);
		}
		return $list;
	}

	/**
	 * @return array{qr_data_url:string,secret_key:string,connect_url:string,error:string}
	 */
	private function buildQrPayload(): array {
		$qr_class = $this->getQrcodeClass();
		if ( null === $qr_class ) {
			return array(
				'qr_data_url'  => '',
				'secret_key'   => '',
				'connect_url'  => '',
				'error'        => __(
					'QR code is unavailable because another plugin loaded a conflicting QR library.',
					'post-smtp'
				),
			);
		}

		$auth_key   = get_transient( self::AUTH_TRANSIENT );
		$auth_key   = $auth_key ? (string) $auth_key : $this->generateAuthKey();
		$site_title = get_bloginfo( 'name' );
		set_transient( self::AUTH_TRANSIENT, $auth_key, 1800 );

		$connect_url = site_url( '?authkey=' . rawurlencode( $auth_key ) . '&site_title=' . rawurlencode( $site_title ) );
		$encoded     = urlencode_deep( $connect_url );

		ob_start();
		$qr_class::png( $encoded );
		$png = ob_get_clean();

		if ( empty( $png ) ) {
			return array(
				'qr_data_url' => '',
				'secret_key'  => '',
				'connect_url' => '',
				'error'       => __( 'Could not generate QR code.', 'post-smtp' ),
			);
		}

		$secret_display = 'pst_sk_' . substr( $auth_key, 0, 16 );

		return array(
			'qr_data_url'  => 'data:image/png;base64,' . base64_encode( $png ),
			'secret_key'   => $secret_display,
			'connect_url'  => $connect_url,
			'error'        => '',
		);
	}

	private function generateAuthKey(): string {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( \Exception $e ) {
			return md5( uniqid( (string) mt_rand(), true ) );
		}
	}

	/**
	 * @return class-string|null
	 */
	private function getQrcodeClass() {
		if ( ! PostmanPreRequisitesCheck::checkZlibEncode() ) {
			return null;
		}

		$candidates = array( 'QRcode', 'Yeekitqrcode' );
		$candidates = apply_filters( 'post_smtp_qrcode_class_candidates', $candidates );
		foreach ( $candidates as $class_name ) {
			if ( class_exists( $class_name ) && method_exists( $class_name, 'png' ) ) {
				return $class_name;
			}
		}
		if ( class_exists( 'QRcode', false ) ) {
			return null;
		}
		if ( defined( 'QR_MODE_NUL' ) || class_exists( 'qrstr' ) ) {
			return null;
		}

		$lib = POST_SMTP_PATH . '/Postman/Mobile/includes/phpqrcode/qrlib.php';
		if ( file_exists( $lib ) ) {
			include_once $lib;
		}

		return class_exists( 'QRcode' ) ? 'QRcode' : null;
	}
}
