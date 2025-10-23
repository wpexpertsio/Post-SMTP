<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Maileroo
 * @since 2.1
 * @version 1.0
 */
if( !class_exists( 'PostmanMailerooTransport' ) ):
class PostmanMailerooTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG = 'maileroo_api';
    const PORT = 587;
    const HOST = 'smtp.maileroo.com';
    const PRIORITY = 50000;
    const MAILEROO_AUTH_OPTIONS = 'postman_maileroo_auth_options';
    const MAILEROO_AUTH_SECTION = 'postman_maileroo_auth_section';

    public function __construct( $rootPluginFilenameAndPath ) {
        parent::__construct ( $rootPluginFilenameAndPath );
        add_action ( 'admin_init', array ( $this, 'on_admin_init' ) );
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

        public function getName() {
            return __('Maileroo API', 'post-smtp');
        }

        public function getHostname() {
            return self::HOST;
        }

        public function getTransportType() {
            return 'Maileroo_api';
        }

        public function getDeliveryDetails() {
            return sprintf(__('Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp'), '🔐', $this->getName());
        }

        protected function validateTransportConfiguration() {
            $messages = parent::validateTransportConfiguration();
            $apiKey = $this->options->getMailerooApiKey();
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
            add_settings_section(self::MAILEROO_AUTH_SECTION, __('Authentication', 'post-smtp'), array($this, 'printMailerooAuthSectionInfo'), self::MAILEROO_AUTH_OPTIONS);
            add_settings_field('maileroo_api_key', __('API Key', 'post-smtp'), array($this, 'maileroo_api_key_callback'), self::MAILEROO_AUTH_OPTIONS, self::MAILEROO_AUTH_SECTION);
        }

        public function printMailerooAuthSectionInfo() {
            printf('<p id="wizard_maileroo_auth_help">%s</p>', sprintf(__('Enter your Maileroo API key and endpoint below.', 'post-smtp')));
        }

        public function maileroo_api_key_callback() {
            printf('<input type="password" autocomplete="off" id="maileroo_api_key" name="postman_options[maileroo_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getMailerooApiKey() ? esc_attr(PostmanUtils::obfuscatePassword($this->options->getMailerooApiKey())) : '', __('Required', 'post-smtp'));
            print ' <input type="button" id="toggleMailerooApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
        }

        public function registerStylesAndScripts() {
            // Add Maileroo-specific JS/CSS if needed
        }

        public function enqueueScript() {
            // Enqueue Maileroo-specific JS if needed
        }

        public function printWizardAuthenticationStep() {
            print '<section class="wizard_maileroo">';
            $this->printMailerooAuthSectionInfo();
            printf('<label for="api_key">%s</label>', __('API Key', 'post-smtp'));
            print '<br />';
            print $this->maileroo_api_key_callback();
            print '</section>';
        }

        public function getLogoURL() {
            return POST_SMTP_ASSETS . "images/logos/maileroo.png";
        }

        public function has_granted() {
            return true;
        }
        public function getHost() {
            return self::HOST;
        }

        public function getPriority() {
            return self::PRIORITY;
        }

        public function getAuthOptionsName() {
            return self::MAILEROO_AUTH_OPTIONS;
        }

        public function getAuthSectionName() {
            return self::MAILEROO_AUTH_SECTION;
        }

        /**
         * Create Maileroo mail engine instance
         * Adds smart routing and connection-aware key resolution.
         * @since 2.1
         * @version 1.1
         */
        public function createMailEngine() {
            $existing_db_version = get_option( 'postman_db_version' );
            $connection_details  = get_option( 'postman_connections' );

            // Check if a transient for smart routing is set
            $route_key = get_transient( 'post_smtp_smart_routing_route' );

            if ( $route_key != null ) {
                // Smart routing: use the connection associated with the route_key
                $api_key = $this->getApiKeyForRoute( $route_key, $connection_details );
            } else {
                // Default selection: primary or legacy option
                $api_key = $this->getApiKeyForDefaultConnection( $existing_db_version, $connection_details );
            }

            require_once 'PostmanMailerooMailEngine.php';
            $engine = new PostmanMailerooMailEngine( $api_key );
            return $engine;
        }

        /**
         * Create Maileroo mail engine for Fallback delivery.
         * Passes a structured credentials array and flags fallback mode.
         * @since 3.0.1
         * @version 1.0
         */
        public function createMailEngineFallback() {
            $connection_details = get_option( 'postman_connections' );
            $fallback           = $this->options->getSelectedFallback();
            $api_key            = isset( $connection_details[ $fallback ] ) ? ( $connection_details[ $fallback ]['maileroo_api_key'] ?? '' ) : '';
            $api_credentials    = array(
                'api_key'     => $api_key,
                'is_fallback' => 1,
            );

            require_once 'PostmanMailerooMailEngine.php';
            $engine = new PostmanMailerooMailEngine( $api_credentials );
            return $engine;
        }

        /**
         * Retrieves the API key for a specific route (smart routing).
         * @since 3.0.1
         * @version 1.0
         * @param string $route_key
         * @param array  $connection_details
         * @return string
         */
        private function getApiKeyForRoute( $route_key, $connection_details ) {
            if ( isset( $connection_details[ $route_key ] ) ) {
                return $connection_details[ $route_key ]['maileroo_api_key'] ?? '';
            }
            return '';
        }

        /**
         * Retrieves the API key for default connection selection.
         * Uses legacy option when DB is legacy, otherwise primary connection.
         * @since 3.0.1
         * @version 1.0
         * @param string $existing_db_version
         * @param array  $connection_details
         * @return string
         */
        private function getApiKeyForDefaultConnection( $existing_db_version, $connection_details ) {
            if ( $existing_db_version !== POST_SMTP_DB_VERSION ) {
                // Legacy storage
                return $this->options->getMailerooApiKey();
            }
            // New connection system - use primary connection
            $primary = $this->options->getSelectedPrimary();
            return isset( $connection_details[ $primary ] ) ? ( $connection_details[ $primary ]['maileroo_api_key'] ?? '' ) : '';
        }
}
endif;
