<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Mailjet
 * @since 2.7
 * @version 1.0
 */
if( !class_exists( 'PostmanMailjetTransport' ) ):
class PostmanMailjetTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG = 'mailjet_api';
    const PORT = 587;
    const HOST = 'in-v3.mailjet.com';
    const PRIORITY = 52000;
    const MAILJET_AUTH_OPTIONS = 'postman_mailjet_auth_options';
    const MAILJET_AUTH_SECTION = 'postman_mailjet_auth_section';

    /**
     * PostmanMailjetTransport constructor.
     * @param $rootPluginFilenameAndPath
     * @since 2.7
     * @version 1.0
     */
    public function __construct( $rootPluginFilenameAndPath ) {

        parent::__construct ( $rootPluginFilenameAndPath );

        // add a hook on the plugins_loaded event
        add_action ( 'admin_init', array ( $this, 'on_admin_init' ) );

    }

    /**
     * @return int
     * @since 2.7
     * @version 1.0
     */
    public function getPort() {
        return self::PORT;
    }

    /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getSlug() {
        return self::SLUG;
    }

    /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getProtocol() {
        return 'https';
    }

    /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getHostname() {
        return self::HOST;
    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {

        $recommendation = array();
		$recommendation ['priority'] = 0;
		$recommendation ['transport'] = self::SLUG;
		$recommendation ['hostname'] = null; // scribe looks this
		$recommendation ['label'] = $this->getName();
        $recommendation['logo_url'] = $this->getLogoURL();
        
		if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
			$recommendation ['priority'] = self::PRIORITY;
			/* translators: where variables are (1) transport name (2) host and (3) port */
			$recommendation ['message'] = sprintf ( __ ( ('Postman recommends the %1$s to host %2$s on port %3$d.') ), $this->getName(), self::HOST, self::PORT );
		}

		return $recommendation;

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function createMailEngine() {

        $api_key = $this->options->getMailjetApiKey();
        $secret_key = $this->options->getMailjetSecretKey();
        require_once 'PostmanMailjetMailEngine.php';
		$engine = new PostmanMailjetMailEngine( $api_key,$secret_key );

		return $engine;

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function getName() {

        return __( 'Mailjet', 'post-smtp' );

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function getDeliveryDetails() {



    }

    /**
     * @param PostmanWizardSocket $socket
     * @param $winningRecommendation
     * @param $userSocketOverride
     * @param $userAuthOverride
     * @return array
     * @since 2.7
     * @version 1.0
     */
    public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {

        $overrideItem = parent::createOverrideMenu ( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );

        // push the authentication options into the $overrideItem structure
        $overrideItem ['auth_items'] = array (
            array (
                'selected' => true,
                'name' => __ ( 'API Key', 'post-smtp' ),
                'value' => 'api_key'
            )
        );

        return $overrideItem;

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function on_admin_init() {

        if( PostmanUtils::isAdmin() ) {

            $this->addSettings();
            $this->registerStylesAndScripts();

        }

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function addSettings()
    {

        add_settings_section(
            self::MAILJET_AUTH_SECTION,
            __('Authentication', 'post-smtp'),
            array( $this, 'printMailjetAuthSectionInfo' ),
            self::MAILJET_AUTH_OPTIONS
        );

        add_settings_field(
            PostmanOptions::MAILJET_API_KEY,
            __( 'API Key', 'post-smtp' ),
            array( $this, 'mailjet_api_key_callback' ),
            self::MAILJET_AUTH_OPTIONS,
            self::MAILJET_AUTH_SECTION
        );

        add_settings_field(
            PostmanOptions::MAILJET_SECRET_KEY,
            __( 'Secret Key', 'post-smtp' ),
            array( $this, 'mailjet_secret_key_callback' ),
            self::MAILJET_AUTH_OPTIONS,
            self::MAILJET_AUTH_SECTION
        );

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function printMailjetAuthSectionInfo() {

        printf (
            '<p id="wizard_mailjet_auth_help">%s</p>', sprintf ( __ ( 'Create an account at <a href="%1$s" target="_blank">%2$s </a> and enter <a href="%3$s" target="_blank">an API key and Secret Key</a> below.', 'post-smtp' ),
            'https://app.mailjet.com', 'mailjet.com', 'https://app.mailjet.com/account/apikeys' )
        );

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function mailjet_api_key_callback() {

        printf ( '<input type="password" autocomplete="off" id="mailjet_api_key" name="postman_options[mailjet_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getMailjetApiKey() ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getMailjetApiKey()) ) : '', __ ( 'Required', 'post-smtp' ) );
        print ' <input type="button" id="toggleMailjetApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function mailjet_secret_key_callback(){

        printf ( '<input type="password" autocomplete="off" id="mailjet_secret_key" name="postman_options[mailjet_secret_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getMailjetSecretKey() ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getMailjetSecretKey()) ) : '', __ ( 'Required', 'post-smtp' ) );
        print ' <input type="button" id="toggleMailjetSecretKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
    
    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function registerStylesAndScripts() {

        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );

        wp_register_script (
            'postman-mailjet',
            plugins_url ( 'Postman/Postman-Mail/postman-mailjet.js', $this->rootPluginFilenameAndPath ),
            array (
                PostmanViewController::JQUERY_SCRIPT,
                'jquery_validation',
                PostmanViewController::POSTMAN_SCRIPT
            ),
            $pluginData['version']
        );

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function enqueueScript() {

        wp_enqueue_script( 'postman-mailjet' );

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_mailjet">';
        $this->printMailjetAuthSectionInfo();
        printf ( '<label for="api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
        print '<br />';
        print $this->mailjet_api_key_callback();
        printf ( '<label for="secret_key">%s</label>', __ ( 'Secret Key', 'post-smtp' ) );
        print '<br />';
        print $this->mailjet_secret_key_callback();
        print '</section>';
    }

    /**
	 * Get Socket's logo
	 * 
	 * @since 2.7
	 * @version 1.0
	 */
	public function getLogoURL() {

        return POST_SMTP_ASSETS . "images/logos/Mailjet.png";

	}

    /**
	 * Returns true, to prevent from errors because it's default Module Transport.
	 * 
	 * @since 2.7.8
	 * @version 1.0
	 */
	public function has_granted() {

		return true;

	}

    /**
	 * (non-PHPdoc)
	 *
	 * @see PostmanTransport::getMisconfigurationMessage()
     * @since 2.7.8
     * @version 1.0
	 */
	protected function validateTransportConfiguration() {
		$messages = parent::validateTransportConfiguration ();
		$apiKey = $this->options->getMailjetApiKey ();
		if (empty ( $apiKey )) {
			array_push ( $messages, __ ( 'API Key can not be empty', 'post-smtp' ) . '.' );
			$this->setNotConfiguredAndReady ();
		}
		if (! $this->isSenderConfigured ()) {
			array_push ( $messages, __ ( 'Message From Address can not be empty', 'post-smtp' ) . '.' );
			$this->setNotConfiguredAndReady ();
		}
		return $messages;
	}

    /**
	 *
	 * @param mixed $data     
     * @since 2.7.8
     * @version 1.0   	
	 */
	public function prepareOptionsForExport($data) {
		$data = parent::prepareOptionsForExport ( $data );
		$data [PostmanOptions::MAILJET_API_KEY] = PostmanOptions::getInstance ()->getMailjetApiKey ();
        $data [PostmanOptions::MAILJET_SECRET_KEY] = PostmanOptions::getInstance ()->getMailjetSecretKey ();
		return $data;
	}
}
endif;