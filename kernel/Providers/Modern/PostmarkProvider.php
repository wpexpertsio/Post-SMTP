<?php
/**
 * Postmark API provider.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostmarkProvider extends AbstractApiProvider implements ProviderInterface {

	private const API_URL = 'https://api.postmarkapp.com/email';

	public static function slug(): string {
		return 'postmark_api';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'Postmark Connection', 'post-smtp' ),
			'description'       => __( 'Add your Postmark details and how your emails should look.', 'post-smtp' ),
			'display_name'      => __( 'Postmark', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 45,
			'configure_title'   => __( 'Configure Postmark', 'post-smtp' ),
			'doc_url'           => 'https://postmansmtp.com/documentation/sockets-addons/postmark/',
			'doc_label'         => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'    => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'postmark_api_key',
			),
			'fields'            => array(
				'connection_title'              => array(
					'required'    => true,
					'label'       => __( 'Connection title', 'post-smtp' ),
					'description' => __( 'Use a unique title, such as “Website emails” or “Backup delivery”, even when both connections use the same mailer.', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'e.g Website email', 'post-smtp' ),
				),
				'from_name'                     => array(
					'required'    => false,
					'label'       => __( 'From name', 'post-smtp' ),
					'description' => __( 'The name people see in their inbox.', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'Placeholder', 'post-smtp' ),
				),
				'prevent_sender_name_override'  => array(
					'required'   => false,
					'label'      => __( 'Use this From name even when another plugin sets a different name.', 'post-smtp' ),
					'input_type' => 'checkbox',
					'inline'     => true,
				),
				'from_email'                    => array(
					'required'    => true,
					'label'       => __( 'From email address', 'post-smtp' ),
					'description' => __( 'Use an email on your own domain.', 'post-smtp' ),
					'input_type'  => 'email',
					'placeholder' => __( 'Placeholder', 'post-smtp' ),
				),
				'prevent_sender_email_override' => array(
					'required'   => false,
					'label'      => __( 'Use this From email address even when another plugin sets a different address.', 'post-smtp' ),
					'input_type' => 'checkbox',
					'inline'     => true,
				),
				'postmark_api_key'              => array(
					'required'    => true,
					'label'       => __( 'API key', 'post-smtp' ),
					'input_type'  => 'password',
					'encrypt'     => true,
					'placeholder' => __( 'Enter your API key', 'post-smtp' ),
					'section'     => 'api_credentials',
				),
			),
			'sections'          => array(
				'api_credentials' => array(
					'title'       => __( 'API credentials', 'post-smtp' ),
					'description' => __( 'Paste the API key from your Postmark account.', 'post-smtp' ),
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		$api_key    = (string) ( $connection['postmark_api_key'] ?? '' );
		$from_email = (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' );

		if ( '' === $api_key || '' === $from_email ) {
			return array(
				'success'    => false,
				'message'    => __( 'API key and From email are required.', 'post-smtp' ),
				'error_code' => 400,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'Postmark connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$api_key    = sanitize_text_field( (string) ( $connection['postmark_api_key'] ?? '' ) );
		$from_email = sanitize_email( (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ) );
		$from_name  = sanitize_text_field( (string) ( $connection['sender_name'] ?? $connection['from_name'] ?? get_bloginfo( 'name' ) ) );
		$to         = $processed_data['to'][0]['email'] ?? ( is_string( $atts['to'] ?? '' ) ? $atts['to'] : '' );

		$payload = array(
			'From'     => sprintf( '%s <%s>', $from_name, $from_email ),
			'To'       => sanitize_email( $to ),
			'Subject'  => sanitize_text_field( (string) ( $atts['subject'] ?? '' ) ),
			'HtmlBody' => (string) ( $atts['message'] ?? '' ),
		);

		return $this->postJson(
			self::API_URL,
			array(
				'Accept'                  => 'application/json',
				'Content-Type'            => 'application/json',
				'X-Postmark-Server-Token' => $api_key,
			),
			$payload
		);
	}
}
