<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (! class_exists ( "PostmanAuthenticationManagerFactory" )) {
	
	require_once 'PostmanGoogleAuthenticationManager.php';
	require_once 'PostmanMicrosoftAuthenticationManager.php';
	require_once 'PostmanNonOAuthAuthenticationManager.php';
	require_once 'PostmanYahooAuthenticationManager.php';
	
	class PostmanAuthenticationManagerFactory {
		private $logger;

		/**
		 * Holds the existing database version.
		 *
		 * This property stores the current database version from the WordPress 
		 * options table, allowing version checks to manage upgrades or compatibility 
		 * checks within the plugin.
		 *
		 * @var string Database version.
		 */
		private $existing_db_version = '';
		
		// singleton instance
		public static function getInstance() {
			static $inst = null;
			if ($inst === null) {
				$inst = new PostmanAuthenticationManagerFactory ();
			}
			return $inst;
		}
		private function __construct() {
			$this->logger = new PostmanLogger ( get_class ( $this ) );
			$this->existing_db_version = get_option( 'postman_db_version' );
		}
		public function createAuthenticationManager() {
			if ( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
				$transport = PostmanTransportRegistry::getInstance()->getTransports();
				return $this->createManager( $transport['gmail_api'] );
			}else{
				$transport = PostmanTransportRegistry::getInstance ()->getSelectedTransport ();
				return $this->createManager ( $transport );
			}
		}
		private function createManager(PostmanZendModuleTransport $transport) {
			$options = PostmanOptions::getInstance ();
			$authorizationToken = PostmanOAuthToken::getInstance();
			$authenticationType = $options->getAuthenticationType ();
			$hostname = $options->getHostname ();
			if ( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
				if ( false === get_transient( 'client_id' ) && isset( $_GET['client_id'], $_GET['client_secret'] ) ) {
					set_transient(
						'client_id',
						sanitize_text_field( wp_unslash( $_GET['client_id'] ) ),
						15 * MINUTE_IN_SECONDS
					);
					set_transient(
						'client_secret',
						sanitize_text_field( wp_unslash( $_GET['client_secret'] ) ),
						15 * MINUTE_IN_SECONDS
					);
				}
				$clientId     = get_transient( 'client_id' );
				$clientSecret = get_transient( 'client_secret' );
				$clientId     = is_string( $clientId ) ? trim( $clientId ) : '';
				$clientSecret = is_string( $clientSecret ) ? trim( $clientSecret ) : '';

				// Wizard saves OAuth credentials on the connection; admin-post OAuth grant has no form context.
				if ( ( '' === $clientId || '' === $clientSecret ) && 'gmail_api' === $transport->getSlug() ) {
					$connections = get_option( 'postman_connections', array() );
					$index       = $options->getSelectedPrimary();
					if ( isset( $_GET['id'] ) && is_array( $connections ) && array_key_exists( (string) $_GET['id'], $connections ) ) {
						$index = (string) sanitize_text_field( wp_unslash( $_GET['id'] ) );
					}
					if ( is_array( $connections ) && '' !== (string) $index && isset( $connections[ $index ] ) ) {
						$row = $connections[ $index ];
						if ( '' === $clientId && ! empty( $row['oauth_client_id'] ) ) {
							$clientId = trim( (string) $row['oauth_client_id'] );
						}
						if ( '' === $clientSecret && ! empty( $row['oauth_client_secret'] ) ) {
							$clientSecret = trim( (string) $row['oauth_client_secret'] );
						}
					}
				}

			}else{
				$clientId = $options->getClientId ();
				$clientSecret = $options->getClientSecret();	
			}
			$senderEmail = $options->getMessageSenderEmail();
			if ( $this->existing_db_version == POST_SMTP_DB_VERSION && 'gmail_api' === $transport->getSlug() ) {
				$connections = get_option( 'postman_connections', array() );
				$index       = $options->getSelectedPrimary();
				if ( isset( $_GET['id'] ) && is_array( $connections ) && array_key_exists( (string) $_GET['id'], $connections ) ) {
					$index = (string) sanitize_text_field( wp_unslash( $_GET['id'] ) );
				}
				if ( is_array( $connections ) && '' !== (string) $index && isset( $connections[ $index ]['sender_email'] ) ) {
					$hint = trim( (string) $connections[ $index ]['sender_email'] );
					if ( '' !== $hint && is_email( $hint ) ) {
						$senderEmail = $hint;
					}
				}
			}
			$scribe = $transport->getScribe();;
			$redirectUrl = $scribe->getCallbackUrl ();
			if ($transport->isOAuthUsed ( $options->getAuthenticationType () )) {
				if ($transport->isServiceProviderGoogle ( $hostname )) {
					$authenticationManager = new PostmanGoogleAuthenticationManager ( $clientId, $clientSecret, $authorizationToken, $redirectUrl, $senderEmail );
				} else if ($transport->isServiceProviderMicrosoft ( $hostname )) {
					$authenticationManager = new PostmanMicrosoftAuthenticationManager ( $clientId, $clientSecret, $authorizationToken, $redirectUrl );
				} else if ($transport->isServiceProviderYahoo ( $hostname )) {
					$authenticationManager = new PostmanYahooAuthenticationManager ( $clientId, $clientSecret, $authorizationToken, $redirectUrl );
				} else {
					assert ( false );
				}
			} else {
				$authenticationManager = new PostmanNonOAuthAuthenticationManager ();
			}
			$this->logger->debug ( 'Created ' . get_class ( $authenticationManager ) );
			return $authenticationManager;
		}
	}
}