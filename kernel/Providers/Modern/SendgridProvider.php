<?php
/**
 * SendGrid API provider.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SendgridProvider extends AbstractApiProvider implements ProviderInterface {

	private const API_URL = 'https://api.sendgrid.com/v3/mail/send';

	public static function slug(): string {
		return 'sendgrid_api';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'SendGrid Connection', 'post-smtp' ),
			'description'       => __( 'Enter your SendGrid API key.', 'post-smtp' ),
			'display_name'      => __( 'SendGrid', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 40,
			'field_sequence'    => array(
				'connection_title',
				'sendgrid_api_key',
				'from_email',
				'from_name',
			),
			'fields'            => array(
				'sendgrid_api_key' => array(
					'required'   => true,
					'label'      => __( 'API Key', 'post-smtp' ),
					'input_type' => 'password',
					'encrypt'    => true,
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		$mapped = $this->mapConnectionFields( $connection );
		if ( empty( $mapped['api_key'] ) || empty( $mapped['from_email'] ) ) {
			return array(
				'success'    => false,
				'message'    => __( 'API key and From Email are required.', 'post-smtp' ),
				'error_code' => 400,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'SendGrid connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$mapped = $this->mapConnectionFields( $connection );
		$to     = $processed_data['to'][0]['email'] ?? ( is_string( $atts['to'] ?? '' ) ? $atts['to'] : '' );

		$payload = array(
			'personalizations' => array(
				array(
					'to' => array(
						array( 'email' => sanitize_email( $to ) ),
					),
				),
			),
			'from'             => array(
				'email' => sanitize_email( $mapped['from_email'] ),
				'name'  => sanitize_text_field( $mapped['from_name'] ),
			),
			'subject'          => sanitize_text_field( (string) ( $atts['subject'] ?? '' ) ),
			'content'          => array(
				array(
					'type'  => 'text/html',
					'value' => (string) ( $atts['message'] ?? '' ),
				),
			),
		);

		return $this->postJson(
			self::API_URL,
			array(
				'Authorization' => 'Bearer ' . sanitize_text_field( $mapped['api_key'] ),
				'Content-Type'  => 'application/json',
			),
			$payload
		);
	}
}
