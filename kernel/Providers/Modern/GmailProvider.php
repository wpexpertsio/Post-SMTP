<?php
/**
 * Gmail API provider (manual OAuth credentials in free; Pro extends via hooks).
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GmailProvider extends AbstractApiProvider implements ProviderInterface {

	public static function slug(): string {
		return 'gmail_api';
	}

	public static function getOptions(): array {
		$schema = array(
			'title'               => __( 'Gmail Connection', 'post-smtp' ),
			'description'         => __( 'Add your Gmail details and how your emails should look.', 'post-smtp' ),
			'display_name'        => __( 'Gmail', 'post-smtp' ),
			'provider_type'       => 'oauth',
			'provider_sequence'   => 10,
			'configure_title'     => __( 'Configure Gmail', 'post-smtp' ),
			'configure_layout'    => 'gmail_oauth',
			'configure_figma_node' => '1310:15740',
			'doc_url'             => 'https://postmansmtp.com/docs/mailers/how-to-setup-gmail-with-post-smtp/',
			'doc_label'           => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'      => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'oauth_client_id',
				'oauth_client_secret',
			),
			'fields'              => array(
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
				'oauth_client_id'               => array(
					'required'    => true,
					'label'       => __( 'Client ID', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( '00000000-0000-0000-0000-000000000', 'post-smtp' ),
					'section'     => 'gmail_manual',
				),
				'oauth_client_secret'           => array(
					'required'    => true,
					'label'       => __( 'Client secret', 'post-smtp' ),
					'input_type'  => 'password',
					'encrypt'     => true,
					'placeholder' => __( 'Placeholder', 'post-smtp' ),
					'section'     => 'gmail_manual',
				),
			),
			'sections'            => array(
				'gmail_manual' => array(
					'title'       => __( 'Connect it yourself', 'post-smtp' ),
					'description' => __( 'Create an app in your Google account, then paste the values below.', 'post-smtp' ),
				),
			),
		);

		/**
		 * Filter Gmail provider schema for Connections configure UI.
		 *
		 * @param array<string,mixed> $schema Provider schema from GmailProvider::getOptions().
		 */
		return apply_filters( 'post_smtp_gmail_provider_schema', $schema );
	}

	public function authenticate( array $connection ): array {
		$from_email = (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' );

		/**
		 * Allow Pro one-click Gmail to validate/skip manual client credentials.
		 *
		 * @param array|null          $result     Return array to short-circuit, or null to continue.
		 * @param array<string,mixed> $connection Connection payload.
		 */
		$pro_result = apply_filters( 'post_smtp_gmail_authenticate', null, $connection );
		if ( is_array( $pro_result ) ) {
			return $pro_result;
		}

		$client_id     = (string) ( $connection['oauth_client_id'] ?? '' );
		$client_secret = (string) ( $connection['oauth_client_secret'] ?? '' );

		if ( '' === $client_id || '' === $client_secret || '' === $from_email ) {
			return array(
				'success'    => false,
				'message'    => __( 'Client ID, Client secret, and From email are required.', 'post-smtp' ),
				'error_code' => 400,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'Gmail connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$adapter = new \PostSMTP\Kernel\Providers\Legacy\LegacyProviderAdapter( self::slug() );
		return $adapter->send( $atts, $log_id, $connection, $processed_data );
	}
}
