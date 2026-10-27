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
			'title'             => __( 'SMTP Connection', 'post-smtp' ),
			'description'       => __( 'Send mail through an SMTP server.', 'post-smtp' ),
			'display_name'      => __( 'SMTP', 'post-smtp' ),
			'provider_type'     => 'free',
			'provider_sequence' => 10,
			'field_sequence'    => array(
				'connection_title',
				'hostname',
				'port',
				'enc_type',
				'basic_auth_username',
				'basic_auth_password',
				'from_email',
				'from_name',
			),
			'fields'            => array(
				'hostname'            => array(
					'required'   => true,
					'label'      => __( 'SMTP Host', 'post-smtp' ),
					'input_type' => 'text',
				),
				'port'                => array(
					'required'   => true,
					'label'      => __( 'Port', 'post-smtp' ),
					'input_type' => 'number',
				),
				'basic_auth_password' => array(
					'required'   => false,
					'label'      => __( 'Password', 'post-smtp' ),
					'input_type' => 'password',
					'encrypt'    => true,
				),
			),
		);
	}

	public function authenticate( array $connection ): array {
		if ( empty( $connection['hostname'] ) || empty( $connection['port'] ) ) {
			return array(
				'success'    => false,
				'message'    => __( 'SMTP host and port are required.', 'post-smtp' ),
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
