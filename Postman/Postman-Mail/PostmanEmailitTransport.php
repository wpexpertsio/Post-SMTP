<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';
/**
 * Postman Emailit module
 */
class PostmanEmailitTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
    const SLUG = 'emailit_api';
    const PORT = 443;
    const HOST = 'api.emailit.com'; // Change to actual Emailit API host if different
    const PRIORITY = 48010;
    const EMAILIT_AUTH_OPTIONS = 'postman_emailit_auth_options';
    const EMAILIT_AUTH_SECTION = 'postman_emailit_auth_section';

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
        return __('Emailit API', 'post-smtp');
    }
    public function getHostname() {
        return self::HOST;
    }
    public function getPort() {
        return self::PORT;
    }
    public function getTransportType() {
        return 'Emailit_api';
    }
    public function createMailEngine() {
        $apiKey = $this->options->getEmailitApiKey();
        require_once 'PostmanEmailitMailEngine.php';
        $engine = new PostmanEmailitMailEngine($apiKey);
        return $engine;
    }
    public function getDeliveryDetails() {
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
        add_settings_section(self::EMAILIT_AUTH_SECTION, __('Authentication', 'post-smtp'), array($this, 'printEmailitAuthSectionInfo'), self::EMAILIT_AUTH_OPTIONS);
        add_settings_field('emailit_api_key', __('API Key', 'post-smtp'), array($this, 'emailit_api_key_callback'), self::EMAILIT_AUTH_OPTIONS, self::EMAILIT_AUTH_SECTION);
    }
    public function printEmailitAuthSectionInfo() {
        printf('<p id="wizard_emailit_auth_help">%s</p>', sprintf(__('Enter your Emailit API key and endpoint below.', 'post-smtp')));
    }
    public function emailit_api_key_callback() {
        printf('<input type="password" autocomplete="off" id="emailit_api_key" name="postman_options[emailit_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getEmailitApiKey() ? esc_attr(PostmanUtils::obfuscatePassword($this->options->getEmailitApiKey())) : '', __('Required', 'post-smtp'));
        print ' <input type="button" id="toggleEmailitApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
    }
    public function registerStylesAndScripts() {
        // Add Emailit-specific JS/CSS if needed
    }
    public function enqueueScript() {
        // Enqueue Emailit-specific JS if needed
    }
    public function printWizardAuthenticationStep() {
        print '<section class="wizard_emailit">';
        $this->printEmailitAuthSectionInfo();
        printf('<label for="api_key">%s</label>', __('API Key', 'post-smtp'));
        print '<br />';
        print $this->emailit_api_key_callback();
        print '</section>';
    }
    public function getLogoURL() {
        return POST_SMTP_ASSETS . "images/logos/emailit.png";
    }
    public function has_granted() {
        return true;
    }
}
