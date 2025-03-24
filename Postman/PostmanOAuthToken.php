<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if (! class_exists ( 'PostmanOAuthToken.php' )) {
	
	class PostmanOAuthToken {
		const OPTIONS_NAME = 'postman_auth_token';
		//
		const REFRESH_TOKEN = 'refresh_token';
		const EXPIRY_TIME = 'auth_token_expires';
		const ACCESS_TOKEN = 'access_token';
		const VENDOR_NAME = 'vendor_name';
		//
		private $vendorName;
		private $accessToken;
		private $refreshToken;
		private $expiryTime;
		
		// singleton instance
		public static function getInstance() {
			static $inst = null;
			if ($inst === null) {
				$inst = new PostmanOAuthToken ();
			}
			return $inst;
		}
		
		// private constructor
		private function __construct() {
			$this->load ();
		}
		
		/**
		 * Is there a valid access token and refresh token
		 */
		public function isValid() {
			$accessToken = $this->getAccessToken ();
			$refreshToken = $this->getRefreshToken ();
			return ! (empty ( $accessToken ) || empty ( $refreshToken ));
		}
		
		/**
		 * Load the Postman OAuth token properties to the database
		 */
		private function load() {
			$a = get_option ( PostmanOAuthToken::OPTIONS_NAME );

			if ( ! is_array( $a ) ) {
			    return;
            }

			if ( isset( $a [PostmanOAuthToken::ACCESS_TOKEN] ) ) {
                $this->setAccessToken ( $a [PostmanOAuthToken::ACCESS_TOKEN] );
            }

            if ( isset( $a [PostmanOAuthToken::REFRESH_TOKEN] ) ) {
                $this->setRefreshToken($a [PostmanOAuthToken::REFRESH_TOKEN]);
            }

            if ( isset( $a [PostmanOAuthToken::EXPIRY_TIME] ) ) {
                $this->setExpiryTime($a [PostmanOAuthToken::EXPIRY_TIME]);
            }

            if ( isset( $a [PostmanOAuthToken::VENDOR_NAME] ) ) {
                $this->setVendorName($a [PostmanOAuthToken::VENDOR_NAME]);
            }
		}
		
		/**
		 * Save the Postman OAuth token properties to the database
		 */
		public function save() {
			$a [PostmanOAuthToken::ACCESS_TOKEN] = $this->getAccessToken ();
			$a [PostmanOAuthToken::REFRESH_TOKEN] = $this->getRefreshToken ();
			$a [PostmanOAuthToken::EXPIRY_TIME] = $this->getExpiryTime ();
			$a [PostmanOAuthToken::VENDOR_NAME] = $this->getVendorName ();
			if ( is_multisite() && $this->is_network_settings_enabled() ) {
				// Get all child sites.
				$sites = get_sites( array('fields' => 'ids') );
				foreach ( $sites as $site_id ) {
					switch_to_blog( $site_id );
					update_option( PostmanOAuthToken::OPTIONS_NAME, $a );
					restore_current_blog();
				}
			} else {
				// Update for a single site.
				update_option( PostmanOAuthToken::OPTIONS_NAME, $a );
			}
		}
		
		/**
		 * Check if network-wide settings are enabled
		 *
		 * @return bool
		 */
		private function is_network_settings_enabled() {
			if ( !is_multisite() ) {
				return false;
			}

			$network_options = get_site_option( 'postman_network_options' );

			if ( !empty( $network_options ) && is_array( $network_options ) ) {
				return isset( $network_options['post_smtp_global_settings'] ) && $network_options['post_smtp_global_settings'] == '1';
			}

			return false;
		}
		public function getVendorName() {
			return $this->vendorName;
		}
		public function getExpiryTime() {
			return $this->expiryTime;
		}
		public function getAccessToken() {
			return $this->accessToken;
		}
		public function getRefreshToken() {
			return $this->refreshToken;
		}
		public function setVendorName($name) {
			$this->vendorName = sanitize_text_field ( $name );
		}
		public function setExpiryTime($time) {
			$this->expiryTime = sanitize_text_field ( $time );
		}
		public function setAccessToken($token) {
			$this->accessToken = sanitize_text_field ( $token );
		}
		public function setRefreshToken($token) {
			$this->refreshToken = sanitize_text_field ( $token );
		}
	}
}