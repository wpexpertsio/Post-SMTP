<?php
/**
 * Sync revamp connection rows into legacy PostmanOptions for wp_mail delivery.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionLegacySendBridge {

	public static function init(): void {
		add_action( 'post_smtp_prepare_legacy_mail_send', array( self::class, 'prepare_for_send' ), 10, 1 );
	}

	/**
	 * @param bool $is_fallback When true, hydrate legacy fallback SMTP fields from the active fallback connection.
	 */
	public static function prepare_for_send( bool $is_fallback = false ): void {
		if ( ! class_exists( '\Postman_Connection_Resolver' ) || \Postman_Connection_Resolver::is_legacy_mode() ) {
			return;
		}

		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$row = self::resolve_active_row( $is_fallback );
		if ( empty( $row ) ) {
			return;
		}

		$patch = self::map_row_to_legacy_options( $row, $is_fallback );
		if ( empty( $patch ) ) {
			return;
		}

		PostmanOptions::getInstance()->apply_runtime_patch( $patch );
	}

	/**
	 * Apply connection credentials to in-memory PostmanOptions for one revamp send attempt.
	 *
	 * @param array<string,mixed> $row         Connection row.
	 * @param bool                $is_fallback Fallback attempt.
	 */
	public static function apply_runtime_patch_for_connection( array $row, bool $is_fallback ): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$patch = self::map_row_to_legacy_options( $row, $is_fallback );
		if ( empty( $patch ) ) {
			return;
		}

		PostmanOptions::getInstance()->apply_runtime_patch( $patch );
	}

	/**
	 * Persist primary connection transport + credentials into postman_options (admin saves).
	 */
	public static function sync_primary_to_options(): void {
		self::sync_primary_from_connection_index( 0 );
	}

	/**
	 * Persist connection row at index into postman_options (primary slot = 0 in revamp UI).
	 *
	 * @param int $index Connection index; primary mail uses 0.
	 */
	public static function sync_primary_from_connection_index( int $index ): void {
		if ( ! class_exists( '\Postman_Connection_Resolver' ) || \Postman_Connection_Resolver::is_legacy_mode() ) {
			return;
		}

		$row = \Postman_Connection_Resolver::get_connection( $index );
		if ( empty( $row ) ) {
			return;
		}

		$patch = self::map_row_to_legacy_options( $row, false );
		if ( empty( $patch ) ) {
			return;
		}

		$options = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options['selected_primary']   = 0;
		$options['primary_connection'] = 0;
		$options                       = array_merge( $options, $patch );
		update_option( PostmanOptions::POSTMAN_OPTIONS, $options, false );
		PostmanOptions::getInstance()->reload();
	}

	/**
	 * @param string $provider Connection provider slug.
	 */
	public static function legacyTransportFromProvider( string $provider ): string {
		return self::legacy_transport_slug( $provider );
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function resolve_active_row( bool $is_fallback ): array {
		$id = \Postman_Connection_Resolver::get_active_connection_id( $is_fallback );
		return \Postman_Connection_Resolver::get_connection( $id );
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	private static function map_row_to_legacy_options( array $row, bool $is_fallback ): array {
		$provider = (string) ( $row['provider'] ?? $row['transport_type'] ?? '' );
		if ( '' === $provider ) {
			return array();
		}

		$legacy_transport = self::legacy_transport_slug( $provider );

		if ( $is_fallback ) {
			return self::map_fallback_smtp_patch( $row, $legacy_transport );
		}

		$patch = array(
			PostmanOptions::TRANSPORT_TYPE        => $legacy_transport,
			PostmanOptions::MESSAGE_SENDER_EMAIL  => sanitize_email( (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ) ),
			PostmanOptions::MESSAGE_SENDER_NAME   => sanitize_text_field( (string) ( $row['sender_name'] ?? $row['from_name'] ?? '' ) ),
		);

		if ( array_key_exists( 'prevent_sender_name_override', $row ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ] = ! empty( $row['prevent_sender_name_override'] ) ? 'true' : '';
		}
		if ( array_key_exists( 'prevent_sender_email_override', $row ) ) {
			$patch[ PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ] = ! empty( $row['prevent_sender_email_override'] ) ? 'true' : '';
		}

		foreach ( self::credential_field_map() as $connection_key => $options_key ) {
			if ( ! array_key_exists( $connection_key, $row ) || '' === (string) $row[ $connection_key ] ) {
				continue;
			}
			$value = (string) $row[ $connection_key ];
			if ( in_array( $options_key, \Postman_Connection_Resolver::get_postman_options_base64_secret_keys(), true ) ) {
				$value = \Postman_Connection_Resolver::normalize_stored_option_secret_to_single_base64( $value );
			}
			$patch[ $options_key ] = $value;
		}

		if ( 'smtp' === $legacy_transport || 'default' === $legacy_transport ) {
			$patch[ PostmanOptions::AUTHENTICATION_TYPE ] = ! empty( $row['basic_auth_username'] )
				? PostmanOptions::AUTHENTICATION_TYPE_LOGIN
				: PostmanOptions::AUTHENTICATION_TYPE_NONE;
		} elseif ( in_array( $legacy_transport, array( 'gmail_api', 'office365_api', 'zohomail_api' ), true ) ) {
			$patch[ PostmanOptions::AUTHENTICATION_TYPE ] = PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;
			$patch                                        = array_merge( self::clearPrimarySmtpCredentialFields(), $patch );
		} else {
			$patch = array_merge( self::clearPrimarySmtpCredentialFields(), $patch );
		}

		return $patch;
	}

	/**
	 * Prevent stale SMTP host/auth from a previous primary from affecting legacy readers.
	 *
	 * @return array<string,mixed>
	 */
	private static function clearPrimarySmtpCredentialFields(): array {
		return array(
			PostmanOptions::HOSTNAME             => '',
			PostmanOptions::PORT                 => '',
			PostmanOptions::SECURITY_TYPE        => '',
			PostmanOptions::BASIC_AUTH_USERNAME  => '',
			PostmanOptions::BASIC_AUTH_PASSWORD  => '',
			PostmanOptions::AUTHENTICATION_TYPE  => PostmanOptions::AUTHENTICATION_TYPE_NONE,
		);
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array<string,mixed>
	 */
	private static function map_fallback_smtp_patch( array $row, string $legacy_transport ): array {
		$patch = array(
			PostmanOptions::FALLBACK_SMTP_ENABLED => 'yes',
			PostmanOptions::MESSAGE_SENDER_EMAIL  => sanitize_email( (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ) ),
			PostmanOptions::MESSAGE_SENDER_NAME   => sanitize_text_field( (string) ( $row['sender_name'] ?? $row['from_name'] ?? '' ) ),
		);

		if ( 'smtp' === $legacy_transport || 'default' === $legacy_transport ) {
			$patch[ PostmanOptions::FALLBACK_SMTP_HOSTNAME ] = (string) ( $row['hostname'] ?? '' );
			$patch[ PostmanOptions::FALLBACK_SMTP_PORT ]     = (string) ( $row['port'] ?? '587' );
			$patch[ PostmanOptions::FALLBACK_SMTP_SECURITY ] = (string) ( $row['enc_type'] ?? 'tls' );
			$patch[ PostmanOptions::FALLBACK_FROM_EMAIL ]    = sanitize_email( (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ) );
			if ( ! empty( $row['basic_auth_username'] ) ) {
				$patch[ PostmanOptions::FALLBACK_SMTP_USE_AUTH ] = 'yes';
				$patch[ PostmanOptions::FALLBACK_SMTP_USERNAME ] = (string) $row['basic_auth_username'];
			}
			if ( ! empty( $row['basic_auth_password'] ) ) {
				$patch[ PostmanOptions::FALLBACK_SMTP_PASSWORD ] = \Postman_Connection_Resolver::normalize_stored_option_secret_to_single_base64(
					(string) $row['basic_auth_password']
				);
			}
			return $patch;
		}

		// Non-SMTP fallback mailers: legacy registry uses primary transport_type during fallback send.
		$patch[ PostmanOptions::TRANSPORT_TYPE ] = $legacy_transport;
		foreach ( self::credential_field_map() as $connection_key => $options_key ) {
			if ( ! array_key_exists( $connection_key, $row ) || '' === (string) $row[ $connection_key ] ) {
				continue;
			}
			$value = (string) $row[ $connection_key ];
			if ( in_array( $options_key, \Postman_Connection_Resolver::get_postman_options_base64_secret_keys(), true ) ) {
				$value = \Postman_Connection_Resolver::normalize_stored_option_secret_to_single_base64( $value );
			}
			$patch[ $options_key ] = $value;
		}

		return $patch;
	}

	private static function legacy_transport_slug( string $provider ): string {
		$map = array(
			'amazon_ses_api' => 'aws_ses_api',
		);

		return $map[ $provider ] ?? $provider;
	}

	/**
	 * @return array<string,string> Connection field => postman_options key.
	 */
	private static function credential_field_map(): array {
		return array(
			'hostname'                 => PostmanOptions::HOSTNAME,
			'port'                     => PostmanOptions::PORT,
			'enc_type'                 => PostmanOptions::SECURITY_TYPE,
			'basic_auth_username'      => PostmanOptions::BASIC_AUTH_USERNAME,
			'basic_auth_password'      => PostmanOptions::BASIC_AUTH_PASSWORD,
			'sendgrid_api_key'         => PostmanOptions::SENDGRID_API_KEY,
			'sendinblue_api_key'       => PostmanOptions::SENDINBLUE_API_KEY,
			'postmark_api_key'         => PostmanOptions::POSTMARK_API_KEY,
			'mailgun_api_key'          => PostmanOptions::MAILGUN_API_KEY,
			'mailgun_domain_name'      => PostmanOptions::MAILGUN_DOMAIN_NAME,
			'ses_access_key_id'        => 'ses_access_key_id',
			'ses_secret_access_key'    => 'ses_secret_access_key',
			'ses_region'               => 'ses_region',
			'mandrill_api_key'         => PostmanOptions::MANDRILL_API_KEY,
			'sparkpost_api_key'        => PostmanOptions::SPARKPOST_API_KEY,
			'elasticemail_api_key'     => PostmanOptions::ELASTICEMAIL_API_KEY,
			'smtp2go_api_key'          => PostmanOptions::SMTP2GO_API_KEY,
			'mailersend_api_key'       => PostmanOptions::MAILERSEND_API_KEY,
			'resend_api_key'           => PostmanOptions::RESEND_API_KEY,
			'mailtrap_api_key'         => PostmanOptions::MAILTRAP_API_KEY,
			'oauth_client_id'          => PostmanOptions::CLIENT_ID,
			'oauth_client_secret'      => PostmanOptions::CLIENT_SECRET,
		);
	}
}
