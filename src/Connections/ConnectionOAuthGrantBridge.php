<?php
/**
 * Sync modern connection rows into legacy PostmanOptions for OAuth grant admin-post flow.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Data\SecretCodec;
use PostSMTP\Multisite\MultisitePolicy;

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
		OAuthAdminReturnTrace::register();
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'stripLegacyGrantHandlerForOffice365' ), 1 );
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'onBeforeGrant' ), 5 );
		add_action( 'admin_post_postman/requestOauthGrant', array( self::class, 'preventLegacyOffice365GrantHandler' ), 99 );
	}

	/**
	 * Gmail-style admin-post grant must not run for Entra manual Office 365 (would redirect to admin.php?page=postman).
	 */
	public static function stripLegacyGrantHandlerForOffice365(): void {
		if ( ! self::isOffice365GrantRequest() ) {
			return;
		}

		global $wp_filter;

		$hook = 'admin_post_postman/requestOauthGrant';
		if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
			return;
		}

		$hook_obj = $wp_filter[ $hook ];
		foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
			if ( (int) $priority <= 5 ) {
				continue;
			}
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if (
					is_array( $function )
					&& isset( $function[1] )
					&& 'handleOAuthPermissionRequestAction' === $function[1]
				) {
					remove_action( $hook, $function, (int) $priority );
				}
			}
		}
	}

	private static function isOffice365GrantRequest(): bool {
		$mailer = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( (string) $_GET['mailer'] ) ) : '';

		return 'office365_api' === $mailer;
	}

	public static function onBeforeGrant(): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		if ( MultisitePolicy::shouldBlockChildMailerWrites() ) {
			wp_die(
				esc_html__( 'Mailer settings are managed on the main site. Ask a network administrator to sync sites.', 'post-smtp' ),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 403 )
			);
		}

		if ( class_exists( '\PostSMTP\MainWP\MainwpPolicy' ) && \PostSMTP\MainWP\MainwpPolicy::shouldBlockChildMailerWrites() ) {
			wp_die(
				esc_html__( 'Mailer settings are managed on the MainWP dashboard site.', 'post-smtp' ),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 403 )
			);
		}

		OAuthAdminReturnContext::captureFromGrantRequest();

		$requested_id = isset( $_GET['connection_id'] ) ? absint( $_GET['connection_id'] ) : null;
		$mailer_hint  = isset( $_GET['mailer'] ) ? sanitize_key( wp_unslash( (string) $_GET['mailer'] ) ) : '';

		$connection_id = self::resolveGrantConnectionId( $requested_id, $mailer_hint );
		$connection_id = self::coerceGrantConnectionId( $connection_id, $requested_id, $mailer_hint );

		$result = self::prepareForGrant( $connection_id, $mailer_hint );
		if ( ! empty( $result['connection_id'] ) && (int) $result['connection_id'] >= 0 ) {
			$connection_id = (int) $result['connection_id'];
		}
		if ( ! $result['success'] ) {
			wp_die(
				esc_html( $result['message'] ?? __( 'Could not start OAuth for this connection.', 'post-smtp' ) ),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 400 )
			);
		}

		\PostmanOptions::getInstance()->reload();

		$prepared_transport = (string) \PostmanOptions::getInstance()->getTransportType();
		$is_gmail           = 'gmail_api' === $mailer_hint || 'gmail_api' === $prepared_transport;
		if ( $is_gmail && null !== $connection_id && $connection_id >= 0 ) {
			$tab = 'manual';
			if ( isset( $_GET['gmail_connect_tab'] ) ) {
				$tab_candidate = sanitize_key( wp_unslash( (string) $_GET['gmail_connect_tab'] ) );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					$tab = $tab_candidate;
				}
			}
			OAuthAdminReturnContext::rememberGrantReturnForConnection( (int) $connection_id );
			GmailRevampOAuth::rememberGrant( (int) $connection_id, $tab );
		}

		$is_office365       = 'office365_api' === $mailer_hint || 'office365_api' === $prepared_transport;

		if ( $is_office365 && self::shouldStartOffice365RevampGrant() ) {
			if ( class_exists( 'PostmanSession', false ) ) {
				\PostmanSession::getInstance()->unsetOauthInProgress();
			}
			$tab = 'manual';
			if ( isset( $_GET['office365_connect_tab'] ) ) {
				$tab_candidate = sanitize_key( wp_unslash( (string) $_GET['office365_connect_tab'] ) );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					$tab = $tab_candidate;
				}
			}
			if ( null !== $connection_id && $connection_id >= 0 ) {
				OAuthAdminReturnContext::rememberGrantReturnForConnection( (int) $connection_id );
			}
			if ( null !== $connection_id && $connection_id >= 0
				&& class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', true ) ) {
				\PostSMTP\Pro\Connections\Office365RevampOAuth::rememberGrant( (int) $connection_id, $tab );
			} elseif ( null !== $connection_id && $connection_id >= 0 ) {
				set_transient( 'post_smtp_office365_oauth_connection_id', (int) $connection_id, 30 * MINUTE_IN_SECONDS );
				set_transient( 'post_smtp_office365_oauth_connect_tab', $tab, 30 * MINUTE_IN_SECONDS );
			}
			if ( self::redirectOffice365EntraOAuth() ) {
				exit;
			}

			wp_die(
				esc_html__(
					'Microsoft 365 OAuth could not start. Ensure Post SMTP Pro and the Microsoft 365 mailer are active, then save your app credentials and try again.',
					'post-smtp'
				),
				esc_html__( 'Post SMTP', 'post-smtp' ),
				array( 'response' => 400 )
			);
		}
	}

	/**
	 * Gmail-style grant handler must not run for Microsoft 365 (Entra app credentials).
	 */
	public static function preventLegacyOffice365GrantHandler(): void {
		if ( ! class_exists( '\PostmanOptions' ) ) {
			return;
		}

		$transport = (string) \PostmanOptions::getInstance()->getTransportType();
		if ( 'office365_api' !== $transport ) {
			return;
		}

		if ( self::isOffice365GrantRequest() && self::redirectOffice365EntraOAuth() ) {
			exit;
		}

		wp_die(
			esc_html__(
				'Microsoft 365 OAuth could not start. Save your Application ID and client secret, then try Connect to Microsoft API again.',
				'post-smtp'
			),
			esc_html__( 'Post SMTP', 'post-smtp' ),
			array( 'response' => 400 )
		);
	}

	/**
	 * @return array{success:bool,message?:string,connection_id?:int}
	 */
	public static function prepareForGrant( int $connection_id, string $mailer_hint = '' ): array {
		$connections = self::connectionRows();
		if (
			empty( $connections )
			&& 'gmail_api' === $mailer_hint
			&& class_exists( GmailRevampOAuth::class, false )
			&& GmailRevampOAuth::usesReactConnectionsAdmin()
			&& class_exists( GmailConnectionTokens::class, false )
		) {
			$requested = isset( $_GET['connection_id'] ) ? absint( $_GET['connection_id'] ) : 0;
			$seed      = $connection_id >= 0 ? $connection_id : $requested;
			GmailConnectionTokens::ensureConnectionRowExists( $seed, 'gmail_api' );
			$connections = self::connectionRows();
		}
		if ( empty( $connections ) ) {
			$connections = self::hydrateConnectionRowsFromOptions( $mailer_hint );
		}
		if ( empty( $connections ) && ConnectionRepositoryFactory::isLegacyMode() ) {
			return array(
				'success'       => true,
				'connection_id' => max( 0, $connection_id ),
			);
		}

		$requested = isset( $_GET['connection_id'] ) ? absint( $_GET['connection_id'] ) : null;
		$preferred = $connection_id >= 0 ? $connection_id : $requested;
		if ( $preferred < 0 ) {
			$preferred = self::coerceGrantConnectionId( -1, $requested, $mailer_hint );
		}

		$located = self::locateGrantConnection( $connections, $preferred >= 0 ? $preferred : null, $mailer_hint );
		if ( null === $located && empty( $connections ) ) {
			$located = self::locateGrantConnection(
				self::hydrateConnectionRowsFromOptions( $mailer_hint ),
				$preferred >= 0 ? $preferred : null,
				$mailer_hint
			);
		}

		if ( null === $located ) {
			$message = 'office365_api' === $mailer_hint
				? __( 'Save this connection first, then open it again to connect to the Microsoft API.', 'post-smtp' )
				: __( 'Connection not found. Save the connection with your Gmail Client ID and secret, then try Connect again.', 'post-smtp' );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$connection_id = $located['index'];
		$row           = SecretCodec::decodeConnectionRow( $located['row'] );

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			$provider = $mailer_hint;
		} else {
		$provider = self::resolveProviderFromRow( $row, $mailer_hint );
		if ( ! in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
			return array(
				'success' => false,
				'message' => __( 'This mailer does not use OAuth.', 'post-smtp' ),
			);
			}
		}

		$client_id     = (string) ( $row['oauth_client_id'] ?? '' );
		$client_secret = (string) ( $row['oauth_client_secret'] ?? '' );
		$sender_email  = (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' );
		$sender_name   = (string) ( $row['sender_name'] ?? $row['from_name'] ?? '' );

		if ( 'gmail_api' === $provider ) {
			$client_id     = self::resolveGmailOAuthClientIdFromRow( $row );
			$client_secret = SecretCodec::decode( $client_secret );
		}

		if ( 'office365_api' === $provider ) {
			$client_id     = (string) ( $row['office365_app_id'] ?? $client_id );
			$client_secret = (string) ( $row['office365_app_password'] ?? $client_secret );
			$client_id     = SecretCodec::decode( $client_id );
			$client_secret = SecretCodec::decode( $client_secret );
		}

		if ( 'zohomail_api' === $provider ) {
			$client_id     = (string) ( $row['zohomail_client_id'] ?? $client_id );
			$client_secret = (string) ( $row['zohomail_client_secret'] ?? $client_secret );
		}

		if ( '' === $client_id || '' === $client_secret ) {
			if ( 'zohomail_api' === $provider ) {
				$message = __( 'Save Region, Client ID, and Client Secret before connecting to the Zoho API.', 'post-smtp' );
			} elseif ( 'office365_api' === $provider ) {
				$message = __( 'Save Application (client) ID and client secret before connecting to the Microsoft API.', 'post-smtp' );
			} else {
				$message = __( 'Save Client ID and Client secret before connecting to the Gmail API.', 'post-smtp' );
			}
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		if ( 'gmail_api' === $provider && ! self::isPlausibleGmailOAuthClientId( $client_id ) ) {
			$stored_raw = trim( (string) ( $located['row']['oauth_client_id'] ?? '' ) );
			$message    = self::looksLikeMicrosoftApplicationId( SecretCodec::decode( $stored_raw ) )
				? __(
					'This connection is still using a Microsoft application ID in Client ID. Save a Gmail Client ID from Google Cloud Console (ending in .googleusercontent.com), then connect again.',
					'post-smtp'
				)
				: __(
					'Save a Gmail OAuth Client ID from Google Cloud Console (it should end in .googleusercontent.com) and Client secret on this connection, then try Connect again.',
					'post-smtp'
				);

			return array(
				'success' => false,
				'message' => $message,
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

		if ( 'zohomail_api' === $provider ) {
			$options['zohomail_client_id']     = $client_id;
			$options['zohomail_client_secret'] = $client_secret;
			$options['zohomail_region']       = sanitize_key( (string) ( $row['zohomail_region'] ?? 'com' ) );
		}

		if ( 'office365_api' === $provider ) {
			// Legacy Microsoft 365 OAuth expects single-layer base64 in postman_options (see Pro Transport).
			$options['office365_app_id']       = SecretCodec::encodeSingleBase64( $client_id );
			$options['office365_app_password'] = SecretCodec::encodeSingleBase64( $client_secret );
			$options['hostname']               = 'smtp.office365.com';
			$options['port']                   = 443;
			$options['enc_type']               = \PostmanOptions::SECURITY_TYPE_SMTPS;
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

		if ( 'office365_api' === $provider ) {
			$tab = 'manual';
			if ( isset( $_GET['office365_connect_tab'] ) ) {
				$tab_candidate = sanitize_key( wp_unslash( (string) $_GET['office365_connect_tab'] ) );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					$tab = $tab_candidate;
				}
			} elseif ( isset( $row['office365_connect_tab'] ) ) {
				$tab_candidate = sanitize_key( (string) $row['office365_connect_tab'] );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					$tab = $tab_candidate;
				}
			}
			set_transient( 'post_smtp_office365_oauth_connect_tab', $tab, 30 * MINUTE_IN_SECONDS );
			if ( $connection_id >= 0 && class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', true ) ) {
				\PostSMTP\Pro\Connections\Office365RevampOAuth::rememberGrant( $connection_id, $tab );
			}
		}

		if ( 'gmail_api' === $provider && $connection_id >= 0 ) {
			$tab = 'manual';
			if ( isset( $row['gmail_connect_tab'] ) ) {
				$tab_candidate = sanitize_key( (string) $row['gmail_connect_tab'] );
				if ( in_array( $tab_candidate, array( 'manual', 'one_click' ), true ) ) {
					$tab = $tab_candidate;
				}
			}
			GmailRevampOAuth::rememberGrant( $connection_id, $tab );
		}

		return array(
			'success'       => true,
			'connection_id' => (int) $connection_id,
		);
	}

	/**
	 * Revamp rows live in postman_connections (same normalization as the Connections REST API).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function connectionRows(): array {
		$stored = ConnectionSchema::readConnectionsOption();

		$repo = ConnectionRepositoryFactory::make();
		if ( count( $stored ) === 0 ) {
			$stored = $repo->getConnections();
		}
		if ( count( $stored ) === 0 && class_exists( '\Postman_Connection_Resolver', false ) ) {
			$stored = \Postman_Connection_Resolver::get_connections();
		}
		if ( count( $stored ) === 0 && class_exists( GmailConnectionTokens::class, true ) ) {
			$stored = GmailConnectionTokens::getConnectionRows();
		}
		if ( ! is_array( $stored ) || count( $stored ) === 0 ) {
			return array();
		}

		$options = $repo->getOptions();
		if ( class_exists( ConnectionService::class, false ) ) {
			$service = new ConnectionService();

			return $service->normalizeOnRead( $stored, $options );
		}

		return $stored;
	}

	/**
	 * When postman_connections is empty but legacy postman_options still has OAuth credentials.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function hydrateConnectionRowsFromOptions( string $mailer_hint ): array {
		if ( ! in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			return array();
		}

		$options = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			return array();
		}

		$row = array(
			'provider'       => $mailer_hint,
			'transport_type' => (string) ( $options['transport_type'] ?? $mailer_hint ),
			'sender_email'   => (string) ( $options['sender_email'] ?? '' ),
			'sender_name'    => (string) ( $options['sender_name'] ?? '' ),
		);

		if ( 'gmail_api' === $mailer_hint ) {
			$row['oauth_client_id']     = SecretCodec::decode( (string) ( $options['oauth_client_id'] ?? '' ) );
			$row['oauth_client_secret'] = SecretCodec::decode( (string) ( $options['oauth_client_secret'] ?? '' ) );
			if (
				'' === $row['oauth_client_id']
				|| '' === $row['oauth_client_secret']
				|| ! self::isPlausibleGmailOAuthClientId( $row['oauth_client_id'] )
			) {
				return array();
			}
		} elseif ( 'office365_api' === $mailer_hint ) {
			$row['office365_app_id']       = (string) ( $options['office365_app_id'] ?? $options['oauth_client_id'] ?? '' );
			$row['office365_app_password'] = (string) ( $options['office365_app_password'] ?? $options['oauth_client_secret'] ?? '' );
			if ( '' === $row['office365_app_id'] || '' === $row['office365_app_password'] ) {
				return array();
			}
		} else {
			$row['zohomail_client_id']     = (string) ( $options['zohomail_client_id'] ?? '' );
			$row['zohomail_client_secret'] = (string) ( $options['zohomail_client_secret'] ?? '' );
			if ( '' === $row['zohomail_client_id'] || '' === $row['zohomail_client_secret'] ) {
				return array();
			}
		}

		return array( 0 => $row );
	}

	/**
	 * @param array<int|string,array<string,mixed>> $connections Stored rows.
	 * @return array{index:int,row:array<string,mixed>}|null
	 */
	private static function locateGrantConnection( array $connections, ?int $preferred_index, string $mailer_hint ): ?array {
		if ( null !== $preferred_index && $preferred_index >= 0 ) {
			foreach ( array( $preferred_index, (string) $preferred_index ) as $key ) {
				if ( isset( $connections[ $key ] ) && is_array( $connections[ $key ] ) ) {
					$row = $connections[ $key ];
					if ( '' === $mailer_hint || self::rowIsEligibleForMailerGrant( $row, $mailer_hint ) ) {
						return array(
							'index' => (int) $preferred_index,
							'row'   => $row,
						);
					}
				}
			}
		}

		if ( '' !== $mailer_hint ) {
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
					return array(
						'index' => (int) $idx,
						'row'   => $row,
					);
				}
				if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) || self::rowHasOAuthFieldDraft( $row, $mailer_hint ) ) {
					return array(
						'index' => (int) $idx,
						'row'   => $row,
					);
				}
				if ( $mailer_hint === self::resolveProviderFromRow( $row, $mailer_hint ) ) {
					return array(
						'index' => (int) $idx,
						'row'   => $row,
					);
				}
			}
		}

		if ( null !== $preferred_index && $preferred_index >= 0 ) {
			$ordinal = 0;
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $ordinal === $preferred_index ) {
					return array(
						'index' => (int) $idx,
						'row'   => $row,
					);
				}
				++$ordinal;
			}
		}

		foreach ( $connections as $idx => $row ) {
			if ( is_array( $row ) ) {
				return array(
					'index' => (int) $idx,
					'row'   => $row,
				);
			}
		}

		return null;
	}

	/**
	 * Resolve Gmail connection index for revamp OAuth (manual grant + one-click), using postman_connections / postman_options.
	 *
	 * @param int|null $requested_id Connection index from the Connections UI (null when absent, e.g. add flow).
	 */
	public static function resolveGmailGrantConnectionId( ?int $requested_id = null ): int {
		$resolved = self::resolveGrantConnectionId( $requested_id, 'gmail_api' );

		// Add-connection flow: never fall back to connection 0 / first Gmail row.
		if ( null === $requested_id && $resolved < 0 ) {
			return -1;
		}

		return self::coerceGrantConnectionId( $resolved, $requested_id, 'gmail_api' );
	}

	/**
	 * Last-resort index when resolveGrantConnectionId returns -1 but rows exist.
	 */
	private static function coerceGrantConnectionId( int $resolved_id, ?int $requested_id, string $mailer_hint ): int {
		if ( $resolved_id >= 0 ) {
			return $resolved_id;
		}

		$connections = self::connectionRows();
		if ( count( $connections ) === 0 ) {
			return $resolved_id;
		}

		if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
			return $requested_id;
		}

		if ( '' !== $mailer_hint ) {
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
					return (int) $idx;
				}
			}
		}

		$first = array_key_first( $connections );

		return null !== $first ? (int) $first : -1;
	}

	private static function resolveGrantConnectionId( ?int $requested_id, string $mailer_hint ): int {
		$connections = self::connectionRows();

		if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
			if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
				if ( self::rowIsEligibleForMailerGrant( $connections[ $requested_id ], $mailer_hint ) ) {
					return $requested_id;
				}
			} else {
			$provider = self::resolveProviderFromRow( $connections[ $requested_id ], $mailer_hint );
			if ( in_array( $provider, self::OAUTH_PROVIDERS, true ) ) {
				return $requested_id;
				}
			}
		}

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			foreach ( $connections as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
					return (int) $idx;
				}
				if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
					return (int) $idx;
				}
				if ( self::rowHasOAuthFieldDraft( $row, $mailer_hint ) ) {
					return (int) $idx;
				}
				$provider = self::resolveProviderFromRow( $row, $mailer_hint );
				if ( $provider === $mailer_hint ) {
					return (int) $idx;
				}
			}

			if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) && is_array( $connections[ $requested_id ] ) ) {
				$candidate = $connections[ $requested_id ];
				if (
					$mailer_hint === (string) ( $candidate['provider'] ?? '' )
					|| self::rowHasOAuthCredentials( $candidate, $mailer_hint )
					|| self::rowHasOAuthFieldDraft( $candidate, $mailer_hint )
				) {
					return $requested_id;
				}
			}

			if (
				null !== $requested_id
				&& $requested_id >= 0
				&& isset( $connections[ $requested_id ] )
				&& is_array( $connections[ $requested_id ] )
				&& self::rowIsEligibleForMailerGrant( $connections[ $requested_id ], $mailer_hint )
			) {
				return $requested_id;
			}

			return -1;
		}

		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			if ( null !== $requested_id && $requested_id >= 0 && isset( $connections[ $requested_id ] ) ) {
				return $requested_id;
			}
			return isset( $connections[0] ) ? 0 : (int) array_key_first( $connections );
		}

		$control = get_option( 'postman_options', array() );
		if ( is_array( $control ) && isset( $control['primary_connection'] ) ) {
			$primary = (int) $control['primary_connection'];
			if ( isset( $connections[ $primary ] ) && is_array( $connections[ $primary ] ) ) {
				$primary_provider = self::resolveProviderFromRow( $connections[ $primary ], $mailer_hint );
				if ( '' === $mailer_hint || in_array( $primary_provider, self::OAUTH_PROVIDERS, true ) ) {
					return $primary;
				}
			}
		}

		return isset( $connections[0] ) ? 0 : (int) array_key_first( $connections );
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

		if ( '' !== $mailer_hint && in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
				return $mailer_hint;
			}
			if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
			return $mailer_hint;
			}
		}

		if ( self::rowHasOAuthCredentials( $row, 'gmail_api' ) && ( '' === $provider || 'default' === $provider || 'smtp' === $provider ) ) {
			return 'gmail_api';
		}

		return $provider;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowIsEligibleForMailerGrant( array $row, string $mailer_hint ): bool {
		if ( ! in_array( $mailer_hint, self::OAUTH_PROVIDERS, true ) ) {
			return true;
		}

		if ( $mailer_hint === (string) ( $row['provider'] ?? '' ) ) {
			return true;
		}

		if ( self::rowHasOAuthCredentials( $row, $mailer_hint ) ) {
			return true;
		}

		if ( self::rowHasOAuthFieldDraft( $row, $mailer_hint ) ) {
			return true;
		}

		return $mailer_hint === self::resolveProviderFromRow( $row, $mailer_hint );
	}

	/**
	 * Google OAuth client IDs are issued as *.apps.googleusercontent.com (legacy Post SMTP checks the suffix).
	 */
	/**
	 * @param array<string,mixed> $row Connection row (encoded or decoded).
	 */
	private static function resolveGmailOAuthClientIdFromRow( array $row ): string {
		$client_id = trim( SecretCodec::decode( (string) ( $row['oauth_client_id'] ?? '' ) ) );
		if ( '' === $client_id || self::looksLikeMicrosoftApplicationId( $client_id ) ) {
			return '';
		}

		if ( ! self::isPlausibleGmailOAuthClientId( $client_id ) ) {
			return '';
		}

		return $client_id;
	}

	private static function isPlausibleGmailOAuthClientId( string $client_id ): bool {
		$client_id = trim( SecretCodec::decode( $client_id ) );
		if ( '' === $client_id || self::looksLikeMicrosoftApplicationId( $client_id ) ) {
			return false;
		}

		if ( class_exists( '\PostmanUtils', false ) ) {
			return \PostmanUtils::endsWith( strtolower( $client_id ), 'googleusercontent.com' );
		}

		return false !== strpos( strtolower( $client_id ), 'googleusercontent.com' );
	}

	private static function looksLikeMicrosoftApplicationId( string $client_id ): bool {
		$client_id = trim( $client_id );

		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
			$client_id
		);
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowHasOAuthCredentials( array $row, string $provider ): bool {
		switch ( $provider ) {
			case 'gmail_api':
				$client_id = self::resolveGmailOAuthClientIdFromRow( $row );

				return self::isPlausibleGmailOAuthClientId( $client_id )
					&& '' !== (string) ( $row['oauth_client_secret'] ?? '' );
			case 'office365_api':
				$app_id = (string) ( $row['office365_app_id'] ?? $row['oauth_client_id'] ?? '' );
				$secret = (string) ( $row['office365_app_password'] ?? $row['oauth_client_secret'] ?? '' );
				return '' !== $app_id && '' !== $secret;
			case 'zohomail_api':
				return '' !== (string) ( $row['zohomail_client_id'] ?? $row['oauth_client_id'] ?? '' )
					&& '' !== (string) ( $row['zohomail_client_secret'] ?? $row['oauth_client_secret'] ?? '' );
		}

		return false;
	}

	/**
	 * Detect saved OAuth credential fields even when client id + secret are not both present yet.
	 *
	 * @param array<string,mixed> $row Connection row.
	 */
	private static function rowHasOAuthFieldDraft( array $row, string $provider ): bool {
		switch ( $provider ) {
			case 'office365_api':
				return '' !== (string) ( $row['office365_app_id'] ?? '' )
					|| '' !== (string) ( $row['office365_app_password'] ?? '' );
			case 'gmail_api':
				$client_id = (string) ( $row['oauth_client_id'] ?? '' );
				if ( self::looksLikeMicrosoftApplicationId( $client_id ) ) {
					return false;
				}
				if ( 'office365_api' === (string) ( $row['provider'] ?? '' ) ) {
					return self::isPlausibleGmailOAuthClientId( $client_id );
				}

				return '' !== $client_id
					|| '' !== (string) ( $row['oauth_client_secret'] ?? '' );
			case 'zohomail_api':
				return '' !== (string) ( $row['zohomail_client_id'] ?? '' )
					|| '' !== (string) ( $row['zohomail_client_secret'] ?? '' );
		}

		return false;
	}

	/**
	 * Read App ID / secret from the revamp connection row during an in-flight OAuth grant.
	 *
	 * @return array{client_id:string,client_secret:string}|null
	 */
	public static function getOffice365GrantCredentialsForOAuth(): ?array {
		$connection_id = get_transient( 'post_smtp_office365_oauth_connection_id' );
		if ( ! is_numeric( $connection_id ) || (int) $connection_id < 0 ) {
			return self::getOffice365GrantCredentialsFromPostmanOptions();
		}

		if ( ! class_exists( '\PostSMTP\Pro\Connections\Office365ConnectionTokens', true ) ) {
			return self::getOffice365GrantCredentialsFromPostmanOptions();
		}

		$connections = \PostSMTP\Pro\Connections\Office365ConnectionTokens::getConnectionRows();
		$index       = (int) $connection_id;

		if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
			$connections = self::connectionRows();
			if ( ! isset( $connections[ $index ] ) || ! is_array( $connections[ $index ] ) ) {
				return self::getOffice365GrantCredentialsFromPostmanOptions();
			}
		}

		$row = SecretCodec::decodeConnectionRow( $connections[ $index ] );

		$client_id = SecretCodec::decode( (string) ( $row['office365_app_id'] ?? $row['oauth_client_id'] ?? '' ) );
		$secret    = SecretCodec::decode( (string) ( $row['office365_app_password'] ?? $row['oauth_client_secret'] ?? '' ) );

		if ( '' === $client_id || '' === $secret || preg_match( '/^•+$/u', $secret ) ) {
			return self::getOffice365GrantCredentialsFromPostmanOptions();
		}

		return array(
			'client_id'     => $client_id,
			'client_secret' => $secret,
		);
	}

	/**
	 * Credentials copied into postman_options by {@see prepareForGrant()} for this admin-post request.
	 *
	 * @return array{client_id:string,client_secret:string}|null
	 */
	private static function getOffice365GrantCredentialsFromPostmanOptions(): ?array {
		$options = get_option( 'postman_options', array() );
		if ( ! is_array( $options ) ) {
			return null;
		}

		$client_id = SecretCodec::decode( (string) ( $options['office365_app_id'] ?? $options['oauth_client_id'] ?? '' ) );
		$secret    = SecretCodec::decode( (string) ( $options['office365_app_password'] ?? $options['oauth_client_secret'] ?? '' ) );

		if ( '' === $client_id || '' === $secret || preg_match( '/^•+$/u', $secret ) ) {
			return null;
		}

		return array(
			'client_id'     => $client_id,
			'client_secret' => $secret,
		);
	}

	/**
	 * Whether Entra manual OAuth should run on the revamp Connections UI (requires Pro).
	 */
	private static function shouldStartOffice365RevampGrant(): bool {
		if ( ! defined( 'POST_SMTP_PRO_PATH' ) ) {
			return false;
		}

		if ( ! class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', true ) ) {
			return false;
		}

		return \PostSMTP\Pro\Connections\Office365RevampOAuth::isActive();
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function buildOffice365EntraClientConfig( string $client_id, string $client_secret ): array {
		$redirect_uri = class_exists( '\PostSMTP\Pro\Connections\Office365OAuthRedirectUri', true )
			? \PostSMTP\Pro\Connections\Office365OAuthRedirectUri::resolve()
			: trailingslashit( esc_url_raw( untrailingslashit( site_url( '/wp-admin/' ) ) ) );
		$authority    = 'https://login.microsoftonline.com/common';

		return array(
			'clientId'                => $client_id,
			'clientSecret'            => $client_secret,
			'redirectUri'             => $redirect_uri,
			'urlAuthorize'            => $authority . '/oauth2/v2.0/authorize',
			'urlAccessToken'          => $authority . '/oauth2/v2.0/token',
			'urlResourceOwnerDetails' => '',
			'scopes'                  => 'openid profile offline_access Mail.Send Mail.Send.Shared',
		);
	}

	/**
	 * Microsoft 365 manual connect uses Entra OAuth (Pro), not the Gmail-style grant handler.
	 */
	private static function redirectOffice365EntraOAuth(): bool {
	
		if ( ! defined( 'POST_SMTP_PRO_PATH' ) ) {
			return false;
		}

		if ( function_exists( 'psp_load_microsoft_365' ) ) {
			psp_load_microsoft_365();
		} else {
			$bootstrap = POST_SMTP_PRO_PATH . 'extensions/microsoft-365/microsoft-365.php';
			if ( ! is_readable( $bootstrap ) ) {
				return false;
			}
			require_once $bootstrap;
			if ( function_exists( 'psp_load_microsoft_365' ) ) {
				psp_load_microsoft_365();
			}
		}

		if ( ! class_exists( 'OAuthOffice365', false ) || ! class_exists( 'Post_Smtp_Office365', false ) ) {
			return false;
		}

		if ( class_exists( '\PostSMTP\Pro\Connections\Office365OAuthRedirectUri', true ) ) {
			\PostSMTP\Pro\Connections\Office365OAuthRedirectUri::syncOAuthOption();
		}

		$config = get_option( 'postman_office365_oauth', array() );
		if ( ! is_array( $config ) || empty( $config ) ) {
			if ( function_exists( 'post_smtp_pffice365_activate' ) ) {
				post_smtp_pffice365_activate();
			}
		}

		$grant_creds = self::getOffice365GrantCredentialsForOAuth();
		if ( is_array( $grant_creds ) ) {
			$oauth_client = self::buildOffice365EntraClientConfig(
				$grant_creds['client_id'],
				$grant_creds['client_secret']
			);
		} else {
			$office365    = new \Post_Smtp_Office365();
			$oauth_client = $office365->get_office365_configuration();
		}

		if ( empty( $oauth_client['clientId'] ) || empty( $oauth_client['clientSecret'] ) ) {
			return false;
		}

		if ( empty( $oauth_client['urlAuthorize'] ) ) {
			$oauth_client = self::buildOffice365EntraClientConfig(
				(string) $oauth_client['clientId'],
				(string) $oauth_client['clientSecret']
			);
		}

		$client = new \OAuthOffice365( $oauth_client );

		$grant_connection = get_transient( 'post_smtp_office365_oauth_connection_id' );
		$grant_tab        = get_transient( 'post_smtp_office365_oauth_connect_tab' );
		$tab              = is_string( $grant_tab ) && in_array( $grant_tab, array( 'manual', 'one_click' ), true )
			? $grant_tab
			: 'manual';
		$connection_index = is_numeric( $grant_connection ) ? (int) $grant_connection : -1;
		if ( $connection_index < 0 && isset( $_GET['connection_id'] ) && is_numeric( $_GET['connection_id'] ) ) {
			$connection_index = max( 0, (int) $_GET['connection_id'] );
		}

		if ( $connection_index >= 0 && class_exists( '\PostSMTP\Pro\Connections\Office365RevampOAuth', true ) ) {
			\PostSMTP\Pro\Connections\Office365RevampOAuth::rememberGrant( $connection_index, $tab );
		}

		$grant_ps_route = '';
		if ( $connection_index >= 0 && class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', true ) ) {
			$grant_ps_route = \PostSMTP\Pro\Connections\Office365OAuthGrantContext::resolveGrantPsRouteForConnection( $connection_index );
		}

		if ( class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', true ) ) {
			$state_ps_route = \PostSMTP\Connections\OAuthAdminReturnContext::ROUTE_SETUP_WIZARD === $grant_ps_route
				? $grant_ps_route
				: null;
			$state = \PostSMTP\Pro\Connections\Office365OAuthGrantContext::buildStateToken( $connection_index, $tab, $state_ps_route );
			\PostSMTP\Pro\Connections\Office365OAuthGrantContext::rotateOAuthState( $state );
		} else {
			$state = get_transient( \Post_Smtp_Office365::STATE );
			if ( false === $state || ! is_string( $state ) || '' === $state ) {
				$state = bin2hex( random_bytes( 16 ) );
			}
		}

		set_transient( \Post_Smtp_Office365::STATE, $state, 30 * MINUTE_IN_SECONDS );

		if ( $connection_index >= 0 ) {
			OAuthAdminReturnContext::rememberGrantReturnForConnection( $connection_index );
		}

		if ( $connection_index >= 0 && class_exists( '\PostSMTP\Pro\Connections\Office365OAuthGrantContext', true ) ) {
			\PostSMTP\Pro\Connections\Office365OAuthGrantContext::rememberStateBinding(
				(string) $state,
				$connection_index,
				$tab,
				'' !== $grant_ps_route ? $grant_ps_route : null
			);
		}

		$url = $client->getOffice365AuthURL(
			array(
				'state'  => $state,
				'prompt' => 'consent',
			)
		);

		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		if ( false === strpos( $url, 'login.microsoftonline.com' ) ) {
			return false;
		}

		OAuthAdminReturnTrace::record(
			'office365_grant_start',
			array(
				'connection_index' => $connection_index,
				'tab'              => $tab,
				'grant_ps_route'   => $grant_ps_route,
				'state'            => isset( $state ) ? (string) $state : '',
				'ps_return_route'  => OAuthAdminReturnContext::readReturnRouteFromSuperglobals(),
				'entra_url_host'   => wp_parse_url( $url, PHP_URL_HOST ),
			)
		);

		wp_redirect( $url );
		exit;
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
