<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';

if ( ! class_exists( 'PostmanCloudflareTransport' ) ) :
class PostmanCloudflareTransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

	const SLUG = 'cloudflare_api';
	const PORT = 443;
	const HOST = 'api.cloudflare.com';
	const PRIORITY = 48500;
	const CLOUDFLARE_AUTH_OPTIONS = 'postman_cloudflare_auth_options';
	const CLOUDFLARE_AUTH_SECTION = 'postman_cloudflare_auth_section';

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
		return __( 'Cloudflare', 'post-smtp' );
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

	public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {
		$recommendation               = array();
		$recommendation['priority']   = 0;
		$recommendation['transport']  = self::SLUG;
		$recommendation['hostname']   = null;
		$recommendation['label']      = $this->getName();
		$recommendation['logo_url']   = $this->getLogoURL();

		if ( $hostData->hostname == self::HOST && $hostData->port == self::PORT ) {
			$recommendation['priority'] = self::PRIORITY;
			$recommendation['message']  = sprintf( __( 'Postman recommends the %1$s to host %2$s on port %3$d.', 'post-smtp' ), $this->getName(), self::HOST, self::PORT );
		}

		return $recommendation;
	}

	public function createMailEngine() {
		$api_token  = $this->options->getCloudflareApiToken();
		$account_id = $this->options->getCloudflareAccountId();
		require_once 'PostmanCloudflareMailEngine.php';
		return new PostmanCloudflareMailEngine( $api_token, $account_id );
	}

	public function getDeliveryDetails() {
		return sprintf( __( 'Postman will send mail via the <b>%1$s %2$s</b>.', 'post-smtp' ), '🔐', $this->getName() );
	}

	public function populateConfiguration( $hostname ) {
		$response = parent::populateConfiguration( $hostname );
		$response[PostmanOptions::TRANSPORT_TYPE]      = $this->getSlug();
		$response[PostmanOptions::PORT]                = $this->getPort();
		$response[PostmanOptions::HOSTNAME]            = $this->getHostname();
		$response[PostmanOptions::SECURITY_TYPE]       = PostmanOptions::SECURITY_TYPE_SMTPS;
		$response[PostmanOptions::AUTHENTICATION_TYPE] = PostmanOptions::AUTHENTICATION_TYPE_OAUTH2;
		return $response;
	}

	public function on_admin_init() {
		if ( PostmanUtils::isAdmin() ) {
			$this->addSettings();
			$this->registerStylesAndScripts();
		}
	}

	public function addSettings() {
		add_settings_section(
			self::CLOUDFLARE_AUTH_SECTION,
			__( 'Authentication', 'post-smtp' ),
			array( $this, 'printCloudflareAuthSectionInfo' ),
			self::CLOUDFLARE_AUTH_OPTIONS
		);

		add_settings_field(
			PostmanOptions::CLOUDFLARE_API_TOKEN,
			__( 'API Token', 'post-smtp' ),
			array( $this, 'cloudflare_api_token_callback' ),
			self::CLOUDFLARE_AUTH_OPTIONS,
			self::CLOUDFLARE_AUTH_SECTION
		);

		add_settings_field(
			PostmanOptions::CLOUDFLARE_ACCOUNT_ID,
			__( 'Account ID', 'post-smtp' ),
			array( $this, 'cloudflare_account_id_callback' ),
			self::CLOUDFLARE_AUTH_OPTIONS,
			self::CLOUDFLARE_AUTH_SECTION
		);
	}

	public function printCloudflareAuthSectionInfo() {
		printf(
			'<p id="wizard_cloudflare_auth_help">%s</p>',
			sprintf(
				__( 'Create an account at <a href="%1$s" target="_blank">%2$s</a>, then enter your account ID and an API token with email sending permissions.', 'post-smtp' ),
				'https://dash.cloudflare.com/',
				'Cloudflare'
			)
		);
	}

	public function cloudflare_api_token_callback() {
		printf(
			'<input type="password" autocomplete="off" id="cloudflare_api_token" name="postman_options[cloudflare_api_token]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
			null !== $this->options->getCloudflareApiToken() ? esc_attr( PostmanUtils::obfuscatePassword( $this->options->getCloudflareApiToken() ) ) : '',
			__( 'Required', 'post-smtp' )
		);
		print ' <input type="button" id="toggleCloudflareApiToken" value="Show Password" class="button button-secondary" style="visibility:hidden" />';
	}

	public function cloudflare_account_id_callback() {
		printf(
			'<input type="text" autocomplete="off" id="cloudflare_account_id" name="postman_options[cloudflare_account_id]" value="%s" size="60" class="required ps-input ps-w-75" placeholder="%s"/>',
			null !== $this->options->getCloudflareAccountId() ? esc_attr( $this->options->getCloudflareAccountId() ) : '',
			__( 'Required', 'post-smtp' )
		);
	}

	public function registerStylesAndScripts() {
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		wp_register_script(
			'postman-cloudflare',
			plugins_url( 'Postman/Postman-Mail/postman-cloudflare.js', $this->rootPluginFilenameAndPath ),
			array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT,
			),
			$pluginData['version']
		);
	}

	public function enqueueScript() {
		wp_enqueue_script( 'postman-cloudflare' );
	}

	public function printWizardAuthenticationStep() {
		print '<section class="wizard_cloudflare">';
		$this->printCloudflareAuthSectionInfo();
		printf( '<label for="cloudflare_api_token">%s</label>', __( 'API Token', 'post-smtp' ) );
		print '<br />';
		print $this->cloudflare_api_token_callback();
		print '<br />';
		printf( '<label for="cloudflare_account_id">%s</label>', __( 'Account ID', 'post-smtp' ) );
		print '<br />';
		print $this->cloudflare_account_id_callback();
		print '</section>';
	}

	public function getLogoURL() {
		return POST_SMTP_ASSETS . 'images/logos/cloudflare.svg';
	}

	public function has_granted() {
		return true;
	}

	protected function validateTransportConfiguration() {
		$messages  = parent::validateTransportConfiguration();
		$api_token = $this->options->getCloudflareApiToken();
		$accountId = $this->options->getCloudflareAccountId();

		if ( empty( $api_token ) ) {
			array_push( $messages, __( 'API Token can not be empty', 'post-smtp' ) . '.' );
			$this->setNotConfiguredAndReady();
		}

		if ( empty( $accountId ) ) {
			array_push( $messages, __( 'Account ID can not be empty', 'post-smtp' ) . '.' );
			$this->setNotConfiguredAndReady();
		}

		if ( ! $this->isSenderConfigured() ) {
			array_push( $messages, __( 'Message From Address can not be empty', 'post-smtp' ) . '.' );
			$this->setNotConfiguredAndReady();
		}

		return $messages;
	}

	public function prepareOptionsForExport( $data ) {
		$data = parent::prepareOptionsForExport( $data );
		$data[PostmanOptions::CLOUDFLARE_API_TOKEN] = PostmanOptions::getInstance()->getCloudflareApiToken();
		$data[PostmanOptions::CLOUDFLARE_ACCOUNT_ID] = PostmanOptions::getInstance()->getCloudflareAccountId();
		return $data;
	}
}
endif;
