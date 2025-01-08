<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';
/**
 * Postman SendGrid module
 *
 * @author jasonhendriks
 */
class PostmanSendGridTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {
	const SLUG                  = 'sendgrid_api';
	const PORT                  = 443;
	const HOST                  = 'api.sendgrid.com';
	const PRIORITY              = 48000;
	const SENDGRID_AUTH_OPTIONS = 'postman_sendgrid_auth_options';
	const SENDGRID_AUTH_SECTION = 'postman_sendgrid_auth_section';

	/**
	 *
	 * @param mixed $rootPluginFilenameAndPath
	 */
	public function __construct( $rootPluginFilenameAndPath ) {
		parent::__construct( $rootPluginFilenameAndPath );

		// add a hook on the plugins_loaded event
		add_action(
			'admin_init',
			array(
				$this,
				'on_admin_init',
			)
		);
	}
	public function getProtocol() {
		return 'https';
	}

	// this should be standard across all transports
	public function getSlug() {
		return self::SLUG;
	}
	public function getName() {
		return __( 'SendGrid API', 'post-smtp' );
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
		return 'SendGrid_api';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see PostmanModuleTransport::createMailEngine()
	 */
	public function createMailEngine() {
		$existing_db_version = get_option( 'postman_db_version' );
		$connection_details  = get_option( 'postman_connections' );
		$route_key = null;
		// Check if a transient for smart routing is set.
		$route_key = get_transient( 'post_smtp_smart_routing_route' );
	
		if ( $route_key != null ) {
			// Smart routing is enabled, use the connection associated with the route_key.
			$api_key = $this->getApiKeyForRoute( $route_key, $connection_details );
		} else {
			// No smart routing, proceed with default connection selection.
			$api_key = $this->getApiKeyForDefaultConnection( $existing_db_version, $connection_details );
		}
		
		require_once 'PostmanSendGridMailEngine.php';
		$engine = new PostmanSendGridMailEngine( $api_key );
		return $engine;
	}
	/**
	 * @since 3.0.1
	 * @version 1.0
	 */
	public function createMailEngineFallback() {

		$connection_details = get_option( 'postman_connections' );
		$fallback           = $this->options->getSelectedFallback();
		$api_key            = $connection_details[ $fallback ]['sendgrid_api_key'];
		$api_credentials    = array(
			'api_key'     => $api_key,
			'is_fallback' => 1,
		);
		require_once 'PostmanSendGridMailEngine.php';
		$engine = new PostmanSendGridMailEngine( $api_key );

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
			return $connection_details[ $route_key ]['sendgrid_api_key'];
		}
	}

	/**
	 * Retrieves the API key for the default connection selection.
	 *
	 * @since 3.0.1
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
			return $this->options->getSendGridApiKey();
		}

		// Use the API key of the primary connection.
		$primary = $this->options->getSelectedPrimary();
		return isset( $connection_details[ $primary ] ) ? $connection_details[ $primary ]['sendgrid_api_key'] : ''; 
	}	

	public function getDeliveryDetails() {
		/* translators: where (1) is the secure icon and (2) is the transport name */
		return sprintf( __( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ), '🔐', $this->getName() );
	}

	/**
	 *
	 * @param mixed $data
	 */
	public function prepareOptionsForExport( $data ) {
		$data                                      = parent::prepareOptionsForExport( $data );
		$data [ PostmanOptions::SENDGRID_API_KEY ] = PostmanOptions::getInstance()->getSendGridApiKey();
		return $data;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see PostmanTransport::getMisconfigurationMessage()
	 */
	protected function validateTransportConfiguration() {
		$postman_db_version = get_option( 'postman_db_version' );
		if ( $postman_db_version != POST_SMTP_DB_VERSION ) {
			$messages = parent::validateTransportConfiguration();
			$apiKey   = $this->options->getSendGridApiKey();
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
	 * (non-PHPdoc)
	 *
	 * @see PostmanModuleTransport::getConfigurationBid()
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
	 *
	 * @param mixed $hostname
	 * @param mixed $response
	 */
	public function populateConfiguration( $hostname ) {
		$response = parent::populateConfiguration( $hostname );
		return $response;
	}

	/**
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
	 * Functions to execute on the admin_init event
	 *
	 * "Runs at the beginning of every admin page before the page is rendered."
	 * ref: http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_an_Admin_Page_Request
	 */
	public function on_admin_init() {
		// only administrators should be able to trigger this
		if ( PostmanUtils::isAdmin() ) {
			$this->addSettings();
			$this->registerStylesAndScripts();
		}
	}

	/*
	 * What follows in the code responsible for creating the Admin Settings page
	 */

	/**
	 */
	public function addSettings() {
		// the SendGrid Auth section
		add_settings_section(
			self::SENDGRID_AUTH_SECTION,
			__( 'Authentication', 'post-smtp' ),
			array(
				$this,
				'printSendGridAuthSectionInfo',
			),
			self::SENDGRID_AUTH_OPTIONS
		);

		add_settings_field(
			PostmanOptions::SENDGRID_API_KEY,
			__( 'API Key', 'post-smtp' ),
			array(
				$this,
				'sendgrid_api_key_callback',
			),
			self::SENDGRID_AUTH_OPTIONS,
			self::SENDGRID_AUTH_SECTION
		);
	}
	public function printSendGridAuthSectionInfo() {
		/* Translators: Where (1) is the service URL and (2) is the service name and (3) is a api key URL */
		printf( '<p id="wizard_sendgrid_auth_help">%s</p>', sprintf( __( 'Create an account at <a href="%1$s" target="_blank">%2$s</a> and enter <a href="%3$s" target="_blank">an API key</a> below.', 'post-smtp' ), 'https://sendgrid.com', 'SendGrid.com', 'https://app.sendgrid.com/settings/api_keys' ) );
	}

	/**
	 */
	public function sendgrid_api_key_callback() {
		printf( '<input type="password" autocomplete="off" id="sendgrid_api_key" name="postman_options[sendgrid_api_key]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>', null !== $this->options->getSendGridApiKey() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getSendGridApiKey() ) ) : '', __( 'Required', 'post-smtp' ) );
		print ' <input type="button" id="toggleSendGridApiKey" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
	}

	/**
	 */
	public function registerStylesAndScripts() {
		// register the stylesheet and javascript external resources
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		wp_register_script(
			'postman_sendgrid_script',
			plugins_url( 'Postman/Postman-Mail/postman_sendgrid.js', $this->rootPluginFilenameAndPath ),
			array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT,
			),
			$pluginData ['version']
		);
	}

	/**
	 */
	public function enqueueScript() {
		wp_enqueue_script( 'postman_sendgrid_script' );
	}

	/**
	 */
	public function printWizardAuthenticationStep() {
		print '<section class="wizard_sendgrid">';
		$this->printSendGridAuthSectionInfo();
		printf( '<label for="api_key">%s</label>', __( 'API Key', 'post-smtp' ) );
		print '<br />';
		print $this->sendgrid_api_key_callback();
		print '</section>';
	}

	/**
	 * Get Socket's logo
	 *
	 * @since 2.1
	 * @version 1.0
	 */
	public function getLogoURL() {

		return POST_SMTP_ASSETS . 'images/logos/sendgrid.png';
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
}
