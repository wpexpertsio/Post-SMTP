<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';

/**
 * Postman SMTP.com Transport
 *
 * @since 3.6.0
 * @version 1.0
 */
if ( ! class_exists( 'PostmanSmtpcomTransport' ) ) :
class PostmanSmtpcomTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

	const SLUG = 'smtpcom_api';
	const PORT = 443;
	const HOST = 'api.smtp.com';
	const PRIORITY = 48500;
	const SMTPCOM_AUTH_OPTIONS = 'postman_smtpcom_auth_options';
	const SMTPCOM_AUTH_SECTION = 'postman_smtpcom_auth_section';

	/**
	 * PostmanSmtpcomTransport constructor.
	 *
	 * @param $rootPluginFilenameAndPath
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function __construct( $rootPluginFilenameAndPath ) {

		parent::__construct( $rootPluginFilenameAndPath );

		add_action( 'admin_init', array( $this, 'on_admin_init' ) );

	}

	public function getProtocol() {
		return 'https';
	}

	public function getSlug() {
		return self::SLUG;
	}

	public function getName() {
		return __( 'SMTP.com', 'post-smtp' );
	}

	public function getHostname() {
		return self::HOST;
	}

	public function getPort() {
		return self::PORT;
	}

	public function getTransportType() {
		return 'smtpcom_api';
	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {

		$recommendation = array();
		$recommendation['priority']  = 0;
		$recommendation['transport'] = self::SLUG;
		$recommendation['hostname']  = null;
		$recommendation['label']     = $this->getName();
		$recommendation['logo_url']  = $this->getLogoURL();

		if ( $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
			$recommendation['priority'] = self::PRIORITY;
			$recommendation['message']  = sprintf(
				__( 'Postman recommends the %1$s to host %2$s on port %3$d.', 'post-smtp' ),
				$this->getName(),
				self::HOST,
				self::PORT
			);
		}

		return $recommendation;

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function createMailEngine() {
		$credentials = array(
			'api_key' => Postman_Connection_Resolver::get_primary_field(
				'smtpcom_api_key',
				array( $this->options, 'getSmtpcomApiKey' )
			),
			'channel' => Postman_Connection_Resolver::get_primary_field(
				'smtpcom_channel',
				array( $this->options, 'getSmtpcomChannel' )
			),
		);

		require_once 'PostmanSmtpcomMailEngine.php';
		return new PostmanSmtpcomMailEngine( $credentials );
	}

	public function createMailEngineFallback() {
		$credentials = array(
			'api_key'     => Postman_Connection_Resolver::get_fallback_field( 'smtpcom_api_key' ),
			'channel'     => Postman_Connection_Resolver::get_fallback_field( 'smtpcom_channel' ),
			'is_fallback' => 1,
		);

		require_once 'PostmanSmtpcomMailEngine.php';
		return new PostmanSmtpcomMailEngine( $credentials );
	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function getDeliveryDetails() {
		return sprintf(
			__( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ),
			'🔐',
			$this->getName()
		);
	}

	/**
	 * @param PostmanWizardSocket $socket
	 * @param $winningRecommendation
	 * @param $userSocketOverride
	 * @param $userAuthOverride
	 * @return array
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function populateConfiguration( $hostname ) {

		$response = parent::populateConfiguration( $hostname );
		$response[ PostmanOptions::TRANSPORT_TYPE ]      = $this->getSlug();
		$response[ PostmanOptions::PORT ]                = $this->getPort();
		$response[ PostmanOptions::HOSTNAME ]            = $this->getHostname();
		$response[ PostmanOptions::SECURITY_TYPE ]       = PostmanOptions::SECURITY_TYPE_SMTPS;
		$response[ PostmanOptions::AUTHENTICATION_TYPE ] = PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;

		return $response;

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function on_admin_init() {
		$this->addSettings();
		$this->registerStylesAndScripts();
	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function addSettings() {

		add_settings_section(
			self::SMTPCOM_AUTH_SECTION,
			__( 'Authentication', 'post-smtp' ),
			array( $this, 'printSmtpcomAuthSectionInfo' ),
			self::SMTPCOM_AUTH_OPTIONS
		);

		add_settings_field(
			PostmanOptions::SMTPCOM_API_KEY,
			__( 'API Key', 'post-smtp' ),
			array( $this, 'smtpcom_api_key_callback' ),
			self::SMTPCOM_AUTH_OPTIONS,
			self::SMTPCOM_AUTH_SECTION
		);

		add_settings_field(
			PostmanOptions::SMTPCOM_CHANNEL,
			__( 'Channel', 'post-smtp' ),
			array( $this, 'smtpcom_channel_callback' ),
			self::SMTPCOM_AUTH_OPTIONS,
			self::SMTPCOM_AUTH_SECTION
		);

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function printSmtpcomAuthSectionInfo() {

		printf(
			'<p id="wizard_smtpcom_auth_help">%s</p>',
			sprintf(
				__( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ),
				'https://www.smtp.com/',
				'smtp.com',
				'https://www.smtp.com/resources/api-documentation/'
			)
		);

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function smtpcom_api_key_callback() {

		printf(
			'<input type="password" autocomplete="off" id="smtpcom_api_key" name="postman_options[smtpcom_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
			null !== $this->options->getSmtpcomApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getSmtpcomApiKey() ) ) : '',
			__( 'Required', 'post-smtp' )
		);
		print ' <input type="button" id="toggleSmtpcomApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function smtpcom_channel_callback() {

		printf(
			'<input type="text" autocomplete="off" id="smtpcom_channel" name="postman_options[smtpcom_channel]" value="%s" size="60" class="ps-input ps-w-75" placeholder="%s"/>',
			esc_attr( $this->options->getSmtpcomChannel() ?? '' ),
			__( 'Optional', 'post-smtp' )
		);
		print '<p class="description">' . esc_html__( 'Channel name from your SMTP.com account. Leave empty to use the default channel.', 'post-smtp' ) . '</p>';

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function registerStylesAndScripts() {

		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		wp_register_script(
			'postman-smtpcom',
			plugins_url( 'Postman/Postman-Mail/postman-smtpcom.js', $this->rootPluginFilenameAndPath ),
			array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT,
			),
			$pluginData['version']
		);

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function enqueueScript() {

		wp_enqueue_script( 'postman-smtpcom' );

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function printWizardAuthenticationStep() {
		print '<section class="wizard_smtpcom">';
		$this->printSmtpcomAuthSectionInfo();
		printf( '<label for="api_key">%s</label>', __( 'API Key', 'post-smtp' ) );
		print '<br />';
		print $this->smtpcom_api_key_callback();
		printf( '<label for="channel">%s</label>', __( 'Channel', 'post-smtp' ) );
		print '<br />';
		print $this->smtpcom_channel_callback();
		print '</section>';
	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function getLogoURL() {

		return POST_SMTP_ASSETS . 'images/logos/smtpcom.png';

	}

	/**
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function has_granted() {

		return true;

	}

	/**
	 * @see PostmanTransport::getMisconfigurationMessage()
	 * @since 3.6.0
	 * @version 1.0
	 */
	protected function validateTransportConfiguration() {
		$messages = parent::validateTransportConfiguration();
		$apiKey   = $this->options->getSmtpcomApiKey();
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

	/**
	 * @param mixed $data
	 * @since 3.6.0
	 * @version 1.0
	 */
	public function prepareOptionsForExport( $data ) {
		$data = parent::prepareOptionsForExport( $data );
		$data[ PostmanOptions::SMTPCOM_API_KEY ]   = PostmanOptions::getInstance()->getSmtpcomApiKey();
		$data[ PostmanOptions::SMTPCOM_CHANNEL ]   = PostmanOptions::getInstance()->getSmtpcomChannel();
		return $data;
	}
}
endif;
