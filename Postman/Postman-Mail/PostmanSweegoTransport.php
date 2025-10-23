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
        return __('Sweego API', 'post-smtp');
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
        $existing_db_version = get_option( 'postman_db_version' );
        $connection_details  = get_option( 'postman_connections' );

        // Smart routing support
        $route_key = get_transient( 'post_smtp_smart_routing_route' );
        if ( $route_key != null ) {
            $apiKey = $this->getApiKeyForRoute( $route_key, $connection_details );
        } else {
            $apiKey = $this->getApiKeyForDefaultConnection( $existing_db_version, $connection_details );
        }

        require_once 'PostmanSweegoMailEngine.php';
        $engine = new PostmanSweegoMailEngine( $apiKey );
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
        // Add Sweego-specific JS/CSS if needed
    }
    public function enqueueScript() {
        // Enqueue Sweego-specific JS if needed
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

    /**
     * Create Sweego mail engine for Fallback delivery.
     * @since 3.0.1
     * @version 1.0
     */
    public function createMailEngineFallback() {
        $connection_details = get_option( 'postman_connections' );
        $fallback           = $this->options->getSelectedFallback();
        $api_key            = isset( $connection_details[ $fallback ] ) ? ( $connection_details[ $fallback ]['sweego_api_key'] ?? '' ) : '';
        $api_credentials    = array(
            'api_key'     => $api_key,
            'is_fallback' => 1,
        );

        require_once 'PostmanSweegoMailEngine.php';
        $engine = new PostmanSweegoMailEngine( $api_credentials );
        return $engine;
    }

    private function getApiKeyForRoute( $route_key, $connection_details ) {
        if ( isset( $connection_details[ $route_key ] ) ) {
            return $connection_details[ $route_key ]['sweego_api_key'] ?? '';
        }
        return '';
    }

    private function getApiKeyForDefaultConnection( $existing_db_version, $connection_details ) {
        if ( $existing_db_version !== POST_SMTP_DB_VERSION ) {
            return $this->options->getSweegoApiKey();
        }
        $primary = $this->options->getSelectedPrimary();
        return isset( $connection_details[ $primary ] ) ? ( $connection_details[ $primary ]['sweego_api_key'] ?? '' ) : '';
    }
}
