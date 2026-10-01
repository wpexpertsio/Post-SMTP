<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/PostmanModuleTransport.php';
require_once __DIR__ . '/PostmanBirdSettings.php';

final class PostmanBirdTransport extends PostmanAbstractModuleTransport {
	const PRIORITY = 48012;

	public function __construct( $rootPluginFilenameAndPath ) {
		parent::__construct( $rootPluginFilenameAndPath );
		// Register after Post SMTP so its sanitizer cannot replace Bird validation failures.
		add_action( 'admin_init', [PostmanBirdSettings::class, 'register'], 20 );
		add_action( 'post_smtp_settings_sections', [PostmanBirdSettings::class, 'render'] );
		add_action( 'post_smtp_before_reset_plugin', [PostmanBirdSettings::class, 'reset'] );
	}

	public function getSlug() {
		return 'bird_api';
	}

	public function getName() {
		return __( 'Bird', 'post-smtp' );
	}

	public function getProtocol() {
		return 'https';
	}

	public function getPort() {
		return 443;
	}

	public function getTransportType() {
		return $this->getSlug();
	}

	public function isOAuthUsed( $authType ) {
		return false;
	}

	public function has_granted() {
		return true;
	}
	// Upstream validates a Reply-To list as one address. Bird validates the reconstructed list.
	public function isEmailValidationSupported() {
		return false;
	}

	public function getHostname() {
		try {
			return wp_parse_url( PostmanBirdSettings::endpoint( PostmanBirdSettings::get()['api_key'] ?? '' ), PHP_URL_HOST );
		} catch ( RuntimeException $error ) {
			return '';
		}
	}

	protected function validateTransportConfiguration() {
		$messages = parent::validateTransportConfiguration();
		try {
			PostmanBirdSettings::endpoint( PostmanBirdSettings::get()['api_key'] ?? '' );
		} catch ( RuntimeException $error ) {
			$messages[] = $error->getMessage();
			$this->setNotConfiguredAndReady();
		}
		if ( ! $this->isSenderConfigured() ) {
			$messages[] = __( 'Message From Address can not be empty.', 'post-smtp' );
			$this->setNotConfiguredAndReady();
		}
		return $messages;
	}

	public function getSocketsForSetupWizardToProbe( $hostname, $smtpServerGuess ) {
		return [self::createSocketDefinition( $this->getHostname() ?: 'platform.bird.com', 443 )];
	}

	public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {
		$hosts = ['platform.bird.com', 'eu1.platform.bird.com', 'us1.platform.bird.com'];
		$matches = in_array( $hostData->hostname, $hosts, true ) && (int) $hostData->port === 443;
		return [
			'priority' => $matches && in_array( strtolower( (string) $originalSmtpServer ), $hosts, true ) ? self::PRIORITY : 0,
			'transport' => $this->getSlug(),
			'hostname' => $hostData->hostname,
			'label' => $this->getName(),
			'logo_url' => $this->getLogoURL(),
			'message' => __( 'Use Bird with API Key authentication. This connectivity check does not verify your API key.', 'post-smtp' ),
		];
	}

	public function populateConfiguration( $hostname ) {
		return [
			PostmanOptions::TRANSPORT_TYPE => $this->getSlug(),
			PostmanOptions::HOSTNAME => $hostname,
			PostmanOptions::PORT => 443,
			PostmanOptions::AUTHENTICATION_TYPE => 'api_key',
		];
	}

	public function createOverrideMenu( PostmanWizardSocket $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride ) {
		$item = parent::createOverrideMenu( $socket, $winningRecommendation, $userSocketOverride, $userAuthOverride );
		$item['auth_items'] = [['selected' => true, 'name' => __( 'API Key', 'post-smtp' ), 'value' => 'api_key']];
		return $item;
	}

	public function printWizardAuthenticationStep() {
		echo '<section class="wizard_bird" style="display:none">' . PostmanBirdSettings::fields( true ) . '</section>';
	}

	public function getDeliveryDetails() {
		return __( 'Post SMTP sends email through the Bird API. Acceptance does not confirm delivery.', 'post-smtp' );
	}

	public function getLogoURL() {
		return plugins_url( 'assets/images/logos/bird.svg', $this->rootPluginFilenameAndPath );
	}

	public function createMailEngine() {
		require_once __DIR__ . '/PostmanBirdMailEngine.php';
		return new PostmanBirdMailEngine( PostmanBirdSettings::get() );
	}

	public function enqueueScript() {
		$metadata = apply_filters( 'postman_get_plugin_metadata', [] );
		wp_register_script( 'postman-bird', plugins_url( 'Postman/Postman-Mail/postman-bird.js', $this->rootPluginFilenameAndPath ), ['jquery', PostmanViewController::POSTMAN_SCRIPT], $metadata['version'], true );
		wp_enqueue_script( 'postman-bird' );
	}
}
