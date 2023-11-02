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
	private $allowed_tags = array( 
		'input'			=>	array(
			'type'			=>	array(),
			'id'			=>	array(),
			'name'			=>	array(),
			'value'			=>	array(),
			'class'			=>	array(),
			'placeholder'	=>	array(),
			'size'			=>	array(),
		)
	);

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

		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 21 );
		add_action( 'admin_menu', array( $this, 'addSetupWizardSubmenu' ), 21 );
		add_filter( 'submenu_file', array( $this, 'hide_submenu_item' ) );

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
	 * Adds sub menu page `Settings`
	 * 
	 * @since 2.1
	 * @version 1.0
	 */
	public function add_submenu_page() {

		// only do this for administrators
		if ( PostmanUtils::isAdmin() ) {

			$this->logger->trace( 'created PostmanSettings admin menu item' );

			$page = add_submenu_page( 
				PostmanViewController::POSTMAN_MENU_SLUG, 
				sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), 
				__( 'Settings', 'post-smtp' ), 
				Postman::MANAGE_POSTMAN_CAPABILITY_NAME, 
				PostmanConfigurationController::CONFIGURATION_SLUG, 
				array(
					$this,
					'outputManualConfigurationContent',
				) );

				// When the plugin options page is loaded, also load the stylesheet
				add_action( 'admin_print_styles-' . $page, array( $this, 'enqueueConfigurationResources' ) );

		}

	}

	/**
	 */
	function enqueueConfigurationResources() {
		$this->addLocalizeScriptsToPage();
		wp_enqueue_style( 'jquery_ui_style' );
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman_manual_config_script' );
	}

	/**
	 * Register the Setup Wizard screen
	 */
	public function addSetupWizardSubmenu() {
		$page = add_submenu_page( 
			PostmanViewController::POSTMAN_MENU_SLUG, 
			sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), 
			__( 'Postman SMTP', 'post-smtp' ), 
			Postman::MANAGE_POSTMAN_CAPABILITY_NAME, 
			PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG, 
			array(
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
	 * Hides submenu 
	 */
	public function hide_submenu_item( $submenu_file ) {

		$hidden_submenus = array(
			PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG => true,
		);

		// Hide the submenu.
		foreach ( $hidden_submenus as $submenu => $unused ) {
			remove_submenu_page( PostmanViewController::POSTMAN_MENU_SLUG, $submenu );
		}

		return $submenu_file;

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

		wp_localize_script( 'postman_wizard_script', 'postman',
			array(
				'assets'	=>	POST_SMTP_ASSETS
			)
	 	);

		//wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, '$jq', 'jQuery.noConflict(true)' );
		$shortLocale = substr( get_locale(), 0, 2 );
		if ( $shortLocale != 'en' ) {
			$url = plugins_url( sprintf( 'script/jquery-validate/localization/messages_%s.js', $shortLocale ), $this->rootPluginFilenameAndPath );
			wp_enqueue_script( sprintf( 'jquery-validation-locale-%s', $shortLocale ), $url, array(), POST_SMTP_VER );
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
            printf( '<li><a href="#%s">%s</a></li>', esc_attr( $slug ), esc_html( $tab ) );
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
		} 
		else {
			printf( 
				'<input id="input_%2$s" type="hidden" name="%1$s[%2$s]" value="%3$s"/>', 
				esc_attr( PostmanOptions::POSTMAN_OPTIONS ), 
				esc_attr( PostmanOptions::TRANSPORT_TYPE ), 
				esc_attr( PostmanSmtpModuleTransport::SLUG ) 
			);
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
        print '<div id="sendinblue_settings" class="authentication_setting non-basic non-oauth2">';
        do_settings_sections( PostmanSendinblueTransport::SENDINBLUE_AUTH_OPTIONS );
        print '</div>';
		print '<div id="mailjet_settings" class="authentication_setting non-basic non-oauth2">';
        do_settings_sections( PostmanMailjetTransport::MAILJET_AUTH_OPTIONS );
        print '</div>';
        print '<div id="postmark_settings" class="authentication_setting non-basic non-oauth2">';
        do_settings_sections( PostmanPostmarkTransport::POSTMARK_AUTH_OPTIONS );
		print '</div>';
		print '<div id="sparkpost_settings" class="authentication_setting non-basic non-oauth2">';
        do_settings_sections( PostmanSparkPostTransport::SPARKPOST_AUTH_OPTIONS );
        print '</div>';
		print '<div id="elasticemail_settings" class="authentication_setting non-basic non-oauth2">';
        do_settings_sections( PostmanElasticEmailTransport::ELASTICEMAIL_AUTH_OPTIONS );
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
                    <th scope="row"><?php esc_html_e( 'Use Fallback?', 'post-smtp' ); ?></th>
                    <td>
                        <label>
                            <input name="postman_options[<?php esc_attr_e( PostmanOptions::FALLBACK_SMTP_ENABLED ); ?>]" type="radio"
                                   value="no"<?php echo checked( $this->options->getFallbackIsEnabled(), 'no' ); ?>>
                            <?php esc_html_e( 'No', 'post-smtp' ); ?>
                        </label>
                        &nbsp;
                        <label>
                            <?php $checked = checked( $this->options->getFallbackIsEnabled(), 'yes', false ); ?>
                            <input name="postman_options[<?php esc_attr_e( PostmanOptions::FALLBACK_SMTP_ENABLED ); ?>]" type="radio"
                                   value="yes"<?php echo checked( $this->options->getFallbackIsEnabled(), 'yes' ); ?>>
                            <?php esc_html_e( 'Yes', 'post-smtp' ); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Outgoing Mail Server', 'post-smtp' ); ?></th>
                    <?php $host = $this->options->getFallbackHostname(); ?>
                    <td>
                        <input type="text" id="fallback-smtp-host" name="postman_options[<?php esc_attr_e( PostmanOptions::FALLBACK_SMTP_HOSTNAME ); ?>]"
                               value="<?php esc_attr_e( $host ); ?>" placeholder="Example: smtp.host.com">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Mail Server Port', 'post-smtp' ); ?></th>
                    <?php $port = $this->options->getFallbackPort(); ?>
                    <td>
                        <input type="number" id="fallback-smtp-port" name="postman_options[<?php esc_attr_e( PostmanOptions::FALLBACK_SMTP_PORT ); ?>]"
                               value="<?php esc_attr_e( $port ); ?>" placeholder="Example: 587">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Security', 'post-smtp' ); ?></th>
                    <?php
                    $security_options = array(
                        'none' => __( 'None', 'post-smtp' ),
                        'ssl' => __( 'SSL', 'post-smtp' ),
                        'tls' => __( 'TLS', 'post-smtp' ),
                    );
                    ?>
                    <td>
                        <select id="fallback-smtp-security" name="postman_options[<?php esc_attr_e( PostmanOptions::FALLBACK_SMTP_SECURITY ); ?>]">
                            <?php
                            foreach ( $security_options as $key => $label ) {
                                $selected = selected( $this->options->getFallbackSecurity(), $key,false );
                                ?>
                                <option value="<?php esc_attr_e( $key ); ?>"<?php esc_attr_e( $selected ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'From Email', 'post-smtp' ); ?></th>
                    <td>
                        <input type="email" id="fallback-smtp-from-email"
                               value="<?php echo esc_attr( $this->options->getFallbackFromEmail() ); ?>"
                               name="postman_options[<?php echo esc_attr( PostmanOptions::FALLBACK_FROM_EMAIL ); ?>]"
                        >
                        <br>
                        <small><?php esc_html_e( "Use allowed email, for example: If you are using Gmail, type your Gmail adress.", 'post-smtp' ); ?></small>
                    </td>
                </tr>

                <tr valign="">
                    <th scope="row"><?php esc_html_e( 'Use SMTP Authentication?', 'post-smtp' ); ?></th>
                    <td>
                        <label>
                            <input name="postman_options[<?php echo esc_attr( PostmanOptions::FALLBACK_SMTP_USE_AUTH ); ?>]"
                                   type="radio" value="none"<?php checked( $this->options->getFallbackAuth(), 'none' ); ?>>
                            <?php esc_html_e( 'No', 'post-smtp' ); ?>
                        </label>
                        &nbsp;
                        <label>
                            <input name="postman_options[<?php echo esc_attr( PostmanOptions::FALLBACK_SMTP_USE_AUTH ); ?>]"
                                   type="radio" value="login"<?php checked( $this->options->getFallbackAuth(), 'login' ); ?>>
                            <?php esc_html_e( 'Yes', 'post-smtp' ); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('User name', 'post-smtp' ); ?></th>
                    <td>
                        <input type="text" id="fallback-smtp-username"
                               value="<?php echo esc_attr( $this->options->getFallbackUsername() ); ?>"
                               name="postman_options[<?php echo esc_attr( PostmanOptions::FALLBACK_SMTP_USERNAME ); ?>]"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Password', 'post-smtp' ); ?></th>
                    <td>
                        <input type="password" id="fallback-smtp-password"
                               value="<?php echo esc_attr( PostmanUtils::obfuscatePassword( $this->options->getFallbackPassword() ) ); ?>"
                               name="postman_options[<?php echo esc_attr( PostmanOptions::FALLBACK_SMTP_PASSWORD ); ?>]"
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

		submit_button( 'Save Changes', 'button button-primary' );
		print '</form>';
		print '</div>';
		print '</div>';
	}

	/**
	 */
	public function outputWizardContent() {
		
		/**
		 * Filters whether to display the legacy wizard or not.
		 * 
		 * @since 2.6.2
		 */
		if( apply_filters( 'post_smtp_legacy_wizard', true ) ) {

			// Set default values for input fields
			$this->options->setMessageSenderEmailIfEmpty( wp_get_current_user()->user_email );
			$this->options->setMessageSenderNameIfEmpty( wp_get_current_user()->display_name );

			// construct Wizard
			print '<div class="wrap">';

			PostmanViewController::outputChildPageHeader( __( 'Setup Wizard', 'post-smtp' ) );

			print '<form id="postman_wizard" method="post" action="options.php">';

			// account tab
			// message tab
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ), esc_attr( $this->options->isPluginSenderEmailEnforced() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ), esc_attr( $this->options->isPluginSenderNameEnforced() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::REPLY_TO ), esc_attr( $this->options->getReplyTo() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::FORCED_TO_RECIPIENTS ), esc_attr( $this->options->getForcedToRecipients() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::FORCED_CC_RECIPIENTS ), esc_attr( $this->options->getForcedCcRecipients() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::FORCED_BCC_RECIPIENTS ), esc_attr( $this->options->getForcedBccRecipients() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::ADDITIONAL_HEADERS ), esc_attr( $this->options->getAdditionalHeaders() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::DISABLE_EMAIL_VALIDAITON ), esc_attr( $this->options->isEmailValidationDisabled() ) );

			// logging tab
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::MAIL_LOG_ENABLED_OPTION ), esc_attr( $this->options->getMailLoggingEnabled() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::MAIL_LOG_MAX_ENTRIES ), esc_attr( $this->options->getMailLoggingMaxEntries() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::TRANSCRIPT_SIZE ), esc_attr( $this->options->getTranscriptSize() ) );

			// advanced tab
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::CONNECTION_TIMEOUT ), esc_attr( $this->options->getConnectionTimeout() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::READ_TIMEOUT ), esc_attr( $this->options->getReadTimeout() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::LOG_LEVEL ), esc_attr( $this->options->getLogLevel() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::RUN_MODE ), esc_attr( $this->options->getRunMode() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::STEALTH_MODE ), esc_attr( $this->options->isStealthModeEnabled() ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::TEMPORARY_DIRECTORY ), esc_attr( $this->options->getTempDirectory() ) );

			wp_nonce_field('post-smtp', 'security' );

			// display the setting text
			settings_fields( PostmanAdminController::SETTINGS_GROUP_NAME );

			// Wizard Step 0
			printf( '<h5>%s</h5>', esc_html_x( 'Import Configuration', 'Wizard Step Title', 'post-smtp' ) );
			print '<fieldset>';
			printf( '<legend>%s</legend>', esc_html_x( 'Import configuration from another plugin?', 'Wizard Step Title', 'post-smtp' ) );
			printf( '<p>%s</p>', esc_html__( 'If you had a working configuration with another Plugin, the Setup Wizard can begin with those settings.', 'post-smtp' ) );
			
			$style = '';

			if( !$this->importableConfiguration->isImportAvailable() ) {

				$style = 'style="display: none"';
				
				printf(
					'<div class="no-configuration ps-config-bar">
						<div class="ps-right">
							%s
						</div>
						<div class="clear"></div>
					</div>',
					esc_html__( 'No other SMTP plugin configuration has been detected in your installation. You can skip this step.', 'post-smtp' )
				);

			}

			printf(
				'<div class="input_auth_type">
					<div class="ps-socket-wizad-row" %s>
						<label>
							
							<div class="ps-single-socket-outer">
								<img src="%s" class="ps-wizard-socket-logo" width="165px">
							</div>
							<input type="radio" id="import_none" name="input_plugin" value="%s" checked="checked">
							<label> %s</label>
						</label>',
				wp_kses_post( $style ),
				esc_url( POST_SMTP_ASSETS . "images/logos/gear.png" ),
				'none', 
				esc_html__( 'None', 'post-smtp' )
			);

			$row = 1;

			if ( $this->importableConfiguration->isImportAvailable() ) {
				foreach ( $this->importableConfiguration->getAvailableOptions() as $options ) {
					printf( 
						'<label>
							<div class="ps-single-socket-outer">
								<img src="%s" class="ps-wizard-socket-logo" width="165px">
							</div>
							<input type="radio" id="import_none" name="input_plugin" value="%s" checked="checked">
							<label> %s</label>
						</label>', 
						esc_url( $options->getPluginLogo() ),
						esc_attr( $options->getPluginSlug() ), 
						esc_html( $options->getPluginName() )   
					);

					$row++;

					if( $row == 3 ) {
						print '</div>';
						print '<div class="ps-socket-wizad-row">';
						$row = 0;
					}
				}
			}

			print '</div>';
			print '</div>';
			print '</fieldset>';

			// Wizard Step 1
			printf( '<h5>%s</h5>', esc_html_x( 'Sender Details', 'Wizard Step Title', 'post-smtp' ) );
			print '<fieldset>';
			printf( '<legend>%s</legend>', esc_html_x( 'Who is the mail coming from?', 'Wizard Step Title', 'post-smtp' ) );
			printf( '<p>%s</p>', esc_html__( 'Enter the email address and name you\'d like to send mail as.', 'post-smtp' ) );
			// translators: 1: Opening paragraph tag, 2: Emphasized "not", 3: Remaining sentence
			printf( 
				'<p>%1$s <em>%2$s</em> %3$s</p>', 
				esc_html__( 'Please note that to prevent abuse, many email services will ', 'post-smtp' ), 
				esc_html__( 'not', 'post-smtp' ), 
				esc_html__( 'let you send from an email address other than the one you authenticate with.', 'post-smtp' ) 
			);
			
			print( '<div class="ps-ib ps-w-50">' );
			printf( '<label for="postman_options[sender_name]">%s</label>', esc_html__( 'Name', 'post-smtp' ) );
			print wp_kses( $this->settingsRegistry->sender_name_callback( false ), $this->allowed_tags );
			print( '</div>' );

			print( '<div class="ps-ib ps-w-50">' );
			printf( '<label for="postman_options[sender_email]">%s</label>', esc_html__( 'Email Address', 'post-smtp' ) );
			print wp_kses( $this->settingsRegistry->from_email_callback( false ), $this->allowed_tags );
			print( '</div>' );

			print( '<div class="clear"></div>' );

			print '</fieldset>';

			// Wizard Step 2
			printf( '<h5>%s</h5>', esc_html__( 'Outgoing Mail Server Hostname', 'post-smtp' ) );
			print '<fieldset>';
			foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
				$transport->printWizardMailServerHostnameStep();
			}
			print '</fieldset>';

			// Wizard Step 3
			printf( '<h5>%s</h5>', esc_html__( 'Connectivity Test', 'post-smtp' ) );
			print '<fieldset>';
			printf( '<legend>%s</legend>', esc_html__( 'How will the connection to the mail server be established?', 'post-smtp' ) );
			printf( '<p>%s</p>', esc_html__( 'Your connection settings depend on what your email service provider offers, and what your WordPress host allows.', 'post-smtp' ) );
			printf( '<p id="connectivity_test_status">%s: <span id="port_test_status">%s</span></p>', esc_html__( 'Connectivity Test', 'post-smtp' ), esc_html_x( 'Ready', 'TCP Port Test Status', 'post-smtp' ) );
			printf( '<p class="ajax-loader" style="display:none"><img src="%s"/></p>', esc_url( plugins_url( 'post-smtp/style/ajax-loader.gif' ) ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::TRANSPORT_TYPE ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::PORT ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::SECURITY_TYPE ) );
			printf( '<input type="hidden" id="input_%2$s" name="%1$s[%2$s]">', esc_attr( PostmanOptions::POSTMAN_OPTIONS ), esc_attr( PostmanOptions::AUTHENTICATION_TYPE ) );
			print '<legend id="wizard_recommendation"></legend>';
			/* Translators: Where %1$s is the socket identifier and %2$s is the authentication type */
			printf( '<p class="user_override" style="display:none"><label><span>%s:</span></label> <div id="user_socket_override" class="user_override"></div></p>', esc_html_x( 'Socket', 'A socket is the network term for host and port together', 'post-smtp' ) );
			printf( '<p class="user_override" style="display:none"><label><span>%s:</span></label> <div id="user_auth_override" class="user_override"></div></p>', esc_html__( 'Authentication', 'post-smtp' ) );
			print ('<p><span id="smtp_mitm" style="display:none; background-color:yellow"></span></p>') ;
			$warning = esc_html__( 'Warning', 'post-smtp' );
			$clearCredentialsWarning = esc_html__( 'This configuration option will send your authorization credentials in the clear.', 'post-smtp' );
			printf(
				'<p id="smtp_not_secure" style="display:none"><span style="background-color:yellow">%s: %s</span></p>', 
				esc_html( $warning ),
				esc_html( $clearCredentialsWarning ) 
			);
			print '</fieldset>';

			// Wizard Step 4
			printf( '<h5>%s</h5>', esc_html__( 'Authentication', 'post-smtp' ) );
			print '<fieldset>';
			printf( '<legend>%s</legend>', esc_html__( 'How will you prove your identity to the mail server?', 'post-smtp' ) );
			foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
				$transport->printWizardAuthenticationStep();
			}
			print '</fieldset>';

			// Wizard Step 5 - Notificiations
			printf( '<h5>%s</h5>', esc_html__( 'Notifications', 'post-smtp' ) );
			print '<fieldset>';
			$logs_url = admin_url( 'admin.php?page=postman_email_log' );
			
			$notification_emails = PostmanNotifyOptions::getInstance()->get_notification_email();
			
			?>
			<h2><?php esc_html_e( 'Select notification service', 'post-smtp' ); ?></h2>
			<p><?php printf( esc_html( 'Select a service to notify you when an email delivery will fail. It helps keep track, so you can resend any such emails from the %s if required.', 'post-smtp' ), '<a href="'.$logs_url.'" target="_blank">log section</a>' ) ?></p>
			<div class="ps-notify-radios">
				<div class="ps-notify-radio-outer">
					<div class="ps-notify-radio">
						<input type="radio" value="none" name="postman_options[notification_service]" id="ps-notify-none" class="input_notification_service" />
						<label for="ps-notify-none">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/none.png' ) ?>" />
							<div class="ps-notify-tick-container">
								<div class="ps-notify-tick"><span class="dashicons dashicons-yes"></span></div>
							</div>
						</label>
					</div>
					<h4>Disable</h4>
				</div>
				<div class="ps-notify-radio-outer">
					<div class="ps-notify-radio">
						<input type="radio" value="default" name="postman_options[notification_service]" id="ps-notify-default" class="input_notification_service" />
						<label for="ps-notify-default">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/default.png' ) ?>" />
							<div class="ps-notify-tick-container">
								<div class="ps-notify-tick"><span class="dashicons dashicons-yes"></span></div>
							</div>
						</label>
					</div>
					<h4>Admin Email</h4>
				</div>
				<div class="ps-notify-radio-outer">
					<div class="ps-notify-radio">
						<input type="radio" value="slack" name="postman_options[notification_service]" id="ps-notify-slack" class="input_notification_service" />
						<label for="ps-notify-slack">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/slack.png' ) ?>" />
							<div class="ps-notify-tick-container">
								<div class="ps-notify-tick"><span class="dashicons dashicons-yes"></span></div>
							</div>
						</label>
					</div>
					<h4>Slack</h4>
				</div>
				<div class="ps-notify-radio-outer">
					<div class="ps-notify-radio">
						<input type="radio" value="pushover" name="postman_options[notification_service]" id="ps-notify-pushover" class="input_notification_service" />
						<label for="ps-notify-pushover">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/pushover.png' ) ?>" />
							<div class="ps-notify-tick-container">
								<div class="ps-notify-tick"><span class="dashicons dashicons-yes"></span></div>
							</div>
						</label>
					</div>
					<h4>Pushover</h4>
				</div>
				<?php if( !class_exists( 'PostSMTPTwilio' ) ): ?>
				<a href="https://postmansmtp.com/extensions/twilio-extension-pro/" target="_blank">
					<div class="ps-notify-radio-outer">
						<div class="ps-notify-radio pro-container">
							<label for="ps-notify-twilio-pro">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/pro.png' ) ?>" class="pro-icon" />
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/twilio.png' ) ?>" />
							</label>
						</div>
						<h4>Twilio(SMS)</h4>
					</div>
				</a>
				<?php endif; ?>
			</div>
			<div id="email_notify" style="display: none;">
				<input type="text" name="postman_options[notification_email]" value="<?php echo esc_attr( $notification_emails ); ?>" />
			</div>
			<div id="pushover_cred" style="display: none;">
				<h2><?php esc_html_e( 'Pushover Credentials', 'post-smtp' ); ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Pushover User Key', 'post-smtp' ); ?></th>
							<td>
								<input type="password" id="pushover_user" name="postman_options[pushover_user]" value="">
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Pushover App Token', 'post-smtp' ); ?></th>
							<td>
								<input type="password" id="pushover_token" name="postman_options[pushover_token]" value="">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="slack_cred" style="display: none;">
				<h2><?php esc_html_e( 'Slack Credentials', 'post-smtp' ); ?></h2>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Slack webhook', 'post-smtp' ); ?></th>
						<td>
							<input type="password" id="slack_token" name="postman_options[slack_token]" value="">
							<a target="_blank" class="" href="https://slack.postmansmtp.com/">
								<?php esc_html_e( 'Get your webhook URL here.', 'post-smtp' ); ?>
							</a>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div id="use-chrome-extension" class="ps-use-chrome-extension">
				<h2><?php esc_html_e( 'Setup Chrome extension (optional)', 'post-smtp' ); ?></h2>
				<p><?php _e( 'You can also get notifications in chrome for Post SMTP in case of email delivery failure.', 'post-smtp' ) ?></p>
				<a target="_blank" class="ps-chrome-download" href="https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch">
					<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/logos/chrome-24x24.png' ) ?>" />
					<?php esc_html_e( 'Download Chrome extension', 'post-smtp' ); ?>
				</a>
				<a href="https://postmansmtp.com/post-smtp-1-9-6-new-chrome-extension/" target="_blank"><?php _e( 'Detailed Documentation.', 'post-smtp' ) ?></a>
				<div>
					<table>
						<tr>
							<td>
								<?php _e( 'Enable chrome extension', 'post-smtp' ) ?>
							</td>
							<td>
								<label class="ps-switch-1">
									<input type="checkbox" name="postman_options[notification_use_chrome]" id="notification_use_chrome">
									<span class="slider round"></span>
								</label> 
							</td>
						</tr>
						<tr>
							<td>
								<?php _e( 'Your UID', 'post-smtp' ) ?>
							</td>
							<td>
								<input type="password" id="notification_chrome_uid" name="postman_options[notification_chrome_uid]" value="">
							</td>
						</tr>
					</table>
				</div>
			</div>

			<?php
			print '</fieldset>';

			// Wizard Step 6
			printf( '<h5>%s</h5>', esc_html_x( 'Finish', 'The final step of the Wizard', 'post-smtp' ) );
			print '<fieldset>';
			printf( '<legend>%s</legend>', esc_html_x( 'You\'re Done!', 'Wizard Step Title', 'post-smtp' ) );
			print '<section>';
			printf( '<p>%s</p>', esc_html__( 'Click Finish to save these settings, then:', 'post-smtp' ) );
			print '<ul style="margin-left: 20px">';
			printf( '<li class="wizard-auth-oauth2">%s</li>', esc_html__( 'Grant permission with the Email Provider for Postman to send email and', 'post-smtp' ) );
			printf( '<li>%s</li>', esc_html__( 'Send yourself a Test Email to make sure everything is working!', 'post-smtp' ) );
			print '</ul>';

			// Get PHPmailer recommendation
			Postman::getMailerTypeRecommend();

			$in_wizard = true;

			print '</section>';
			print '</fieldset>';
			print '</form>';
			print '</div>';

		}
		else {

			/**
			 * Fires to load new wizard
			 * 
			 * @since 2.6.2
			 */
			do_action( 'post_smtp_new_wizard' );

		}

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

		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error( 
				array(
					'Message'	=>	'Unauthorized.'
				), 
				401
			);
		}

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
		
		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error( 
				array(
					'Message'	=>	'Unauthorized.'
				), 
				401
			);
		}

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

		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error( 
				array(
					'Message'	=>	'Unauthorized.'
				), 
				401
			);
		}

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
		$last_items = array();

		foreach ( $sockets as $socket ) {

			$overrideItem = $this->createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );
			if ( $overrideItem != null ) {
				
				$transport = PostmanTransportRegistry::getInstance()->getTransport( $socket->transport );

				//If class has constant
				if( defined( get_class( $transport ) . "::PRIORITY" ) ) {

					$priority = $transport::PRIORITY;
					$overrideMenu[$priority] = $overrideItem;

				}
				else {

					$last_items[] = $overrideItem;

				}

			}

		}

		//Sort in DESC order
		krsort( $overrideMenu );
		
		//Start Placing sockets in last, because they don't have there own priority.
		foreach( $last_items as $item ) {

			$overrideMenu[] = $item;

		}
		
		$menu = array();
		foreach ( $overrideMenu as $key ) {
			array_push( $menu, $key );
		}
		
		return $menu;
		
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

		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error( 
				array(
					'Message'	=>	'Unauthorized.'
				), 
				401
			);
		}

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