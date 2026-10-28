<?php
/**
 * Default form schemas for catalog mailers without dedicated provider classes.
 *
 * @package PostSMTP\Kernel\Providers
 */

namespace PostSMTP\Kernel\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CatalogProviderSchema {

	/**
	 * @return array<string,mixed>
	 */
	public static function forSlug( string $slug ): array {
		$label = ucwords( str_replace( array( '_api', '_' ), array( '', ' ' ), $slug ) );

		$common = array(
			'connection_title' => array(
				'required'   => true,
				'label'      => __( 'Connection Title', 'post-smtp' ),
				'input_type' => 'text',
			),
			'from_name'          => array(
				'required'   => false,
				'label'      => __( 'From Name', 'post-smtp' ),
				'input_type' => 'text',
			),
			'from_email'         => array(
				'required'   => true,
				'label'      => __( 'From Email Address', 'post-smtp' ),
				'input_type' => 'email',
			),
		);

		$sequence = array( 'connection_title', 'from_name', 'from_email' );

		if ( 'smtp' === $slug ) {
			$fields = array_merge(
				$common,
				array(
					'hostname'            => array(
						'required'   => true,
						'label'      => __( 'SMTP Host', 'post-smtp' ),
						'input_type' => 'text',
					),
					'port'                => array(
						'required'   => true,
						'label'      => __( 'SMTP Port', 'post-smtp' ),
						'input_type' => 'number',
					),
					'enc_type'            => array(
						'required'   => false,
						'label'      => __( 'Encryption', 'post-smtp' ),
						'input_type' => 'select',
						'options'    => array(
							'none' => __( 'None', 'post-smtp' ),
							'ssl'  => __( 'SSL', 'post-smtp' ),
							'tls'  => __( 'TLS', 'post-smtp' ),
						),
					),
					'basic_auth_username' => array(
						'required'   => false,
						'label'      => __( 'SMTP Username', 'post-smtp' ),
						'input_type' => 'text',
					),
					'basic_auth_password' => array(
						'required'   => false,
						'label'      => __( 'SMTP Password', 'post-smtp' ),
						'input_type' => 'password',
						'encrypt'    => true,
					),
				)
			);
			$sequence = array_merge( $sequence, array( 'hostname', 'port', 'enc_type', 'basic_auth_username', 'basic_auth_password' ) );
		} elseif ( in_array( $slug, array( 'gmail_api', 'office365_api', 'zohomail_api' ), true ) ) {
			$fields = array_merge(
				$common,
				array(
					'oauth_authorize' => array(
						'label'      => __( 'Authorize', 'post-smtp' ),
						'input_type' => 'oauth_button',
					),
				)
			);
			$sequence[] = 'oauth_authorize';
		} else {
			$key_field = self::apiKeyFieldForSlug( $slug );
			$fields    = array_merge( $common, array( $key_field => self::apiKeyFieldDef() ) );
			$sequence[] = $key_field;
		}

		return array(
			'title'             => $label,
			'display_name'      => $label,
			'description'       => '',
			'provider_type'     => 'api',
			'field_sequence'    => $sequence,
			'fields'            => $fields,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function apiKeyFieldDef(): array {
		return array(
			'required'   => true,
			'label'      => __( 'API Key', 'post-smtp' ),
			'input_type' => 'password',
			'encrypt'    => true,
		);
	}

	private static function apiKeyFieldForSlug( string $slug ): string {
		$map = array(
			'sendgrid_api'      => 'sendgrid_api_key',
			'mailgun_api'       => 'mailgun_api_key',
			'cloudflare_email_api' => 'cloudflare_email_api_key',
			'amazon_ses_api'    => 'ses_access_key',
			'postmark_api'      => 'postmark_api_key',
			'sendinblue_api'    => 'sendinblue_api_key',
			'sendpulse_api'     => 'sendpulse_api_key',
			'mailersend_api'    => 'mailersend_api_key',
			'smtp2go_api'       => 'smtp2go_api_key',
			'elasticemail_api'  => 'elasticemail_api_key',
			'sparkpost_api'     => 'sparkpost_api_key',
			'smtp_com_api'      => 'smtp_com_api_key',
			'maileroo_api'      => 'maileroo_api_key',
			'emailit_api'       => 'emailit_api_key',
			'mailtrap_api'      => 'mailtrap_api_key',
			'sweego_api'        => 'sweego_api_key',
		);
		return $map[ $slug ] ?? 'api_key';
	}
}
