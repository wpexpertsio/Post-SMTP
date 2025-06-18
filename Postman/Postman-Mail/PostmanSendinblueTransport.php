<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Sendinblue
 *
 * @since 2.1
 * @version 1.0
 */
if ( ! class_exists( 'PostmanSendinblueTransport' ) ) :
	class PostmanSendinblueTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

		const SLUG                    = 'sendinblue_api';
		const PORT                    = 587;
		const HOST                    = 'smtp-relay.brevo.com';
		const PRIORITY                = 50000;
		const SENDINBLUE_AUTH_OPTIONS = 'postman_sendinblue_auth_options';
		const SENDINBLUE_AUTH_SECTION = 'postman_sendinblue_auth_section';

		/**
		 * PostmanSendinblueTransport constructor.
		 *
		 * @param $rootPluginFilenameAndPath
		 * @since 2.1
		 * @version 1.0
		 */
		public function __construct( $rootPluginFilenameAndPath ) {

			parent::__construct( $rootPluginFilenameAndPath );

			// add a hook on the plugins_loaded event
			add_action( 'admin_init', array( $this, 'on_admin_init' ) );
		}

		/**
		 * @return int
		 * @since 2.1
		 * @version 1.0
		 */
		public function getPort() {
			return self::PORT;
		}

		/**
		 * @return string
		 * @since 2.1
		 * @version 1.0
		 */
		public function getSlug() {
			return self::SLUG;
		}

		/**
		 * @return string
		 * @since 2.1
		 * @version 1.0
		 */
		public function getProtocol() {
			return 'https';
		}

		/**
		 * @return string
		 * @since 2.1
		 * @version 1.0
		 */
		public function getHostname() {
			return self::HOST;
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {

			$recommendation               = array();
			$recommendation ['priority']  = 0;
			$recommendation ['transport'] = self::SLUG;
			$recommendation ['hostname']  = null; // scribe looks this
			$recommendation ['label']     = $this->getName();
			$recommendation['logo_url']   = $this->getLogoURL();

			if ( $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
				$recommendation ['priority'] = self::PRIORITY;
				/* translators: where variables are (1) transport name (2) host and (3) port */
				$recommendation ['message'] = sprintf( __( ( 'Postman recommends the %1$s to host %2$s on port %3$d.' ) ), $this->getName(), self::HOST, self::PORT );
			}

			return $recommendation;
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function createMailEngine() {

			$existing_db_version = get_option( 'postman_db_version' );
			$connection_details  = get_option( 'postman_connections' );
			$route_key = null;
			// Check if a transient for smart routing is set
    		$route_key = get_transient( 'post_smtp_smart_routing_route' );
	
			if ( $route_key != null ) {
				// Smart routing is enabled, use the connection associated with the route_key.
				$api_key = $this->getApiKeyForRoute( $route_key, $connection_details );
			} else {
				// No smart routing, proceed with default connection selection.
				$api_key = $this->getApiKeyForDefaultConnection( $existing_db_version, $connection_details );
			}

			require_once 'PostmanSendinblueMailEngine.php';
			$engine = new PostmanSendinblueMailEngine( $api_key );

			return $engine;
		}

		/**
		 * @since 3.0.1
		 * @version 1.0
		 */
		public function createMailEngineFallback() {

			$connection_details = get_option( 'postman_connections' );
			$fallback           = $this->options->getSelectedFallback();
			$api_key            = $connection_details[ $fallback ]['sendinblue_api_key'];
			$api_credentials    = array(
				'api_key'     => $api_key,
				'is_fallback' => 1,
			);
			require_once 'PostmanSendinblueMailEngine.php';
			$engine = new PostmanSendinblueMailEngine( $api_credentials );

			return $engine;
		}


		/**
		 * Retrieves the API key for a specific route.
		 *
		 * @since 3.0.1
		 * @version 1.0
		 *
		 * @param string $route_key The route key used for smart routing.
		 * @param array  $connection_details The array of connection details.
		 *
		 * @return string The API key for the specified route.
		 */
		private function getApiKeyForRoute( $route_key, $connection_details ) {
			// Ensure the route exists in the connection details and return the corresponding API key.
			if ( isset( $connection_details[ $route_key ] ) ) {
				return $connection_details[ $route_key ]['sendinblue_api_key'];
			}

		}

		/**
		 * Retrieves the API key for the default connection selection.
		 *
		 * @since  3.0.1
		 * @version 1.0
		 *
		 * @param string $existing_db_version The existing database version.
		 * @param array  $connection_details The array of connection details.
		 *
		 * @return string The API key for the default connection.
		 */
		private function getApiKeyForDefaultConnection( $existing_db_version, $connection_details ) {
			// Check if the database version is different to decide which connection to use.
			if ( $existing_db_version !== POST_SMTP_DB_VERSION ) {
				return $this->options->getSendinblueApiKey();
			}

			// Use the API key of the primary connection.
			$primary = $this->options->getSelectedPrimary();
			return isset( $connection_details[ $primary ] ) ? $connection_details[ $primary ]['sendinblue_api_key'] : ''; 
		}


		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function getName() {

			return __( 'Brevo', 'post-smtp' );
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function getDeliveryDetails() {
		}

		/**
		 * @param PostmanWizardSocket   $socket
		 * @param $winningRecommendation
		 * @param $userSocketOverride
		 * @param $userAuthOverride
		 * @return array
		 * @since 2.1
		 * @version 1.0
		 */
		public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {

			$overrideItem = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );

			// push the authentication options into the $overrideItem structure
			$overrideItem ['auth_items'] = array(
				array(
					'selected' => true,
					'name'     => __( 'API Key', 'post-smtp' ),
					'value'    => 'api_key',
				),
			);

			return $overrideItem;
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function on_admin_init() {

			if ( PostmanUtils::isAdmin() ) {

				$this->addSettings();
				$this->registerStylesAndScripts();

			}
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function addSettings() {

			add_settings_section(
				self::SENDINBLUE_AUTH_SECTION,
				__( 'Authentication', 'post-smtp' ),
				array( $this, 'printSendinblueAuthSectionInfo' ),
				self::SENDINBLUE_AUTH_OPTIONS
			);

			add_settings_field(
				PostmanOptions::SENDINBLUE_API_KEY,
				__( 'API Key', 'post-smtp' ),
				array( $this, 'sendinblue_api_key_callback' ),
				self::SENDINBLUE_AUTH_OPTIONS,
				self::SENDINBLUE_AUTH_SECTION
			);
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function printSendinblueAuthSectionInfo() {

			printf(
				'<p id="wizard_sendinblue_auth_help">%s</p>',
				sprintf(
					__( 'Create an account at <a href="%1$s" target="_blank">%2$s (formely Sendinblue)</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ),
					'https://www.brevo.com/',
					'brevo.com',
					'https://account.brevo.com/advanced/api'
				)
			);
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function sendinblue_api_key_callback() {

			printf( '<input type="password" autocomplete="off" id="sendinblue_api_key" name="postman_options[sendinblue_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSendinblueApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getSendinblueApiKey() ) ) : '', __( 'Required', 'post-smtp' ) );
			print ' <input type="button" id="toggleSendinblueApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function registerStylesAndScripts() {

			$pluginData = apply_filters( 'postman_get_plugin_metadata', null );

			wp_register_script(
				'postman-sendinblue',
				plugins_url( 'Postman/Postman-Mail/postman-sendinblue.js', $this->rootPluginFilenameAndPath ),
				array(
					PostmanViewController::JQUERY_SCRIPT,
					'jquery_validation',
					PostmanViewController::POSTMAN_SCRIPT,
				),
				$pluginData['version']
			);
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function enqueueScript() {

			wp_enqueue_script( 'postman-sendinblue' );
		}

		/**
		 * @since 2.1
		 * @version 1.0
		 */
		public function printWizardAuthenticationStep() {
			print '<section class="wizard_sendinblue">';
			$this->printSendinblueAuthSectionInfo();
			printf( '<label for="api_key">%s</label>', __( 'API Key', 'post-smtp' ) );
			print '<br />';
			print $this->sendinblue_api_key_callback();
			print '</section>';
		}

		/**
		 * Get Socket's logo
		 *
		 * @since 2.1
		 * @version 1.0
		 */
		public function getLogoURL() {

			return POST_SMTP_ASSETS . 'images/logos/sendinblue.png';
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
		 * (non-PHPdoc)
		 *
		 * @see PostmanTransport::getMisconfigurationMessage()
		 * @since 2.1.8
		 * @version 1.0
		 */
		protected function validateTransportConfiguration() {
			$postman_db_version = get_option( 'postman_db_version' );
			if ( $postman_db_version != POST_SMTP_DB_VERSION ) {
				$messages = parent::validateTransportConfiguration();
				$apiKey   = $this->options->getSendinblueApiKey();
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

		/**
		 *
		 * @param mixed $data
		 * @since 2.1.8
		 * @version 1.0
		 */
		public function prepareOptionsForExport( $data ) {
			$data                                        = parent::prepareOptionsForExport( $data );
			$data [ PostmanOptions::SENDINBLUE_API_KEY ] = PostmanOptions::getInstance()->getSendinblueApiKey();
			return $data;
		}
	}
endif;
