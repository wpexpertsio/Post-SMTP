<?php
/**
 * Handles the migration for fallback support.
 *
 * This class handles the logging and migration process
 * for fallback email settings in the Post SMTP plugin.
 *
 * @package PostSMTP
 * @version 1.0.0
 */

if ( ! class_exists( 'PostmanFallbackMigration' ) ) :
	/**
	 * Class PostmanFallbackMigration
	 *
	 * Handles the migration process for the fallback email settings.
	 *
	 * @package PostSMTP
	 * @version 1.0.0
	 */
	class PostmanFallbackMigration {

		/**
		 * Indicates if logging is enabled.
		 *
		 * @var bool
		 */
		private $existing_logging = false;

		/**
		 * Indicates if migration is in progress.
		 *
		 * @var bool
		 */
		private $migrating = false;

		/**
		 * Indicates if there are existing logs.
		 *
		 * @var bool
		 */
		private $have_old_logs = false;

		/**
		 * Path to the logging file.
		 *
		 * @var string
		 */
		private $logging_file = '';

		/**
		 * URL of the logging file.
		 *
		 * @var string
		 */
		private $logging_file_url = '';

		/**
		 * Time (in seconds) for which deleted settings can be recovered.
		 *
		 * @var int
		 */
		private $recover_settings = 5 * DAY_IN_SECONDS;

		/**
		 * Constructor PostmanFallbackMigration
		 *
		 * @since 2.4.0
		 * @version 1.0.0
		 */
		public function __construct() {

			if ( is_multisite() ) {

				$this->logging_file     = WP_CONTENT_DIR . '/post-smtp-migration-' . get_current_blog_id() . '.log';
				$this->logging_file_url = WP_CONTENT_URL . '/post-smtp-migration-' . get_current_blog_id() . '.log';

			} else {

				$this->logging_file     = WP_CONTENT_DIR . '/post-smtp-migration.log';
				$this->logging_file_url = WP_CONTENT_URL . '/post-smtp-migration.log';

			}

			// Custom option-based transient replacement.
			$hide_notice               = $this->get_expiring_option( 'ps_dismiss_fallback_update_notice' );
			$deleted_email_settings    = $this->get_expiring_option( 'deleted_email_settings' );
			
			// Show DB Update Notice.
			if ( $this->has_migrated() && ( Postman_Connection_Resolver::is_legacy_mode() || false !== $deleted_email_settings ) ) {
				add_action( 'admin_notices', array( $this, 'notice' ) );
			}

			add_action( 'wp_ajax_ps-db-update-notice-dismiss', array( $this, 'ps_dismiss_fallback_update_notice' ) );
			// Register the action to handle the form submission.
			add_action( 'admin_post_ps_fallback_update_action', array( $this, 'handle_fallback_update' ) );
			add_action( 'admin_post_restore_email_settings', array( $this, 'restore_email_settings' ) );
		}

		/**
		 *  Shows DB Update Notice | Action call-back
		 *
		 * @since 2.4.0
		 * @version 1.0.0
		 */
		public function notice() {
			$security               = wp_create_nonce( 'ps-migrate-fallback-db' );
			$migration_url          = admin_url( 'admin.php?page=postman' ) . '&security=' . $security . '&action=ps-migrate-logs';
			$this->migrating        = get_option( 'ps_migrate_fallback' );
			$current_page           = isset( $_GET['page'] ) ? $_GET['page'] : '';
			$new_logging            = get_option( 'postman_db_version' );
			$dismissible            = ( ! $this->has_migrated() && ! $this->migrating ) ? 'is-dismissible' : '';
			$revert_url             = admin_url( 'admin.php?page=postman_email_log' ) . '&security=' . $security . '&action=ps-revert-migration';
			$deleted_email_settings = $this->get_expiring_option( 'deleted_email_settings' );

			if ( isset( $_GET['fallback_update'] ) && $_GET['fallback_update'] == 'success' ) {
				?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Fallback support has been updated successfully!', 'post-smtp' ); ?></p>
			</div>
				<?php
			}
			?>
		<div class="notice ps-db-update-notice <?php echo esc_attr( $dismissible ); ?>" style="border: 1px solid #2271b1; border-left-width: 4px;">
			<input type="hidden" value="<?php echo esc_attr( $security ); ?>" class="ps-security">
			<p><b><?php _e( 'Post SMTP database update required', 'post-smtp' ); ?></b></p>
			<?php if ( Postman_Connection_Resolver::is_legacy_mode() && $deleted_email_settings === false ) : ?>
				<p><?php echo _e( 'Additional Socket support will be now available in our fallback module.', 'post-smtp' ); ?></p>
				<?php 
				// Check if Pro version meets requirements
				$can_update_fallback = $this->can_update_fallback();
				$pro_version_error = $this->get_pro_version_error();
				?>
				<?php if ( !$can_update_fallback && $pro_version_error ) : ?>
					<div class="notice notice-error inline" style="margin: 10px 0; padding: 10px;">
						<p><strong><?php _e( 'Fallback Update Blocked:', 'post-smtp' ); ?></strong> <?php echo esc_html( $pro_version_error ); ?></p>
					</div>
				<?php endif; ?>
				<form method="post" class="fallback_migration_form" style="" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ps_fallback_update_action">
					<input type="hidden" name="security" value="<?php echo esc_attr( $security ); ?>">
					<button type="submit" class="button button-primary" <?php echo $can_update_fallback ? '' : 'disabled'; ?>>
						<?php _e( 'Update for Fallback Support', 'post-smtp' ); ?>
					</button>
					<?php if ( !$can_update_fallback ) : ?>
						<p class="description" style="margin-top: 5px; color: #d63638;">
							<?php _e( 'Please update Post SMTP Pro to version 1.6.0 or higher to enable fallback support.', 'post-smtp' ); ?>
						</p>
					<?php endif; ?>
				</form>
			<?php endif; ?>
			<?php if ( $deleted_email_settings !== false ) : ?>
				<form method="post" class="fallback_migration_form" style="" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="restore_email_settings">
					<input type="hidden" name="security" value="<?php echo esc_attr( $security ); ?>">
					<button type="submit" class="button button-primary">Restore Settings</button>
				</form>
			<?php endif; ?>
			<a href="https://postmansmtp.com/new-and-better-email-log-post-smtp-feature-update/" target="__blank" class="button button-secondary">Learn about migration</a>
			<div style="float: right"></div>
			<div style="clear: both;"></div>
			<div style="margin: 10px 0;"></div>
		</div>
			<?php
		}


		/**
		 * Checks if logs migrated or not | Same as is_migrated() but used custom query
		 *
		 * @since 2.5.2
		 * @version 1.0.0
		 */
		public function has_migrated() {

			global $wpdb;

			$response = $wpdb->get_results(
				"SELECT 
            count(*) AS count
            FROM 
            {$wpdb->posts}
            WHERE 
            post_type = 'postman_sent_mail'"
			);

			$total_old_logs = empty( $response ) ? 0 : (int) $response[0]->count;

			return true;
		}


		/**
		 * Dismiss update notice | AJAX call-back
		 *
		 * @since 2.5.2
		 * @version 1.0.0
		 */
		public function dismiss_update_notice() {

			if ( isset( $_POST['action'] ) && $_POST['action'] == 'ps-db-update-notice-dismiss' && wp_verify_nonce( $_POST['security'], 'ps-migrate-logs' ) ) {

				set_transient( 'ps_dismiss_update_notice', 1, WEEK_IN_SECONDS );

			}
		}

		/**
		 *  Handles the fallback update action
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		public function handle_fallback_update() {
			// Check security nonce
			if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'ps-migrate-fallback-db' ) ) {
				wp_die( 'Security check failed.' );
			}

			// Check Pro version requirements before proceeding
			if ( ! $this->can_update_fallback() ) {
				$error_message = $this->get_pro_version_error();
				wp_die( 
					sprintf( 
						__( 'Fallback update blocked: %s', 'post-smtp' ), 
						$error_message ?: __( 'Pro version requirements not met.', 'post-smtp' )
					)
				);
			}

			// Gather mail settings and save connections if security passes.
			$this->save_mail_connections();

			// Update the Postman DB version if necessary.
			$this->update_db_version();

			// Save the Postman Details In Transient.
			$this->store_email_settings();

			// Redirect to the same page or any other page with a success message.
			wp_redirect( admin_url( 'admin.php?page=postman&fallback_update=success' ) );

			exit;
		}

		/**
		 * Gathers mail settings from 'postman_options', saves them in 'postman_connections',
		 * placing the current transport type options at index 0 and fallback settings at index 1 if enabled.
		 *
		 * The active `transport_type` always becomes connection `0` and `primary_connection`.
		 * Other providers still present in `postman_options` (not the active transport) are
		 * appended next. Finally, every remaining wizard row in the pre-migration
		 * `postman_connections` snapshot that has usable credentials and a distinct
		 * fingerprint is appended — including inactive OAuth accounts and extra API mailers.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function save_mail_connections() {
			$postman_options = get_option( 'postman_options', array() );
			$current_transport_type = isset( $postman_options['transport_type'] ) && '' !== (string) $postman_options['transport_type']
				? (string) $postman_options['transport_type']
				: 'default';

			// Snapshot before this run replaces the option (multi-connection / Pro wizard data).
			$legacy_connections_snapshot = get_option( 'postman_connections', array() );
			if ( ! is_array( $legacy_connections_snapshot ) ) {
				$legacy_connections_snapshot = array();
			}

			/*
			 * Wizard often leaves `transport_type` as `default` while the real mailer exists
			 * only on `postman_connections` (see NewWizard::handle_new_version_save). Infer
			 * `gmail_api` / `office365_api` / `zohomail_api` / usable `smtp` from the snapshot
			 * so hydration and connection `0` still build correctly.
			 */
			if ( 'default' === $current_transport_type ) {
				$oauth_slugs = array( 'gmail_api', 'office365_api', 'zohomail_api' );
				$primary_idx = isset( $postman_options['primary_connection'] ) ? $postman_options['primary_connection'] : 0;
				if ( null === $primary_idx || '' === (string) $primary_idx ) {
					$primary_idx = 0;
				}
				$inferred = null;
				if ( isset( $legacy_connections_snapshot[ $primary_idx ] ) && is_array( $legacy_connections_snapshot[ $primary_idx ] ) ) {
					$cand   = $legacy_connections_snapshot[ $primary_idx ];
					$cand_p = isset( $cand['provider'] ) ? (string) $cand['provider'] : '';
					if ( 'smtp' === $cand_p && ! empty( $cand['hostname'] ) ) {
						$inferred = 'smtp';
					} elseif ( in_array( $cand_p, $oauth_slugs, true ) ) {
						$inferred = $cand_p;
					}
				}
				if ( null === $inferred ) {
					foreach ( $legacy_connections_snapshot as $cand ) {
						if ( ! is_array( $cand ) ) {
							continue;
						}
						$cand_p = isset( $cand['provider'] ) ? (string) $cand['provider'] : '';
						if ( in_array( $cand_p, $oauth_slugs, true ) ) {
							$inferred = $cand_p;
							break;
						}
					}
				}
				if ( null !== $inferred ) {
					$postman_options['transport_type'] = $inferred;
					$current_transport_type            = $inferred;
				}
			}

			$mail_connections = array();

			// Sender details - only for primary
			$sender_details = array(
				'sender_email'    => sanitize_email( $postman_options['sender_email'] ?? '' ),
				'envelope_sender' => sanitize_email( $postman_options['envelope_sender'] ?? '' ),
				'sender_name'     => sanitize_text_field( $postman_options['sender_name'] ?? '' ),
			);

			// All API providers data
			$api_keys = $this->get_api_keys( $postman_options );
			$this->ensure_oauth_connections_exist( $api_keys );
			$this->ensure_primary_transport_api_keys( $api_keys, $current_transport_type, $postman_options );

			// Sensitive keys to decode before saving — single canonical list.
			$sensitive_keys = Postman_Connection_Resolver::get_sensitive_keys();

			// Decode sensitive keys if present (unwrap multi-layer base64 from legacy postman_options).
			foreach ( $api_keys as $provider => &$connection ) {
				foreach ( $sensitive_keys as $key ) {
					if ( isset( $connection[ $key ] ) && '' !== (string) $connection[ $key ] ) {
						$connection[ $key ] = Postman_Connection_Resolver::decode_stored_option_secret( $connection[ $key ] );
					}
				}
			}
			unset( $connection );

			/*
			 * Gmail / Microsoft / Zoho one-click often persist OAuth tokens only on
			 * `postman_connections` (wizard save) while `postman_auth_token` or
			 * `postman_office365_oauth` is empty. Merge those plaintext fields *after*
			 * the base64 pass so we never double-decode secrets that already live on a row.
			 */
			$this->hydrate_api_keys_from_saved_connections(
				$api_keys,
				$current_transport_type,
				$postman_options,
				$legacy_connections_snapshot
			);

			$this->assign_primary_mail_connection(
				$mail_connections,
				$postman_options,
				$api_keys,
				$current_transport_type,
				$sender_details
			);

			$connection_index = 1;
				// Optional fallback SMTP
			$fallback_enabled = $postman_options['fallback_smtp_enabled'] ?? 'no';
			$fallback_hostname = $postman_options['fallback_smtp_hostname'] ?? '';
			
			// Check if fallback SMTP is enabled.
			if ( $fallback_enabled === 'yes' && ! empty( $fallback_hostname ) ) {
				/*
				 * Fallback SMTP credentials live single-base64-encoded in
				 * `postman_options` (some historical installs are even double-
				 * encoded, which is why PostmanOptions::getFallbackPassword()
				 * carries a smart double-decode rescue). The new schema in
				 * `postman_connections` expects plaintext credentials, so we
				 * resolve them through the legacy getters here instead of
				 * copying the raw option value through unchanged.
				 */
				$legacy_options       = PostmanOptions::getInstance();
				$fallback_username    = $legacy_options->getFallbackUsername();
				$fallback_password    = $legacy_options->getFallbackPassword();
				// Gather all fallback-related settings except 'fallback_smtp_enabled'.
				$mail_connections[ $connection_index ] = array(
					'enc_type'            => sanitize_text_field( $postman_options['fallback_smtp_security'] ?? '' ),
					'hostname'            => sanitize_text_field( $postman_options['fallback_smtp_hostname'] ?? '' ),
					'port'                => sanitize_text_field( $postman_options['fallback_smtp_port'] ?? '' ),
					'auth_type'           => sanitize_text_field( $postman_options['fallback_smtp_use_auth'] ?? '' ),
					'sender_name'         => sanitize_text_field( $postman_options['sender_name'] ?? '' ),
					'sender_email'        => sanitize_email( $postman_options['fallback_from_email'] ?? '' ),
					'envelope_sender'     => sanitize_text_field( $postman_options['fallback_from_email'] ?? '' ),
					'basic_auth_username' => sanitize_text_field( $fallback_username ?? '' ),
					'basic_auth_password' => sanitize_text_field( $fallback_password ?? '' ),
					'provider'            => 'smtp',
					'title'               => 'SMTP',
				);
				 // Save fallback settings into mail_connections[1]
				$postman_options['selected_fallback'] = $connection_index;
				$connection_index++;
			}

			foreach ( $api_keys as $provider_key => $connection_data  ) {
				if (
					$provider_key === $current_transport_type ||
					empty( $connection_data )
				) {
					continue;
				}
				$mail_connections[ $connection_index ] = array_merge(
					$connection_data,
					array_filter( $sender_details )
				);
				$connection_index++;
			}

			$this->append_additional_saved_connections( $mail_connections, $legacy_connections_snapshot, $sender_details );

			$this->ensure_connection_zero_is_primary( $mail_connections, $current_transport_type );

			// Save the new mail connections to the 'postman_connections' option.
			update_option( 'postman_connections', $mail_connections );
			update_option( 'postman_options', $postman_options );
		}

		/**
		 * Appends wizard-saved rows from the legacy `postman_connections` snapshot that are
		 * not already represented (same provider + same identity / token fingerprint).
		 *
		 * @param array $mail_connections          Built connections (modified by reference).
		 * @param array $legacy_connections_snapshot Option value read at migration start.
		 * @param array $sender_details             Default sender fields for empty cells only.
		 */
		private function append_additional_saved_connections( array &$mail_connections, array $legacy_connections_snapshot, array $sender_details ) {
			$signatures = array();
			foreach ( $mail_connections as $row ) {
				if ( is_array( $row ) ) {
					$sig = $this->migration_row_fingerprint( $row );
					if ( '' !== $sig ) {
						$signatures[ $sig ] = true;
					}
				}
			}

			foreach ( $legacy_connections_snapshot as $row ) {
				if ( ! is_array( $row ) || empty( $row['provider'] ) ) {
					continue;
				}
				if ( ! $this->saved_connection_row_has_credentials( $row ) ) {
					continue;
				}
				$normalized = $row;
				foreach ( Postman_Connection_Resolver::get_sensitive_keys() as $key ) {
					if ( isset( $normalized[ $key ] ) && '' !== (string) $normalized[ $key ] ) {
						$normalized[ $key ] = base64_decode( (string) $normalized[ $key ] );
					}
				}
				$sig = $this->migration_row_fingerprint( $normalized );
				if ( '' === $sig || isset( $signatures[ $sig ] ) ) {
					continue;
				}

				$merged = $normalized;
				foreach ( array_filter( $sender_details ) as $key => $value ) {
					if ( ! isset( $merged[ $key ] ) || '' === (string) $merged[ $key ] ) {
						$merged[ $key ] = $value;
					}
				}

				$mail_connections[] = $merged;
				$signatures[ $sig ] = true;
			}
		}

		/**
		 * Whether a saved connection row still carries enough data to keep after migration.
		 *
		 * @param array $row Connection row.
		 * @return bool
		 */
		private function saved_connection_row_has_credentials( array $row ) {
			$provider = isset( $row['provider'] ) ? (string) $row['provider'] : '';
			if ( '' === $provider ) {
				return false;
			}

			switch ( $provider ) {
				case 'gmail_api':
					return ! empty( $row['access_token'] ) || ! empty( $row['refresh_token'] )
						|| ! empty( $row['oauth_client_id'] ) || ! empty( $row['oauth_client_secret'] );
				case 'office365_api':
					return ! empty( $row['access_token'] ) || ! empty( $row['refresh_token'] )
						|| ! empty( $row['office365_app_id'] ) || ! empty( $row['office365_app_password'] );
				case 'zohomail_api':
					return ! empty( $row['access_token'] ) || ! empty( $row['refresh_token'] )
						|| ! empty( $row['zohomail_client_id'] ) || ! empty( $row['zohomail_client_secret'] );
				case 'smtp':
					return ! empty( $row['hostname'] );
				default:
					$skip = array(
						'provider',
						'title',
						'provider_name',
						'sender_email',
						'sender_name',
						'envelope_sender',
						'prevent_sender_name_override',
						'prevent_sender_email_override',
					);
					foreach ( $row as $key => $value ) {
						if ( in_array( $key, $skip, true ) ) {
							continue;
						}
						if ( is_string( $value ) && '' !== $value ) {
							return true;
						}
						if ( is_numeric( $value ) && (string) $value !== '' ) {
							return true;
						}
					}
					return false;
			}
		}

		/**
		 * Fingerprint for one saved connection: used when appending legacy rows so we skip
		 * only true duplicates. OAuth rows without tokens used to share the same short key;
		 * in that case a hash of credential-like fields is appended so inactive accounts
		 * are not dropped.
		 *
		 * @param array $row Connection row (same shape as stored in `postman_connections`).
		 * @return string Empty when the row cannot be fingerprinted.
		 */
		private function migration_row_fingerprint( array $row ) {
			if ( empty( $row['provider'] ) ) {
				return '';
			}

			$provider = (string) $row['provider'];
			$base     = '';

			switch ( $provider ) {
				case 'gmail_api':
					$identity = ! empty( $row['account_key'] ) ? (string) $row['account_key'] : (string) ( $row['user_email'] ?? '' );
					$app      = (string) ( $row['oauth_client_id'] ?? '' );
					$tok      = ! empty( $row['refresh_token'] )
						? md5( (string) $row['refresh_token'] )
						: ( ! empty( $row['access_token'] ) ? md5( (string) $row['access_token'] ) : '' );
					$base     = $provider . "\x1e" . $identity . "\x1e" . $app . "\x1e" . $tok;
					break;

				case 'office365_api':
					$identity = (string) ( $row['user_email'] ?? '' );
					$app      = (string) ( $row['office365_app_id'] ?? '' );
					$tok      = ! empty( $row['refresh_token'] )
						? md5( (string) $row['refresh_token'] )
						: ( ! empty( $row['access_token'] ) ? md5( (string) $row['access_token'] ) : '' );
					$base     = $provider . "\x1e" . $identity . "\x1e" . $app . "\x1e" . $tok;
					break;

				case 'zohomail_api':
					$tok  = ! empty( $row['refresh_token'] )
						? md5( (string) $row['refresh_token'] )
						: ( ! empty( $row['access_token'] ) ? md5( (string) $row['access_token'] ) : '' );
					$base = $provider . "\x1e" . (string) ( $row['zohomail_client_id'] ?? '' ) . "\x1e" . (string) ( $row['user_email'] ?? '' ) . "\x1e" . $tok;
					break;

				case 'smtp':
					$base = $provider . "\x1e" . (string) ( $row['hostname'] ?? '' ) . "\x1e" . (string) ( $row['port'] ?? '' )
						. "\x1e" . (string) ( $row['basic_auth_username'] ?? '' ) . "\x1e" . md5( (string) ( $row['basic_auth_password'] ?? '' ) );
					break;

				case 'aws_ses_api':
					$base = $provider . "\x1e" . (string) ( $row['ses_access_key_id'] ?? '' ) . "\x1e" . (string) ( $row['ses_region'] ?? '' );
					break;

				default:
					$credential_keys = array(
						'mandrill_api_key', 'sendgrid_api_key', 'sendinblue_api_key', 'mailjet_api_key', 'mailjet_secret_key',
						'sendpulse_api_key', 'sendpulse_secret_key', 'postmark_api_key', 'sparkpost_api_key',
						'mailgun_api_key', 'mailgun_domain_name', 'elasticemail_api_key', 'smtp2go_api_key',
						'mailersend_api_key', 'emailit_api_key', 'resend_api_key', 'maileroo_api_key', 'sweego_api_key',
						'mailtrap_api_key',
					);
					$parts = array( $provider );
					foreach ( $credential_keys as $key ) {
						if ( ! empty( $row[ $key ] ) ) {
							$parts[] = $key . '=' . md5( (string) $row[ $key ] );
						}
					}
					if ( count( $parts ) === 1 ) {
						$base = $provider . "\x1e" . md5( wp_json_encode( $row ) );
					} else {
						$base = implode( "\x1e", $parts );
					}
					break;
			}

			if ( '' === $base ) {
				return '';
			}

			if ( in_array( $provider, array( 'gmail_api', 'office365_api', 'zohomail_api' ), true )
				&& empty( $row['refresh_token'] ) && empty( $row['access_token'] ) ) {
				$skip_material = array(
					'title',
					'provider_name',
					'prevent_sender_name_override',
					'prevent_sender_email_override',
					'timestamp',
				);
				$material = array();
				foreach ( $row as $k => $v ) {
					if ( 'provider' === $k || in_array( $k, $skip_material, true ) ) {
						continue;
					}
					if ( is_scalar( $v ) && '' !== (string) $v ) {
						$material[ (string) $k ] = (string) $v;
					}
				}
				ksort( $material );
				$base .= "\x1e" . md5( (string) wp_json_encode( $material ) );
			}

			return $base;
		}

		/**
		 * Retrieves the API keys and their parent categories from the postman_options.
		 *
		 * @param array $postman_options The options array from the database.
		 * @return array The array of API keys organized by parent categories.
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function get_api_keys( $postman_options ) {
			// Define the keys we want to extract for each API type.
			$api_keys_definitions = array(
				'smtp'             => array(
					'enc_type',
					'hostname',
					'port',
					'sender_email',
					'envelope_sender',
					'basic_auth_username',
					'basic_auth_password',
				),
				'mandrill'         => array( 'mandrill_api_key' ),
				'sendgrid_api'     => array( 'sendgrid_api_key' ),
				'sendinblue_api'   => array( 'sendinblue_api_key' ),
				'mailjet_api'      => array(
					'mailjet_api_key',
					'mailjet_secret_key',
				),
				'sendpulse_api'    => array(
					'sendpulse_api_key',
					'sendpulse_secret_key',
				),
				'postmark_api'     => array( 'postmark_api_key' ),
				'sparkpost_api'    => array( 'sparkpost_api_key' ),
				'mailgun_api'      => array(
					'mailgun_api_key',
					'mailgun_domain_name',
				),
				'elasticemail_api' => array( 'elasticemail_api_key' ),
				'smtp2go_api'      => array( 'smtp2go_api_key' ),
				'gmail_api'        => array(
					'oauth_client_id',
					'oauth_client_secret',
				),
				'office365_api'    => array(
					'office365_app_id',
					'office365_app_password',
				),
				'zohomail_api'     => array(
					'zohomail_client_id',
					'zohomail_client_secret',
					'zohomail_region',
				),
				'aws_ses_api'      => array(
					'ses_access_key_id',
					'ses_secret_access_key',
					'ses_region',
				),
				'mailersend_api'   => array( 'mailersend_api_key'),
				'emailit_api'      => array( 'emailit_api_key'),
				'resend_api'       => array( 'resend_api_key'),	
				'maileroo_api'     => array( 'maileroo_api_key' ),
				'mailtrap_api'     => array( 'mailtrap_api_key' ),
				'sweego_api'       => array( 'sweego_api_key' ),
			);

			// Initialize the API keys array.
			$api_keys = array();

			// Helper function to sanitize and get values.
			$get_value = function ( $key ) use ( $postman_options ) {
				return isset( $postman_options[ $key ] ) ? sanitize_text_field( $postman_options[ $key ] ) : '';
			};

			// Build the api_keys array based on definitions.
			foreach ( $api_keys_definitions as $key => $fields ) {
				$values = array_map( $get_value, $fields );

				// Special check: Ensure 'hostname' is not empty for SMTP.
				if ( $key === 'smtp' && empty( $values[ array_search( 'hostname', $fields ) ] ) ) {
					continue; // Skip this entry if hostname is empty.
				}

				// Skip SES when access key is empty (region alone is not a usable connection).
				if ( 'aws_ses_api' === $key ) {
					$access_idx = array_search( 'ses_access_key_id', $fields, true );
					if ( false === $access_idx || empty( $values[ $access_idx ] ) ) {
						continue;
					}
				}

				// Gmail manual / One-Click may keep empty oauth app fields on the active transport.
				if ( 'gmail_api' === $key ) {
					$include = (bool) array_filter( $values );
					if ( ! $include && isset( $postman_options['transport_type'] ) && 'gmail_api' === $postman_options['transport_type'] ) {
						$include = true;
					}
					foreach ( $fields as $field ) {
						if ( array_key_exists( $field, $postman_options ) ) {
							$include = true;
							break;
						}
					}
					if ( $include ) {
						$api_keys[ $key ]             = array_combine( $fields, $values );
						$api_keys[ $key ]['provider'] = $key;
						$api_keys[ $key ]['title']    = $this->format_provider_title( $key );
					}
					continue;
				}

				// Check if any values are non-empty before adding to the api_keys array.
				if ( array_filter( $values ) ) {
					$api_keys[ $key ]             = array_filter( array_combine( $fields, $values ) );
					$api_keys[ $key ]['provider'] = $key;
					$api_keys[ $key ]['title']    = $this->format_provider_title( $key );
				}
			}

			return $api_keys;
		}

		/**
		 * Pulls OAuth token data from global options into `api_keys` for every provider that
		 * has tokens stored outside `postman_options`, even when that mailer is not the active
		 * `transport_type` (so inactive Gmail / Microsoft / Zoho accounts still migrate).
		 *
		 * @param array $api_keys Map keyed by provider slug (by reference).
		 */
		private function ensure_oauth_connections_exist( array &$api_keys ) {
			$gmail_tokens = get_option( 'postman_auth_token', array() );
			if ( is_array( $gmail_tokens ) && (
				! empty( $gmail_tokens['access_token'] ) || ! empty( $gmail_tokens['refresh_token'] )
			) ) {
				if ( ! isset( $api_keys['gmail_api'] ) ) {
					$api_keys['gmail_api'] = array(
						'provider' => 'gmail_api',
						'title'    => $this->format_provider_title( 'gmail_api' ),
					);
				}
				foreach ( $gmail_tokens as $token_key => $token_value ) {
					if ( null !== $token_value && '' !== (string) $token_value ) {
						$api_keys['gmail_api'][ $token_key ] = $token_value;
					}
				}
			}

			$office365 = get_option( 'postman_office365_oauth', array() );
			if ( is_array( $office365 ) && (
				! empty( $office365['access_token'] ) || ! empty( $office365['refresh_token'] )
			) ) {
				if ( ! isset( $api_keys['office365_api'] ) ) {
					$api_keys['office365_api'] = array(
						'provider' => 'office365_api',
						'title'    => $this->format_provider_title( 'office365_api' ),
					);
				}
				$office_merge_keys = array( 'access_token', 'refresh_token', 'token_expires', 'user_email' );
				foreach ( $office_merge_keys as $field ) {
					if ( isset( $office365[ $field ] ) && '' !== (string) $office365[ $field ] ) {
						$api_keys['office365_api'][ $field ] = $office365[ $field ];
					}
				}
			}

			// Pro Zoho extension (`PostSMTP_ZohoMail::OAUTH_OPTIONS`).
			$zoho = get_option( 'postman_zohomail_oauth', array() );
			if ( is_array( $zoho ) && (
				! empty( $zoho['access_token'] ) || ! empty( $zoho['refresh_token'] )
			) ) {
				if ( ! isset( $api_keys['zohomail_api'] ) ) {
					$api_keys['zohomail_api'] = array(
						'provider' => 'zohomail_api',
						'title'    => $this->format_provider_title( 'zohomail_api' ),
					);
				}
				foreach ( $zoho as $key => $value ) {
					if ( null === $value || ! is_scalar( $value ) || '' === (string) $value ) {
						continue;
					}
					$api_keys['zohomail_api'][ $key ] = $value;
				}
			}
		}

		/**
		 * Ensures the active transport exists in the gathered API map when credentials live
		 * only in `postman_options` (OAuth app id/secret or Microsoft app password). Global
		 * OAuth tokens are merged separately in `ensure_oauth_connections_exist()`.
		 *
		 * @param array  $api_keys               API map keyed by provider (by reference).
		 * @param string $current_transport_type Active transport slug from options.
		 * @param array  $postman_options        Raw `postman_options` array.
		 */
		private function ensure_primary_transport_api_keys( array &$api_keys, $current_transport_type, array $postman_options ) {

			if ( 'gmail_api' === $current_transport_type ) {
				$gmail_tokens = get_option( 'postman_auth_token', array() );
				$has_tokens   = is_array( $gmail_tokens ) && (
					! empty( $gmail_tokens['access_token'] ) || ! empty( $gmail_tokens['refresh_token'] )
				);
				$has_oauth_app = ! empty( $postman_options['oauth_client_id'] ) || ! empty( $postman_options['oauth_client_secret'] )
					|| array_key_exists( 'oauth_client_id', $postman_options )
					|| array_key_exists( 'oauth_client_secret', $postman_options );

				if ( ! isset( $api_keys['gmail_api'] ) && ( $has_oauth_app || $has_tokens ) ) {
					$api_keys['gmail_api'] = array(
						'provider' => 'gmail_api',
						'title'    => $this->format_provider_title( 'gmail_api' ),
					);
				}

				if ( isset( $api_keys['gmail_api'] ) ) {
					if ( array_key_exists( 'oauth_client_id', $postman_options ) && ! array_key_exists( 'oauth_client_id', $api_keys['gmail_api'] ) ) {
						$api_keys['gmail_api']['oauth_client_id'] = sanitize_text_field( (string) $postman_options['oauth_client_id'] );
					}
					if ( array_key_exists( 'oauth_client_secret', $postman_options ) && ! array_key_exists( 'oauth_client_secret', $api_keys['gmail_api'] ) ) {
						$api_keys['gmail_api']['oauth_client_secret'] = Postman_Connection_Resolver::decode_stored_option_secret(
							$postman_options['oauth_client_secret']
						);
					}
				}
			}

			if ( 'office365_api' === $current_transport_type ) {
				$oauth     = get_option( 'postman_office365_oauth', array() );
				$has_app   = ! empty( $postman_options['office365_app_id'] ) || ! empty( $postman_options['office365_app_password'] );
				$has_oauth = is_array( $oauth ) && (
					! empty( $oauth['access_token'] ) || ! empty( $oauth['refresh_token'] )
				);

				if ( ! isset( $api_keys['office365_api'] ) && ( $has_app || $has_oauth ) ) {
					$api_keys['office365_api'] = array(
						'provider' => 'office365_api',
						'title'    => $this->format_provider_title( 'office365_api' ),
					);
				}
			}
		}

		/**
		 * Builds `postman_connections[0]` from the active transport entry in `$api_keys`.
		 *
		 * @param array  $mail_connections     Built connections (by reference).
		 * @param array  $postman_options      Options snapshot (by reference).
		 * @param array  $api_keys             Provider map from migration gather step.
		 * @param string $transport_type       Active transport slug.
		 * @param array  $sender_details       Sender fields for the primary row.
		 */
		private function assign_primary_mail_connection( array &$mail_connections, array &$postman_options, array $api_keys, $transport_type, array $sender_details ) {
			if ( ! isset( $api_keys[ $transport_type ] ) || ! is_array( $api_keys[ $transport_type ] ) ) {
				return;
			}

			$row             = $api_keys[ $transport_type ];
			$credential_row  = $row;
			$credential_row['provider'] = $transport_type;

			if ( ! $this->saved_connection_row_has_credentials( $credential_row ) ) {
				return;
			}

			$postman_options['primary_connection'] = 0;
			$mail_connections[0]                   = array_merge(
				$row,
				array_filter( $sender_details )
			);
		}

		/**
		 * Moves the active transport row to index 0 when it was appended at a higher index.
		 *
		 * @param array  $mail_connections Built connections (by reference).
		 * @param string $transport_type   Active transport slug.
		 */
		private function ensure_connection_zero_is_primary( array &$mail_connections, $transport_type ) {
			if ( '' === (string) $transport_type || 'default' === $transport_type ) {
				return;
			}

			if ( isset( $mail_connections[0] ) && is_array( $mail_connections[0] ) && ( $mail_connections[0]['provider'] ?? '' ) === $transport_type ) {
				return;
			}

			$primary = null;
			$others  = array();

			foreach ( $mail_connections as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( ( $row['provider'] ?? '' ) === $transport_type ) {
					if ( null === $primary ) {
						$primary = $row;
						continue;
					}
				}
				$others[] = $row;
			}

			if ( null !== $primary ) {
				$mail_connections = array_merge( array( $primary ), $others );
			}
		}

		/**
		 * Whether `store_email_settings()` may strip flat credentials from `postman_options`.
		 *
		 * @param array  $primary_connection Connection row at index 0.
		 * @param string $transport_type     Active transport slug from options.
		 * @return bool
		 */
		private function migration_primary_connection_is_usable( array $primary_connection, $transport_type ) {
			if ( array() === $primary_connection ) {
				return false;
			}

			$check = $primary_connection;
			if ( '' !== (string) $transport_type && 'default' !== $transport_type && empty( $check['provider'] ) ) {
				$check['provider'] = $transport_type;
			}

			return $this->saved_connection_row_has_credentials( $check );
		}

		/**
		 * Copies plaintext credential fields from the pre-migration `postman_connections`
		 * row for the active transport (when present).
		 *
		 * @param array  $api_keys               API map keyed by provider (by reference).
		 * @param string $current_transport_type Active transport slug.
		 * @param array  $postman_options        Options before this migration run (may include `primary_connection`).
		 * @param array  $existing_connections   Pre-migration `postman_connections` snapshot (same source as append step).
		 */
		private function hydrate_api_keys_from_saved_connections( array &$api_keys, $current_transport_type, array $postman_options, array $existing_connections = null ) {
			$fields = $this->get_connection_hydration_fields( $current_transport_type );
			if ( empty( $fields ) ) {
				return;
			}

			$existing = null !== $existing_connections && is_array( $existing_connections )
				? $existing_connections
				: (array) get_option( 'postman_connections', array() );
			if ( ! is_array( $existing ) || array() === $existing ) {
				return;
			}

			$row = $this->pick_saved_connection_row_for_transport( $existing, $current_transport_type, $postman_options );
			if ( ! is_array( $row ) ) {
				return;
			}

			$has_payload = false;
			foreach ( $fields as $field ) {
				if ( isset( $row[ $field ] ) && '' !== (string) $row[ $field ] ) {
					$has_payload = true;
					break;
				}
			}
			if ( ! $has_payload ) {
				return;
			}

			if ( ! isset( $api_keys[ $current_transport_type ] ) ) {
				$api_keys[ $current_transport_type ] = array(
					'provider' => $current_transport_type,
					'title'    => $this->format_provider_title( $current_transport_type ),
				);
			}

			foreach ( $fields as $field ) {
				if ( isset( $row[ $field ] ) && '' !== (string) $row[ $field ] ) {
					$api_keys[ $current_transport_type ][ $field ] = $row[ $field ];
				}
			}
		}

		/**
		 * @param array  $existing        Current `postman_connections` option.
		 * @param string $transport_type  Provider slug to match.
		 * @param array  $postman_options Options snapshot (uses `primary_connection` when set).
		 * @return array|null
		 */
		private function pick_saved_connection_row_for_transport( array $existing, $transport_type, array $postman_options ) {
			$primary_idx = isset( $postman_options['primary_connection'] ) ? $postman_options['primary_connection'] : null;
			if ( null !== $primary_idx && '' !== (string) $primary_idx && isset( $existing[ $primary_idx ] ) ) {
				$candidate = $existing[ $primary_idx ];
				if ( is_array( $candidate ) && ( $candidate['provider'] ?? '' ) === $transport_type ) {
					return $candidate;
				}
			}
			foreach ( $existing as $candidate ) {
				if ( is_array( $candidate ) && ( $candidate['provider'] ?? '' ) === $transport_type ) {
					return $candidate;
				}
			}
			return null;
		}

		/**
		 * Fields stored as plaintext on wizard-built connection rows (safe to overlay after option decode).
		 *
		 * @param string $transport_type Transport slug.
		 * @return string[]
		 */
		private function get_connection_hydration_fields( $transport_type ) {
			switch ( $transport_type ) {
				case 'gmail_api':
					return array(
						'oauth_client_id',
						'oauth_client_secret',
						'access_token',
						'refresh_token',
						'auth_token_expires',
						'token_expires',
						'user_email',
						'account_key',
						'provider_name',
						'timestamp',
					);
				case 'office365_api':
					return array(
						'office365_app_id',
						'office365_app_password',
						'access_token',
						'refresh_token',
						'token_expires',
						'user_email',
						'provider_name',
						'timestamp',
					);
				case 'zohomail_api':
					return array(
						'zohomail_client_id',
						'zohomail_client_secret',
						'zohomail_region',
						'access_token',
						'refresh_token',
						'token_expires',
						'user_email',
						'provider_name',
						'timestamp',
					);
				case 'mailtrap_api':
					return array( 'mailtrap_api_key' );
				default:
					return array();
			}
		}

		/**
		 * Updates the 'postman_db_version' from 1.0.1 to 1.0.2 if necessary.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function update_db_version() {
			// Get the current DB version.
			$current_db_version = get_option( 'postman_db_version', '1.0.1' ); // Fallback to 1.0.0 if not set.

			// Only update if the current version is 1.0.1.
			if ( $current_db_version === '1.0.1' ) {
				update_option( 'postman_db_version', POST_SMTP_DB_VERSION );
			}
		}

		/**
		 * Deletes email settings keys from postman_options and stores them in transients for 2 days.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function store_email_settings() {
			// Get the 'postman_options' array from the database.
			$postman_options = get_option( 'postman_options', array() );

			// Get the 'postman_connections' array and retrieve the primary connection.
			$mail_connections   = get_option( 'postman_connections', array() );
			$primary_connection = isset( $mail_connections[0] ) && is_array( $mail_connections[0] ) ? $mail_connections[0] : array();
			$transport_type     = isset( $postman_options['transport_type'] ) ? (string) $postman_options['transport_type'] : '';

			// Never strip flat credentials when migration failed to build a usable primary row.
			if ( ! $this->migration_primary_connection_is_usable( $primary_connection, $transport_type ) ) {
				return;
			}

			/*
			 * Back up postman_options EXACTLY as it sits in the database
			 * before merge. After merge, {@see Postman_Connection_Resolver::repair_postman_options_secret_encoding()}
			 * normalizes secrets to a single base64 layer so getters and restore stay consistent.
			 */
			$this->set_expiring_option( 'deleted_email_settings', $postman_options, $this->recover_settings );

			// Define all keys to be deleted from postman_options.
			$email_keys = array(
				'enc_type', 'hostname', 'port', 'envelope_sender',
				'basic_auth_username', 'basic_auth_password',
				'fallback_smtp_security', 'fallback_smtp_hostname',
				'fallback_smtp_port', 'fallback_smtp_use_auth', 'fallback_from_email',
				'fallback_smtp_username', 'fallback_smtp_password',
				'mandrill_api_key', 'sendgrid_api_key', 'sendinblue_api_key',
				'mailjet_api_key', 'mailjet_secret_key',
				'mailersend_api_key', 'sendpulse_api_key', 'sendpulse_secret_key',
				'postmark_api_key', 'sparkpost_api_key', 'mailgun_api_key',
				'mailgun_domain_name', 'elasticemail_api_key', 'smtp2go_api_key',
				'oauth_client_id', 'oauth_client_secret','emailit_api_key',
				'resend_api_key','maileroo_api_key', 'mailtrap_api_key', 'sweego_api_key',
				'office365_app_id', 'office365_app_password',
				'zohomail_client_id', 'zohomail_client_secret', 'zohomail_region',
				'ses_access_key_id', 'ses_secret_access_key', 'ses_region',
			);

			// Loop through the defined email keys.
			foreach ( $email_keys as $key ) {
				if ( array_key_exists( $key, $postman_options ) ) {
					// Remove the key from postman_options.
					unset( $postman_options[ $key ] );
				}
			}

			// If there is a primary connection, overwrite the values in $postman_options.
			if ( ! empty( $primary_connection ) ) {
				foreach ( $primary_connection as $key => $value ) {
					$postman_options[ $key ] = $value;
				}
			}

			Postman_Connection_Resolver::repair_postman_options_secret_encoding( $postman_options );

			// Update the postman_options in the database.
			update_option( 'postman_options', $postman_options );
		}

		/**
		 * Restores the email settings from the transient.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		public function restore_email_settings() {

			if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'ps-migrate-fallback-db' ) ) {
				wp_die( 'Security check failed.' );
			}

			$deleted_email_settings = $this->get_expiring_option( 'deleted_email_settings' );

			if ( false !== $deleted_email_settings ) {
				if ( is_array( $deleted_email_settings ) ) {
					Postman_Connection_Resolver::repair_postman_options_secret_encoding( $deleted_email_settings );
				}
				// Save the restored settings
				update_option( 'postman_options', $deleted_email_settings );

				// Clear fallback connections (if needed)
				delete_option( 'postman_connections' );
				delete_option( 'deleted_email_settings' );
				update_option( 'postman_db_version', '1.0.1' );

				// Redirect with success notice
				wp_safe_redirect( admin_url( 'admin.php?page=postman&settings_restored=1' ) );
				exit;
			}

		}

		/**
		 * Format provider name to title case, removing underscores and 'api'.
		 *
		 * @param string $key The provider key.
		 * @return string The formatted provider name.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function format_provider_title( $key ) {
			// Remove 'api' from the key and replace underscores with spaces.
			$formatted = str_replace( array( 'api', '_' ), '', $key );

			// Convert the formatted string to title case.
			return ucwords( $formatted );
		}
		
		
		/**
		 * Custom function to mimic transient with options and expiration.
		 */
		private function get_expiring_option( $option_name ) {
			$value = get_option( $option_name );
			
			if ( empty( $value ) || ! is_array( $value ) ) {
				return false;
			}

			// Check if expired.
			if ( isset( $value['expires'] ) && time() > $value['expires'] ) {
				delete_option( $option_name );
				return false;
			}

			return isset( $value['data'] ) ? $value['data'] : false;
		}

		/**
		 * Set an option with expiration (replacement for set_transient).
		 */
		private function set_expiring_option( $option_name, $data, $expiration ) {
			
			$option_value = array(
				'data'    => $data,
				'expires' => time() + $expiration,
			);

			update_option( $option_name, $option_value );
		}

		/**
		 * Check if fallback update is allowed based on Pro version requirements
		 * 
		 * @since 3.0.1
		 * @version 1.0.0
		 * @return bool True if update is allowed, false otherwise
		 */
		private function can_update_fallback() {
			// Always allow if Pro is not installed
			if ( ! defined( 'POST_SMTP_PRO_VERSION' ) ) {
				return true;
			}

			// Check if Pro version meets minimum requirement
			$required_version = '1.6.0';
			$current_version = POST_SMTP_PRO_VERSION;

			return version_compare( $current_version, $required_version, '>=' );
		}

		/**
		 * Get Pro version error message if any
		 * 
		 * @since 3.0.1
		 * @version 1.0.0
		 * @return string|null Error message or null if no error
		 */
		private function get_pro_version_error() {
			if ( ! defined( 'POST_SMTP_PRO_VERSION' ) ) {
				return null; // No Pro plugin, no error
			}

			$required_version = '1.6.0';
			$current_version = POST_SMTP_PRO_VERSION;

			if ( version_compare( $current_version, $required_version, '<' ) ) {
				return sprintf(
					__( 'Post SMTP Pro version %s detected. Version %s or higher is required for fallback support.', 'post-smtp' ),
					$current_version,
					$required_version
				);
			}

			return null; // No error
		}
	}

	new PostmanFallbackMigration();

endif;
