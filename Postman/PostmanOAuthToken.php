<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'PostmanOAuthToken.php' ) ) {

	class PostmanOAuthToken {
		const OPTIONS_NAME          = 'postman_auth_token';
				const REFRESH_TOKEN = 'refresh_token';
		const EXPIRY_TIME           = 'auth_token_expires';
		const ACCESS_TOKEN          = 'access_token';
		const VENDOR_NAME           = 'vendor_name';
				private $vendorName;
		private $accessToken;
		private $refreshToken;
		private $expiryTime;

		// singleton instance
		public static function getInstance() {
			static $inst = null;
			if ( $inst === null ) {
				$inst = new PostmanOAuthToken();
			}
			return $inst;
		}

		// private constructor
		private function __construct() {
			$this->load();
		}

		/**
		 * Is there a valid access token and refresh token
		 */
		public function isValid() {
			$accessToken  = $this->getAccessToken();
			$refreshToken = $this->getRefreshToken();
			return ! ( empty( $accessToken ) || empty( $refreshToken ) );
		}

		/**
		 * Load the Postman OAuth token properties to the database
		 */
		private function load() {
			$a = get_option( self::OPTIONS_NAME );

			if ( ! is_array( $a ) ) {
				return;
			}

			if ( isset( $a [ self::ACCESS_TOKEN ] ) ) {
				$this->setAccessToken( $a [ self::ACCESS_TOKEN ] );
			}

			if ( isset( $a [ self::REFRESH_TOKEN ] ) ) {
				$this->setRefreshToken( $a [ self::REFRESH_TOKEN ] );
			}

			if ( isset( $a [ self::EXPIRY_TIME ] ) ) {
				$this->setExpiryTime( $a [ self::EXPIRY_TIME ] );
			}

			if ( isset( $a [ self::VENDOR_NAME ] ) ) {
				$this->setVendorName( $a [ self::VENDOR_NAME ] );
			}
		}

		/**
		 * Save the Postman OAuth token properties to the database
		 */
		public function save() {
			$a [ self::ACCESS_TOKEN ]  = $this->getAccessToken();
			$a [ self::REFRESH_TOKEN ] = $this->getRefreshToken();
			$a [ self::EXPIRY_TIME ]   = $this->getExpiryTime();
			$a [ self::VENDOR_NAME ]   = $this->getVendorName();
			update_option( self::OPTIONS_NAME, $a );
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
		public function setVendorName( $name ) {
			$this->vendorName = sanitize_text_field( $name );
		}
		public function setExpiryTime( $time ) {
			$this->expiryTime = sanitize_text_field( $time );
		}
		public function setAccessToken( $token ) {
			$this->accessToken = sanitize_text_field( $token );
		}
		public function setRefreshToken( $token ) {
			$this->refreshToken = sanitize_text_field( $token );
		}
	}
}
