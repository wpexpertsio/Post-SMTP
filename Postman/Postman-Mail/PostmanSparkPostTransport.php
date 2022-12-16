<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman SparkPost
 * @since 2.2
 * @version 1.0
 */
if( !class_exists( 'PostmanSparkPostTransport' ) ):
    class PostmanSparkPostTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

        const SLUG = 'sparkpost_api';
        const PORT = 587;
        const HOST = 'smtp.sparkpostmail.com';
        const PRIORITY = 45000;
        const SPARKPOST_AUTH_OPTIONS = 'postman_sparkpost_auth_options';
        const SPARKPOST_AUTH_SECTION = 'postman_sparkpost_auth_section';

        /**
         * PostmanSparkPostTransport constructor.
         * @param $rootPluginFilenameAndPath
         * @since 2.2
         * @version 1.0
         */
        public function __construct( $rootPluginFilenameAndPath ) {

            parent::__construct ( $rootPluginFilenameAndPath );

            // add a hook on the plugins_loaded event
            add_action ( 'admin_init', array ( $this, 'on_admin_init' ) );

        }

        /**
         * @return int
         * @since 2.2
         * @version 1.0
         */
        public function getPort() {
            return self::PORT;
        }

        /**
         * @return string
         * @since 2.2
         * @version 1.0
         */
        public function getSlug() {
            return self::SLUG;
        }

        /**
         * @return string
         * @since 2.2
         * @version 1.0
         */
        public function getProtocol() {
            return 'https';
        }

        /**
         * @return string
         * @since 2.2
         * @version 1.0
         */
        public function getHostname() {
            return self::HOST;
        }

        /**
         * @since 2.2
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

         public function createMailEngine() {

            $api_key = $this->options->getSparkPostApiKey();
            require_once 'PostmanSparkPostMailEngine.php';
            $engine = new PostmanSparkPostMailEngine( $api_key );

            return $engine;

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function getName() {

            return __( 'SparkPost', 'post-smtp' );

        }

        /**
         * @since 2.2
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
         * @since 2.2
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
         * @since 2.2
         * @version 1.0
         */
        public function on_admin_init() {

            if( PostmanUtils::isAdmin() ) {

                $this->addSettings();
                $this->registerStylesAndScripts();

            }

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function addSettings()
        {

            add_settings_section(
                self::SPARKPOST_AUTH_SECTION,
                __('Authentication', 'post-smtp'),
                array( $this, 'printSparkPostAuthSectionInfo' ),
                self::SPARKPOST_AUTH_OPTIONS
            );

            add_settings_field(
                PostmanOptions::SPARKPOST_API_KEY,
                __( 'API Token', 'post-smtp' ),
                array( $this, 'sparkpost_api_key_callback' ),
                self::SPARKPOST_AUTH_OPTIONS,
                self::SPARKPOST_AUTH_SECTION
            );

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function printSparkPostAuthSectionInfo() {

            printf (
                '<p id="wizard_sparkpost_auth_help">%s</p>', sprintf ( __ ( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API Key</a> below.', 'post-smtp' ),
                'https://app.sparkpost.com/join', 'SparkPost', 'https://app.sparkpost.com/account/api-keys' )
            );

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function sparkpost_api_key_callback() {

            printf ( '<input type="password" autocomplete="off" id="sparkpost_api_key" name="postman_options[sparkpost_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSparkPostApiKey() ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getSparkPostApiKey() ) ) : '', __ ( 'Required', 'post-smtp' ) );
            print ' <input type="button" id="toggleSparkPostApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function registerStylesAndScripts() {

            $pluginData = apply_filters( 'postman_get_plugin_metadata', null );

            wp_register_script (
                'postman-sparkpost',
                plugins_url ( 'Postman/Postman-Mail/postman-sparkpost.js', $this->rootPluginFilenameAndPath ),
                array (
                    PostmanViewController::JQUERY_SCRIPT,
                    'jquery_validation',
                    PostmanViewController::POSTMAN_SCRIPT
                ),
                $pluginData['version']
            );

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function enqueueScript() {

            wp_enqueue_script( 'postman-sparkpost' );

        }

        /**
         * @since 2.2
         * @version 1.0
         */
        public function printWizardAuthenticationStep() {
            print '<section class="wizard_sparkpost">';
            $this->printSparkPostAuthSectionInfo();
            printf ( '<label for="api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
            print '<br />';
            print $this->sparkpost_api_key_callback();
            print '
            <div class="sparkpost-documentation">
                <div>
                <iframe width="300" height="200" src="https://www.youtube.com/embed/-uVVGvsAZ_E" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <div>
                    <a href="https://postmansmtp.com/documentation/#configure-sparkpost-integration" target="_blank">Stuck in Setup?</a>
                </div>
            </div>';
            print '</section>';
        }

        /**
         * (non-PHPdoc)
         *
         * @see PostmanTransport::getMisconfigurationMessage()
         * @since 2.2
         * @version 1.0
         */
        protected function validateTransportConfiguration() {
            $messages = parent::validateTransportConfiguration ();
            $apiKey = $this->options->getSparkPostApiKey ();
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
         * Returns true, to prevent from errors because it's default Module Transport.
         * 
         * @since 2.1.8
         * @version 1.0
         */
        public function has_granted() {

            return true;

        }

        /**
         * Get Socket's logo
         * 
         * @since 2.2
         * @version 1.0
         */
        public function getLogoURL() {

            return POST_SMTP_ASSETS . "images/logos/sparkpost.png";

        }

    }

endif;