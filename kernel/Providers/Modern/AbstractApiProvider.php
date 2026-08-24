<?php
/**
 * Shared HTTP logic for API mail providers.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractApiProvider {

	/**
	 * @param string               $url     API URL.
	 * @param array<string,string> $headers Request headers.
	 * @param array<string,mixed>  $body    JSON body.
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string}
	 */
	protected function postJson( string $url, array $headers, array $body ): array {
		$response = wp_safe_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'    => false,
				'message'    => $response->get_error_message(),
				'send'       => false,
				'error_code' => $response->get_error_code(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return array(
				'success' => true,
				'message' => __( 'Email sent successfully.', 'post-smtp' ),
				'send'    => true,
			);
		}

		$body_text = wp_remote_retrieve_body( $response );
		return array(
			'success'    => false,
			'message'    => $body_text ?: sprintf( __( 'HTTP %d error.', 'post-smtp' ), $code ),
			'send'       => false,
			'error_code' => $code,
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 */
	protected function mapConnectionFields( array $connection ): array {
		return array(
			'api_key'    => $connection['sendgrid_api_key'] ?? $connection['sendinblue_api_key'] ?? $connection['api_key'] ?? '',
			'from_email' => $connection['sender_email'] ?? $connection['from_email'] ?? '',
			'from_name'  => $connection['sender_name'] ?? $connection['from_name'] ?? get_bloginfo( 'name' ),
		);
	}
}
