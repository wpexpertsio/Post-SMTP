<?php
/**
 * Modern SMTP provider — delegates to legacy transport when needed.
 *
 * @package PostSMTP\Kernel\Providers\Modern
 */

namespace PostSMTP\Kernel\Providers\Modern;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SmtpProvider implements ProviderInterface {

	public static function slug(): string {
		return 'smtp';
	}

	public static function getOptions(): array {
		return array(
			'title'             => __( 'Other SMTP Connection', 'post-smtp' ),
			'description'       => __( 'Add your Other SMTP details and how your emails should look.', 'post-smtp' ),
			'display_name'      => __( 'Other SMTP', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 10,
			'configure_title'   => __( 'Configure Other SMTP', 'post-smtp' ),
			'doc_url'           => 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-other-smtp/',
			'doc_label'         => __( 'Get credentials', 'post-smtp' ),
			'field_sequence'    => array(
				'connection_title',
				'from_name',
				'prevent_sender_name_override',
				'from_email',
				'prevent_sender_email_override',
				'hostname',
				'port',
				'basic_auth_username',
				'basic_auth_password',
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
				'hostname'                      => array(
					'required'    => true,
					'label'       => __( 'Outgoing mail server (hostname)', 'post-smtp' ),
					'description' => __( 'From your mail provider, e.g. smtp.example.com.', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'e.g. smtp.mailprovider.com', 'post-smtp' ),
					'section'     => 'smtp_server_details',
				),
				'port'                          => array(
					'required'    => true,
					'label'       => __( 'Port', 'post-smtp' ),
					'description' => __( 'Usually 587 (STARTTLS) or 465 (SSL).', 'post-smtp' ),
					'input_type'  => 'number',
					'placeholder' => __( 'e.g. 587', 'post-smtp' ),
					'section'     => 'smtp_server_details',
				),
				'basic_auth_username'           => array(
					'required'    => false,
					'label'       => __( 'Username', 'post-smtp' ),
					'input_type'  => 'text',
					'placeholder' => __( 'Usually the same as your Envelope-From email address', 'post-smtp' ),
					'section'     => 'smtp_server_details',
				),
				'basic_auth_password'           => array(
					'required'    => false,
					'label'       => __( 'Password / app password', 'post-smtp' ),
					'input_type'  => 'password',
					'encrypt'     => true,
					'placeholder' => __( 'Enter your password or app password', 'post-smtp' ),
					'section'     => 'smtp_server_details',
				),
			),
			'sections'          => array(
				'smtp_server_details' => array(
					'title'       => __( 'SMTP server details', 'post-smtp' ),
					'description' => __( 'Enter the details your provider gave you.', 'post-smtp' ),
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		$from_email = (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' );
		if ( empty( $connection['hostname'] ) || empty( $connection['port'] ) || '' === $from_email ) {
			return array(
				'success'    => false,
				'message'    => __( 'SMTP host, port, and From email are required.', 'post-smtp' ),
				'error_code' => 400,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'SMTP connection saved.', 'post-smtp' ),
			'error_code' => 200,
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		$adapter = new \PostSMTP\Kernel\Providers\Legacy\LegacyProviderAdapter( self::slug() );
		return $adapter->send( $atts, $log_id, $connection, $processed_data );
	}
}
