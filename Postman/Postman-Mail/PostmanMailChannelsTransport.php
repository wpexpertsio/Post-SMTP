<?php
/**
 * Extension: MailChannels
 * Type: Transport
 *
 * @package Postman SMTP
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PostmanMailChannelsTransport' ) ) {
	class PostmanMailChannelsTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
		const SLUG = 'mailchannels_api';
		const PORT = 0;
		const HOST = 'api.mailchannels.net';
		const PRIORITY = 49000;
		const MAILCHANNELS_AUTH_OPTIONS = 'postman_mailchannels_auth_options';
		const MAILCHANNELS_AUTH_SECTION = 'postman_mailchannels_auth_section';

		public function __construct( $params ) {
			parent::__construct( $params );

			add_action( 'admin_init', array( $this, 'mailchannels_admin_init' ) );
		}

		public function getProtocol() {
			return 'https';
		}

		public function getSlug() {
			return self::SLUG;
		}

		public function getName() {
			return __( 'MailChannels', 'post-smtp' );
		}

		public function getHostname() {
			return self::HOST;
		}

		public function getPort() {
			return self::PORT;
		}

		public function getTransportType() {
			return self::SLUG;
		}

		public function createMailEngine() {
			$apiKey = $this->options->getMailChannelsApiKey();
			require_once __DIR__ . '/PostmanMailChannelsMailEngine.php';
			$engine = new PostmanMailChannelsMailEngine( $apiKey );

			return $engine;
		}

		public function getDeliveryDetails() {
			return sprintf(
				__( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ),
				'🔐',
				$this->getName ()
			);
		}

		public function prepareOptionsForExport( $data ) {
			$data = parent::prepareOptionsForExport( $data );
			$data[ PostmanOptions::MAILCHANNELS_API_KEY ] = PostmanOptions::getInstance()->getMailChannelsApiKey();
			return $data;
		}

		protected function validateTransportConfiguration() {
			$messages = parent::validateTransportConfiguration();
			$apiKey = $this->options->getMailChannelsApiKey();

			if ( PHP_VERSION_ID < 80200 ) {
				$messages[] = __( 'MailChannels requires PHP 8.2 or later.', 'post-smtp' );
				$this->setNotConfiguredAndReady();
			}

			if ( empty( $apiKey ) ) {
				array_push( $messages, __ ( 'API Key can not be empty', 'post-smtp' ) . '.' );
				$this->setNotConfiguredAndReady();
			}

			if ( ! $this->isSenderConfigured() ) {
				array_push ( $messages, __ ( 'Message From Address can not be empty', 'post-smtp' ) . '.' );
				$this->setNotConfiguredAndReady();
			}

			return $messages;
		}

		public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {
			$recommendation = array ();
			$recommendation ['priority'] = 0;
			$recommendation ['transport'] = self::SLUG;
			$recommendation ['hostname'] = $this->getHostname(); // scribe looks this
			$recommendation ['label'] = $this->getName ();
			$recommendation['logo_url'] = $this->getLogoURL();

			if ( PHP_VERSION_ID >= 80200 && $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
				$recommendation ['priority'] = self::PRIORITY;
				/* translators: where variables are (1) transport name (2) host and (3) port */
				$recommendation ['message'] = sprintf ( __( 'Postman recommends the %1$s to host %2$s on port %3$d.', 'post-smtp' ), $this->getName (), self::HOST, self::PORT );
			}

			return $recommendation;
		}

		public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
			$overrideItem = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );

			$overrideItem['auth_items'] = array(
				array(
					'selected' => true,
					'name'     => __( 'API Key', 'post-smtp' ),
					'value'    => 'api_key'
				),
			);
			return $overrideItem;
		}

		public function mailchannels_admin_init() {
			if ( PostmanUtils::isAdmin() ) {
				$this->addSettings();
				$this->registerStylesAndScripts();
			}
		}

		private function addSettings() {
			add_settings_section(
				PostmanMailChannelsTransport::MAILCHANNELS_AUTH_SECTION,
				__( 'Authentication', 'post-smtp' ),
				array( $this, 'printMailChannelsSectionInfo' ),
				PostmanMailChannelsTransport::MAILCHANNELS_AUTH_OPTIONS
			);

			add_settings_field(
				PostmanOptions::MAILCHANNELS_API_KEY,
				__( 'API Key', 'post-smtp' ),
				array( $this, 'mailchannelsApiKeyCallback' ),
				PostmanMailChannelsTransport::MAILCHANNELS_AUTH_OPTIONS,
				PostmanMailChannelsTransport::MAILCHANNELS_AUTH_SECTION
			);
		}

		public function printMailChannelsSectionInfo() {
			printf(
				'<p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_html__( 'MailChannels requires PHP 8.2 or later and an API key with the api scope. Configure Domain Lockdown and SPF for your sender domain before sending.', 'post-smtp' ),
				esc_url( 'https://docs.mailchannels.com/email-api/php/quickstart' ),
				esc_html__( 'MailChannels setup guide', 'post-smtp' )
			);
		}

		public function mailchannelsApiKeyCallback( $showToggle = true ) {
			printf(
				'<input type="password" autocomplete="off" id="mailchannels_api_key" name="postman_options[mailchannels_api_key]" value="%s" size="60" class="required ps-input ps-w-75" required data-error="%s" placeholder="%s"/>',
				null !== $this->options->getMailChannelsApiKey()  ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getMailChannelsApiKey () ) ) : '',
				esc_attr__( 'Please enter API Key.', 'post-smtp' ),
				esc_attr__( 'Required', 'post-smtp' )
			);
			if ( false !== $showToggle ) {
				printf( ' <input type="button" id="toggleMailChannelsApiKey" value="%s" class="button button-secondary" style="visibility:hidden" />', esc_attr__( 'Show Password', 'post-smtp' ) );
			}
		}

		private function registerStylesAndScripts() {
			$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
			wp_register_script( 'postman_mailchannels_script', plugins_url( 'Postman/Postman-Mail/postman_mailchannels.js', $this->rootPluginFilenameAndPath ), array( PostmanViewController::JQUERY_SCRIPT, 'jquery_validation', PostmanViewController::POSTMAN_SCRIPT ), $pluginData['version'] );
		}

		public function enqueueScript() {
			wp_enqueue_script( 'postman_mailchannels_script' );
		}

		public function printWizardAuthenticationStep() {
			print '<section class="wizard_mailchannels">';
			$this->printMailChannelsSectionInfo();
			printf ( '<label for="mailchannels_api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
			print '<br />';
			$this->mailchannelsApiKeyCallback();
			print '</section>';
		}

		public function getLogoURL() {
			return POST_SMTP_ASSETS . "images/logos/mailchannels.svg";
		}

		public function has_granted() {
			return true;
		}
	}
}
