<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PostmanSettingsRegistry {

	private $options;

	public function __construct() {
		$this->options = PostmanOptions::getInstance();
	}

	/**
	 * Fires on the admin_init method
	 */
	public function on_admin_init() {

		$this->registerSettings();

	}

	/**
	 * Register and add settings
	 */
	private function registerSettings() {

		// only administrators should be able to trigger this
		if ( PostmanUtils::isAdmin() ) {
			$sanitizer = new PostmanInputSanitizer();
		
			register_setting( PostmanAdminController::SETTINGS_GROUP_NAME, PostmanOptions::POSTMAN_OPTIONS, array(
					$sanitizer,
					'sanitize',
			) );

			// Sanitize
			add_settings_section( 'transport_section', __( 'Transport', 'post-smtp' ), array(
					$this,
					'printTransportSectionInfo',
			), 'transport_options' );

			add_settings_field( PostmanOptions::TRANSPORT_TYPE, _x( 'Type', '(i.e.) What kind is it?', 'post-smtp' ), array(
                $this,
                'transport_type_callback',
            ), 'transport_options', 'transport_section' );

            add_settings_field( 'smtp_mailers', __( 'Mailer Type', 'post-smtp' ), array(
                $this,
                'smtp_mailer_callback',
            ), 'transport_options', 'transport_section'  );

			// Register the Manage Connections section.
			add_settings_section(
				'manage_connections_section',
				__( 'Manage Connections', 'post-smtp' ),
				array( $this, 'manage_connections_section_callback' ),
				'manage_connections'
			);

			// Register the Primary Connection field.
			add_settings_field(
				'primary_connection',
				__( 'Select Primary Connection', 'post-smtp' ),
				array( $this, 'primary_connection_callback' ),
				'manage_connections',
				'manage_connections_section'
			);

			// Register the Add New Connection button as a field.
			add_settings_field(
				'add_new_connection',
				'',
				array( $this, 'add_new_connection_callback' ),
				'manage_connections',
				'manage_connections_section'
			);
			
			// the Message From section
			add_settings_section( PostmanAdminController::MESSAGE_FROM_SECTION, _x( 'From Address', 'The Message Sender Email Address', 'post-smtp' ), array(
					$this,
					'printMessageFromSectionInfo',
			), PostmanAdminController::MESSAGE_FROM_OPTIONS );

			add_settings_field( PostmanOptions::MESSAGE_SENDER_EMAIL, __( 'Email Address', 'post-smtp' ), array(
					$this,
					'from_email_callback',
			), PostmanAdminController::MESSAGE_FROM_OPTIONS, PostmanAdminController::MESSAGE_FROM_SECTION, array( true ) );

			add_settings_field( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE, '', array(
					$this,
					'prevent_from_email_override_callback',
			), PostmanAdminController::MESSAGE_FROM_OPTIONS, PostmanAdminController::MESSAGE_FROM_SECTION );

			add_settings_field( PostmanOptions::MESSAGE_SENDER_NAME, __( 'Name', 'post-smtp' ), array(
					$this,
					'sender_name_callback',
			), PostmanAdminController::MESSAGE_FROM_OPTIONS, PostmanAdminController::MESSAGE_FROM_SECTION, array( true ) );

			add_settings_field( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE, '', array(
					$this,
					'prevent_from_name_override_callback',
			), PostmanAdminController::MESSAGE_FROM_OPTIONS, PostmanAdminController::MESSAGE_FROM_SECTION );

			// the Additional Addresses section
			add_settings_section( PostmanAdminController::MESSAGE_SECTION, __( 'Additional Email Addresses', 'post-smtp' ), array(
					$this,
					'printMessageSectionInfo',
			), PostmanAdminController::MESSAGE_OPTIONS );

			add_settings_field( PostmanOptions::REPLY_TO, __( 'Reply-To', 'post-smtp' ), array(
					$this,
					'reply_to_callback',
			), PostmanAdminController::MESSAGE_OPTIONS, PostmanAdminController::MESSAGE_SECTION );

			add_settings_field( PostmanOptions::FORCED_TO_RECIPIENTS, __( 'To Recipient(s)', 'post-smtp' ), array(
					$this,
					'to_callback',
			), PostmanAdminController::MESSAGE_OPTIONS, PostmanAdminController::MESSAGE_SECTION );

			add_settings_field( PostmanOptions::FORCED_CC_RECIPIENTS, __( 'Carbon Copy Recipient(s)', 'post-smtp' ), array(
					$this,
					'cc_callback',
			), PostmanAdminController::MESSAGE_OPTIONS, PostmanAdminController::MESSAGE_SECTION );

			add_settings_field( PostmanOptions::FORCED_BCC_RECIPIENTS, __( 'Blind Carbon Copy Recipient(s)', 'post-smtp' ), array(
					$this,
					'bcc_callback',
			), PostmanAdminController::MESSAGE_OPTIONS, PostmanAdminController::MESSAGE_SECTION );

			// the Additional Headers section
			add_settings_section( PostmanAdminController::MESSAGE_HEADERS_SECTION, __( 'Additional Headers', 'post-smtp' ), array(
					$this,
					'printAdditionalHeadersSectionInfo',
			), PostmanAdminController::MESSAGE_HEADERS_OPTIONS );

			add_settings_field( PostmanOptions::ADDITIONAL_HEADERS, __( 'Custom Headers', 'post-smtp' ), array(
					$this,
					'headers_callback',
			), PostmanAdminController::MESSAGE_HEADERS_OPTIONS, PostmanAdminController::MESSAGE_HEADERS_SECTION );

			// Fallback

			// the Email Validation section
			add_settings_section( PostmanAdminController::EMAIL_VALIDATION_SECTION, __( 'Validation', 'post-smtp' ), array(
					$this,
					'printEmailValidationSectionInfo',
			), PostmanAdminController::EMAIL_VALIDATION_OPTIONS );

			add_settings_field( PostmanOptions::ENVELOPE_SENDER, __( 'Email Address', 'post-smtp' ), array(
					$this,
					'disable_email_validation_callback',
			), PostmanAdminController::EMAIL_VALIDATION_OPTIONS, PostmanAdminController::EMAIL_VALIDATION_SECTION );

			// the Logging section
			add_settings_section( PostmanAdminController::LOGGING_SECTION, __( 'Email Log Settings', 'post-smtp' ), array(
					$this,
					'printLoggingSectionInfo',
			), PostmanAdminController::LOGGING_OPTIONS );

			add_settings_field( 'logging_status', __( 'Enable Logging', 'post-smtp' ), array(
					$this,
					'loggingStatusInputField',
			), PostmanAdminController::LOGGING_OPTIONS, PostmanAdminController::LOGGING_SECTION );

			add_settings_field( 'logging_max_entries', __( 'Maximum Log Entries', 'post-smtp' ), array(
					$this,
					'loggingMaxEntriesInputField',
			), PostmanAdminController::LOGGING_OPTIONS, PostmanAdminController::LOGGING_SECTION );

			add_settings_field( PostmanOptions::TRANSCRIPT_SIZE, __( 'Maximum Transcript Size', 'post-smtp' ), array(
					$this,
					'transcriptSizeInputField',
			), PostmanAdminController::LOGGING_OPTIONS, PostmanAdminController::LOGGING_SECTION );

			// the Network section
			add_settings_section( PostmanAdminController::NETWORK_SECTION, __( 'Network Settings', 'post-smtp' ), array(
					$this,
					'printNetworkSectionInfo',
			), PostmanAdminController::NETWORK_OPTIONS );

			add_settings_field( 'connection_timeout', _x( 'TCP Connection Timeout (sec)', 'Configuration Input Field', 'post-smtp' ), array(
					$this,
					'connection_timeout_callback',
			), PostmanAdminController::NETWORK_OPTIONS, PostmanAdminController::NETWORK_SECTION );

			add_settings_field( 'read_timeout', _x( 'TCP Read Timeout (sec)', 'Configuration Input Field', 'post-smtp' ), array(
					$this,
					'read_timeout_callback',
			), PostmanAdminController::NETWORK_OPTIONS, PostmanAdminController::NETWORK_SECTION );

			// the Advanced section
			add_settings_section( PostmanAdminController::ADVANCED_SECTION, _x( 'Miscellaneous Settings', 'Configuration Section Title', 'post-smtp' ), array(
					$this,
					'printAdvancedSectionInfo',
			), PostmanAdminController::ADVANCED_OPTIONS );

			add_settings_field( PostmanOptions::LOG_LEVEL, _x( 'PHP Log Level', 'Configuration Input Field', 'post-smtp' ), array(
					$this,
					'log_level_callback',
			), PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION );

			add_settings_field( PostmanOptions::RUN_MODE, _x( 'Delivery Mode', 'Configuration Input Field', 'post-smtp' ), array(
					$this,
					'runModeCallback',
			), PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION );

			add_settings_field( PostmanOptions::STEALTH_MODE, _x( 'Stealth Mode', 'This mode removes the Postman X-Mailer signature from emails', 'post-smtp' ), array(
					$this,
					'stealthModeCallback',
			), PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION );

			add_settings_field( PostmanOptions::TEMPORARY_DIRECTORY, __( 'Temporary Directory', 'post-smtp' ), array(
					$this,
					'temporaryDirectoryCallback',
			), PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION );

			add_settings_field( PostmanOptions::INCOMPATIBLE_PHP_VERSION, __( 'Broken Email Fix', 'post-smtp' ), array(
					$this,
					'incompatible_php_version_callback',
			), PostmanAdminController::ADVANCED_OPTIONS, PostmanAdminController::ADVANCED_SECTION );

            do_action( 'post_smtp_settings_fields' );
		}
	}

	/**
	 * Print the Transport section info
	 */
	public function printTransportSectionInfo() {
		print __( 'Choose SMTP or a vendor-specific API:', 'post-smtp' );
	}
	public function printLoggingSectionInfo() {
		print __( 'Configure the delivery audit log:', 'post-smtp' );
	}

	/**
	 * Print the Section text
	 */
	public function printMessageFromSectionInfo() {
		print sprintf( __( 'This address, like the <b>letterhead</b> printed on a letter, identifies the sender to the recipient. Change this when you are sending on behalf of someone else, for example to use Google\'s <a href="%s">Send Mail As</a> feature. Other plugins, especially Contact Forms, may override this field to be your visitor\'s address.', 'post-smtp' ), 'https://support.google.com/mail/answer/22370?hl=en' );
	}

	/**
	 * Print the Section text
	 */
	public function printMessageSectionInfo() {
		print __( 'Separate multiple <b>to</b>/<b>cc</b>/<b>bcc</b> recipients with commas.', 'post-smtp' );
	}

	/**
	 * Print the Section text
	 */
	public function printNetworkSectionInfo() {
		print __( 'Increase the timeouts if your host is intermittenly failing to send mail. Be careful, this also correlates to how long your user must wait if the mail server is unreachable.', 'post-smtp' );
	}

	/**
	 * Print the Section text
	 */
	public function printAdvancedSectionInfo() {
	}

	/**
	 * Print the Section text
	 */
	public function printNotificationsSectionInfo() {
	}

	/**
	 * Print the Section text
	 */
	public function printAdditionalHeadersSectionInfo() {
		print __( 'Specify custom headers (e.g. <code>X-MC-Tags: wordpress-site-A</code>), one per line. Use custom headers with caution as they can negatively affect your Spam score.', 'post-smtp' );
	}

	/**
	 * Print the Email Validation Description
	 */
	public function printEmailValidationSectionInfo() {
		print __( 'E-mail addresses can be validated before sending e-mail, however this may fail with some newer domains.', 'post-smtp' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function transport_type_callback() {
		$transportType = $this->options->getTransportType();
		printf( '<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::TRANSPORT_TYPE );
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			printf( '<option class="input_tx_type_%1$s" value="%1$s" %3$s>%2$s</option>', $transport->getSlug(), $transport->getName(), $transportType == $transport->getSlug() ? 'selected="selected"' : '' );
		}
		print '</select>';
	}

	/**
	 * Callback for the Manage Connections section.
	 */
	public function manage_connections_section_callback() {
		$wizard_uri = admin_url("admin.php?page=postman/configuration_wizard");
		echo '<div style="margin-top: -36px;margin-left: 200px;" > <a style="color: #2271B1; font-weight:bold; font-size:10px" href="' . esc_url( $wizard_uri ) . '">' . esc_html__( 'Add New Connection', 'post-smtp' ) . '</a></div>';
		echo '<p>' . esc_html__( 'You can select the primary connection and manage additional connections from here.', 'post-smtp' ) . '</p>';
	}

	/**
	 * Callback for the Primary Connection field.
	 */
	public function primary_connection_callback() {
		$connections = get_option( 'postman_connections', array() ); // Retrieve saved connections.
		$primary_connection = $this->options->getSelectedPrimary();

		echo '<select name="postman_options[primary_connection]" id="postman_primary_connection">';
		// Display the "None" option as the default selection.
		echo sprintf(
			'<option value="" %s>%s</option>',
			selected( $primary_connection, '', false ),
			esc_html__( 'None', 'post-smtp' )
		);

		// Check if there are any saved connections to display.
		if ( ! empty( $connections ) ) {
			foreach ( $connections as $key => $connection ) {
				$selected = selected( $primary_connection, $key, false );
				$email = isset( $connection['sender_email'] ) ? $connection['sender_email'] : '';
				echo sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $key ),
					$selected,
					esc_html(
						ucfirst(
							str_replace('_', ' ', __( $connection['provider'] . ' ( ' . $email . ' ) ', 'post-smtp' )) 
						)
					)
				);
			}
		}

		echo '</select>';

		// Display a message if no connections are available.
		if ( empty( $connections ) ) {
			$wizard_uri = admin_url( "admin.php?page=postman/configuration_wizard" );
			echo '<p class="description">' . esc_html__( 
				'You haven’t added any SMTP connection yet. Click ', 
				'post-smtp' 
			) . '<strong><a href="' . esc_url( $wizard_uri ) . '">' . esc_html__( 'Add New Connection', 'post-smtp' ) . '</a></strong>' . esc_html__( ' option to get started.', 'post-smtp' ) . '</p>';
		}else{
			echo '<p class="description">' . esc_html__( 
				'Selected connection will be used as the primary option for all your email deliveries.', 
				'post-smtp' 
			) . '</p>';
		}
	}


	/**
	 * Callback for the Add New Connection button.
	 */
	public function add_new_connection_callback() {
		$connections = get_option( 'postman_connections', array() ); // Retrieve saved connections.
		$wizard_uri = admin_url( "admin.php?page=postman/configuration_wizard" );
		$primary_connection = $this->options->getSelectedPrimary();
		$primary_fallback = $this->options->getSelectedFallback();	
		// Check if connections exist
		if ( !empty( $connections ) ) {
			// Start outputting the div for existing connections
			echo '<div style="background-color: white; padding: 20px; border-radius: 5px; margin-top: 20px;position: relative;left: -220px;">';
			echo '<h2>' . esc_html__( 'All Connections', 'post-smtp' ) . '</h2>';
	
			// Start the table
			echo '<table class="widefat striped">';
			echo '<thead>';
			echo '</thead>';
			echo '<tbody>';
	
			// Loop through the connections to populate the table
			foreach ( $connections as $key => $connection ) {
				$sender_email = esc_html( $connection['sender_email'] ?? '' ); // Use email from connection or an empty string
				$provider_title = ucfirst(
					str_replace(
						'_', 
						' ', 
						__( str_replace('api', 'API', $connection['provider']), 'post-smtp' )
					)
				);
				$status = ( $key == $primary_connection ) ? 'Primary' : ( ( $key == $primary_fallback ) ? 'Fallback' : 'None' );	
			
				echo '<tr>';
				echo '<td>';
				echo '<strong>' . esc_html( $provider_title ) . '</strong><br>'; // Bold main text
				echo '<small>' . esc_html__( 'Selected as:', 'post-smtp' ) . ' ' . esc_html( $status ) . '</small>';
				echo '</td>';
				echo '<td>' . $sender_email . '</td>';
				echo '<td>
					<a href="' . esc_url( $wizard_uri ) . '&id=' . esc_attr( $key ) . '" class="button postman-add-connection-btn" id="add_new_connection">
						<img src="' . esc_url( plugin_dir_url( dirname( __FILE__, 2 ) ) . 'Postman/Dashboard/assets/new.svg' ) . '" 
							alt="' . esc_attr__( 'Edit With Wizard', 'post-smtp' ) . '" 
							style="vertical-align: middle; margin-right: 5px;" /> 
						' . esc_html__( 'Edit With Wizard', 'post-smtp' ) . '
					</a>
				</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
			?>
			<div style="padding-top: 20px;"> 
				<a href="<?php echo esc_url( $wizard_uri); ?>" class="button postman-add-connection-btn" id="add_new_connection">
					<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__, 2 ) ) . 'Postman/Dashboard/assets/new.svg' ); ?>" 
						alt="<?php esc_attr_e( 'New', 'post-smtp' ); ?>" />
					<?php esc_html_e( 'Add New Connection', 'post-smtp' ); ?>
				</a>
			</div>
			<?php
			echo '</div>'; 
		}
	}
	
    /**
     * Get the settings option array and print one of its values
     */
    public function smtp_mailer_callback() {
        $smtp_mailers = PostmanOptions::SMTP_MAILERS;
        $current_smtp_mailer = $this->options->getSmtpMailer();
        printf( '<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, 'smtp_mailers' );
        foreach ( $smtp_mailers as $key => $smtp_mailer ) {
            printf( '<option class="input_tx_type_%1$s" value="%1$s" %3$s>%2$s</option>', $key, $smtp_mailer, $current_smtp_mailer == $key ? 'selected="selected"' : '' );
        }
        print '</select>';
        ?>
        <p class="description" id="mailer-type-description"><?php _e( 'Beta Feature: ONLY change this to <strong>PHPMailer</strong> only if you see <code>wp_mail</code> conflict message, conflicts when another plugin is activated, and <strong><u>sometimes</u></strong> your mail marked as spam.', 'post-smtp' ); ?></p>
        <?php
    }

	/**
	 * Get the settings option array and print one of its values
	 */
	public function sender_name_callback( $_echo = true ) {

		if( $_echo ) {

			printf( '<input type="text" id="input_sender_name" class="ps-input ps-w-75" name="postman_options[sender_name]" value="%s" size="40" />', null !== $this->options->getMessageSenderName() ? esc_attr( $this->options->getMessageSenderName() ) : '' );

		}
		else {

			return sprintf( '<input type="text" id="input_sender_name" class="ps-input ps-w-75" name="postman_options[sender_name]" value="%s" size="40" />', null !== $this->options->getMessageSenderName() ? esc_attr( $this->options->getMessageSenderName() ) : '' );
			
		}

	}

	/**
	 */
	public function prevent_from_name_override_callback() {
		$enforced = $this->options->isPluginSenderNameEnforced();
		printf( '<input type="checkbox" id="input_prevent_sender_name_override" name="postman_options[prevent_sender_name_override]" %s /> %s', $enforced ? 'checked="checked"' : '', __( 'Prevent <b>plugins</b> and <b>themes</b> from changing this', 'post-smtp' ) );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function from_email_callback( $_echo = true ) {

		if( $_echo ) {

			printf( '<input type="email" id="input_sender_email" class="ps-input ps-w-75" name="postman_options[sender_email]" value="%s" size="40" class="required" placeholder="%s"/>', null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '', __( 'Required', 'post-smtp' ) );
	
		}
		else {

			return sprintf( '<input type="email" id="input_sender_email" class="ps-input ps-w-75" name="postman_options[sender_email]" value="%s" size="40" class="required" placeholder="%s"/>', null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '', __( 'Required', 'post-smtp' ) );

		}

	}

	/**
	 * Print the Section text
	 */
	public function printMessageSenderSectionInfo() {
		print sprintf( __( 'This address, like the <b>return address</b> printed on an envelope, identifies the account owner to the SMTP server.', 'post-smtp' ), 'https://support.google.com/mail/answer/22370?hl=en' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function prevent_from_email_override_callback() {
		$enforced = $this->options->isPluginSenderEmailEnforced();
		printf( '<input type="checkbox" id="input_prevent_sender_email_override" name="postman_options[prevent_sender_email_override]" %s /> %s', $enforced ? 'checked="checked"' : '', __( 'Prevent <b>plugins</b> and <b>themes</b> from changing this', 'post-smtp' ) );
	}

	/**
	 * Shows the Mail Logging enable/disabled option
	 */
	public function loggingStatusInputField() {
		// isMailLoggingAllowed
		$disabled = '';
		if ( ! $this->options->isMailLoggingAllowed() ) {
			$disabled = 'disabled="disabled" ';
		}
		printf( '<select ' . $disabled . 'id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::MAIL_LOG_ENABLED_OPTION );
		printf( '<option value="%s" %s>%s</option>', PostmanOptions::MAIL_LOG_ENABLED_OPTION_YES, $this->options->isMailLoggingEnabled() ? 'selected="selected"' : '', __( 'Yes', 'post-smtp' ) );
		printf( '<option value="%s" %s>%s</option>', PostmanOptions::MAIL_LOG_ENABLED_OPTION_NO, ! $this->options->isMailLoggingEnabled() ? 'selected="selected"' : '', __( 'No', 'post-smtp' ) );
		printf( '</select>' );
	}
	public function loggingMaxEntriesInputField() {
		printf( '<input type="text" id="input_logging_max_entries" name="postman_options[%s]" value="%s"/>', PostmanOptions::MAIL_LOG_MAX_ENTRIES, $this->options->getMailLoggingMaxEntries() );
	}
	public function transcriptSizeInputField() {
		$inputOptionsSlug = PostmanOptions::POSTMAN_OPTIONS;
		$inputTranscriptSlug = PostmanOptions::TRANSCRIPT_SIZE;
		$inputValue = $this->options->getTranscriptSize();
		$inputDescription = __( 'Change this value if you can\'t see the beginning of the transcript because your messages are too big.', 'post-smtp' );
		printf( '<input type="text" id="input%2$s" name="%1$s[%2$s]" value="%3$s"/><br/><span class="postman_input_description">%4$s</span>', $inputOptionsSlug, $inputTranscriptSlug, $inputValue, $inputDescription );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function reply_to_callback() {
		printf( '<input type="text" id="input_reply_to" name="%s[%s]" value="%s" size="40" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::REPLY_TO, null !== $this->options->getReplyTo() ? esc_attr( $this->options->getReplyTo() ) : '' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function to_callback() {
		printf( '<input type="text" id="input_to" name="%s[%s]" value="%s" size="60" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_TO_RECIPIENTS, null !== $this->options->getForcedToRecipients() ? esc_attr( $this->options->getForcedToRecipients() ) : '' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function cc_callback() {
		printf( '<input type="text" id="input_cc" name="%s[%s]" value="%s" size="60" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_CC_RECIPIENTS, null !== $this->options->getForcedCcRecipients() ? esc_attr( $this->options->getForcedCcRecipients() ) : '' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function bcc_callback() {
		printf( '<input type="text" id="input_bcc" name="%s[%s]" value="%s" size="60" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_BCC_RECIPIENTS, null !== $this->options->getForcedBccRecipients() ? esc_attr( $this->options->getForcedBccRecipients() ) : '' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function headers_callback() {
		printf( '<textarea id="input_headers" name="%s[%s]" cols="60" rows="5" >%s</textarea>', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::ADDITIONAL_HEADERS, null !== $this->options->getAdditionalHeaders() ? esc_attr( $this->options->getAdditionalHeaders() ) : '' );
	}

	/**
	 */
	public function disable_email_validation_callback() {
		$disabled = $this->options->isEmailValidationDisabled();
		printf( '<input type="checkbox" id="%2$s" name="%1$s[%2$s]" %3$s /> %4$s', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::DISABLE_EMAIL_VALIDAITON, $disabled ? 'checked="checked"' : '', __( 'Disable e-mail validation', 'post-smtp' ) );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function log_level_callback() {
		$inputDescription = sprintf( __( 'Log Level specifies the level of detail written to the <a target="_blank" href="%s">WordPress Debug log</a> - view the log with <a target-"_new" href="%s">Debug</a>.', 'post-smtp' ), 'https://codex.wordpress.org/Debugging_in_WordPress', 'https://wordpress.org/plugins/debug/' );
		printf( '<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::LOG_LEVEL );
		$currentKey = $this->options->getLogLevel();
		$this->printSelectOption( __( 'Off', 'post-smtp' ), PostmanLogger::OFF_INT, $currentKey );
		$this->printSelectOption( __( 'Trace', 'post-smtp' ), PostmanLogger::TRACE_INT, $currentKey );
		$this->printSelectOption( __( 'Debug', 'post-smtp' ), PostmanLogger::DEBUG_INT, $currentKey );
		$this->printSelectOption( __( 'Info', 'post-smtp' ), PostmanLogger::INFO_INT, $currentKey );
		$this->printSelectOption( __( 'Warning', 'post-smtp' ), PostmanLogger::WARN_INT, $currentKey );
		$this->printSelectOption( __( 'Error', 'post-smtp' ), PostmanLogger::ERROR_INT, $currentKey );
		printf( '</select><br/><span class="postman_input_description">%s</span>', $inputDescription );
	}

	private function printSelectOption( $label, $optionKey, $currentKey ) {
		$optionPattern = '<option value="%1$s" %2$s>%3$s</option>';
		printf( $optionPattern, $optionKey, $optionKey == $currentKey ? 'selected="selected"' : '', $label );
	}
	public function runModeCallback() {
		$inputDescription = __( 'Delivery mode offers options useful for developing or testing.', 'post-smtp' );
		printf( '<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::RUN_MODE );
		$currentKey = $this->options->getRunMode();
		$this->printSelectOption( _x( 'Log Email and Send', 'When the server is online to the public, this is "Production" mode', 'post-smtp' ), PostmanOptions::RUN_MODE_PRODUCTION, $currentKey );
		$this->printSelectOption( __( 'Log only', 'post-smtp' ), PostmanOptions::RUN_MODE_LOG_ONLY, $currentKey );
		$this->printSelectOption( __( 'No Action', 'post-smtp' ), PostmanOptions::RUN_MODE_IGNORE, $currentKey );
		printf( '</select><br/><span class="postman_input_description">%s</span>', $inputDescription );
	}

	public function stealthModeCallback() {
		printf( '<input type="checkbox" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s /> %4$s', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::STEALTH_MODE, $this->options->isStealthModeEnabled() ? 'checked="checked"' : '', __( 'Remove the Postman X-Header signature from messages', 'post-smtp' ) );
	}

	public function temporaryDirectoryCallback() {
		$inputDescription = __( 'Lockfiles are written here to prevent users from triggering an OAuth 2.0 token refresh at the same time.' );
		printf( 
			'<input type="text" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', 
			PostmanOptions::POSTMAN_OPTIONS, 
			PostmanOptions::TEMPORARY_DIRECTORY, 
			esc_attr( $this->options->getTempDirectory() ) 
		);

		if ( PostmanState::getInstance()->isFileLockingEnabled() ) {
			printf( ' <span style="color:green">%s</span></br><span class="postman_input_description">%s</span>', __( 'Valid', 'post-smtp' ), $inputDescription );
		} else {
			printf( ' <span style="color:red">%s</span></br><span class="postman_input_description">%s</span>', __( 'Invalid', 'post-smtp' ), $inputDescription );
		}
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function connection_timeout_callback() {
		printf( '<input type="text" id="input_connection_timeout" name="%s[%s]" value="%s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::CONNECTION_TIMEOUT, $this->options->getConnectionTimeout() );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function read_timeout_callback() {
		printf( '<input type="text" id="input_read_timeout" name="%s[%s]" value="%s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::READ_TIMEOUT, $this->options->getReadTimeout() );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function port_callback( $args ) {
		printf( '<input type="text" id="input_port" name="postman_options[port]" value="%s" %s placeholder="%s"/>', null !== $this->options->getPort() ? esc_attr( $this->options->getPort() ) : '', isset( $args ['style'] ) ? $args ['style'] : '', __( 'Required', 'post-smtp' ) );
	}


	/**
	 * Incompatible PHP Version Callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function incompatible_php_version_callback() {

		printf( '<input type="checkbox" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s /> %4$s', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::INCOMPATIBLE_PHP_VERSION, $this->options->is_php_compatibility_enabled() ? 'checked="checked"' : '', __( 'Only enable this option, if the email\'s header or body seems broken.', 'post-smtp' ) );

	}
}
