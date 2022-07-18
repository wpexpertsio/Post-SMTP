<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( ! interface_exists( 'PostmanOptionsInterface' ) ) {
	interface PostmanOptionsInterface {
		/**
		 * I'm stuck with these methods because of Gmail API Extension
		 */
		public function save();
		public function isNew();
		public function getLogLevel();
		public function getHostname();
		public function getPort();
		public function getMessageSenderEmail();
		public function getMessageSenderName();
		public function getClientId();
		public function getClientSecret();
		public function getTransportType();
		public function getAuthenticationType();
		public function getEncryptionType();
		public function getUsername();
		public function getPassword();
		public function getReplyTo();
		public function getConnectionTimeout();
		public function getReadTimeout();
		public function isSenderNameOverridePrevented();
		public function isAuthTypePassword();
		public function isAuthTypeOAuth2();
		public function isAuthTypeLogin();
		public function isAuthTypePlain();
		public function isAuthTypeCrammd5();
		public function isAuthTypeNone();

		/**
		 *
		 * @deprecated
		 */
		public function getSenderEmail();
		/**
		 *
		 * @deprecated
		 */
		public function getSenderName();
	}
}

if ( ! class_exists( 'PostmanOptions' ) ) {

	/**
	 * http://stackoverflow.com/questions/23880928/use-oauth-refresh-token-to-obtain-new-access-token-google-api
	 * http://pastebin.com/jA9sBNTk
	 *
	 * Make sure these emails are permitted (see http://en.wikipedia.org/wiki/E-mail_address#Internationalization):
	 */
	class PostmanOptions implements PostmanOptionsInterface {

		// the option database name
		const POSTMAN_OPTIONS = 'postman_options';
		const POSTMAN_NETWORK_OPTIONS = 'postman_network_options';

		// the options fields
		const VERSION = 'version';
		const ENVELOPE_SENDER = 'envelope_sender';
		const MESSAGE_SENDER_EMAIL = 'sender_email';
		const MESSAGE_SENDER_NAME = 'sender_name';
		const REPLY_TO = 'reply_to';
		const FORCED_TO_RECIPIENTS = 'forced_to';
		const FORCED_CC_RECIPIENTS = 'forced_cc';
		const FORCED_BCC_RECIPIENTS = 'forced_bcc';
		const ADDITIONAL_HEADERS = 'headers';
		const TEST_EMAIL = 'test_email';
		const HOSTNAME = 'hostname';
		const PORT = 'port';
		const TRANSPORT_TYPE = 'transport_type';
		const AUTHENTICATION_TYPE = 'auth_type';
		const AUTHENTICATION_TYPE_NONE = 'none';
		const AUTHENTICATION_TYPE_PLAIN = 'plain';
		const AUTHENTICATION_TYPE_LOGIN = 'login';
		const AUTHENTICATION_TYPE_CRAMMD5 = 'crammd5';
		const AUTHENTICATION_TYPE_OAUTH2 = 'oauth2';
		const SECURITY_TYPE = 'enc_type';
		const SECURITY_TYPE_NONE = 'none';
		const SECURITY_TYPE_SMTPS = 'ssl';
		const SECURITY_TYPE_STARTTLS = 'tls';
		const CLIENT_ID = 'oauth_client_id';
		const CLIENT_SECRET = 'oauth_client_secret';
		const BASIC_AUTH_USERNAME = 'basic_auth_username';
		const BASIC_AUTH_PASSWORD = 'basic_auth_password';
		const MANDRILL_API_KEY = 'mandrill_api_key';
		const SENDGRID_API_KEY = 'sendgrid_api_key';
		const MAILGUN_API_KEY = 'mailgun_api_key';
		const MAILGUN_DOMAIN_NAME = 'mailgun_domain_name';
		const MAILGUN_REGION = 'mailgun_region';
		const PREVENT_MESSAGE_SENDER_NAME_OVERRIDE = 'prevent_sender_name_override';
		const PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE = 'prevent_sender_email_override';
		const CONNECTION_TIMEOUT = 'connection_timeout';
		const READ_TIMEOUT = 'read_timeout';
		const LOG_LEVEL = 'log_level';
		const RUN_MODE = 'run_mode';
		const RUN_MODE_PRODUCTION = 'production';
		const RUN_MODE_LOG_ONLY = 'log_only';
		const RUN_MODE_IGNORE = 'ignore';
		const MAIL_LOG_ENABLED_OPTION = 'mail_log_enabled';
		const MAIL_LOG_ENABLED_OPTION_YES = 'true';
		const MAIL_LOG_ENABLED_OPTION_NO = 'false';
		const MAIL_LOG_MAX_ENTRIES = 'mail_log_max_entries';
		const STEALTH_MODE = 'stealth_mode';
		const TRANSCRIPT_SIZE = 'transcript_size';
		const TEMPORARY_DIRECTORY = 'tmp_dir';
		const DISABLE_EMAIL_VALIDAITON = 'disable_email_validation';
		const NOTIFICATION_SERVICE = 'notification_service';
        const NOTIFICATION_USE_CHROME = 'notification_use_chrome';
        const NOTIFICATION_CHROME_UID = 'notification_chrome_uid';
		const PUSHOVER_USER = 'pushover_user';
		const PUSHOVER_TOKEN = 'pushover_token';
		const SLACK_TOKEN = 'slack_token';

		// Fallback
        const FALLBACK_SMTP_ENABLED = 'fallback_smtp_enabled';
        const FALLBACK_SMTP_HOSTNAME = 'fallback_smtp_hostname';
        const FALLBACK_SMTP_PORT = 'fallback_smtp_port';
        const FALLBACK_SMTP_SECURITY = 'fallback_smtp_security';
		const FALLBACK_FROM_EMAIL = 'fallback_from_email';
        const FALLBACK_SMTP_USE_AUTH = 'fallback_smtp_use_auth';
        const FALLBACK_SMTP_USERNAME = 'fallback_smtp_username';
        const FALLBACK_SMTP_PASSWORD = 'fallback_smtp_password';

		// defaults
		const DEFAULT_TRANSCRIPT_SIZE = 128;
		const DEFAULT_STEALTH_MODE = false;
		const DEFAULT_RUN_MODE = self::RUN_MODE_PRODUCTION;
		const DEFAULT_MAIL_LOG_ENABLED = self::MAIL_LOG_ENABLED_OPTION_YES;
		const DEFAULT_MAIL_LOG_ENTRIES = 250;
		const DEFAULT_LOG_LEVEL = PostmanLogger::ERROR_INT;
		const DEFAULT_NOTIFICATION_SERVICE = 'default';
		const DEFAULT_TRANSPORT_TYPE = 'smtp'; // must match what's in PostmanSmtpModuleTransport
		const DEFAULT_TCP_READ_TIMEOUT = 60;
		const DEFAULT_TCP_CONNECTION_TIMEOUT = 10;
		const DEFAULT_PLUGIN_MESSAGE_SENDER_NAME_ENFORCED = false;
		const DEFAULT_PLUGIN_MESSAGE_SENDER_EMAIL_ENFORCED = false;
		const DEFAULT_TEMP_DIRECTORY = '/tmp';

		const SMTP_MAILERS = [
		    'phpmailer' => 'PHPMailer',
            'postsmtp' => 'PostSMTP'
        ];

		public $is_fallback = false;

		// options data
		private $options;

		// singleton instance
		public static function getInstance() {
			static $inst = null;
			if ( $inst === null ) {
				$inst = new PostmanOptions();
			}
			return $inst;
		}

		/**
		 * private constructor
		 */
		private function __construct() {
			$this->load();
		}

		public function save() {
            update_option( PostmanOptions::POSTMAN_OPTIONS, $this->options );
		}

		public function reload() {
			$this->load();
		}

		public function load() {

            $options = get_option( self::POSTMAN_OPTIONS );

		    if ( is_multisite() ) {
                $network_options = get_site_option( self::POSTMAN_NETWORK_OPTIONS );

                $blog_id = get_current_blog_id();
                if ( isset( $network_options['post_smtp_global_settings'] ) ) {
                    $blog_id = apply_filters( 'post_smtp_default_site_option', 1 );
                }

                switch_to_blog($blog_id);
                $options = get_option( self::POSTMAN_OPTIONS );
                restore_current_blog();
            }

            $this->options = $options;
		}

		public function isNew() {
			return ! isset( $this->options [ PostmanOptions::TRANSPORT_TYPE ] );
		}
		public function isMailLoggingEnabled() {
			$allowed = $this->isMailLoggingAllowed();
			$enabled = $this->getMailLoggingEnabled() == self::MAIL_LOG_ENABLED_OPTION_YES;
			return $allowed && $enabled;
		}
		public function getTempDirectory() {
			if ( isset( $this->options [ self::TEMPORARY_DIRECTORY ] ) ) {
				return $this->options [ self::TEMPORARY_DIRECTORY ];
			} else { 				return self::DEFAULT_TEMP_DIRECTORY; }
		}
		public function isMailLoggingAllowed() {
			return true;
		}
		public function isStealthModeEnabled() {
			if ( isset( $this->options [ PostmanOptions::STEALTH_MODE ] ) ) {
				return $this->options [ PostmanOptions::STEALTH_MODE ];
			} else { 				return self::DEFAULT_STEALTH_MODE; }
		}
		public function getMailLoggingEnabled() {
			if ( isset( $this->options [ PostmanOptions::MAIL_LOG_ENABLED_OPTION ] ) ) {
				return $this->options [ PostmanOptions::MAIL_LOG_ENABLED_OPTION ];
			} else { 				return self::DEFAULT_MAIL_LOG_ENABLED; }
		}
		public function getRunMode() {
			if ( defined( 'POST_SMTP_RUN_MODE' ) ) {
				return POST_SMTP_RUN_MODE;
			}
			
			if ( isset( $this->options [ self::RUN_MODE ] ) ) {
				return $this->options [ self::RUN_MODE ];
			} else { 				return self::DEFAULT_RUN_MODE; }
		}
		public function getMailLoggingMaxEntries() {
			if ( isset( $this->options [ PostmanOptions::MAIL_LOG_MAX_ENTRIES ] ) ) {
				return $this->options [ PostmanOptions::MAIL_LOG_MAX_ENTRIES ];
			} else { 				return self::DEFAULT_MAIL_LOG_ENTRIES; }
		}
		public function getTranscriptSize() {
			if ( isset( $this->options [ PostmanOptions::TRANSCRIPT_SIZE ] ) ) {
				return $this->options [ PostmanOptions::TRANSCRIPT_SIZE ];
			} else { 				return self::DEFAULT_TRANSCRIPT_SIZE; }
		}

		public function getLogLevel() {
			if ( isset( $this->options [ PostmanOptions::LOG_LEVEL ] ) ) {
				return $this->options [ PostmanOptions::LOG_LEVEL ];
			} else { 				return self::DEFAULT_LOG_LEVEL; }
		}

		public function getNotificationService() {
			if ( isset( $this->options [ PostmanOptions::NOTIFICATION_SERVICE ] ) ) {
				return $this->options [ PostmanOptions::NOTIFICATION_SERVICE ];
			} else {
				return self::DEFAULT_NOTIFICATION_SERVICE;
			}
		}

		public function getForcedToRecipients() {
			if ( isset( $this->options [ self::FORCED_TO_RECIPIENTS ] ) ) {
				return $this->options [ self::FORCED_TO_RECIPIENTS ]; }
		}
		public function getForcedCcRecipients() {
			if ( isset( $this->options [ self::FORCED_CC_RECIPIENTS ] ) ) {
				return $this->options [ self::FORCED_CC_RECIPIENTS ]; }
		}
		public function getForcedBccRecipients() {
			if ( isset( $this->options [ self::FORCED_BCC_RECIPIENTS ] ) ) {
				return $this->options [ self::FORCED_BCC_RECIPIENTS ]; }
		}
		public function getAdditionalHeaders() {
			if ( isset( $this->options [ self::ADDITIONAL_HEADERS ] ) ) {
				return $this->options [ self::ADDITIONAL_HEADERS ]; }
		}
		public function getHostname() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackHostname();
            }

			if ( isset( $this->options [ PostmanOptions::HOSTNAME ] ) ) {
				return $this->options [ PostmanOptions::HOSTNAME ]; }
		}

		public function getPort() {

            if ( $this->is_fallback ) {
                return $this->getFallbackPort();
            }

			if ( isset( $this->options [ PostmanOptions::PORT ] ) ) {
				return $this->options [ PostmanOptions::PORT ]; }
		}

		public function getEnvelopeSender() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackFromEmail();
            }

			if ( isset( $this->options [ PostmanOptions::ENVELOPE_SENDER ] ) ) {
				return $this->options [ PostmanOptions::ENVELOPE_SENDER ]; }
		}

		public function getMessageSenderEmail() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackFromEmail();
            }

			if ( isset( $this->options [ PostmanOptions::MESSAGE_SENDER_EMAIL ] ) ) {
				return $this->options [ PostmanOptions::MESSAGE_SENDER_EMAIL ]; }
		}

		public function getFallbackFromEmail() {
			if ( isset( $this->options [ PostmanOptions::FALLBACK_FROM_EMAIL ] ) ) {
				return $this->options [ PostmanOptions::FALLBACK_FROM_EMAIL ]; }
		}

		public function getMessageSenderName() {
			if ( isset( $this->options [ PostmanOptions::MESSAGE_SENDER_NAME ] ) ) {
				return $this->options [ PostmanOptions::MESSAGE_SENDER_NAME ]; }
		}
		public function getClientId() {
			if ( isset( $this->options [ PostmanOptions::CLIENT_ID ] ) ) {
				return $this->options [ PostmanOptions::CLIENT_ID ]; }
		}
		public function getClientSecret() {
			if ( isset( $this->options [ PostmanOptions::CLIENT_SECRET ] ) ) {
				return $this->options [ PostmanOptions::CLIENT_SECRET ]; }
		}

		public function getTransportType() {

		    if ( $this->is_fallback ) {
		        return 'smtp';
            }

			if ( isset( $this->options [ PostmanOptions::TRANSPORT_TYPE ] ) ) {
				return $this->options [ PostmanOptions::TRANSPORT_TYPE ]; }
		}

		public function getAuthenticationType() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackAuth();
            }

			if ( isset( $this->options [ PostmanOptions::AUTHENTICATION_TYPE ] ) ) {
				return $this->options [ PostmanOptions::AUTHENTICATION_TYPE ]; }
		}

		public function getEncryptionType() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackSecurity();
            }


			if ( isset( $this->options [ PostmanOptions::SECURITY_TYPE ] ) ) {
				return $this->options [ PostmanOptions::SECURITY_TYPE ];
			}
		}

		public function getUsername() {

		    if ( $this->is_fallback ) {
		        return $this->getFallbackUsername();
            }

            if ( defined( 'POST_SMTP_AUTH_USERNAME' ) ) {
                return POST_SMTP_AUTH_USERNAME;
            }

			if ( isset( $this->options [ PostmanOptions::BASIC_AUTH_USERNAME ] ) ) {
				return $this->options [ PostmanOptions::BASIC_AUTH_USERNAME ];
			}
		}

        public function getPassword() {

            if ( $this->is_fallback ) {
                return $this->getFallbackPassword();
            }

            if ( defined( 'POST_SMTP_AUTH_PASSWORD' ) ) {
                return POST_SMTP_AUTH_PASSWORD;
            }

            if ( isset( $this->options [ PostmanOptions::BASIC_AUTH_PASSWORD ] ) ) {
                return base64_decode( $this->options [ PostmanOptions::BASIC_AUTH_PASSWORD ] );
            }
        }

        // Fallback
        public function getFallbackIsEnabled() {
            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_ENABLED ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_ENABLED ];
            }
			return false;
        }

        public function getFallbackHostname() {
            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_HOSTNAME ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_HOSTNAME ];
            }
        }

        public function getFallbackPort() {
            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_PORT ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_PORT ];
            }
        }

        public function getFallbackSecurity() {
            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_SECURITY ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_SECURITY ];
            }
        }

        public function getFallbackAuth() {
            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_USE_AUTH ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_USE_AUTH ];
            }
        }

        public function getFallbackUsername() {
            if ( defined( 'POST_SMTP_FALLBACK_AUTH_USERNAME' ) ) {
                return POST_SMTP_FALLBACK_AUTH_USERNAME;
            }

            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_USERNAME ] ) ) {
                return $this->options [ PostmanOptions::FALLBACK_SMTP_USERNAME ];
            }
        }


        public function getFallbackPassword() {
            if ( defined( 'POST_SMTP_FALLBACK_AUTH_PASSWORD' ) ) {
                return POST_SMTP_FALLBACK_AUTH_PASSWORD;
            }

            if ( isset( $this->options [ PostmanOptions::FALLBACK_SMTP_PASSWORD ] ) ) {
                return base64_decode( $this->options [ PostmanOptions::FALLBACK_SMTP_PASSWORD ] );
            }
        }

        // End Fallback

		public function getMandrillApiKey() {
			if ( defined( 'POST_SMTP_API_KEY' ) ) {
				return POST_SMTP_API_KEY;
			}
			
			if ( isset( $this->options [ PostmanOptions::MANDRILL_API_KEY ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::MANDRILL_API_KEY ] ); }
		}
		public function getSendGridApiKey() {
			if ( defined( 'POST_SMTP_API_KEY' ) ) {
				return POST_SMTP_API_KEY;
			}

			if ( isset( $this->options [ PostmanOptions::SENDGRID_API_KEY ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::SENDGRID_API_KEY ] ); }
		}
		public function getMailgunApiKey() {
			if ( defined( 'POST_SMTP_API_KEY' ) ) {
				return POST_SMTP_API_KEY;
			}

			if ( isset( $this->options [ PostmanOptions::MAILGUN_API_KEY ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::MAILGUN_API_KEY ] ); }
		}
		public function getMailgunDomainName() {
			if ( isset( $this->options [ PostmanOptions::MAILGUN_DOMAIN_NAME ] ) ) {
				return $this->options [ PostmanOptions::MAILGUN_DOMAIN_NAME ];
			}
		}

		public function getMailgunRegion() {
			if ( isset( $this->options [ PostmanOptions::MAILGUN_REGION ] ) ) {
				return $this->options [ PostmanOptions::MAILGUN_REGION ];
			}
		}

		public function getPushoverUser() {
			if ( isset( $this->options [ PostmanOptions::PUSHOVER_USER ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::PUSHOVER_USER ] );
			}
		}

		public function getPushoverToken() {
			if ( isset( $this->options [ PostmanOptions::PUSHOVER_TOKEN ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::PUSHOVER_TOKEN ] );
			}
		}

		public function getSlackToken() {
			if ( isset( $this->options [ PostmanOptions::SLACK_TOKEN ] ) ) {
				return base64_decode( $this->options [ PostmanOptions::SLACK_TOKEN ] );
			}
		}

        public function useChromeExtension() {
            if ( isset( $this->options [ PostmanOptions::NOTIFICATION_USE_CHROME ] ) ) {
                return $this->options [ PostmanOptions::NOTIFICATION_USE_CHROME ];
            }
        }

        public function getNotificationChromeUid() {
            if ( isset( $this->options [ PostmanOptions::NOTIFICATION_CHROME_UID ] ) ) {
                return base64_decode( $this->options [ PostmanOptions::NOTIFICATION_CHROME_UID ] );
            }
        }
		
		public function getReplyTo() {
			if ( isset( $this->options [ PostmanOptions::REPLY_TO ] ) ) {
				return $this->options [ PostmanOptions::REPLY_TO ]; }
		}
		public function getConnectionTimeout() {
			if ( ! empty( $this->options [ self::CONNECTION_TIMEOUT ] ) ) {
				return $this->options [ self::CONNECTION_TIMEOUT ];
			} else { 				return self::DEFAULT_TCP_CONNECTION_TIMEOUT; }
		}
		public function getReadTimeout() {
			if ( ! empty( $this->options [ self::READ_TIMEOUT ] ) ) {
				return $this->options [ self::READ_TIMEOUT ];
			} else { 				return self::DEFAULT_TCP_READ_TIMEOUT; }
		}
		public function isPluginSenderNameEnforced() {
			if ( $this->isNew() ) {
				return self::DEFAULT_PLUGIN_MESSAGE_SENDER_NAME_ENFORCED; }
			if ( isset( $this->options [ PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ] ) ) {
				return $this->options [ PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ]; }
		}

		public function isEmailValidationDisabled() {
			if ( isset( $this->options [ PostmanOptions::DISABLE_EMAIL_VALIDAITON ] ) ) {
				return $this->options [ PostmanOptions::DISABLE_EMAIL_VALIDAITON ]; }
		}
		
		/**
		 * (non-PHPdoc)
		 *
		 * @see PostmanOptions::isSenderNameOverridePrevented()
		 * @deprecated by isPluginSenderNameEnforced
		 */
		public function isSenderNameOverridePrevented() {
			return $this->isPluginSenderEmailEnforced();
		}
		public function isPluginSenderEmailEnforced() {

			if ( $this->isNew() ) {
				return self::DEFAULT_PLUGIN_MESSAGE_SENDER_EMAIL_ENFORCED; }
			if ( isset( $this->options [ PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ] ) ) {
				return $this->options [ PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ]; }
		}
		/**
		 *
		 * @deprecated by isPluginSenderEmailEnforced
		 */
		public function isSenderEmailOverridePrevented() {
			return $this->isPluginSenderEmailEnforced();
		}
		private function setSenderEmail( $senderEmail ) {
			$this->options [ PostmanOptions::MESSAGE_SENDER_EMAIL ] = $senderEmail;
		}
		public function setMessageSenderEmailIfEmpty( $senderEmail ) {
			if ( empty( $this->options [ PostmanOptions::MESSAGE_SENDER_EMAIL ] ) ) {
				$this->setSenderEmail( $senderEmail );
			}
		}
		private function setSenderName( $senderName ) {
			$this->options [ PostmanOptions::MESSAGE_SENDER_NAME ] = $senderName;
		}
		public function setMessageSenderNameIfEmpty( $senderName ) {
			if ( empty( $this->options [ PostmanOptions::MESSAGE_SENDER_NAME ] ) ) {
				$this->setSenderName( $senderName );
			}
		}

		public function getSmtpMailer() {
		    if ( empty($this->options [ 'smtp_mailers' ]) ) {
		        return 'postsmtp';
            }

            return $this->options [ 'smtp_mailers' ];
        }

		public function isAuthTypePassword() {
			return $this->isAuthTypeLogin() || $this->isAuthTypeCrammd5() || $this->isAuthTypePlain();
		}
		public function isAuthTypeOAuth2() {
			return PostmanOptions::AUTHENTICATION_TYPE_OAUTH2 == $this->getAuthenticationType();
		}
		public function isAuthTypeLogin() {
			return PostmanOptions::AUTHENTICATION_TYPE_LOGIN == $this->getAuthenticationType();
		}
		public function isAuthTypePlain() {
			return PostmanOptions::AUTHENTICATION_TYPE_PLAIN == $this->getAuthenticationType();
		}
		public function isAuthTypeCrammd5() {
			return PostmanOptions::AUTHENTICATION_TYPE_CRAMMD5 == $this->getAuthenticationType();
		}
		public function isAuthTypeNone() {
			return PostmanOptions::AUTHENTICATION_TYPE_NONE == $this->getAuthenticationType();
		}
		/**
		 *
		 * @deprecated Required by the Postman Gmail Extension
		 *
		 * @see PostmanOptionsInterface::getSenderEmail()
		 */
		public function getSenderEmail() {
			return $this->getMessageSenderEmail();
		}
		/**
		 *
		 * @deprecated Required by the Postman Gmail Extension
		 *
		 * @see PostmanOptionsInterface::getSenderEmail()
		 */
		public function getSenderName() {
			return $this->getMessageSenderName();
		}

		/**
		 *
		 * @return string
		 */
		public function export() {
			if ( PostmanPreRequisitesCheck::checkZlibEncode() ) {
				$data = $this->options;
				$data ['version'] = PostmanState::getInstance()->getVersion();
				foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
					$data = $transport->prepareOptionsForExport( $data );
				}
				$data = base64_encode( gzcompress( json_encode( $data ), 9 ) );
				return $data;
			}
		}

		/**
		 *
		 * @param mixed $data
		 */
		public function import( $data ) {
			if ( PostmanPreRequisitesCheck::checkZlibEncode() ) {
				$logger = new PostmanLogger( get_class( $this ) );
				$logger->debug( 'Importing Settings' );
				$base64 = $data;
				$logger->trace( $base64 );
				$gz = base64_decode( $base64 );
				$logger->trace( $gz );
				$json = @gzuncompress( $gz );
				$logger->trace( $json );
				if ( ! empty( $json ) ) {
					$data = json_decode( $json, true );
					$logger->trace( $data );
					{
						// overwrite the current version with the version from the imported options
						// this way database upgrading can occur
						$postmanState = get_option( 'postman_state' );
						$postmanState ['version'] = $data ['version'];
						$logger->trace( sprintf( 'Setting Postman version to %s', $postmanState ['version'] ) );
						assert( $postmanState ['version'] == $data ['version'] );
						update_option( 'postman_state', $postmanState );
					}
					$this->options = $data;
					$logger->info( 'Imported data' );
					$this->save();
					return true;
				} else {
					$logger->error( 'Could not import data - data error' );
					return false;
				}
			}
		}
	}
}
