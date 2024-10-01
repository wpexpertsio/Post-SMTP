<?php
	/**
	 * Extension: Smtp2Go
	 * Type: Transport
	 *
	 * @package Postman SMTP
	 */

	defined( 'ABSPATH' ) || exit;

	if ( ! class_exists( 'PostmanSmtp2GoTransport' ) ) {
		class PostmanSmtp2GoTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
			const SLUG = 'smtp2go_api';
			const PORT = 0;
			const HOST = 'api.smtp2go.com';
			const PRIORITY = 48000;
			const SMTP2GO_AUTH_OPTIONS = 'postman_smtp2go_auth_options';
			const SMTP2GO_AUTH_SECTION = 'postman_smtp2go_auth_section';

			public function __construct( $params ) {
				parent::__construct( $params );

				add_action( 'admin_init', array( $this, 'smtp2go_admin_init' ) );
			}

			public function getProtocol() {
				return 'https';
			}

			public function getSlug() {
				return self::SLUG;
			}

			public function getName() {
				return __( 'SMTP2Go', 'post-smtp' );
			}

			public function getHostname() {
				return self::HOST;
			}

			public function getPort() {
				return self::PORT;
			}

			public function getTransportType() {
				return 'Smtp2Go_api';
			}

			public function createMailEngine() {
				$apiKey = $this->options->getSmtp2GoApiKey();
				require_once 'PostmanSmtp2GoEngine.php';
				$engine = new PostmanSmtp2GoEngine( $apiKey );

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
				$data[ PostmanOptions::SMTP2GO_API_KEY ] = PostmanOptions::getInstance()->getSmtp2GoApiKey();
				return $data;
			}

			protected function validateTransportConfiguration() {
				$messages = parent::validateTransportConfiguration();
				$apiKey = $this->options->getSmtp2GoApiKey();

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

			public function getConfigurationBid( $hostData, $userAuthOverride, $orignalSmtpServer  ) {
				$recommendation = array ();
				$recommendation ['priority'] = 0;
				$recommendation ['transport'] = self::SLUG;
				$recommendation ['hostname'] = $this->getHostname(); // scribe looks this
				$recommendation ['label'] = $this->getName ();
				$recommendation['logo_url'] = $this->getLogoURL();

				if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
					$recommendation ['priority'] = self::PRIORITY;
					/* translators: where variables are (1) transport name (2) host and (3) port */
					$recommendation ['message'] = sprintf ( __ ( ('Postman recommends the %1$s to host %2$s on port %3$d.') ), $this->getName (), self::HOST, self::PORT );
				}

				return $recommendation;
			}

			public function populateConfiguration( $hostname ) {
				$response = parent::populateConfiguration( $hostname );
				return $response;
			}

			public function createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
				$overrideItem = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );

				$overrideItem['auth_items'] = array(
					array(
						'selected' => true,
						'name'     => __( 'Api Key', 'post-smtp' ),
						'value'    => 'api_key'
					),
				);
				return $overrideItem;
			}

			public function smtp2go_admin_init() {
				if ( PostmanUtils::isAdmin() ) {
					$this->addSettings();
					$this->registerStylesAndScripts();
				}
			}

			private function addSettings() {
				add_settings_section(
					PostmanSmtp2GoTransport::SMTP2GO_AUTH_SECTION,
					__( 'Authentication', 'post-smtp' ),
					array( $this, 'printSmtp2goSectionInfo' ),
					PostmanSmtp2GoTransport::SMTP2GO_AUTH_OPTIONS
				);

				add_settings_field(
					PostmanOptions::SMTP2GO_API_KEY,
					__( 'API Key', 'post-smtp' ),
					array( $this, 'smtp2goApiKeyCallback' ),
					PostmanSmtp2GoTransport::SMTP2GO_AUTH_OPTIONS,
					PostmanSmtp2GoTransport::SMTP2GO_AUTH_SECTION
				);
			}

			public function printSmtp2goSectionInfo() {
				printf(
					'<p id="wizard_smtp2go_auth_help">%s</p>',
					sprintf(
						__( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ),
						'https://www.smtp2go.com/',
						'smtp2go.com',
						'https://app-us.smtp2go.com/sending/apikeys/'
					)
				);
			}

			public function smtp2goApiKeyCallback() {
				printf(
					'<input type="password" autocomplete="off" id="smtp2go_api_key" name="postman_options[smtp2go_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
					null !== $this->options->getSmtp2GoApiKey()  ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getSmtp2GoApiKey () ) ) : '',
					__ ( 'Required', 'post-smtp' )
				);
				print ' <input type="button" id="toggleSmtp2goApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
			}

			private function registerStylesAndScripts() {
				$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
				wp_register_script( 'postman_smtp2go_script', plugins_url( 'Postman/Postman-Mail/postman_smtp2go.js', $this->rootPluginFilenameAndPath ), array( PostmanViewController::JQUERY_SCRIPT, 'jquery_validation', PostmanViewController::POSTMAN_SCRIPT ), $pluginData['version'] );
			}

			public function enqueueScript() {
				wp_enqueue_script( 'postman_smtp2go_script' );
			}

			public function printWizardAuthenticationStep() {
				print '<section class="wizard_smtp2go">';
				$this->printSmtp2goSectionInfo();
				printf ( '<label for="api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
				print '<br />';
				$this->smtp2goApiKeyCallback();
				print '</section>';
			}

			public function getLogoURL() {
				return POST_SMTP_ASSETS . "images/logos/smtp2go.png";
			}

			public function has_granted() {
				return true;
			}
		}
	}

