<?php
/**
 * Brevo (Sendinblue) API provider.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BrevoProvider extends AbstractApiProvider implements ProviderInterface {

	private const API_URL = 'https://api.brevo.com/v3/smtp/email';

	public static function slug(): string {
		return 'sendinblue_api';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'Brevo Connection', 'post-smtp' ),
			'description'       => __( 'Enter your Brevo API key.', 'post-smtp' ),
			'display_name'      => __( 'Brevo', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 50,
			'field_sequence'    => array(
				'connection_title',
				'sendinblue_api_key',
				'from_email',
				'from_name',
			),
			'fields'            => array(
				'sendinblue_api_key' => array(
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
			'message'    => __( 'Brevo connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$mapped = $this->mapConnectionFields( $connection );
		$to     = $processed_data['to'][0]['email'] ?? ( is_string( $atts['to'] ?? '' ) ? $atts['to'] : '' );

		$payload = array(
			'sender'      => array(
				'email' => sanitize_email( $mapped['from_email'] ),
				'name'  => sanitize_text_field( $mapped['from_name'] ),
			),
			'to'          => array(
				array( 'email' => sanitize_email( $to ) ),
			),
			'subject'     => sanitize_text_field( (string) ( $atts['subject'] ?? '' ) ),
			'htmlContent' => (string) ( $atts['message'] ?? '' ),
		);

		return $this->postJson(
			self::API_URL,
			array(
				'api-key'      => sanitize_text_field( $mapped['api_key'] ),
				'Content-Type' => 'application/json',
				'accept'       => 'application/json',
			),
			$payload
		);
	}
}
