<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';
/**
 * Postman MailerSend module
 *
 * @author jasonhendriks
 *        
 */
class PostmanMailerSendTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
	const SLUG = 'mailersend_api';
	const PORT = 443;
	const HOST = 'api.mailersend.com';
	const PRIORITY = 48000;
	const MAILERSEND_AUTH_OPTIONS = 'postman_mailersend_auth_options';
	const MAILERSEND_AUTH_SECTION = 'postman_mailersend_auth_section';
	
	/**
	 *
	 * @param mixed $rootPluginFilenameAndPath        	
	 */
	public function __construct($rootPluginFilenameAndPath) {
		parent::__construct ( $rootPluginFilenameAndPath );
		
		// add a hook on the plugins_loaded event
		add_action ( 'admin_init', array (
				$this,
				'on_admin_init' 
		) );
	}
	public function getProtocol() {
		return 'https';
	}
	
	// this should be standard across all transports
	public function getSlug() {
		return self::SLUG;
	}
	public function getName() {
		return __ ( 'MailerSend API', 'post-smtp' );
	}
	/**
	 * v0.2.1
	 *
	 * @return string
	 */
	public function getHostname() {
		return self::HOST;
	}
	/**
	 * v0.2.1
	 *
	 * @return int
	 */
	public function getPort() {
		return self::PORT;
	}
	/**
	 * v1.7.0
	 *
	 * @return string
	 */
	public function getTransportType() {
		return 'MailerSend_api';
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see PostmanModuleTransport::createMailEngine()
	 */
	public function createMailEngine() {
		$apiKey = $this->options->getMailerSendApiKey ();
		require_once 'PostmanMailerSendMailEngine.php';
		$engine = new PostmanMailerSendMailEngine ( $apiKey );
		return $engine;
	}
	public function getDeliveryDetails() {
		/* translators: where (1) is the secure icon and (2) is the transport name */
		return sprintf ( __ ( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ), '🔐', $this->getName () );
	}
	
	/**
	 *
	 * @param mixed $data        	
	 */
	public function prepareOptionsForExport($data) {
		$data = parent::prepareOptionsForExport ( $data );
		$data [PostmanOptions::MAILERSEND_API_KEY] = PostmanOptions::getInstance ()->getMailerSendApiKey ();
		return $data;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see PostmanTransport::getMisconfigurationMessage()
	 */
	protected function validateTransportConfiguration() {
		$messages = parent::validateTransportConfiguration ();
		$apiKey = $this->options->getMailerSendApiKey ();
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
	 * (non-PHPdoc)
	 *
	 * @see PostmanModuleTransport::getConfigurationBid()
	 */
	public function getConfigurationBid(PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer) {
		$recommendation = array ();
		$recommendation ['priority'] = 0;
		$recommendation ['transport'] = self::SLUG;
		$recommendation ['hostname'] = null; // scribe looks this
		$recommendation ['label'] = $this->getName ();
		$recommendation['logo_url'] = $this->getLogoURL();
		
		if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
			$recommendation ['priority'] = self::PRIORITY;
			/* translators: where variables are (1) transport name (2) host and (3) port */
			$recommendation ['message'] = sprintf ( __ ( ('Postman recommends the %1$s to host %2$s on port %3$d.') ), $this->getName (), self::HOST, self::PORT );
		}
		return $recommendation;
	}
	
	/**
	 *
	 * @param mixed $hostname        	
	 * @param mixed $response        	
	 */
	public function populateConfiguration($hostname) {
		$response = parent::populateConfiguration ( $hostname );
		return $response;
	}
	
	/**
	 */
	public function createOverrideMenu(PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride) {
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
	 * Functions to execute on the admin_init event
	 *
	 * "Runs at the beginning of every admin page before the page is rendered."
	 * ref: http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_an_Admin_Page_Request
	 */
	public function on_admin_init() {
		// only administrators should be able to trigger this
		if (PostmanUtils::isAdmin ()) {
			$this->addSettings ();
			$this->registerStylesAndScripts ();
		}
	}
	
	/*
	 * What follows in the code responsible for creating the Admin Settings page
	 */
	
	/**
	 */
	public function addSettings() {
		// the MailerSend Auth section
		add_settings_section ( PostmanMailerSendTransport::MAILERSEND_AUTH_SECTION, __ ( 'Authentication', 'post-smtp' ), array (
				$this,
				'printMailerSendAuthSectionInfo' 
		), PostmanMailerSendTransport::MAILERSEND_AUTH_OPTIONS );
		
		add_settings_field ( PostmanOptions::MAILERSEND_API_KEY, __ ( 'API Key', 'post-smtp' ), array (
				$this,
				'mailersend_api_key_callback' 
		), PostmanMailerSendTransport::MAILERSEND_AUTH_OPTIONS, PostmanMailerSendTransport::MAILERSEND_AUTH_SECTION );

	}
	public function printMailerSendAuthSectionInfo() {
		/* Translators: Where (1) is the service URL and (2) is the service name and (3) is a api key URL */
		printf ( '<p id="wizard_mailersend_auth_help">%s</p>', sprintf ( __ ( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ), 'https://mailersend.com', 'MailerSend.com', 'https://app.mailersend.com/settings/api_keys' ) );
	}
	
	/**
	 */
	public function mailersend_api_key_callback() {
		printf ( '<input type="password" autocomplete="off" id="mailersend_api_key" name="postman_options[mailersend_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getMailerSendApiKey () ? esc_attr ( PostmanUtils::obfuscatePassword ( $this->options->getMailerSendApiKey () ) ) : '', __ ( 'Required', 'post-smtp' ) );
		print ' <input type="button" id="toggleMailerSendApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
	}
	
	/**
	 */
	public function registerStylesAndScripts() {
		// register the stylesheet and javascript external resources
		$pluginData = apply_filters ( 'postman_get_plugin_metadata', null );
		wp_register_script ( 'postman_mailersend_script', plugins_url ( 'Postman/Postman-Mail/postman_mailersend.js', $this->rootPluginFilenameAndPath ), array (
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT 
		), $pluginData ['version'] );
	}
	
	/**
	 */
	public function enqueueScript() {
		wp_enqueue_script ( 'postman_mailersend_script' );
	}
	
	/**
	 */
	public function printWizardAuthenticationStep() {
		print '<section class="wizard_mailersend">';
		$this->printMailerSendAuthSectionInfo ();
		printf ( '<label for="api_key">%s</label>', __ ( 'API Key', 'post-smtp' ) );
		print '<br />';
		print $this->mailersend_api_key_callback ();
		print '</section>';
	}

	/**
	 * Get Socket's logo
	 * 
	 * @since 3.3.0
	 * @version 1.0
	 */
	public function getLogoURL() {

		return POST_SMTP_ASSETS . "images/logos/mailersend.png";

	}


	/**
	 * Returns true, to prevent from errors because it's default Module Transport.
	 * 
	 * @since 3.3.0
	 * @version 1.0
	 */
	public function has_granted() {

		return true;

	}
}
