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
			'description'       => __( 'Add your SendGrid details and how your emails should look.', 'post-smtp' ),
			'display_name'      => __( 'SendGrid', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 40,
			'configure_title'   => __( 'Configure SendGrid', 'post-smtp' ),
			'doc_url'           => 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendgrid-with-post-smtp/',
			'doc_label'         => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'    => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'sendgrid_api_key',
			),
			'fields'            => array(
				'connection_title' => array(
					'required'    => true,
					'label'       => __( 'Connection title', 'post-smtp' ),
					'description' => __( 'Use a unique title, such as “Website emails” or “Backup delivery”, even when both connections use the same mailer.', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'e.g Website email', 'post-smtp' ),
				),
				'from_name'        => array(
					'required'    => false,
					'label'       => __( 'From name', 'post-smtp' ),
					'description' => __( 'The name people see in their inbox.', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'Placeholder', 'post-smtp' ),
				),
				'prevent_sender_name_override' => array(
					'required'    => false,
					'label'       => __( 'Use this From name even when another plugin sets a different name.', 'post-smtp' ),
					'input_type'  => 'checkbox',
					'inline'      => true,
				),
				'from_email'       => array(
					'required'    => true,
					'label'       => __( 'From email address', 'post-smtp' ),
					'description' => __( 'Use an email on your own domain.', 'post-smtp' ),
					'input_type'  => 'email',
					'placeholder' => __( 'Placeholder', 'post-smtp' ),
				),
				'prevent_sender_email_override' => array(
					'required'    => false,
					'label'       => __( 'Use this From email address even when another plugin sets a different address.', 'post-smtp' ),
					'input_type'  => 'checkbox',
					'inline'      => true,
				),
				'sendgrid_api_key' => array(
					'required'    => true,
					'label'       => __( 'API key', 'post-smtp' ),
					'input_type'  => 'password',
					'encrypt'     => true,
					'placeholder' => __( 'Enter your API key', 'post-smtp' ),
					'section'       => 'api_credentials',
				),
			),
			'sections'          => array(
				'api_credentials' => array(
					'title'       => __( 'API credentials', 'post-smtp' ),
					'description' => __( 'Paste the API key from your SendGrid account.', 'post-smtp' ),
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
