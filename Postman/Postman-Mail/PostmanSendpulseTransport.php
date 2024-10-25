<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman Sendpulse
 * @since 2.9.0
 * @version 1.0
 */
if ( ! class_exists( 'PostmanSendpulseTransport' ) ) :
	class PostmanSendpulseTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

		const SLUG = 'sendpulse_api';
		const PORT = 2525;
		const HOST = 'smtp-pulse.com';
		const PRIORITY = 52000;
		const SENDPULSE_AUTH_OPTIONS = 'postman_sendpulse_auth_options';
		const SENDPULSE_AUTH_SECTION = 'postman_sendpulse_auth_section';

		/**
		 * PostmanSendpulseTransport constructor.
		 *
		 * @param $rootPluginFilenameAndPath
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function __construct( $rootPluginFilenameAndPath ) {

			parent::__construct( $rootPluginFilenameAndPath );

			// add a hook on the plugins_loaded event.
			add_action( 'admin_init', array( $this, 'on_admin_init' ) );
		}

		/**
		 *
		 * @return int
		 * @since 2.7
		 * @version 1.0
		 */
		public function getPort() {
			return self::PORT;
		}

		/**
		 *
		 * @return string
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getSlug() {
			return self::SLUG;
		}

		/**
		 *
		 * @return string
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getProtocol() {
			return 'https';
		}

		/**
		 *
		 * @return string
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getHostname() {
			return self::HOST;
		}

		/**
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {

			$recommendation = array();
			$recommendation['priority'] = 0;
			$recommendation['transport'] = self::SLUG;
			$recommendation['hostname'] = null; // scribe looks this.
			$recommendation['label'] = $this->getName();
			$recommendation['logo_url'] = $this->getLogoURL();

			if ( $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
				$recommendation['priority'] = self::PRIORITY;
				/* translators: where variables are (1) transport name (2) host and (3) port */
				$recommendation['message'] = sprintf( __( ( 'Postman recommends the %1$s to host %2$s on port %3$d.' ) ), $this->getName(), self::HOST, self::PORT );
			}

			return $recommendation;
		}

		/**
		 * Creating Mail Engine
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function createMailEngine() {

			$api_key = $this->options->getSendpulseApiKey();
			$secret_key = $this->options->getSendpulseSecretKey();
			require_once 'PostmanSendpulseMailEngine.php';
			$engine = new PostmanSendpulseMailEngine( $api_key, $secret_key );

			return $engine;
		}

		/**
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getName() {

			return __( 'SendPulse', 'post-smtp' );
		}

		/**
		 * 
		 * @since 2.9.0
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
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {

			$overrideItem = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );

			// push the authentication options into the $overrideItem structure.
			$overrideItem['auth_items'] = array(
				array(
					'selected' => true,
					'name' => __( 'API Key', 'post-smtp' ),
					'value' => 'api_key'
				)
			);

			return $overrideItem;
		}

		/**
		 * Action Hook
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function on_admin_init() {

			if ( PostmanUtils::isAdmin() ) {

				$this->addSettings();
				$this->registerStylesAndScripts();
			}
		}

		/**
		 * Add Settings
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function addSettings() {

			add_settings_section(
				self::SENDPULSE_AUTH_SECTION,
				__( 'Authentication', 'post-smtp' ),
				array( $this, 'printSendpulseAuthSectionInfo' ),
				self::SENDPULSE_AUTH_OPTIONS
			);

			add_settings_field(
				PostmanOptions::SENDPULSE_API_KEY,
				__( 'API Key', 'post-smtp' ),
				array( $this, 'sendpulse_api_key_callback' ),
				self::SENDPULSE_AUTH_OPTIONS,
				self::SENDPULSE_AUTH_SECTION
			);

			add_settings_field(
				PostmanOptions::SENDPULSE_SECRET_KEY,
				__( 'Secret Key', 'post-smtp' ),
				array( $this, 'sendpulse_secret_key_callback' ),
				self::SENDPULSE_AUTH_OPTIONS,
				self::SENDPULSE_AUTH_SECTION
			);
		}

		/**
		 * Print Auth Section
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function printSendpulseAuthSectionInfo()
		{

			printf(
				'<p id="wizard_sendpulse_auth_help">%s</p>',
				sprintf(
					__( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key and Secret</a> below.', 'post-smtp' ),
					'https://sendpulse.com/',
					'sendpulse.com',
					'https://login.sendpulse.com/settings/#api'
				)
			);
		}

		/**
		 * API Callback
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function sendpulse_api_key_callback() {

			printf( '<input type="password" autocomplete="off" id="sendpulse_api_key" name="postman_options[sendpulse_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSendpulseApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getSendpulseApiKey() ) ) : '', __( 'Required', 'post-smtp' ) );
			print ' <input type="button" id="toggleSendpulseApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
		}

		/**
		 * Secret Callback
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function sendpulse_secret_key_callback() {

			printf( '<input type="password" autocomplete="off" id="sendpulse_secret_key" name="postman_options[sendpulse_secret_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSendpulseSecretKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getSendpulseSecretKey() ) ) : '', __( 'Required', 'post-smtp' ) );
			print ' <input type="button" id="toggleSendpulseSecretKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
		}

		/**
		 * Register Styles
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function registerStylesAndScripts() {

			$pluginData = apply_filters( 'postman_get_plugin_metadata', null );

			wp_register_script(
				'postman-sendpulse',
				plugins_url( 'Postman/Postman-Mail/postman-sendpulse.js', $this->rootPluginFilenameAndPath ),
				array(
					PostmanViewController::JQUERY_SCRIPT,
					'jquery_validation',
					PostmanViewController::POSTMAN_SCRIPT,
				),
				$pluginData['version']
			);
		}

		/**
		 * Enqueue Script
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function enqueueScript() {

			wp_enqueue_script( 'postman-sendpulse' );
		}

		/**
		 * Ptint Authenitcation Section.
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function printWizardAuthenticationStep() {
			print '<section class="wizard_sendpulse">';
			$this->printSendpulseAuthSectionInfo();
			printf( '<label for="api_key">%s</label>', __( 'API ID', 'post-smtp' ) );
			print '<br />';
			print $this->sendpulse_api_key_callback();
			print '<br />';
			printf( '<label for="secret_key">%s</label>', __( 'API Secret', 'post-smtp' ) );
			print '<br />';
			print $this->sendpulse_secret_key_callback();
			print '</section>';
		}

		/**
		 * Get Socket's logo
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function getLogoURL() {

			return POST_SMTP_ASSETS . 'images/logos/sendpulse.png';
		}

		/**
		 * Returns true, to prevent from errors because it's default Module Transport.
		 *
		 * @since 2.9.0
		 * @version 1.0
		 */
		public function has_granted() {

			return true;
		}

		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanTransport::getMisconfigurationMessage()
		 * @since 2.9.0
		 * @version 1.0
		 */
		protected function validateTransportConfiguration() {
			$messages = parent::validateTransportConfiguration();
			$apiKey = $this->options->getSendpulseApiKey();
			$secretKey = $this->options->getSendpulseSecretKey();
			if ( empty( $apiKey ) ) {
				array_push( $messages, __( 'ID Key can not be empty', 'post-smtp' ) . '.' );
				$this->setNotConfiguredAndReady();
			}
			if ( empty( $secretKey ) ) {
				array_push( $messages, __( 'Secret Key can not be empty', 'post-smtp' ) . '.' );
				$this->setNotConfiguredAndReady();
			}
			if ( ! $this->isSenderConfigured() ) {
				array_push( $messages, __( 'Message From Address can not be empty', 'post-smtp' ) . '.' );
				$this->setNotConfiguredAndReady();
			}
			return $messages;
		}

		/**
		 * Prepare Options for Export
		 *
		 * @param mixed $data // data.
		 * @since 2.7
		 * @version 1.0
		 */
		public function prepareOptionsForExport( $data ) {
			$data = parent::prepareOptionsForExport( $data );
			$data[ PostmanOptions::SENDPULSE_API_KEY ] = PostmanOptions::getInstance()->getSendpulseApiKey();
			$data[ PostmanOptions::SENDPULSE_SECRET_KEY ] = PostmanOptions::getInstance()->getSendpulseSecretKey();
			return $data;
		}
	}
endif;
