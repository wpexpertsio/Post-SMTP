<?php
/**
 * Cloudflare Email API provider.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CloudflareEmailProvider extends AbstractApiProvider implements ProviderInterface {

	public static function slug(): string {
		return 'cloudflare_email_api';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'Cloudflare Email Connection', 'post-smtp' ),
			'description'       => __( 'Add your Cloudflare Email details and how your emails should look.', 'post-smtp' ),
			'display_name'      => __( 'Cloudflare Email', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 48,
			'configure_title'   => __( 'Configure Cloudflare Email', 'post-smtp' ),
			'doc_url'           => 'https://postmansmtp.com/documentation/sockets-addons/post-smtp-complete-mailer-guide/',
			'doc_label'         => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'    => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'cloudflare_email_api_key',
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
				'cloudflare_email_api_key'      => array(
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
					'description' => __( 'Paste the API key from your Cloudflare Email account.', 'post-smtp' ),
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		$api_key    = (string) ( $connection['cloudflare_email_api_key'] ?? '' );
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
			'message'    => __( 'Cloudflare Email connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		return array(
			'success' => false,
			'message' => __( 'Cloudflare Email send is not available in this build yet.', 'post-smtp' ),
			'send'    => false,
		);
	}
}
