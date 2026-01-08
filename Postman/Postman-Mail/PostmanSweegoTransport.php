<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';
/**
 * Postman Sweego module
 */
class PostmanSweegoTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
    const SLUG = 'sweego_api';
    const PORT = 443;
    const HOST = 'api.sweego.com'; // Change to actual Sweego API host if different
    const PRIORITY = 48011;
    const SWEEGO_AUTH_OPTIONS = 'postman_sweego_auth_options';
    const SWEEGO_AUTH_SECTION = 'postman_sweego_auth_section';

    public function __construct($rootPluginFilenameAndPath) {
        parent::__construct($rootPluginFilenameAndPath);
        add_action('admin_init', array($this, 'on_admin_init'));
    }
    public function getProtocol() {
        return 'https';
    }
    public function getSlug() {
        return self::SLUG;
    }
    public function getName() {
        return __('Sweego', 'post-smtp');
    }
    public function getHostname() {
        return self::HOST;
    }
    public function getPort() {
        return self::PORT;
    }
    public function getTransportType() {
        return 'Sweego_api';
    }
    public function createMailEngine() {
        $apiKey = $this->options->getSweegoApiKey();
        require_once 'PostmanSweegoMailEngine.php';
        $engine = new PostmanSweegoMailEngine($apiKey);
        return $engine;
    }
    public function getDeliveryDetails() {
        return sprintf(__('Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp'), '🔐', $this->getName());
    }
    protected function validateTransportConfiguration() {
        $messages = parent::validateTransportConfiguration();
        $apiKey = $this->options->getSweegoApiKey();
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
    public function getConfigurationBid(PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer) {
        $recommendation = array();
        $recommendation['priority'] = 0;
        $recommendation['transport'] = self::SLUG;
        $recommendation['hostname'] = null;
        $recommendation['label'] = $this->getName();
        $recommendation['logo_url'] = $this->getLogoURL();
        if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
            $recommendation['priority'] = self::PRIORITY;
            $recommendation['message'] = sprintf(__('Postman recommends the %1$s to host %2$s on port %3$d.'), $this->getName(), self::HOST, self::PORT);
        }
        return $recommendation;
    }
    public function on_admin_init() {
        if (PostmanUtils::isAdmin()) {
            $this->addSettings();
            $this->registerStylesAndScripts();
        }
    }
    public function addSettings() {
        add_settings_section(self::SWEEGO_AUTH_SECTION, __('Authentication', 'post-smtp'), array($this, 'printSweegoAuthSectionInfo'), self::SWEEGO_AUTH_OPTIONS);
        add_settings_field('sweego_api_key', __('API Key', 'post-smtp'), array($this, 'sweego_api_key_callback'), self::SWEEGO_AUTH_OPTIONS, self::SWEEGO_AUTH_SECTION);
    }
    public function printSweegoAuthSectionInfo() {
        printf('<p id="wizard_sweego_auth_help">%s</p>', sprintf(__('Enter your Sweego API key and endpoint below.', 'post-smtp')));
    }
    public function sweego_api_key_callback() {
        printf('<input type="password" autocomplete="off" id="sweego_api_key" name="postman_options[sweego_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSweegoApiKey() ? esc_attr(PostmanUtils::obfuscatePassword($this->options->getSweegoApiKey())) : '', __('Required', 'post-smtp'));
        print ' <input type="button" id="toggleSweegoApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
    }
    public function registerStylesAndScripts() {
        // register the stylesheet and javascript external resources
        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );
        wp_register_script(
            'postman-sweego',
            plugins_url( 'Postman/Postman-Mail/postman-sweego.js', $this->rootPluginFilenameAndPath ),
            array(
                PostmanViewController::JQUERY_SCRIPT,
                'jquery_validation',
                PostmanViewController::POSTMAN_SCRIPT
            ),
            $pluginData['version']
        );
    }
    public function enqueueScript() {
        wp_enqueue_script( 'postman-sweego' );
    }
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_sweego">';
        $this->printSweegoAuthSectionInfo();
        printf('<label for="api_key">%s</label>', __('API Key', 'post-smtp'));
        print '<br />';
        print $this->sweego_api_key_callback();
        print '</section>';
    }
    public function getLogoURL() {
        return POST_SMTP_ASSETS . "images/logos/sweego.png";
    }
    public function has_granted() {
        return true;
    }
}
