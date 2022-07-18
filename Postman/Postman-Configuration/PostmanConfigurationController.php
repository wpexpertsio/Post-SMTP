<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once( 'PostmanRegisterConfigurationSettings.php' );
class PostmanConfigurationController {
	const CONFIGURATION_SLUG = 'postman/configuration';
	const CONFIGURATION_WIZARD_SLUG = 'postman/configuration_wizard';

	// logging
	private $logger;
	private $options;
	private $settingsRegistry;

	// Holds the values to be used in the fields callbacks
	private $rootPluginFilenameAndPath;

	private $importableConfiguration;

	/**
	 * Constructor
	 *
	 * @param mixed $rootPluginFilenameAndPath
	 */
	public function __construct( $rootPluginFilenameAndPath ) {
		assert( ! empty( $rootPluginFilenameAndPath ) );
		assert( PostmanUtils::isAdmin() );
		assert( is_admin() );

		$this->logger = new PostmanLogger( get_class( $this ) );
		$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
		$this->options = PostmanOptions::getInstance();
		$this->settingsRegistry = new PostmanSettingsRegistry();

		PostmanUtils::registerAdminMenu( $this, 'addConfigurationSubmenu' );
		PostmanUtils::registerAdminMenu( $this, 'addSetupWizardSubmenu' );

		// hook on the init event
		add_action( 'init', array(
				$this,
				'on_init',
		) );

		// initialize the scripts, stylesheets and form fields
		add_action( 'admin_init', array(
				$this,
				'on_admin_init',
		) );
	}

	/**
	 * Functions to execute on the init event
	 *
	 * "Typically used by plugins to initialize. The current user is already authenticated by this time."
	 * ref: http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request
	 */
	public function on_init() {
		// register Ajax handlers
		new PostmanGetHostnameByEmailAjaxController();
		new PostmanManageConfigurationAjaxHandler();
		new PostmanImportConfigurationAjaxController( $this->options );
	}

	/**
	 * Fires on the admin_init method
	 */
	public function on_admin_init() {
				$this->registerStylesAndScripts();
		$this->settingsRegistry->on_admin_init();
	}

	/**
	 * Register and add settings
	 */
	private function registerStylesAndScripts() {
		if ( $this->logger->isTrace() ) {
			$this->logger->trace( 'registerStylesAndScripts()' );
		}
		// register the stylesheet and javascript external resources
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );

		wp_register_script( 'postman_manual_config_script', plugins_url( 'Postman/Postman-Configuration/postman_manual_config.js', $this->rootPluginFilenameAndPath ), array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery-ui-core',
				'jquery-ui-tabs',
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT,
		), $pluginData ['version'] );

		wp_register_script( 'postman_wizard_script', plugins_url( 'Postman/Postman-Configuration/postman_wizard.js', $this->rootPluginFilenameAndPath ), array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				'jquery_steps_script',
				PostmanViewController::POSTMAN_SCRIPT,
				'sprintf',
		), $pluginData ['version'] );
	}

	/**
	 */
	private function addLocalizeScriptsToPage() {

		// the transport modules scripts
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			$transport->enqueueScript();
		}

		// we need data from port test
		PostmanConnectivityTestController::addLocalizeScriptForPortTest();

	}

	/**
	 * Register the Configuration screen
	 */
	public function addConfigurationSubmenu() {
		$page = add_submenu_page( null, sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), __( 'Postman SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanConfigurationController::CONFIGURATION_SLUG, array(
				$this,
				'outputManualConfigurationContent',
		) );
		// When the plugin options page is loaded, also load the stylesheet
		add_action( 'admin_print_styles-' . $page, array(
				$this,
				'enqueueConfigurationResources',
		) );
	}

	/**
	 */
	function enqueueConfigurationResources() {
		$this->addLocalizeScriptsToPage();
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_style( 'jquery_ui_style' );
		wp_enqueue_script( 'postman_manual_config_script' );
	}

	/**
	 * Register the Setup Wizard screen
	 */
	public function addSetupWizardSubmenu() {
		$page = add_submenu_page( null, sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), __( 'Postman SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG, array(
				$this,
				'outputWizardContent',
		) );
		// When the plugin options page is loaded, also load the stylesheet
		add_action( 'admin_print_styles-' . $page, array(
				$this,
				'enqueueWizardResources',
		) );
	}

	/**
	 */
	function enqueueWizardResources() {
		$this->addLocalizeScriptsToPage();
		$this->importableConfiguration = new PostmanImportableConfiguration();
		$startPage = 1;
		if ( $this->importableConfiguration->isImportAvailable() ) {
			$startPage = 0;
		}
		wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_setup_wizard', array(
				'start_page' => $startPage,
		) );
		wp_enqueue_style( 'jquery_steps_style' );
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman_wizard_script' );
		//wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, '$jq', 'jQuery.noConflict(true)' );
		$shortLocale = substr( get_locale(), 0, 2 );
		if ( $shortLocale != 'en' ) {
			$url = plugins_url( sprintf( 'script/jquery-validate/localization/messages_%s.js', $shortLocale ), $this->rootPluginFilenameAndPath );
			wp_enqueue_script( sprintf( 'jquery-validation-locale-%s', $shortLocale ), $url );
		}
	}

	/**
	 */
	public function outputManualConfigurationContent() {
		print '<div class="wrap">';

		PostmanViewController::outputChildPageHeader( __( 'Settings', 'post-smtp' ), 'advanced_config' );

		$config_tabs = apply_filters( 'post_smtp_admin_tabs', array(
		    'account_config' => __( 'Account', 'post-smtp' ),
		    'fallback' => __( 'Fallback', 'post-smtp' ),
		    'message_config' => __( 'Message', 'post-smtp' ),
		    'logging_config' => __( 'Logging', 'post-smtp' ),
		    'advanced_options_config' => __( 'Advanced', 'post-smtp' ),
        ) );

		print '<div id="config_tabs"><ul>';

		foreach ( $config_tabs as $slug => $tab ) :
            printf( '<li><a href="#%s">%s</a></li>', $slug, $tab );
        endforeach;

		print '</ul>';

		print '<form method="post" action="options.php">';

		wp_nonce_field('post-smtp', 'security');

		// This prints out all hidden setting fields
		settings_fields( PostmanAdminController::SETTINGS_GROUP_NAME );

		// account_config
		print '<section id="account_config">';
		if ( sizeof( PostmanTransportRegistry::getInstance()->getTransports() ) > 1 ) {
			do_settings_sections( 'transport_options' );
		} else {
			printf( '<input id="input_%2$s" type="hidden" name="%1$s[%2$s]" value="%3$s"/>', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::TRANSPORT_TYPE, PostmanSmtpModuleTransport::SLUG );
		}
		print '<div id="smtp_config" class="transport_setting">';
		do_settings_sections( PostmanAdminController::SMTP_OPTIONS );
		print '</div>';
		print '<div id="password_settings" class="authentication_setting non-oauth2">';
		do_settings_sections( PostmanAdminController::BASIC_AUTH_OPTIONS );
		print '</div>';
		print '<div id="oauth_settings" class="authentication_setting non-basic">';
		do_settings_sections( PostmanAdminController::OAUTH_AUTH_OPTIONS );
		print '</div>';
		print '<div id="mandrill_settings" class="authentication_setting non-basic non-oauth2">';
		do_settings_sections( PostmanMandrillTransport::MANDRILL_AUTH_OPTIONS );
		print '</div>';
		print '<div id="sendgrid_settings" class="authentication_setting non-basic non-oauth2">';
		do_settings_sections( PostmanSendGridTransport::SENDGRID_AUTH_OPTIONS );
		print '</div>';
		print '<div id="mailgun_settings" class="authentication_setting non-basic non-oauth2">';
		do_settings_sections( PostmanMailgunTransport::MAILGUN_AUTH_OPTIONS );
		print '</div>';

		do_action( 'post_smtp_settings_sections' );

		print '</section>';
        // end account config
		?>

        <!-- Fallback Start -->
        <section id="fallback">
            <h2><?php esc_html_e( 'Failed emails fallback', 'post-smtp' ); ?></h2>
            <p><?php esc_html_e( 'By enable this option, if your email is fail to send Post SMTP will try to use the SMTP service you define here.', 'post-smtp' ); ?></p>
            <table class="form-table">
                <tr valign="">
                    <th scope="row"><?php _e( 'Use Fallback?', 'post-smtp' ); ?></th>
                    <td>
                        <label>
                            <input name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_ENABLED; ?>]" type="radio"
                                   value="no"<?php echo checked( $this->options->getFallbackIsEnabled(), 'no' ); ?>>
                            <?php _e( 'No', 'post-smtp' ); ?>
                        </label>
                        &nbsp;
                        <label>
                            <?php $checked = checked( $this->options->getFallbackIsEnabled(), 'yes', false ); ?>
                            <input name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_ENABLED; ?>]" type="radio"
                                   value="yes"<?php echo checked( $this->options->getFallbackIsEnabled(), 'yes' ); ?>>
                            <?php _e( 'Yes', 'post-smtp' ); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Outgoing Mail Server', 'post-smtp' ); ?></th>
                    <?php $host = $this->options->getFallbackHostname(); ?>
                    <td>
                        <input type="text" id="fallback-smtp-host" name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_HOSTNAME; ?>]"
                               value="<?php echo $host; ?>" placeholder="Example: smtp.host.com">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Mail Server Port', 'post-smtp' ); ?></th>
                    <?php $port = $this->options->getFallbackPort(); ?>
                    <td>
                        <input type="number" id="fallback-smtp-port" name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_PORT; ?>]"
                               value="<?php echo $port; ?>" placeholder="Example: 587">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Security', 'post-smtp' ); ?></th>
                    <?php
                    $security_options = array(
                        'none' => __( 'None', 'post-smtp' ),
                        'ssl' => __( 'SSL', 'post-smtp' ),
                        'tls' => __( 'TLS', 'post-smtp' ),
                    );
                    ?>
                    <td>
                        <select id="fallback-smtp-security" name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_SECURITY; ?>]">
                            <?php
                            foreach ( $security_options as $key => $label ) {
                                $selected = selected( $this->options->getFallbackSecurity(), $key,false );
                                ?>
                                <option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $label; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('From Email', 'post-smtp' ); ?></th>
                    <td>
                        <input type="email" id="fallback-smtp-from-email"
                               value="<?php echo $this->options->getFallbackFromEmail(); ?>"
                               name="postman_options[<?php echo PostmanOptions::FALLBACK_FROM_EMAIL; ?>]"
                        >
                        <br>
                        <small><?php _e( "Use allowed email, for example: If you are using Gmail, type your Gmail adress.", 'post-smtp' ); ?></small>
                    </td>
                </tr>

                <tr valign="">
                    <th scope="row"><?php _e( 'Use SMTP Authentication?', 'post-smtp' ); ?></th>
                    <td>
                        <label>
                            <input name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_USE_AUTH; ?>]"
                                   type="radio" value="none"<?php checked( $this->options->getFallbackAuth(), 'none' ); ?>>
                            <?php _e( 'No', 'post-smtp' ); ?>
                        </label>
                        &nbsp;
                        <label>
                            <input name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_USE_AUTH; ?>]"
                                   type="radio" value="login"<?php checked( $this->options->getFallbackAuth(), 'login' ); ?>>
                            <?php _e( 'Yes', 'post-smtp' ); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('User name', 'post-smtp' ); ?></th>
                    <td>
                        <input type="text" id="fallback-smtp-username"
                               value="<?php echo $this->options->getFallbackUsername(); ?>"
                               name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_USERNAME; ?>]"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Password', 'post-smtp' ); ?></th>
                    <td>
                        <input type="password" id="fallback-smtp-password"
                               value="<?php echo PostmanUtils::obfuscatePassword( $this->options->getFallbackPassword() ); ?>"
                               name="postman_options[<?php echo PostmanOptions::FALLBACK_SMTP_PASSWORD; ?>]"
                        >
                    </td>
                </tr>

            </table>
        </section>
        <!-- Fallback End -->

        <?php
		print '<section id="message_config">';
		do_settings_sections( PostmanAdminController::MESSAGE_SENDER_OPTIONS );
		do_settings_sections( PostmanAdminController::MESSAGE_FROM_OPTIONS );
		do_settings_sections( PostmanAdminController::EMAIL_VALIDATION_OPTIONS );
		do_settings_sections( PostmanAdminController::MESSAGE_OPTIONS );
		do_settings_sections( PostmanAdminController::MESSAGE_HEADERS_OPTIONS );
		print '</section>';
		print '<section id="logging_config">';
		do_settings_sections( PostmanAdminController::LOGGING_OPTIONS );
		print '</section>';
		/*
		 * print '<section id="logging_config">';
		 * do_settings_sections ( PostmanAdminController::MULTISITE_OPTIONS );
		 * print '</section>';
		 */
		print '<section id="advanced_options_config">';
		do_settings_sections( PostmanAdminController::NETWORK_OPTIONS );
		do_settings_sections( PostmanAdminController::ADVANCED_OPTIONS );
		print '</section>';

		do_action( 'post_smtp_settings_menu' );

		submit_button();
		print '</form>';
		print '</div>';
		print '</div>';
	}

	/**
	 */
	public function outputWizardContent() {
		// Set default values for input fields
		$this->options->setMessageSenderEmailIfEmpty( wp_get_current_user()->user_email );
		$this->options->setMessageSenderNameIfEmpty( wp_get_current_user()->display_name );

		// construct Wizard
		print '<div class="wrap">';

		PostmanViewController::outputChildPageHeader( __( 'Setup Wizard', 'post-smtp' ) );

		print '<form id="postman_wizard" method="post" action="options.php">';

		// account tab
		// message tab
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE, $this->options->isPluginSenderEmailEnforced() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE, $this->options->isPluginSenderNameEnforced() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::REPLY_TO, $this->options->getReplyTo() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_TO_RECIPIENTS, $this->options->getForcedToRecipients() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_CC_RECIPIENTS, $this->options->getForcedCcRecipients() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::FORCED_BCC_RECIPIENTS, $this->options->getForcedBccRecipients() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::ADDITIONAL_HEADERS, $this->options->getAdditionalHeaders() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::DISABLE_EMAIL_VALIDAITON, $this->options->isEmailValidationDisabled() );

		// logging tab
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::MAIL_LOG_ENABLED_OPTION, $this->options->getMailLoggingEnabled() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::MAIL_LOG_MAX_ENTRIES, $this->options->getMailLoggingMaxEntries() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::TRANSCRIPT_SIZE, $this->options->getTranscriptSize() );

		// advanced tab
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::CONNECTION_TIMEOUT, $this->options->getConnectionTimeout() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::READ_TIMEOUT, $this->options->getReadTimeout() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::LOG_LEVEL, $this->options->getLogLevel() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::RUN_MODE, $this->options->getRunMode() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::STEALTH_MODE, $this->options->isStealthModeEnabled() );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::TEMPORARY_DIRECTORY, $this->options->getTempDirectory() );

		wp_nonce_field('post-smtp', 'security' );

		// display the setting text
		settings_fields( PostmanAdminController::SETTINGS_GROUP_NAME );

		// Wizard Step 0
		printf( '<h5>%s</h5>', _x( 'Import Configuration', 'Wizard Step Title', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', _x( 'Import configuration from another plugin?', 'Wizard Step Title', 'post-smtp' ) );
		printf( '<p>%s</p>', __( 'If you had a working configuration with another Plugin, the Setup Wizard can begin with those settings.', 'post-smtp' ) );
		print '<table class="input_auth_type">';
		printf( '<tr><td><input type="radio" id="import_none" name="input_plugin" value="%s" checked="checked"></input></td><td><label> %s</label></td></tr>', 'none', __( 'None', 'post-smtp' ) );

		if ( $this->importableConfiguration->isImportAvailable() ) {
			foreach ( $this->importableConfiguration->getAvailableOptions() as $options ) {
				printf( '<tr><td><input type="radio" name="input_plugin" value="%s"/></td><td><label> %s</label></td></tr>', $options->getPluginSlug(), $options->getPluginName() );
			}
		}
		print '</table>';
		print '</fieldset>';

		// Wizard Step 1
		printf( '<h5>%s</h5>', _x( 'Sender Details', 'Wizard Step Title', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', _x( 'Who is the mail coming from?', 'Wizard Step Title', 'post-smtp' ) );
		printf( '<p>%s</p>', __( 'Enter the email address and name you\'d like to send mail as.', 'post-smtp' ) );
		printf( '<p>%s</p>', __( 'Please note that to prevent abuse, many email services will <em>not</em> let you send from an email address other than the one you authenticate with.', 'post-smtp' ) );
		printf( '<label for="postman_options[sender_email]">%s</label>', __( 'Email Address', 'post-smtp' ) );
		print $this->settingsRegistry->from_email_callback();
		print '<br/>';
		printf( '<label for="postman_options[sender_name]">%s</label>', __( 'Name', 'post-smtp' ) );
		print $this->settingsRegistry->sender_name_callback();
		print '</fieldset>';

		// Wizard Step 2
		printf( '<h5>%s</h5>', __( 'Outgoing Mail Server Hostname', 'post-smtp' ) );
		print '<fieldset>';
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			$transport->printWizardMailServerHostnameStep();
		}
		print '</fieldset>';

		// Wizard Step 3
		printf( '<h5>%s</h5>', __( 'Connectivity Test', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', __( 'How will the connection to the mail server be established?', 'post-smtp' ) );
		printf( '<p>%s</p>', __( 'Your connection settings depend on what your email service provider offers, and what your WordPress host allows.', 'post-smtp' ) );
		printf( '<p id="connectivity_test_status">%s: <span id="port_test_status">%s</span></p>', __( 'Connectivity Test', 'post-smtp' ), _x( 'Ready', 'TCP Port Test Status', 'post-smtp' ) );
		printf( '<p class="ajax-loader" style="display:none"><img src="%s"/></p>', plugins_url( 'post-smtp/style/ajax-loader.gif' ) );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::TRANSPORT_TYPE );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::PORT );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::SECURITY_TYPE );
		printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', PostmanOptions::POSTMAN_OPTIONS, PostmanOptions::AUTHENTICATION_TYPE );
		print '<p id="wizard_recommendation"></p>';
		/* Translators: Where %1$s is the socket identifier and %2$s is the authentication type */
		printf( '<p class="user_override" style="display:none"><label><span>%s:</span></label> <table id="user_socket_override" class="user_override"></table></p>', _x( 'Socket', 'A socket is the network term for host and port together', 'post-smtp' ) );
		printf( '<p class="user_override" style="display:none"><label><span>%s:</span></label> <table id="user_auth_override" class="user_override"></table></p>', __( 'Authentication', 'post-smtp' ) );
		print ('<p><span id="smtp_mitm" style="display:none; background-color:yellow"></span></p>') ;
		$warning = __( 'Warning', 'post-smtp' );
		$clearCredentialsWarning = __( 'This configuration option will send your authorization credentials in the clear.', 'post-smtp' );
		printf( '<p id="smtp_not_secure" style="display:none"><span style="background-color:yellow">%s: %s</span></p>', $warning, $clearCredentialsWarning );
		print '</fieldset>';

		// Wizard Step 4
		printf( '<h5>%s</h5>', __( 'Authentication', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', __( 'How will you prove your identity to the mail server?', 'post-smtp' ) );
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			$transport->printWizardAuthenticationStep();
		}
		print '</fieldset>';

		// Wizard Step 5 - Notificiations
		printf( '<h5>%s</h5>', __( 'Notifications', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', __( 'Select a notify service to notify you when an email is failed to delivered.', 'post-smtp' ) );

		?>
		<select id="input_notification_service" class="input_notification_service" name="postman_options[notification_service]">
			<option value="default">Email</option>
			<option value="pushover">Pushover</option>
			<option value="slack">Slack</option>
		</select>
		<div id="pushover_cred" style="display: none;">
			<h2><?php _e( 'Pushover Credentials', 'post-smtp' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e( 'Pushover User Key', 'post-smtp' ); ?></th>
						<td>
							<input type="password" id="pushover_user" name="postman_options[pushover_user]" value="">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Pushover App Token', 'post-smtp' ); ?></th>
						<td>
							<input type="password" id="pushover_token" name="postman_options[pushover_token]" value="">
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="slack_cred" style="display: none;">
			<h2><?php _e( 'Slack Credentials', 'post-smtp' ); ?></h2>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><?php _e( 'Slack webhook', 'post-smtp' ); ?></th>
					<td>
						<input type="password" id="slack_token" name="postman_options[slack_token]" value="">
						<a target="_blank" class="" href="https://slack.postmansmtp.com/">
							<?php _e( 'Get your webhook URL here.', 'post-smtp' ); ?>
						</a>
					</td>
				</tr>
				</tbody>
			</table>
		</div>

        <div id="use-chrome-extension">
            <h2><?php _e( 'Push To Chrome Extension', 'post-smtp' ); ?></h2>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php _e( 'This is an extra notification to the selection above', 'post-smtp' ); ?></th>
                    <td>
                        <input type="checkbox" id="notification_use_chrome" name="postman_options[notification_use_chrome]">
                        <a target="_blank" class="" href="https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch">
                            <?php _e( 'You can download the chrome extension here (if link not available, check later).', 'post-smtp' ); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Your UID as you see in the extension.', 'post-smtp' ); ?></th>
                    <td>
                        <input type="password" id="notification_chrome_uid" name="postman_options[notification_chrome_uid]" value="">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

		<?php
		print '</fieldset>';

		// Wizard Step 6
		printf( '<h5>%s</h5>', _x( 'Finish', 'The final step of the Wizard', 'post-smtp' ) );
		print '<fieldset>';
		printf( '<legend>%s</legend>', _x( 'You\'re Done!', 'Wizard Step Title', 'post-smtp' ) );
		print '<section>';
		printf( '<p>%s</p>', __( 'Click Finish to save these settings, then:', 'post-smtp' ) );
		print '<ul style="margin-left: 20px">';
		printf( '<li class="wizard-auth-oauth2">%s</li>', __( 'Grant permission with the Email Provider for Postman to send email and', 'post-smtp' ) );
		printf( '<li>%s</li>', __( 'Send yourself a Test Email to make sure everything is working!', 'post-smtp' ) );
		print '</ul>';

		// Get PHPmailer recommendation
		Postman::getMailerTypeRecommend();

		$in_wizard = true;
		//include_once POST_SMTP_PATH . '/Postman/extra/donation.php';

		print '</section>';
		print '</fieldset>';
		print '</form>';
		print '</div>';
	}
}

/**
 *
 * @author jasonhendriks
 */
class PostmanGetHostnameByEmailAjaxController extends PostmanAbstractAjaxHandler {
	const IS_GOOGLE_PARAMETER = 'is_google';
	function __construct() {
		parent::__construct();
		PostmanUtils::registerAjaxHandler( 'postman_check_email', $this, 'getAjaxHostnameByEmail' );
	}
	/**
	 * This Ajax function retrieves the smtp hostname for a give e-mail address
	 */
	function getAjaxHostnameByEmail() {

	    check_admin_referer('post-smtp', 'security');

		$goDaddyHostDetected = $this->getBooleanRequestParameter( 'go_daddy' );
		$email = $this->getRequestParameter( 'email' );
		$d = new PostmanSmtpDiscovery( $email );
		$smtp = $d->getSmtpServer();
		$this->logger->debug( 'given email ' . $email . ', smtp server is ' . $smtp );
		$this->logger->trace( $d );
		if ( $goDaddyHostDetected && ! $d->isGoogle ) {
			// override with the GoDaddy SMTP server
			$smtp = 'relay-hosting.secureserver.net';
			$this->logger->debug( 'detected GoDaddy SMTP server, smtp server is ' . $smtp );
		}
		$response = array(
				'hostname' => $smtp,
				self::IS_GOOGLE_PARAMETER => $d->isGoogle,
				'is_go_daddy' => $d->isGoDaddy,
				'is_well_known' => $d->isWellKnownDomain,
		);
		$this->logger->trace( $response );
		wp_send_json_success( $response );
	}
}
class PostmanManageConfigurationAjaxHandler extends PostmanAbstractAjaxHandler {
	function __construct() {
		parent::__construct();
		PostmanUtils::registerAjaxHandler( 'manual_config', $this, 'getManualConfigurationViaAjax' );
		PostmanUtils::registerAjaxHandler( 'get_wizard_configuration_options', $this, 'getWizardConfigurationViaAjax' );
	}

	/**
	 * Handle a Advanced Configuration request with Ajax
	 *
	 * @throws Exception
	 */
	function getManualConfigurationViaAjax() {

	    check_admin_referer('post-smtp', 'security');

		$queryTransportType = $this->getTransportTypeFromRequest();
		$queryAuthType = $this->getAuthenticationTypeFromRequest();
		$queryHostname = $this->getHostnameFromRequest();

		// the outgoing server hostname is only required for the SMTP Transport
		// the Gmail API transport doesn't use an SMTP server
		$transport = PostmanTransportRegistry::getInstance()->getTransport( $queryTransportType );
		if ( ! $transport ) {
			throw new Exception( 'Unable to find transport ' . $queryTransportType );
		}

		// create the response
		$response = $transport->populateConfiguration( $queryHostname );
		$response ['referer'] = 'manual_config';

		// set the display_auth to oauth2 if the transport needs it
		if ( $transport->isOAuthUsed( $queryAuthType ) ) {
			$response ['display_auth'] = 'oauth2';
			$this->logger->debug( 'ajaxRedirectUrl answer display_auth:' . $response ['display_auth'] );
		}
		$this->logger->trace( $response );
		wp_send_json_success( $response );
	}

	/**
	 * Once the Port Tests have run, the results are analyzed.
	 * The Transport place bids on the sockets and highest bid becomes the recommended
	 * The UI response is built so the user may choose a different socket with different options.
	 */
	function getWizardConfigurationViaAjax() {

	    check_admin_referer('post-smtp', 'security');

		$this->logger->debug( 'in getWizardConfiguration' );
		$originalSmtpServer = $this->getRequestParameter( 'original_smtp_server' );
		$queryHostData = $this->getHostDataFromRequest();
		$sockets = array();
		foreach ( $queryHostData as $id => $datum ) {
			array_push( $sockets, new PostmanWizardSocket( $datum ) );
		}
		$this->logger->error( $sockets );
		$userPortOverride = $this->getUserPortOverride();
		$userAuthOverride = $this->getUserAuthOverride();

		// determine a configuration recommendation
		$winningRecommendation = $this->getWinningRecommendation( $sockets, $userPortOverride, $userAuthOverride, $originalSmtpServer );
		if ( $this->logger->isTrace() ) {
			$this->logger->trace( 'winning recommendation:' );
			$this->logger->trace( $winningRecommendation );
		}

		// create the reponse
		$response = array();
		$configuration = array();
		$response ['referer'] = 'wizard';
		if ( isset( $userPortOverride ) || isset( $userAuthOverride ) ) {
			$configuration ['user_override'] = true;
		}

		if ( isset( $winningRecommendation ) ) {

			// create an appropriate (theoretical) transport
			$transport = PostmanTransportRegistry::getInstance()->getTransport( $winningRecommendation ['transport'] );

			// create user override menu
			$overrideMenu = $this->createOverrideMenus( $sockets, $winningRecommendation, $userPortOverride, $userAuthOverride );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'override menu:' );
				$this->logger->trace( $overrideMenu );
			}

			$queryHostName = $winningRecommendation ['hostname'];
			if ( $this->logger->isDebug() ) {
				$this->logger->debug( 'Getting scribe for ' . $queryHostName );
			}
			$generalConfig1 = $transport->populateConfiguration( $queryHostName );
			$generalConfig2 = $transport->populateConfigurationFromRecommendation( $winningRecommendation );
			$configuration = array_merge( $configuration, $generalConfig1, $generalConfig2 );
			$response ['override_menu'] = $overrideMenu;
			$response ['configuration'] = $configuration;
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'configuration:' );
				$this->logger->trace( $configuration );
				$this->logger->trace( 'response:' );
				$this->logger->trace( $response );
			}
			wp_send_json_success( $response );
		} else {
			/* translators: where %s is the URL to the Connectivity Test page */
			$configuration ['message'] = sprintf( __( 'Postman can\'t find any way to send mail on your system. Run a <a href="%s">connectivity test</a>.', 'post-smtp' ), PostmanViewController::getPageUrl( PostmanConnectivityTestController::PORT_TEST_SLUG ) );
			$response ['configuration'] = $configuration;
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'configuration:' );
				$this->logger->trace( $configuration );
			}
			wp_send_json_error( $response );
		}
	}

	/**
	 * // for each successful host/port combination
	 * // ask a transport if they support it, and if they do at what priority is it
	 * // configure for the highest priority you find
	 *
	 * @param mixed $queryHostData
	 * @return mixed
	 */
	private function getWinningRecommendation( $sockets, $userSocketOverride, $userAuthOverride, $originalSmtpServer ) {
		foreach ( $sockets as $socket ) {
			$winningRecommendation = $this->getWin( $socket, $userSocketOverride, $userAuthOverride, $originalSmtpServer );
			$this->logger->error( $socket->label );
		}
		return $winningRecommendation;
	}

	/**
	 *
	 * @param PostmanWizardSocket $socket
	 * @param mixed       $userSocketOverride
	 * @param mixed       $userAuthOverride
	 * @param mixed       $originalSmtpServer
	 * @return mixed
	 */
	private function getWin( PostmanWizardSocket $socket, $userSocketOverride, $userAuthOverride, $originalSmtpServer ) {
		static $recommendationPriority = - 1;
		static $winningRecommendation = null;
		$available = $socket->success;
		if ( $available ) {
			$this->logger->debug( sprintf( 'Asking for judgement on %s:%s', $socket->hostname, $socket->port ) );
			$recommendation = PostmanTransportRegistry::getInstance()->getRecommendation( $socket, $userAuthOverride, $originalSmtpServer );
			$recommendationId = sprintf( '%s_%s', $socket->hostname, $socket->port );
			$recommendation ['id'] = $recommendationId;
			$this->logger->debug( sprintf( 'Got a recommendation: [%d] %s', $recommendation ['priority'], $recommendationId ) );
			if ( isset( $userSocketOverride ) ) {
				if ( $recommendationId == $userSocketOverride ) {
					$winningRecommendation = $recommendation;
					$this->logger->debug( sprintf( 'User chosen socket %s is the winner', $recommendationId ) );
				}
			} elseif ( $recommendation && $recommendation ['priority'] > $recommendationPriority ) {
				$recommendationPriority = $recommendation ['priority'];
				$winningRecommendation = $recommendation;
			}
			$socket->label = $recommendation ['label'];
		}
		return $winningRecommendation;
	}

	/**
	 *
	 * @param mixed $queryHostData
	 * @return multitype:
	 */
	private function createOverrideMenus( $sockets, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
		$overrideMenu = array();
		foreach ( $sockets as $socket ) {
			$overrideItem = $this->createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );
			if ( $overrideItem != null ) {
				$overrideMenu [ $socket->id ] = $overrideItem;
			}
		}

		// sort
		krsort( $overrideMenu );
		$sortedMenu = array();
		foreach ( $overrideMenu as $menu ) {
			array_push( $sortedMenu, $menu );
		}

		return $sortedMenu;
	}

	/**
	 *
	 * @param PostmanWizardSocket $socket
	 * @param mixed             $winningRecommendation
	 * @param mixed             $userSocketOverride
	 * @param mixed             $userAuthOverride
	 */
	private function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
		if ( $socket->success ) {
			$transport = PostmanTransportRegistry::getInstance()->getTransport( $socket->transport );
			$this->logger->debug( sprintf( 'Transport %s is building the override menu for socket', $transport->getSlug() ) );
			$overrideItem = $transport->createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );
			return $overrideItem;
		}
		return null;
	}

	/**
	 */
	private function getTransportTypeFromRequest() {
		return $this->getRequestParameter( 'transport' );
	}

	/**
	 */
	private function getHostnameFromRequest() {
		return $this->getRequestParameter( 'hostname' );
	}

	/**
	 */
	private function getAuthenticationTypeFromRequest() {
		return $this->getRequestParameter( 'auth_type' );
	}

	/**
	 */
	private function getHostDataFromRequest() {
		return $this->getRequestParameter( 'host_data' );
	}

	/**
	 */
	private function getUserPortOverride() {
		return $this->getRequestParameter( 'user_port_override' );
	}

	/**
	 */
	private function getUserAuthOverride() {
		return $this->getRequestParameter( 'user_auth_override' );
	}
}
class PostmanImportConfigurationAjaxController extends PostmanAbstractAjaxHandler {
	private $options;
	/**
	 * Constructor
	 *
	 * @param PostmanOptions $options
	 */
	function __construct( PostmanOptions $options ) {
		parent::__construct();
		$this->options = $options;
		PostmanUtils::registerAjaxHandler( 'import_configuration', $this, 'getConfigurationFromExternalPluginViaAjax' );
	}

	/**
	 * This function extracts configuration details form a competing SMTP plugin
	 * and pushes them into the Postman configuration screen.
	 */
	function getConfigurationFromExternalPluginViaAjax() {

        check_admin_referer('post-smtp', 'security');

		$importableConfiguration = new PostmanImportableConfiguration();
		$plugin = $this->getRequestParameter( 'plugin' );
		$this->logger->debug( 'Looking for config=' . $plugin );
		foreach ( $importableConfiguration->getAvailableOptions() as $this->options ) {
			if ( $this->options->getPluginSlug() == $plugin ) {
				$this->logger->debug( 'Sending configuration response' );
				$response = array(
						PostmanOptions::MESSAGE_SENDER_EMAIL => $this->options->getMessageSenderEmail(),
						PostmanOptions::MESSAGE_SENDER_NAME => $this->options->getMessageSenderName(),
						PostmanOptions::HOSTNAME => $this->options->getHostname(),
						PostmanOptions::PORT => $this->options->getPort(),
						PostmanOptions::AUTHENTICATION_TYPE => $this->options->getAuthenticationType(),
						PostmanOptions::SECURITY_TYPE => $this->options->getEncryptionType(),
						PostmanOptions::BASIC_AUTH_USERNAME => $this->options->getUsername(),
						PostmanOptions::BASIC_AUTH_PASSWORD => $this->options->getPassword(),
						'success' => true,
				);
				break;
			}
		}
		if ( ! isset( $response ) ) {
			$response = array(
					'success' => false,
			);
		}
		wp_send_json( $response );
	}
}
