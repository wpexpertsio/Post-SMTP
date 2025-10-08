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
    
        /**
         * @since 3.2.0
         * @version 1.0
         */
        public function registerStylesAndScripts() {

            // register the stylesheet and javascript external resources
            $pluginData = apply_filters ( 'postman_get_plugin_metadata', null );
            wp_register_script (
                'postman-maileroo',
                plugins_url ( 'Postman/Postman-Mail/postman-maileroo.js', $this->rootPluginFilenameAndPath ),
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

            wp_enqueue_script( 'postman-maileroo' );

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
        * @since 2.1
        * @version 1.0
        */
        public function createMailEngine() {
            $api_key = $this->options->getMailerooApiKey();
            require_once 'PostmanMailerooMailEngine.php';
            $engine = new PostmanMailerooMailEngine( $api_key );
            return $engine;
        }
}
endif;
