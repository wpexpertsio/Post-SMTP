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
		 * Existing database version.
		 *
		 * @var string
		 */
		private $existing_db_version = '';

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

			$this->existing_db_version = get_option( 'postman_db_version' );
			$hide_notice               = get_transient( 'ps_dismiss_fallback_update_notice' );
			$deleted_email_settings    = get_transient( 'deleted_email_settings' );
			// Show DB Update Notice.
			if ( $this->has_migrated() && ( POST_SMTP_DB_VERSION !== $this->existing_db_version || false !== $deleted_email_settings ) ) {
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
			$deleted_email_settings = get_transient( 'deleted_email_settings' );

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
			<?php if ( $this->existing_db_version != POST_SMTP_DB_VERSION && $deleted_email_settings === false ) : ?>
				<p><?php echo _e( 'Additional Socket support will be now available in our fallback module.', 'post-smtp' ); ?></p>
				<form method="post" class="fallback_migration_form" style="" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ps_fallback_update_action">
					<input type="hidden" name="security" value="<?php echo esc_attr( $security ); ?>">
					<button type="submit" class="button button-primary">Update for Fallback Support</button>
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

			// Gather mail settings and save connections if security passes.
			$this->save_mail_connections();

			// Update the Postman DB version if necessary.
			$this->update_db_version();

			// Save the Postman Details In Transient.
			$this->store_email_settings_in_transient();

			// Redirect to the same page or any other page with a success message.
			wp_redirect( admin_url( 'admin.php?page=postman&fallback_update=success' ) );

			exit;
		}

		/**
		 * Gathers mail settings from 'postman_options', saves them in 'postman_connections',
		 * placing the current transport type options at index 0 and fallback settings at index 1 if enabled.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function save_mail_connections() {
			$postman_options = get_option( 'postman_options', array() );
			$current_transport_type = $postman_options['transport_type'] ?? 'default';
			
			$mail_connections = array();

			// Sender details - only for primary
			$sender_details = array(
				'sender_email'    => sanitize_email( $postman_options['sender_email'] ?? '' ),
				'envelope_sender' => sanitize_email( $postman_options['envelope_sender'] ?? '' ),
				'sender_name'     => sanitize_text_field( $postman_options['sender_name'] ?? '' ),
			);

			// All API providers data
			$api_keys = $this->get_api_keys( $postman_options );	
			
				// Sensitive keys to decode before saving
			$sensitive_keys = array(
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
				'mailjet_secret_key'
			);
			
			// Decode sensitive keys if present
			foreach ( $api_keys as $provider => &$connection ) {
				foreach ( $sensitive_keys as $key ) {
					if ( isset( $connection[ $key ] ) && ! empty( $connection[ $key ] ) ) {
						$connection[ $key ] = base64_decode( $connection[ $key ] );
					}
				}
			}
			
			// Merge Gmail tokens if primary connection is gmail_api
			$auth_tokens = get_option( 'postman_auth_token', array() );
			if ( $current_transport_type === 'gmail_api' && isset( $api_keys['gmail_api'] ) && !empty( $auth_tokens ) ) {
				$api_keys['gmail_api'] = array_merge( $api_keys['gmail_api'], $auth_tokens );
			}
			
			if ( isset( $api_keys[ $current_transport_type ] ) ) {
				$postman_options['primary_connection'] = 0;
				$mail_connections[0] = array_merge(
					$api_keys[ $current_transport_type ],
					array_filter( $sender_details )
				);
			}
			
			$connection_index = 1;
				// Optional fallback SMTP
			$fallback_enabled = $postman_options['fallback_smtp_enabled'] ?? 'no';
			$fallback_hostname = $postman_options['fallback_smtp_hostname'] ?? '';
			
			// Check if fallback SMTP is enabled.
			if ( $fallback_enabled === 'yes' && ! empty( $fallback_hostname ) ) {
				// Gather all fallback-related settings except 'fallback_smtp_enabled'.
				$mail_connections[ $connection_index ] = array(
					'enc_type'            => sanitize_text_field( $postman_options['fallback_smtp_security'] ?? '' ),
					'hostname'            => sanitize_text_field( $postman_options['fallback_smtp_hostname'] ?? '' ),
					'port'                => sanitize_text_field( $postman_options['fallback_smtp_port'] ?? '' ),
					'auth_type'           => sanitize_text_field( $postman_options['fallback_smtp_use_auth'] ?? '' ),
					'sender_name'         => sanitize_text_field( $postman_options['sender_name'] ?? '' ),
					'sender_email'        => sanitize_email( $postman_options['fallback_from_email'] ?? '' ),
					'envelope_sender'     => sanitize_text_field( $postman_options['fallback_from_email'] ?? '' ),
					'basic_auth_username' => sanitize_text_field( $postman_options['fallback_smtp_username'] ?? '' ),
					'basic_auth_password' => sanitize_text_field( $postman_options['fallback_smtp_password'] ?? '' ),
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
			
			// Save the new mail connections to the 'postman_connections' option.
			update_option( 'postman_connections', $mail_connections );
			update_option( 'postman_options', $postman_options );
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
				'mailersend_api'   => array( 'mailersend_api_key'),
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
				update_option( 'postman_db_version', '1.0.2' );
			}
		}

		/**
		 * Deletes email settings keys from postman_options and stores them in transients for 2 days.
		 *
		 * @since 3.0.1
		 * @version 1.0.0
		 */
		private function store_email_settings_in_transient() {
			// Get the 'postman_options' array from the database.
			$postman_options = get_option( 'postman_options', array() );

			// Define the sensitive keys that need to be decoded.
			$sensitive_keys = array(
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
				'mailjet_secret_key'
			);

			// Decrypt only the sensitive keys before storing in the transient.
			foreach ( $sensitive_keys as $key ) {
				if ( isset( $postman_options[ $key ] ) ) {
					$postman_options[ $key ] = $this->decrypt( $postman_options[ $key ] );
				}
			}
			// Store the deleted email settings in a transient for 2 days (172800 seconds).
			set_transient( 'deleted_email_settings', $postman_options, 172800 ); // 2 days.
			
			// Get the 'postman_connections' array and retrieve the primary connection.
			$mail_connections   = get_option( 'postman_connections', array() );
			$primary_connection = isset( $mail_connections[0] ) ? $mail_connections[0] : array();
			
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
				'oauth_client_id', 'oauth_client_secret',
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
			$sensitive_keys = array(
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
				'mailjet_secret_key'
			);

			$deleted_email_settings = get_transient( 'deleted_email_settings' );

			if ( false !== $deleted_email_settings ) {
				// Save the restored settings
				update_option( 'postman_options', $deleted_email_settings );

				// Clear fallback connections (if needed)
				delete_option( 'postman_connections' );
				delete_transient( 'deleted_email_settings' );
				update_option( 'postman_db_version', '1.0.1' );

				// Redirect with success notice
				wp_safe_redirect( admin_url( 'admin.php?page=postman&settings_restored=1' ) );
				exit;
			}

		}
		
		private function decrypt( $value ) {
			$decode = base64_decode( $value );
			return base64_decode( $decode );
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
	}

	new PostmanFallbackMigration();

endif;
