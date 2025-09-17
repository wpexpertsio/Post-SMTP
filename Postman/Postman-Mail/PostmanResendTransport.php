<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Resend Transport
 * 
 * @since 3.2.0
 * @version 1.0
 */
if( !class_exists( 'PostmanResendTransport' ) ):
class PostmanResendTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG = 'resend_api';
    const PORT = 443;
    const HOST = 'api.resend.com';
    const PRIORITY = 49000;
    const RESEND_AUTH_OPTIONS = 'postman_resend_auth_options';
    const RESEND_AUTH_SECTION = 'postman_resend_auth_section';

    /**
     * PostmanResendTransport constructor.
     * @param $rootPluginFilenameAndPath
     * @since 3.2.0
     * @version 1.0
     */
    public function __construct( $rootPluginFilenameAndPath ) {

        parent::__construct( $rootPluginFilenameAndPath );

        // add a hook on the plugins_loaded event
        add_action( 'admin_init', array( $this, 'on_admin_init' ) );

    }

    public function getProtocol() {
        return 'https';
    }

    // this should be standard across all transports
    public function getSlug() {
        return self::SLUG;
    }

    public function getName() {
        return __( 'Resend API', 'post-smtp' );
    }

    /**
     * v0.2.1
     *
     * @return string
     */
    public function getHostname() {
        return self::HOST;
    }

    /**
     * v0.2.1
     *
     * @return int
     */
    public function getPort() {
        return self::PORT;
    }

    /**
     * v1.7.0
     *
     * @return string
     */
    public function getTransportType() {
        return 'resend_api';
    }

    /**
     * @since 3.2.0
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
     * @since 3.2.0
     * @version 1.0
     */
    public function createMailEngine() {

        $api_key = $this->options->getResendApiKey();
        require_once 'PostmanResendMailEngine.php';
        $engine = new PostmanResendMailEngine( $api_key );

        return $engine;

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function getDeliveryDetails() {
        /* translators: where (1) is the secure icon and (2) is the transport name */
        return sprintf ( __ ( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ), '🔐', $this->getName () );
    }

    /**
     * @param PostmanWizardSocket $socket
     * @param $winningRecommendation
     * @param $userSocketOverride
     * @param $userAuthOverride
     * @return array
     * @since 3.2.0
     * @version 1.0
     */
    public function populateConfiguration( $hostname ) {

        $response = parent::populateConfiguration( $hostname );
        $response [PostmanOptions::TRANSPORT_TYPE] = $this->getSlug();
        $response [PostmanOptions::PORT] = $this->getPort();
        $response [PostmanOptions::HOSTNAME] = $this->getHostname();
        $response [PostmanOptions::SECURITY_TYPE] = PostmanOptions::SECURITY_TYPE_SMTPS;
        $response [PostmanOptions::AUTHENTICATION_TYPE] = PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;

        return $response;

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function on_admin_init() {
        $this->addSettings();
        $this->registerStylesAndScripts();
    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function addSettings()
    {

        add_settings_section(
            self::RESEND_AUTH_SECTION,
            __('Authentication', 'post-smtp'),
            array( $this, 'printResendAuthSectionInfo' ),
            self::RESEND_AUTH_OPTIONS
        );

        add_settings_field(
            PostmanOptions::RESEND_API_KEY,
            __( 'API Key', 'post-smtp' ),
            array( $this, 'resend_api_key_callback' ),
            self::RESEND_AUTH_OPTIONS,
            self::RESEND_AUTH_SECTION
        );

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function printResendAuthSectionInfo() {

        printf (
            '<p id="wizard_resend_auth_help">%s</p>', sprintf ( __ ( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ),
                'https://resend.com/', 'resend.com', 'https://resend.com/api-keys' )
        );

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function resend_api_key_callback() {

        printf(
            '<input type="password" autocomplete="off" id="resend_api_key" name="postman_options[resend_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
            null !== $this->options->getResendApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getResendApiKey() ) ) : '',
            __( 'Required', 'post-smtp' )
        );
        print ' <input type="button" id="toggleResendApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function registerStylesAndScripts() {

        // register the stylesheet and javascript external resources
        $pluginData = apply_filters ( 'postman_get_plugin_metadata', null );
        wp_register_script (
            'postman-resend',
            plugins_url ( 'Postman/Postman-Mail/postman-resend.js', $this->rootPluginFilenameAndPath ),
            array (
                PostmanViewController::JQUERY_SCRIPT,
                'jquery_validation',
                PostmanViewController::POSTMAN_SCRIPT
            ),
            $pluginData['version']
        );

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function enqueueScript() {

        wp_enqueue_script( 'postman-resend' );

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_resend">';
        $this->printResendAuthSectionInfo();
        printf ( '<label for="api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
        print '<br />';
        print $this->resend_api_key_callback();
        print '</section>';
    }

    /**
     * Get Resend's logo
     * 
     * @since 3.2.0
     * @version 1.0
     */
    public function getLogoURL() {

        return POST_SMTP_ASSETS . "images/logos/resend.png";

    }

    /**
     * Returns true, to prevent from errors because it's default Module Transport.
     * 
     * @since 3.2.0
     * @version 1.0
     */
    public function has_granted() {

        return true;

    }

    /**
     * (non-PHPdoc)
     *
     * @see PostmanTransport::getMisconfigurationMessage()
     * @since 3.2.0
     * @version 1.0
     */
    protected function validateTransportConfiguration() {
        $messages = parent::validateTransportConfiguration ();
        $apiKey = $this->options->getResendApiKey ();
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
     * @since 3.2.0
     * @version 1.0   	
     */
    public function prepareOptionsForExport($data) {
        $data = parent::prepareOptionsForExport ( $data );
        $data [PostmanOptions::RESEND_API_KEY] = PostmanOptions::getInstance ()->getResendApiKey ();
        return $data;
    }
}
endif;
