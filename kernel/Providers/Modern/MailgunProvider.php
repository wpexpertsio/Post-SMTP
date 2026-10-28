<?php
/**
 * Mailgun API provider.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MailgunProvider extends AbstractApiProvider implements ProviderInterface {

	public static function slug(): string {
		return 'mailgun_api';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'Mailgun Connection', 'post-smtp' ),
			'description'       => __( 'Add your Mailgun details and how your emails should look.', 'post-smtp' ),
			'display_name'      => __( 'Mailgun', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 47,
			'configure_title'   => __( 'Configure Mailgun', 'post-smtp' ),
			'doc_url'           => 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-mail-gun/',
			'doc_label'         => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'    => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'mailgun_api_key',
				'mailgun_domain_name',
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
				'mailgun_api_key'               => array(
					'required'    => true,
					'label'       => __( 'API key', 'post-smtp' ),
					'input_type'  => 'password',
					'encrypt'     => true,
					'placeholder' => __( 'Enter your API key', 'post-smtp' ),
					'section'     => 'api_credentials',
				),
				'mailgun_domain_name'           => array(
					'required'    => true,
					'label'       => __( 'Domain name', 'post-smtp' ),
					'description' => __( 'Your Mailgun sending domain (e.g. mg.example.com).', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'Enter your domain name', 'post-smtp' ),
					'section'     => 'api_credentials',
				),
			),
			'sections'          => array(
				'api_credentials' => array(
					'title'       => __( 'API credentials', 'post-smtp' ),
					'description' => __( 'Paste the API key from your Mailgun account.', 'post-smtp' ),
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		$mapped = $this->mapMailgunFields( $connection );
		if ( empty( $mapped['api_key'] ) || empty( $mapped['from_email'] ) || empty( $mapped['domain'] ) ) {
			return array(
				'success'    => false,
				'message'    => __( 'API key, domain name, and From email are required.', 'post-smtp' ),
				'error_code' => 400,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'Mailgun connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$mapped = $this->mapMailgunFields( $connection );
		$to     = $processed_data['to'][0]['email'] ?? ( is_string( $atts['to'] ?? '' ) ? $atts['to'] : '' );
		$domain = sanitize_text_field( $mapped['domain'] );
		$url    = sprintf( 'https://api.mailgun.net/v3/%s/messages', rawurlencode( $domain ) );

		$body = array(
			'from'    => sprintf(
				'%s <%s>',
				sanitize_text_field( $mapped['from_name'] ),
				sanitize_email( $mapped['from_email'] )
			),
			'to'      => sanitize_email( $to ),
			'subject' => sanitize_text_field( (string) ( $atts['subject'] ?? '' ) ),
			'html'    => (string) ( $atts['message'] ?? '' ),
		);

		$response = wp_safe_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'api:' . sanitize_text_field( $mapped['api_key'] ) ),
				),
				'body'    => $body,
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

		return array(
			'success'    => false,
			'message'    => wp_remote_retrieve_body( $response ) ?: sprintf( __( 'HTTP %d error.', 'post-smtp' ), $code ),
			'send'       => false,
			'error_code' => $code,
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array{api_key:string,from_email:string,from_name:string,domain:string}
	 */
	private function mapMailgunFields( array $connection ): array {
		return array(
			'api_key'    => (string) ( $connection['mailgun_api_key'] ?? '' ),
			'from_email' => (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ),
			'from_name'  => (string) ( $connection['sender_name'] ?? $connection['from_name'] ?? get_bloginfo( 'name' ) ),
			'domain'     => (string) ( $connection['mailgun_domain_name'] ?? '' ),
		);
	}
}
