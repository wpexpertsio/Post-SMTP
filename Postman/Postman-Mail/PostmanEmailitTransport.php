<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Emailit Transport
 * 
 * @since 3.2.0
 * @version 1.0
 */
if( !class_exists( 'PostmanEmailitTransport' ) ):
class PostmanEmailitTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
    const SLUG = 'emailit_api';
    const PORT = 443;
    const HOST = 'api.emailit.com'; // Change to actual Emailit API host if different
    const PRIORITY = 48010;
    const EMAILIT_AUTH_OPTIONS = 'postman_emailit_auth_options';
    const EMAILIT_AUTH_SECTION = 'postman_emailit_auth_section';

    /**
     * PostmanEmailitTransport constructor.
     * @param $rootPluginFilenameAndPath
     * @since 3.2.0
     * @version 1.0
     */
    public function __construct($rootPluginFilenameAndPath) {
        parent::__construct($rootPluginFilenameAndPath);
        add_action('admin_init', array($this, 'on_admin_init'));
    }
    public function getProtocol() {
        return 'https';
    }

    // this should be standard across all transports
    public function getSlug() {
        return self::SLUG;
    }

    public function getName() {
        return __('Emailit API', 'post-smtp');
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
        return 'Emailit_api';
    }
    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function createMailEngine() {
        $apiKey = $this->options->getEmailitApiKey();
        require_once 'PostmanEmailitMailEngine.php';
        $engine = new PostmanEmailitMailEngine($apiKey);
        return $engine;
    }
    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function getDeliveryDetails() {
        /* translators: where (1) is the secure icon and (2) is the transport name */
        return sprintf(__('Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp'), '🔐', $this->getName());
    }
    protected function validateTransportConfiguration() {
        $messages = parent::validateTransportConfiguration();
        $apiKey = $this->options->getEmailitApiKey();
        if (empty($apiKey)) {
            array_push($messages, __('API Key can not be empty', 'post-smtp') . '.');
            $this->setNotConfiguredAndReady();
        }
        if (!$this->isSenderConfigured()) {
            array_push($messages, __('Message From Address can not be empty', 'post-smtp') . '.');
            $this->setNotConfiguredAndReady();
        }
        return $messages;
    }
    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function getConfigurationBid(PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer) {
        $recommendation = array();
        $recommendation['priority'] = 0;
        $recommendation['transport'] = self::SLUG;
        $recommendation['hostname'] = null; // scribe looks this
        $recommendation['label'] = $this->getName();
        $recommendation['logo_url'] = $this->getLogoURL();
        if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
            $recommendation['priority'] = self::PRIORITY;
            /* translators: where variables are (1) transport name (2) host and (3) port */
            $recommendation['message'] = sprintf(__('Postman recommends the %1$s to host %2$s on port %3$d.'), $this->getName(), self::HOST, self::PORT);
        }
        return $recommendation;
    }
    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function on_admin_init() {
        if (PostmanUtils::isAdmin()) {
            $this->addSettings();
            $this->registerStylesAndScripts();
        }
    }
    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function addSettings() {
        add_settings_section( self::EMAILIT_AUTH_SECTION, __('Authentication', 'post-smtp'), array($this, 'printEmailitAuthSectionInfo'), self::EMAILIT_AUTH_OPTIONS );
        add_settings_field( 'emailit_api_key', __('API Key', 'post-smtp'), array($this, 'emailit_api_key_callback'), self::EMAILIT_AUTH_OPTIONS, self::EMAILIT_AUTH_SECTION );
    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function printEmailitAuthSectionInfo() {
        printf(
            '<p id="wizard_emailit_auth_help">%s</p>',
            sprintf(
                __(
                    'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">your API key</a> and endpoint below.',
                    'post-smtp'
                ),
                'https://emailit.com/', 'emailit.com', 'https://emailit.com/'
            )
        );
    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function emailit_api_key_callback() {
        printf('<input type="password" autocomplete="off" id="emailit_api_key" name="postman_options[emailit_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getEmailitApiKey() ? esc_attr(PostmanUtils::obfuscatePassword($this->options->getEmailitApiKey())) : '', __('Required', 'post-smtp'));
        print ' <input type="button" id="toggleEmailitApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
    }

     /**
     * @since 3.2.0
     * @version 1.0
     */
    public function registerStylesAndScripts() {

        // register the stylesheet and javascript external resources
        $pluginData = apply_filters ( 'postman_get_plugin_metadata', null );
        wp_register_script (
            'postman-emailit',
            plugins_url ( 'Postman/Postman-Mail/postman-emailit.js', $this->rootPluginFilenameAndPath ),
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

        wp_enqueue_script( 'postman-emailit' );

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_emailit">';
        $this->printEmailitAuthSectionInfo();
        printf('<label for="api_key">%s</label>', __('API Key', 'post-smtp'));
        print '<br />';
        $this->emailit_api_key_callback();
        print '</section>';
    }

    /**
     * Get Socket's logo
     * 
     * @since 3.2.0
     * @version 1.0
     */
    public function getLogoURL() {
        return POST_SMTP_ASSETS . "images/logos/emailit.png";
    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function has_granted() {
        return true;
    }
}
endif;
