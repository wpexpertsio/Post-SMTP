<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! interface_exists( 'PostmanConfigTextHelper' ) ) {
	interface PostmanConfigTextHelper {
		public function isOauthHost();
		public function isGoogle();
		public function isMicrosoft();
		public function isYahoo();
		public function getCallbackUrl();
		public function getCallbackDomain();
		public function getClientIdLabel();
		public function getClientSecretLabel();
		public function getCallbackUrlLabel();
		public function getCallbackDomainLabel();
		public function getOwnerName();
		public function getServiceName();
		public function getApplicationDescription();
		public function getApplicationPortalName();
		public function getApplicationPortalUrl();
		public function getOAuthPort();
		public function getEncryptionType();
	}
}
if ( ! class_exists( 'PostmanAbstractConfigTextHelper' ) ) {

	/**
	 *
	 * @author jasonhendriks
	 */
	abstract class PostmanAbstractConfigTextHelper implements PostmanConfigTextHelper {
		public function getOAuthHelp() {
			$attention = __( 'Attention', 'post-smtp' );
			$errorMessage = sprintf( __('Check this article how to configure Gmail/Gsuite OAuth:<a href="%1$s" target="_blank">Read Here</a>', 'post-smtp' ), 'https://postmansmtp.com/how-to-configure-post-smtp-with-gmailgsuite-using-oauth/' );
			$text = sprintf( '<b style="color:red">%s!</b> %s', $attention, $errorMessage );
			
			return $text;
		}
		function isOauthHost() {
			return false;
		}
		function isGoogle() {
			return false;
		}
		function isMicrosoft() {
			return false;
		}
		function isYahoo() {
			return false;
		}
		public function getRequestPermissionLinkText() {
			/* translators: where %s is the Email Service Owner (e.g. Google, Microsoft or Yahoo) */
			return sprintf( _x( 'Grant permission with %s', 'Command to initiate OAuth authentication', 'post-smtp' ), $this->getOwnerName() );
		}
	}
}
if ( ! class_exists( 'PostmanGoogleOAuthScribe' ) ) {
	class PostmanGoogleOAuthScribe extends PostmanAbstractConfigTextHelper {
		public function isGoogle() {
			return true;
		}
		function isOauthHost() {
			return true;
		}
		public function getCallbackUrl() {
			// see https://codex.wordpress.org/Function_Reference/admin_url#Related
			return admin_url( 'options-general.php' ) . '?page=postman';
		}
		function getCallbackDomain() {
			$urlParts = parse_url( $this->getCallbackUrl() );
			if ( isset( $urlParts ['scheme'] ) && isset( $urlParts ['host'] ) ) {
				return $urlParts ['scheme'] . '://' . $urlParts ['host'];
			} else {
				throw new Exception();
			}
		}
		public function getClientIdLabel() {
			/* Translators: This description is specific to Google */
			return _x( 'Client ID', 'Name of the OAuth 2.0 Client ID', 'post-smtp' );
		}
		public function getClientSecretLabel() {
			/* Translators: This description is specific to Google */
			return _x( 'Client Secret', 'Name of the OAuth 2.0 Client Secret', 'post-smtp' );
		}
		public function getCallbackUrlLabel() {
			/* Translators: This description is specific to Google */
			return _x( 'Authorized redirect URI', 'Name of the Application Callback URI', 'post-smtp' );
		}
		public function getCallbackDomainLabel() {
			/* Translators: This description is specific to Google */
			return _x( 'Authorized JavaScript origins', 'Name of the Application Callback Domain', 'post-smtp' );
		}
		public function getOwnerName() {
			/* Translators: This description is specific to Google */
			return _x( 'Google', 'Name of the email service owner', 'post-smtp' );
		}
		public function getServiceName() {
			/* Translators: This description is specific to Google */
			return _x( 'Gmail', 'Name of the email service', 'post-smtp' );
		}
		public function getApplicationDescription() {
			/* Translators: This description is specific to Google */
			return _x( 'a Client ID for web application', 'Description of the email service OAuth 2.0 Application', 'post-smtp' );
		}
		public function getApplicationPortalName() {
			/* Translators: This description is specific to Google */
			return _x( 'Google Developers Console Gmail Wizard', 'Name of the email service portal', 'post-smtp' );
		}
		public function getApplicationPortalUrl() {
			return 'https://www.google.com/accounts/Logout?continue=https://console.developers.google.com/start/api?id=gmail';
		}
		public function getOAuthPort() {
			return 465;
		}
		public function getEncryptionType() {
			return PostmanOptions::SECURITY_TYPE_SMTPS;
		}
	}
}
if ( ! class_exists( 'PostmanMicrosoftOAuthScribe' ) ) {
	class PostmanMicrosoftOAuthScribe extends PostmanAbstractConfigTextHelper {
		public function isMicrosoft() {
			return true;
		}
		function isOauthHost() {
			return true;
		}
		public function getCallbackUrl() {
			return admin_url( 'options-general.php' );
		}
		function getCallbackDomain() {
			$urlParts = parse_url( $this->getCallbackUrl() );
			if ( isset( $urlParts ['host'] ) ) {
				return $urlParts ['host'];
			} else {
				throw new Exception();
			}
		}
		public function getClientIdLabel() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Client ID', 'Name of the OAuth 2.0 Client ID', 'post-smtp' );
		}
		public function getClientSecretLabel() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Client Secret', 'Name of the OAuth 2.0 Client Secret', 'post-smtp' );
		}
		public function getCallbackUrlLabel() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Redirect URL', 'Name of the Application Callback URI', 'post-smtp' );
		}
		public function getCallbackDomainLabel() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Root Domain', 'Name of the Application Callback Domain', 'post-smtp' );
		}
		public function getOwnerName() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Microsoft', 'Name of the email service owner', 'post-smtp' );
		}
		public function getServiceName() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Outlook.com', 'Name of the email service', 'post-smtp' );
		}
		public function getApplicationDescription() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'an Application', 'Description of the email service OAuth 2.0 Application', 'post-smtp' );
		}
		public function getApplicationPortalName() {
			/* Translators: This description is specific to Microsoft */
			return _x( 'Microsoft Developer Center', 'Name of the email service portal', 'post-smtp' );
		}
		public function getApplicationPortalUrl() {
			return 'https://account.live.com/developers/applications/index';
		}
		public function getOAuthPort() {
			return 587;
		}
		public function getEncryptionType() {
			return PostmanOptions::SECURITY_TYPE_STARTTLS;
		}
	}
}
if ( ! class_exists( 'PostmanYahooOAuthScribe' ) ) {
	class PostmanYahooOAuthScribe extends PostmanAbstractConfigTextHelper {
		public function isYahoo() {
			return true;
		}
		function isOauthHost() {
			return true;
		}
		public function getCallbackUrl() {
			return admin_url( 'options-general.php' ) . '?page=postman';
		}
		function getCallbackDomain() {
			$urlParts = parse_url( $this->getCallbackUrl() );
			if ( isset( $urlParts ['host'] ) ) {
				return $urlParts ['host'];
			} else {
				throw new Exception();
			}
		}
		public function getClientIdLabel() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Client ID', 'Name of the OAuth 2.0 Client ID', 'post-smtp' );
		}
		public function getClientSecretLabel() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Client Secret', 'Name of the OAuth 2.0 Client Secret', 'post-smtp' );
		}
		public function getCallbackUrlLabel() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Home Page URL', 'Name of the Application Callback URI', 'post-smtp' );
		}
		public function getCallbackDomainLabel() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Callback Domain', 'Name of the Application Callback Domain', 'post-smtp' );
		}
		public function getOwnerName() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Yahoo', 'Name of the email service owner', 'post-smtp' );
		}
		public function getServiceName() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Yahoo Mail', 'Name of the email service', 'post-smtp' );
		}
		public function getApplicationDescription() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'an Application', 'Description of the email service OAuth 2.0 Application', 'post-smtp' );
		}
		public function getApplicationPortalName() {
			/* Translators: This description is specific to Yahoo */
			return _x( 'Yahoo Developer Network', 'Name of the email service portal', 'post-smtp' );
		}
		public function getApplicationPortalUrl() {
			return 'https://developer.yahoo.com/apps/';
		}
		public function getOAuthPort() {
			return 465;
		}
		public function getEncryptionType() {
			return PostmanOptions::SECURITY_TYPE_SMTPS;
		}
	}
}
if ( ! class_exists( 'PostmanNonOAuthScribe' ) ) {
	class PostmanNonOAuthScribe extends PostmanAbstractConfigTextHelper {
		protected $hostname;
		public function __construct( $hostname ) {
			$this->hostname = $hostname;
		}
		public function isGoogle() {
			return PostmanUtils::endsWith( $this->hostname, 'gmail.com' );
		}
		public function isMicrosoft() {
			return PostmanUtils::endsWith( $this->hostname, 'live.com' );
		}
		public function isYahoo() {
			return PostmanUtils::endsWith( $this->hostname, 'yahoo.com' );
		}
		public function getOAuthHelp() {
			$text = __( 'Enter an Outgoing Mail Server with OAuth2 capabilities.', 'post-smtp' );
			return sprintf( '<span style="color:red" class="normal">%s</span>', $text );
		}
		public function getCallbackUrl() {
			return '';
		}
		function getCallbackDomain() {
			return '';
		}
		public function getClientIdLabel() {
			return _x( 'Client ID', 'Name of the OAuth 2.0 Client ID', 'post-smtp' );
		}
		public function getClientSecretLabel() {
			return _x( 'Client Secret', 'Name of the OAuth 2.0 Client Secret', 'post-smtp' );
		}
		public function getCallbackUrlLabel() {
			return _x( 'Redirect URI', 'Name of the Application Callback URI', 'post-smtp' );
		}
		public function getCallbackDomainLabel() {
			return _x( 'Website Domain', 'Name of the Application Callback Domain', 'post-smtp' );
		}
		public function getOwnerName() {
			return '';
		}
		public function getServiceName() {
			return '';
		}
		public function getApplicationDescription() {
			return '';
		}
		public function getApplicationPortalName() {
			return '';
		}
		public function getApplicationPortalUrl() {
			return '';
		}
		public function getOAuthPort() {
			return '';
		}
		public function getEncryptionType() {
			return '';
		}
		public function getRequestPermissionLinkText() {
			return __( 'Grant OAuth 2.0 Permission', 'post-smtp' );
		}
	}
}
