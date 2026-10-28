<?php
/**
 * Sync modern connection rows into legacy PostmanOptions for OAuth grant admin-post flow.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionOAuthGrantBridge {

	private const OAUTH_PROVIDERS = array(
		'gmail_api',
		'office365_api',
		'zohomail_api',
	);

	public static function init(): void {
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'onBeforeGrant' ), 5 );
	}

	public static function onBeforeGrant(): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$requested_id = isset( $_GET['connection_id'] ) ? absint( $_GET['connection_id'] ) : null;
		$mailer_hint  = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( (string) $_GET['mailer'] ) ) : '';

		$connection_id = self::resolveGrantConnectionId( $requested_id, $mailer_hint );

		$result = self::prepareForGrant( $connection_id, $mailer_hint );
		if ( ! $result['success'] ) {
			wp_die(
				esc_html( $result['message'] ?? __( 'Could not start OAuth for this connection.', 'post-smtp' ) ),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 400 )
			);
		}

		\PostmanOptions::getInstance()->reload();
	}

	/**
	 * @return array{success:bool,message?:string}
	 */
	public static function prepareForGrant( int $connection_id, string $mailer_hint = '' ): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return array( 'success' => true );
		}

		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();

		if ( ! isset( $connections[ $connection_id ] ) || ! is_array( $connections[ $connection_id ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Connection not found.', 'post-smtp' ),
			);
		}

		$row      = $connections[ $connection_id ];
		$provider = self::resolveProviderFromRow( $row, $mailer_hint );

		if ( ! in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
			return array(
				'success' => false,
				'message' => __( 'This mailer does not use OAuth.', 'post-smtp' ),
			);
		}

		$client_id     = (string) ( $row['oauth_client_id'] ?? '' );
		$client_secret = (string) ( $row['oauth_client_secret'] ?? '' );
		$sender_email  = (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' );
		$sender_name   = (string) ( $row['sender_name'] ?? $row['from_name'] ?? '' );

		if ( '' === $client_id || '' === $client_secret ) {
			return array(
				'success' => false,
				'message' => __( 'Save Client ID and Client secret before connecting to the Gmail API.', 'post-smtp' ),
			);
		}

		$options = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options['transport_type']      = $provider;
		$options['auth_type']           = \PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;
		$options['oauth_client_id']     = $client_id;
		$options['oauth_client_secret'] = $client_secret;
		$options['sender_email']        = sanitize_email( $sender_email );
		$options['sender_name']         = sanitize_text_field( $sender_name );

		if ( 'gmail_api' === $provider ) {
			$options['hostname'] = 'www.googleapis.com';
			$options['port']     = 443;
			$options['enc_type'] = \PostmanOptions::SECURITY_TYPE_SMTPS;
		}

		update_option( 'postman_options', $options, false );

		/**
		 * Fires after a connection row is copied into legacy options for an OAuth grant request.
		 *
		 * @param int                 $connection_id Connection index.
		 * @param array<string,mixed> $row           Connection row.
		 * @param array<string,mixed> $options       Updated postman_options.
		 */
		do_action( 'post_smtp_connection_oauth_grant_prepared', $connection_id, $row, $options );

		return array( 'success' => true );
	}

	/**
	 * @param int|null $requested_id Connection index from the request, if any.
	 * @param string   $mailer_hint  OAuth mailer slug from the request (e.g. gmail_api).
	 */
	private static function resolveGrantConnectionId( ?int $requested_id, string $mailer_hint ): int {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();

		if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
			$provider = self::resolveProviderFromRow( $connections[ $requested_id ], $mailer_hint );
			if ( in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
				return $requested_id;
			}
		}

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$provider = self::resolveProviderFromRow( $row, $mailer_hint );
				if ( $provider === $mailer_hint ) {
					return (int) $idx;
				}
			}
		}

		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return 0;
		}

		$control = get_option( 'postman_options', array() );
		if ( is_array( $control ) && isset( $control['primary_connection'] ) ) {
			return (int) $control['primary_connection'];
		}

		return 0;
	}

	/**
	 * @param array<string,mixed> $row Connection row from postman_connections.
	 */
	private static function resolveProviderFromRow( array $row, string $mailer_hint = '' ): string {
		$provider = (string) ( $row['provider'] ?? '' );
		if ( '' === $provider || 'default' === $provider ) {
			$provider = (string) ( $row['transport_type'] ?? '' );
		}

		if ( in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
			return $provider;
		}

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) && self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
			return $mailer_hint;
		}

		if ( self::rowHasOAuthCredentials( $row, 'gmail_api' ) && ( '' === $provider || 'default' === $provider || 'smtp' === $provider ) ) {
			return 'gmail_api';
		}

		return $provider;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowHasOAuthCredentials( array $row, string $provider ): bool {
		switch ( $provider ) {
			case 'gmail_api':
			case 'office365_api':
				return '' !== (string) ( $row['oauth_client_id'] ?? '' )
					&& '' !== (string) ( $row['oauth_client_secret'] ?? '' );
			case 'zohomail_api':
				return '' !== (string) ( $row['zohomail_client_id'] ?? $row['oauth_client_id'] ?? '' );
		}

		return false;
	}

	/**
	 * @param int    $connection_id Connection index in postman_connections.
	 * @param string $mailer        OAuth provider slug.
	 */
	public static function manualAuthorizeUrl( int $connection_id, string $mailer = 'gmail_api' ): string {
		$args = array(
			'action'        => 'postman/requestOauthGrant',
			'connection_id' => max( 0, $connection_id ),
		);

		if ( in_array( $mailer, self::OAUTH_PROVIDERS, true ) ) {
			$args['mailer'] = $mailer;
		}

		return add_query_arg( $args, admin_url( 'admin-post.php' ) );
	}
}
