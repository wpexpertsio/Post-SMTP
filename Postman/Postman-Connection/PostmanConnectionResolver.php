<?php
/**
 * Postman Connection Resolver.
 *
 * Centralizes the legacy-vs-new schema / smart-routing / primary-or-fallback
 * branching that used to be copy-pasted into every Transport's createMailEngine,
 * every MailEngine's send(), and the PostmanFallbackMigration sensitive-key
 * lists.
 *
 * Storage contract assumed by this helper:
 *  - `postman_options` holds sensitive fields as base64-encoded strings; the
 *    canonical on-disk form is a single base64 layer. {@see self::decode_stored_option_secret()}
 *    unwraps accidental multi-layer encoding on read; {@see self::repair_postman_options_secret_encoding()}
 *    normalizes values on migrate/restore.
 *  - `postman_connections` holds the same fields as plaintext (set by
 *    PostmanFallbackMigration::save_mail_connections() which decodes once
 *    while building the connection rows).
 *
 * @package PostSMTP
 * @since 3.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Postman_Connection_Resolver' ) ) :

	final class Postman_Connection_Resolver {

		/**
		 * Transient that holds the connection index chosen by Smart Routing
		 * for the in-flight email.
		 */
		const ROUTE_TRANSIENT = 'post_smtp_smart_routing_route';

		/**
		 * Returns true when the site is still running on the legacy
		 * postman_options-only schema (i.e. fallback migration has not been
		 * applied yet).
		 *
		 * @return bool
		 */
		public static function is_legacy_mode() {
			return get_option( 'postman_db_version' ) != POST_SMTP_DB_VERSION;
		}

		/**
		 * Smart-routing transient value (null when unset).
		 *
		 * @return mixed
		 */
		public static function get_route_key() {
			$route_key = get_transient( self::ROUTE_TRANSIENT );
			return ( $route_key === false ) ? null : $route_key;
		}

		/**
		 * Full `postman_connections` option (always an array).
		 *
		 * @return array
		 */
		public static function get_connections() {
			$connections = get_option( 'postman_connections', array() );
			return is_array( $connections ) ? $connections : array();
		}

		/**
		 * Returns the active connection index for the current send.
		 *
		 * @param bool $is_fallback         When true, force the fallback index.
		 * @param mixed $route_key_override Optional explicit route key.
		 * @return int|string|null
		 */
		public static function get_active_connection_id( $is_fallback = false, $route_key_override = null ) {
			$options = PostmanOptions::getInstance();

			if ( $is_fallback ) {
				return $options->getSelectedFallback();
			}

			$route_key = ( $route_key_override !== null ) ? $route_key_override : self::get_route_key();
			if ( $route_key !== null ) {
				return $route_key;
			}

			return $options->getSelectedPrimary();
		}

		/**
		 * Returns a single connection row keyed by $id (empty array when missing).
		 *
		 * @param mixed $id Connection index.
		 * @return array
		 */
		public static function get_connection( $id ) {
			if ( $id === null || $id === '' ) {
				return array();
			}
			$connections = self::get_connections();
			return ( isset( $connections[ $id ] ) && is_array( $connections[ $id ] ) ) ? $connections[ $id ] : array();
		}

		/**
		 * Resolve a credential field for the current PRIMARY send path
		 * (used by Transport::createMailEngine()).
		 *
		 * Under legacy schema we delegate to the supplied PostmanOptions
		 * getter so existing base64-decoded reads keep working. Under the
		 * new schema we read plaintext from postman_connections, preferring
		 * a Smart Routing override when present.
		 *
		 * @param string   $field          Connection field name, e.g. 'mailgun_api_key'.
		 * @param callable $legacy_getter  Callable returning the value under legacy schema.
		 * @param string   $default        Default returned when no value is found.
		 * @return string
		 */
		public static function get_primary_field( $field, $legacy_getter, $default = '' ) {
			if ( self::is_legacy_mode() ) {
				if ( is_callable( $legacy_getter ) ) {
					$value = call_user_func( $legacy_getter );
					return ( $value === null ) ? $default : $value;
				}
				return $default;
			}

			$id         = self::get_active_connection_id( false );
			$connection = self::get_connection( $id );

			if ( isset( $connection[ $field ] ) && $connection[ $field ] !== '' ) {
				return $connection[ $field ];
			}
			return $default;
		}

		/**
		 * Resolve a credential field for the FALLBACK send path
		 * (used by Transport::createMailEngineFallback()).
		 *
		 * Fallback rows only live in the new schema, so this always reads
		 * from postman_connections at the selected fallback index.
		 *
		 * @param string $field   Connection field name.
		 * @param string $default Default returned when no value is found.
		 * @return string
		 */
		public static function get_fallback_field( $field, $default = '' ) {
			$id         = self::get_active_connection_id( true );
			$connection = self::get_connection( $id );

			if ( isset( $connection[ $field ] ) && $connection[ $field ] !== '' ) {
				return $connection[ $field ];
			}
			return $default;
		}

		/**
		 * Resolve sender (email + name) for the in-flight message.
		 *
		 * Mirrors the defensive lookup pattern already used by
		 * PostmanMailtrapMailEngine::resolve_sender_identity(): prefer the
		 * value carried on the PostmanMessage, then the value stored on the
		 * active connection row, then PostmanOptions message-sender fields.
		 *
		 * @param object $sender             PostmanEmailAddress-like with getEmail()/getName().
		 * @param bool   $is_fallback        True when called from the fallback engine.
		 * @param mixed  $route_key_override Optional explicit route key.
		 * @return array{email:string,name:string}
		 */
		public static function resolve_sender( $sender, $is_fallback = false, $route_key_override = null ) {
			$options = PostmanOptions::getInstance();

			$sender_email = ( is_object( $sender ) && method_exists( $sender, 'getEmail' ) && ! empty( $sender->getEmail() ) )
				? $sender->getEmail()
				: $options->getMessageSenderEmail();
			$sender_name  = ( is_object( $sender ) && method_exists( $sender, 'getName' ) && ! empty( $sender->getName() ) )
				? $sender->getName()
				: $options->getMessageSenderName();

			if ( self::is_legacy_mode() ) {
				return array(
					'email' => $sender_email,
					'name'  => $sender_name,
				);
			}

			$id         = self::get_active_connection_id( (bool) $is_fallback, $route_key_override );
			$connection = self::get_connection( $id );

			if ( ! empty( $connection ) ) {
				if ( isset( $connection['sender_email'] ) && $connection['sender_email'] !== '' ) {
					$sender_email = $connection['sender_email'];
				}
				if ( isset( $connection['sender_name'] ) && $connection['sender_name'] !== '' ) {
					$sender_name = $connection['sender_name'];
				}
			}

			return array(
				'email' => $sender_email,
				'name'  => $sender_name,
			);
		}

		/**
		 * True when the "gmail-oneclick" Pro extension is toggled on.
		 *
		 * The One-Click feature is driven by per-connection OAuth fields
		 * (access_token, account_key, user_email) that the wizard writes
		 * directly into postman_connections — separately from any
		 * postman_db_version bump. The presence of those fields is
		 * verified at send time inside PostmanGmailApiModuleZendMailTransport::_sendMail()
		 * which surfaces a "setup is not finished" message when they are
		 * missing. So the runtime gate here only needs to mirror the
		 * user's intent (the extension toggle); we deliberately do NOT
		 * also require is_legacy_mode() to be false, because doing so
		 * would strand sites in an in-between state where the wizard
		 * stored a valid token but the migration version hasn't been
		 * bumped yet (legacy SDK then runs with empty PostmanOAuthToken
		 * and Gmail returns 401 UNAUTHENTICATED).
		 *
		 * @return bool
		 */
		public static function is_gmail_oneclick_enabled() {
			$pro_options = get_option( 'post_smtp_pro', array() );
			$extensions  = isset( $pro_options['extensions'] ) && is_array( $pro_options['extensions'] )
				? $pro_options['extensions']
				: array();

			return in_array( 'gmail-oneclick', $extensions, true );
		}

		/**
		 * Alias of is_gmail_oneclick_enabled() preserved for the admin UI
		 * call sites that used to distinguish "user intent" from "runtime
		 * readiness". After dropping the migration-state gate the two
		 * predicates are equivalent, but the alias keeps the existing
		 * call sites readable at the point of use.
		 *
		 * @return bool
		 */
		public static function is_gmail_oneclick_extension_enabled() {
			return self::is_gmail_oneclick_enabled();
		}

		/**
		 * Canonical list of sensitive credential keys handled by the
		 * fallback migration. Add a new provider's secret here and every
		 * migration step picks it up automatically.
		 *
		 * @return string[]
		 */
		public static function get_sensitive_keys() {
			return array(
				'basic_auth_password',
				'mandrill_api_key',
				'sendgrid_api_key',
				'sendinblue_api_key',
				'postmark_api_key',
				'sendpulse_api_key',
				'sendpulse_secret_key',
				'sparkpost_api_key',
				'elasticemail_api_key',
				'smtp2go_api_key',
				'mailersend_api_key',
				'mailjet_api_key',
				'mailjet_secret_key',
				'emailit_api_key',
				'maileroo_api_key',
				'sweego_api_key',
				'resend_api_key',
				'mailtrap_api_key',
				'mailgun_api_key',
				'oauth_client_secret',
				'office365_app_password',
				'zohomail_client_secret',
				'ses_secret_access_key',
			);
		}

		/**
		 * Keys in `postman_options` that are stored as base64-encoded secrets (not `oauth_client_secret`,
		 * which is read plain by {@see PostmanOptions::getClientSecret()}).
		 *
		 * @return string[]
		 */
		public static function get_postman_options_base64_secret_keys() {
			return array(
				'basic_auth_password',
				'fallback_smtp_password',
				'mandrill_api_key',
				'sendgrid_api_key',
				'sendinblue_api_key',
				'postmark_api_key',
				'sendpulse_api_key',
				'sendpulse_secret_key',
				'sparkpost_api_key',
				'elasticemail_api_key',
				'smtp2go_api_key',
				'mailersend_api_key',
				'mailjet_api_key',
				'mailjet_secret_key',
				'emailit_api_key',
				'maileroo_api_key',
				'sweego_api_key',
				'resend_api_key',
				'mailtrap_api_key',
				'mailgun_api_key',
				'office365_app_password',
				'zohomail_client_secret',
				'ses_secret_access_key',
			);
		}

		/**
		 * Decode a secret read from `postman_options`: unwraps any number of accidental base64 layers
		 * until the value is no longer a strict round-tripping base64 armoring of its decoded bytes.
		 *
		 * @param mixed $stored Raw DB value.
		 * @return string Plaintext secret for SMTP/API use.
		 */
		public static function decode_stored_option_secret( $stored ) {
			$current = (string) $stored;
			if ( '' === $current ) {
				return '';
			}
			while ( true ) {
				$decoded = base64_decode( $current, true );
				if ( false === $decoded || '' === $decoded ) {
					break;
				}
				if ( base64_encode( $decoded ) !== $current ) {
					break;
				}
				$current = $decoded;
			}

			return $current;
		}

		/**
		 * Normalize a secret to exactly one base64 layer for persistence in `postman_options`.
		 *
		 * @param mixed $stored Plaintext or multiply-wrapped base64.
		 * @return string Single base64-encoded value, or empty string.
		 */
		public static function normalize_stored_option_secret_to_single_base64( $stored ) {
			$plain = self::decode_stored_option_secret( $stored );

			return '' === $plain ? '' : base64_encode( $plain );
		}

		/**
		 * Rewrite known secret keys in a `postman_options` array to a single base64 layer (migrate/restore).
		 *
		 * @param array $postman_options Options array (modified by reference).
		 * @return void
		 */
		public static function repair_postman_options_secret_encoding( array &$postman_options ) {
			foreach ( self::get_postman_options_base64_secret_keys() as $key ) {
				if ( ! isset( $postman_options[ $key ] ) || '' === (string) $postman_options[ $key ] ) {
					continue;
				}
				$postman_options[ $key ] = self::normalize_stored_option_secret_to_single_base64( $postman_options[ $key ] );
			}
		}
	}

endif;
