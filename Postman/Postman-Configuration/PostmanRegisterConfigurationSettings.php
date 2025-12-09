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
		esc_html_e( 'Choose SMTP or a vendor-specific API:', 'post-smtp' );
	}
	public function printLoggingSectionInfo() {
		esc_html_e( 'Configure the delivery audit log:', 'post-smtp' );
	}

	/**
	 * Print the Section text
	 */
	public function printMessageFromSectionInfo() {
		print wp_kses_post(
			sprintf(
				__( 'This address, like the <b>letterhead</b> printed on a letter, identifies the sender to the recipient. Change this when you are sending on behalf of someone else, for example to use Google\'s <a href="%s">Send Mail As</a> feature. Other plugins, especially Contact Forms, may override this field to be your visitor\'s address.', 'post-smtp' ),
				'https://support.google.com/mail/answer/22370?hl=en'
			)
		);
	}

	/**
	 * Print the Section text
	 */
	public function printMessageSectionInfo() {
		print wp_kses_post(
			__( 'Separate multiple <b>to</b>/<b>cc</b>/<b>bcc</b> recipients with commas.', 'post-smtp' )
		);
	}

	/**
	 * Print the Section text
	 */
	public function printNetworkSectionInfo() {
		esc_html_e( 'Increase the timeouts if your host is intermittenly failing to send mail. Be careful, this also correlates to how long your user must wait if the mail server is unreachable.', 'post-smtp' );
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
		print wp_kses_post(
			__( 'Specify custom headers (e.g. <code>X-MC-Tags: wordpress-site-A</code>), one per line. Use custom headers with caution as they can negatively affect your Spam score.', 'post-smtp' )
		);
	}

	/**
	 * Print the Email Validation Description
	 */
	public function printEmailValidationSectionInfo() {
		esc_html_e( 'E-mail addresses can be validated before sending e-mail, however this may fail with some newer domains.', 'post-smtp' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function transport_type_callback() {
		$transportType = $this->options->getTransportType();
		printf(
			'<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::TRANSPORT_TYPE )
		);
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			printf(
				'<option class="input_tx_type_%1$s" value="%1$s" %3$s>%2$s</option>',
				esc_attr( $transport->getSlug() ),
				esc_html( $transport->getName() ),
				selected( $transportType, $transport->getSlug(), false )
			);
		}
		print '</select>';
	}

    /**
     * Get the settings option array and print one of its values
     */
    public function smtp_mailer_callback() {
        $smtp_mailers = PostmanOptions::SMTP_MAILERS;
        $current_smtp_mailer = $this->options->getSmtpMailer();
        printf(
			'<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( 'smtp_mailers' )
		);
        foreach ( $smtp_mailers as $key => $smtp_mailer ) {
            printf(
				'<option class="input_tx_type_%1$s" value="%1$s" %3$s>%2$s</option>',
				esc_attr( $key ),
				esc_html( $smtp_mailer ),
				selected( $current_smtp_mailer, $key, false )
			);
        }
        print '</select>';
        ?>
        <p class="description" id="mailer-type-description">
			<?php
			echo wp_kses_post(
				__( 'Beta Feature: ONLY change this to <strong>PHPMailer</strong> only if you see <code>wp_mail</code> conflict message, conflicts when another plugin is activated, and <strong><u>sometimes</u></strong> your mail marked as spam.', 'post-smtp' )
			);
			?>
		</p>
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
		printf(
			'<input type="checkbox" id="input_prevent_sender_name_override" name="postman_options[prevent_sender_name_override]" %s /> %s',
			checked( $enforced, true, false ),
			wp_kses_post(
				__( 'Prevent <b>plugins</b> and <b>themes</b> from changing this', 'post-smtp' )
			)
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function from_email_callback( $_echo = true ) {

		if( $_echo ) {

			printf(
				'<input type="email" id="input_sender_email" class="ps-input ps-w-75" name="postman_options[sender_email]" value="%s" size="40" class="required" placeholder="%s"/>',
				null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '',
				esc_attr__( 'Required', 'post-smtp' )
			);
	
		}
		else {

			return sprintf(
				'<input type="email" id="input_sender_email" class="ps-input ps-w-75" name="postman_options[sender_email]" value="%s" size="40" class="required" placeholder="%s"/>',
				null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '',
				esc_attr__( 'Required', 'post-smtp' )
			);

		}

	}

	/**
	 * Print the Section text
	 */
	public function printMessageSenderSectionInfo() {
		print wp_kses_post(
			__( 'This address, like the <b>return address</b> printed on an envelope, identifies the account owner to the SMTP server.', 'post-smtp' )
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function prevent_from_email_override_callback() {
		$enforced = $this->options->isPluginSenderEmailEnforced();
		printf(
			'<input type="checkbox" id="input_prevent_sender_email_override" name="postman_options[prevent_sender_email_override]" %s /> %s',
			checked( $enforced, true, false ),
			wp_kses_post(
				__( 'Prevent <b>plugins</b> and <b>themes</b> from changing this', 'post-smtp' )
			)
		);
	}

	/**
	 * Shows the Mail Logging enable/disabled option
	 */
	public function loggingStatusInputField() {
		// isMailLoggingAllowed
		printf(
			'<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s>',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::MAIL_LOG_ENABLED_OPTION ),
			disabled( ! $this->options->isMailLoggingAllowed(), true, false )
		);
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( PostmanOptions::MAIL_LOG_ENABLED_OPTION_YES ),
			selected( $this->options->isMailLoggingEnabled(), true, false ),
			esc_html__( 'Yes', 'post-smtp' )
		);
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( PostmanOptions::MAIL_LOG_ENABLED_OPTION_NO ),
			selected( ! $this->options->isMailLoggingEnabled(), true, false ),
			esc_html__( 'No', 'post-smtp' )
		);
		printf( '</select>' );
	}
	public function loggingMaxEntriesInputField() {
		printf(
			'<input type="text" id="input_logging_max_entries" name="postman_options[%s]" value="%s"/>',
			esc_attr( PostmanOptions::MAIL_LOG_MAX_ENTRIES ),
			esc_attr( $this->options->getMailLoggingMaxEntries() )
		);
	}
	public function transcriptSizeInputField() {
		$inputOptionsSlug    = PostmanOptions::POSTMAN_OPTIONS;
		$inputTranscriptSlug = PostmanOptions::TRANSCRIPT_SIZE;
		$inputValue          = $this->options->getTranscriptSize();
		$inputDescription    = esc_html__( 'Change this value if you can\'t see the beginning of the transcript because your messages are too big.', 'post-smtp' );
		printf(
			'<input type="text" id="input%2$s" name="%1$s[%2$s]" value="%3$s"/><br/><span class="postman_input_description">%4$s</span>',
			esc_attr( $inputOptionsSlug ),
			esc_attr( $inputTranscriptSlug ),
			esc_attr( $inputValue ),
			$inputDescription
		);
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
		$inputDescription = sprintf(
			/* translators: 1: link to WP debugging docs, 2: link to debug plugin. */
			__( 'Log Level specifies the level of detail written to the <a target="_blank" href="%s">WordPress Debug log</a> - view the log with <a target-"_new" href="%s">Debug</a>.', 'post-smtp' ),
			'https://codex.wordpress.org/Debugging_in_WordPress',
			'https://wordpress.org/plugins/debug/'
		);
		printf(
			'<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::LOG_LEVEL )
		);
		$currentKey = $this->options->getLogLevel();
		$this->printSelectOption( esc_html__( 'Off', 'post-smtp' ), PostmanLogger::OFF_INT, $currentKey );
		$this->printSelectOption( esc_html__( 'Trace', 'post-smtp' ), PostmanLogger::TRACE_INT, $currentKey );
		$this->printSelectOption( esc_html__( 'Debug', 'post-smtp' ), PostmanLogger::DEBUG_INT, $currentKey );
		$this->printSelectOption( esc_html__( 'Info', 'post-smtp' ), PostmanLogger::INFO_INT, $currentKey );
		$this->printSelectOption( esc_html__( 'Warning', 'post-smtp' ), PostmanLogger::WARN_INT, $currentKey );
		$this->printSelectOption( esc_html__( 'Error', 'post-smtp' ), PostmanLogger::ERROR_INT, $currentKey );
		printf(
			'</select><br/><span class="postman_input_description">%s</span>',
			wp_kses_post( $inputDescription )
		);
	}

	private function printSelectOption( $label, $optionKey, $currentKey ) {
		$optionPattern = '<option value="%1$s" %2$s>%3$s</option>';
		printf(
			$optionPattern,
			esc_attr( $optionKey ),
			selected( $optionKey, $currentKey, false ),
			esc_html( $label )
		);
	}
	public function runModeCallback() {
		$inputDescription = esc_html__( 'Delivery mode offers options useful for developing or testing.', 'post-smtp' );
		printf(
			'<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::RUN_MODE )
		);
		$currentKey = $this->options->getRunMode();
		$this->printSelectOption( esc_html_x( 'Log Email and Send', 'When the server is online to the public, this is "Production" mode', 'post-smtp' ), PostmanOptions::RUN_MODE_PRODUCTION, $currentKey );
		$this->printSelectOption( esc_html__( 'Log only', 'post-smtp' ), PostmanOptions::RUN_MODE_LOG_ONLY, $currentKey );
		$this->printSelectOption( esc_html__( 'No Action', 'post-smtp' ), PostmanOptions::RUN_MODE_IGNORE, $currentKey );
		printf( '</select><br/><span class="postman_input_description">%s</span>', $inputDescription );
	}

	public function stealthModeCallback() {
		printf(
			'<input type="checkbox" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s /> %4$s',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::STEALTH_MODE ),
			checked( $this->options->isStealthModeEnabled(), true, false ),
			esc_html__( 'Remove the Postman X-Header signature from messages', 'post-smtp' )
		);
	}

	public function temporaryDirectoryCallback() {
		$inputDescription = esc_html__( 'Lockfiles are written here to prevent users from triggering an OAuth 2.0 token refresh at the same time.', 'post-smtp' );
		printf( 
			'<input type="text" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', 
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ), 
			esc_attr( PostmanOptions::TEMPORARY_DIRECTORY ), 
			esc_attr( $this->options->getTempDirectory() ) 
		);

		if ( PostmanState::getInstance()->isFileLockingEnabled() ) {
			printf(
				' <span style="color:green">%s</span></br><span class="postman_input_description">%s</span>',
				esc_html__( 'Valid', 'post-smtp' ),
				$inputDescription
			);
		} else {
			printf(
				' <span style="color:red">%s</span></br><span class="postman_input_description">%s</span>',
				esc_html__( 'Invalid', 'post-smtp' ),
				$inputDescription
			);
		}
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function connection_timeout_callback() {
		printf(
			'<input type="text" id="input_connection_timeout" name="%s[%s]" value="%s" />',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::CONNECTION_TIMEOUT ),
			esc_attr( $this->options->getConnectionTimeout() )
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function read_timeout_callback() {
		printf(
			'<input type="text" id="input_read_timeout" name="%s[%s]" value="%s" />',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::READ_TIMEOUT ),
			esc_attr( $this->options->getReadTimeout() )
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function port_callback( $args ) {
		$style_attr = '';
		if ( isset( $args['style'] ) && is_string( $args['style'] ) ) {
			$style_value = trim( preg_replace( '/^style=/i', '', $args['style'] ) );
			if ( '' !== $style_value ) {
				$style_attr = 'style="' . esc_attr( $style_value ) . '"';
			}
		}

		printf(
			'<input type="text" id="input_port" name="postman_options[port]" value="%s" %s placeholder="%s"/>',
			null !== $this->options->getPort() ? esc_attr( $this->options->getPort() ) : '',
			$style_attr,
			esc_attr__( 'Required', 'post-smtp' )
		);
	}


	/**
	 * Incompatible PHP Version Callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function incompatible_php_version_callback() {

		printf(
			'<input type="checkbox" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s /> %4$s',
			esc_attr( PostmanOptions::POSTMAN_OPTIONS ),
			esc_attr( PostmanOptions::INCOMPATIBLE_PHP_VERSION ),
			checked( $this->options->is_php_compatibility_enabled(), true, false ),
			esc_html__( 'Only enable this option, if the email\'s header or body seems broken.', 'post-smtp' )
		);

	}
}
