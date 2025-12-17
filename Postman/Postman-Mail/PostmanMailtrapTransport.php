<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Mailtrap
 * @since 2.9.0
 * @version 1.0
 */
if( !class_exists( 'PostmanMailtrapTransport' ) ):
class PostmanMailtrapTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG = 'mailtrap_api';
    const PORT = 587;
    const HOST = 'send.api.mailtrap.io';
    const PRIORITY = 50000;
    const MAILTRAP_AUTH_OPTIONS = 'postman_mailtrap_auth_options';
    const MAILTRAP_AUTH_SECTION = 'postman_mailtrap_auth_section';

    /**
     * PostmanMailtrapTransport constructor.
     * @param $rootPluginFilenameAndPath
     * @since 2.9.0
     * @version 1.0
     */
    public function __construct( $rootPluginFilenameAndPath ) {

        parent::__construct ( $rootPluginFilenameAndPath );

        // add a hook on the plugins_loaded event
        add_action ( 'admin_init', array ( $this, 'on_admin_init' ) );

    }

    /**
     * @return int
     * @since 2.9.0
     * @version 1.0
     */
    public function getPort() {
        return self::PORT;
    }

    /**
     * @return string
     * @since 2.9.0
     * @version 1.0
     */
    public function getSlug() {
        return self::SLUG;
    }

    /**
     * @return string
     * @since 2.9.0
     * @version 1.0
     */
    public function getProtocol() {
        return 'https';
    }

    /**
     * @return string
     * @since 2.9.0
     * @version 1.0
     */
    public function getHostname() {
        return self::HOST;
    }

    /**
     * @since 2.9.0
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
     * @since 2.9.0
     * @version 1.0
     */
    public function createMailEngine() {

        $api_key = $this->options->getMailtrapApiKey();
        require_once 'PostmanMailtrapMailEngine.php';
		$engine = new PostmanMailtrapMailEngine( $api_key );

		return $engine;

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function getName() {

        return __( 'Mailtrap', 'post-smtp' );

    }

    /**
     * @since 2.9.0
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
     * @since 2.9.0
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
     * @since 2.9.0
     * @version 1.0
     */
    public function on_admin_init() {

        if( PostmanUtils::isAdmin() ) {

            $this->addSettings();
            $this->registerStylesAndScripts();

        }

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function addSettings()
    {

        add_settings_section(
            self::MAILTRAP_AUTH_SECTION,
            __('Authentication', 'post-smtp'),
            array( $this, 'printMailtrapAuthSectionInfo' ),
            self::MAILTRAP_AUTH_OPTIONS
        );

        add_settings_field(
            PostmanOptions::MAILTRAP_API_KEY,
            __( 'API Token', 'post-smtp' ),
            array( $this, 'mailtrap_api_key_callback' ),
            self::MAILTRAP_AUTH_OPTIONS,
            self::MAILTRAP_AUTH_SECTION
        );

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function printMailtrapAuthSectionInfo() {

        printf (
            '<p id="wizard_mailtrap_auth_help">%s</p>', sprintf ( __ ( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API token</a> below.', 'post-smtp' ),
                'https://mailtrap.io/', 'mailtrap.io', 'https://mailtrap.io/api-tokens' )
        );

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function mailtrap_api_key_callback() {

        printf ( '<input type="password" autocomplete="off" id="mailtrap_api_key" name="postman_options[mailtrap_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getMailtrapApiKey() ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getMailtrapApiKey() ) ) : '', __ ( 'Required', 'post-smtp' ) );
        print ' <input type="button" id="toggleMailtrapApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function registerStylesAndScripts() {

        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );

        wp_register_script (
            'postman-mailtrap',
            plugins_url ( 'Postman/Postman-Mail/postman-mailtrap.js', $this->rootPluginFilenameAndPath ),
            array (
                PostmanViewController::JQUERY_SCRIPT,
                'jquery_validation',
                PostmanViewController::POSTMAN_SCRIPT
            ),
            $pluginData['version']
        );

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function enqueueScript() {

        wp_enqueue_script( 'postman-mailtrap' );

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_mailtrap">';
        $this->printMailtrapAuthSectionInfo();
        printf ( '<label for="api_key">%s</label>', __ ( 'API Token', 'post-smtp' ) );
        print '<br />';
        print $this->mailtrap_api_key_callback();
        print '</section>';
    }

    /**
	 * Get Socket's logo
	 * 
	 * @since 2.9.0
	 * @version 1.0
	 */
	public function getLogoURL() {

        return POST_SMTP_ASSETS . "images/logos/mailtrap.png";

	}

    /**
	 * Returns true, to prevent from errors because it's default Module Transport.
	 * 
	 * @since 2.9.0
	 * @version 1.0
	 */
	public function has_granted() {

		return true;

	}

    /**
	 * (non-PHPdoc)
	 *
	 * @see PostmanTransport::getMisconfigurationMessage()
     * @since 2.9.0
     * @version 1.0
	 */
	protected function validateTransportConfiguration() {
		$messages = parent::validateTransportConfiguration ();
		$apiKey = $this->options->getMailtrapApiKey ();
		if (empty ( $apiKey )) {
			array_push ( $messages, __ ( 'API Token can not be empty', 'post-smtp' ) . '.' );
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
     * @since 2.9.0
     * @version 1.0   	
	 */
	public function prepareOptionsForExport($data) {
		$data = parent::prepareOptionsForExport ( $data );
		$data [PostmanOptions::MAILTRAP_API_KEY] = PostmanOptions::getInstance ()->getMailtrapApiKey ();
		return $data;
	}
}
endif;
