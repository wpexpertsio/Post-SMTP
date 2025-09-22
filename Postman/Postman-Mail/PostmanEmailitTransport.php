<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

if ( ! class_exists( 'PostmanEmailItTransport' ) ) :
class PostmanEmailItTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG                    = 'emailit_api';
    const PORT                    = 443;
    const HOST                    = 'api.emailit.com'; // Replace with actual EmailIt API host
    const PRIORITY                = 51000;
    const EMAILIT_AUTH_OPTIONS    = 'postman_emailit_auth_options';
    const EMAILIT_AUTH_SECTION    = 'postman_emailit_auth_section';

    public function __construct( $rootPluginFilenameAndPath ) {
        parent::__construct( $rootPluginFilenameAndPath );
        add_action( 'admin_init', array( $this, 'on_admin_init' ) );
    }

    public function getPort() {
        return self::PORT;
    }

    public function getSlug() {
        return self::SLUG;
    }

    public function getProtocol() {
        return 'https';
    }

    public function getHostname() {
        return self::HOST;
    }

    public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {
        $recommendation = array();
        $recommendation['priority']  = 0;
        $recommendation['transport'] = self::SLUG;
        $recommendation['hostname']  = null;
        $recommendation['label']     = $this->getName();
        $recommendation['logo_url']  = $this->getLogoURL();

        if ( $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
            $recommendation['priority'] = self::PRIORITY;
            $recommendation['message'] = sprintf( __( 'Postman recommends the %1$s to host %2$s on port %3$d.' ), $this->getName(), self::HOST, self::PORT );
        }
        return $recommendation;
    }

    public function createMailEngine() {
        $existing_db_version = get_option( 'postman_db_version' );
        $connection_details  = get_option( 'postman_connections' );
        $route_key = get_transient( 'post_smtp_smart_routing_route' );

        if ( $route_key != null ) {
            $api_key = $this->getApiKeyForRoute( $route_key, $connection_details );
        } else {
            $api_key = $this->getApiKeyForDefaultConnection( $existing_db_version, $connection_details );
        }

        require_once 'PostmanEmailitMailEngine.php';
        $engine = new PostmanEmailItMailEngine( $api_key );
        return $engine;
    }

    public function createMailEngineFallback() {
        $connection_details = get_option( 'postman_connections' );
        $fallback           = $this->options->getSelectedFallback();
        $api_key            = isset( $connection_details[ $fallback ]['emailit_api_key'] ) ? $connection_details[ $fallback ]['emailit_api_key'] : '';
        $api_credentials    = array(
            'api_key'     => $api_key,
            'is_fallback' => 1,
        );
        require_once 'PostmanEmailItMailEngine.php';
        $engine = new PostmanEmailItMailEngine( $api_credentials );
        return $engine;
    }

    private function getApiKeyForRoute( $route_key, $connection_details ) {
        if ( isset( $connection_details[ $route_key ] ) ) {
            return $connection_details[ $route_key ]['emailit_api_key'];
        }
        return '';
    }

    private function getApiKeyForDefaultConnection( $existing_db_version, $connection_details ) {
        if ( $existing_db_version !== POST_SMTP_DB_VERSION ) {
            return $this->options->getEmailItApiKey();
        }
        $primary = $this->options->getSelectedPrimary();
        return isset( $connection_details[ $primary ] ) ? $connection_details[ $primary ]['emailit_api_key'] : '';
    }

    public function getName() {
        return __( 'EmailIt', 'post-smtp' );
    }

    public function getDeliveryDetails() {
        return sprintf( __( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ), '🔐', $this->getName() );
    }

    public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
        $overrideItem = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );
        $overrideItem['auth_items'] = array(
            array(
                'selected' => true,
                'name'     => __( 'API Key', 'post-smtp' ),
                'value'    => 'api_key',
            ),
        );
        return $overrideItem;
    }

    public function on_admin_init() {
        if ( PostmanUtils::isAdmin() ) {
            $this->addSettings();
            $this->registerStylesAndScripts();
        }
    }

    public function addSettings() {
        add_settings_section(
            self::EMAILIT_AUTH_SECTION,
            __( 'Authentication', 'post-smtp' ),
            array( $this, 'printEmailItAuthSectionInfo' ),
            self::EMAILIT_AUTH_OPTIONS
        );

        add_settings_field(
            'emailit_api_key',
            __( 'API Key', 'post-smtp' ),
            array( $this, 'emailit_api_key_callback' ),
            self::EMAILIT_AUTH_OPTIONS,
            self::EMAILIT_AUTH_SECTION
        );
    }

    public function printEmailItAuthSectionInfo() {
        printf(
            '<p id="wizard_emailit_auth_help">%s</p>',
            sprintf(
                __( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter your <a href="%3$s" target="_blank">API key</a> below.', 'post-smtp' ),
                'https://www.emailit.com/',
                'emailit.com',
                'https://www.emailit.com/account/api'
            )
        );
    }

    public function emailit_api_key_callback() {
        printf(
            '<input type="password" autocomplete="off" id="emailit_api_key" name="postman_options[emailit_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
            null !== $this->options->getEmailItApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getEmailItApiKey() ) ) : '',
            __( 'Required', 'post-smtp' )
        );
        print ' <input type="button" id="toggleEmailItApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
    }

    public function registerStylesAndScripts() {
        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );
        wp_register_script(
            'postman-emailit',
            plugins_url( 'Postman/Postman-Mail/postman-emailit.js', $this->rootPluginFilenameAndPath ),
            array(
                PostmanViewController::JQUERY_SCRIPT,
                'jquery_validation',
                PostmanViewController::POSTMAN_SCRIPT,
            ),
            $pluginData['version']
        );
    }

    public function enqueueScript() {
        wp_enqueue_script( 'postman-emailit' );
    }

    public function printWizardAuthenticationStep() {
        print '<section class="wizard_emailit">';
        $this->printEmailItAuthSectionInfo();
        printf( '<label for="api_key">%s</label>', __( 'API Key', 'post-smtp' ) );
        print '<br />';
        print $this->emailit_api_key_callback();
        print '</section>';
    }

    public function getLogoURL() {
        return POST_SMTP_ASSETS . 'images/logos/emailit.png';
    }

    public function has_granted() {
        return true;
    }

    protected function validateTransportConfiguration() {
        $postman_db_version = get_option( 'postman_db_version' );
        if ( $postman_db_version != POST_SMTP_DB_VERSION ) {
            $messages = parent::validateTransportConfiguration();
            $apiKey   = $this->options->getEmailItApiKey();
            if ( empty( $apiKey ) ) {
                array_push( $messages, __( 'API Key can not be empty', 'post-smtp' ) . '.' );
                $this->setNotConfiguredAndReady();
            }
            if ( ! $this->isSenderConfigured() ) {
                array_push( $messages, __( 'Message From Address can not be empty', 'post-smtp' ) . '.' );
                $this->setNotConfiguredAndReady();
            }
            return $messages;
        }
    }

    public function prepareOptionsForExport( $data ) {
        $data = parent::prepareOptionsForExport( $data );
        $data['emailit_api_key'] = PostmanOptions::getInstance()->getEmailItApiKey();
        return $data;
    }
}
endif;