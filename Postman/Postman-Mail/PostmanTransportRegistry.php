<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'PostmanModuleTransport.php';
require_once 'PostmanZendMailTransportConfigurationFactory.php';

/**
 *
 * @author jasonhendriks
 */
class PostmanTransportRegistry {
	private $transports;
	private $logger;
	private $registration_attempted = false;

	/**
	 */
	private function __construct() {
		$this->logger = new PostmanLogger( get_class( $this ) );
	}

	// singleton instance
	public static function getInstance() {
		static $inst = null;
		if ( $inst === null ) {
			$inst = new PostmanTransportRegistry();
		}
		return $inst;
	}
	public function registerTransport( PostmanModuleTransport $instance ) {
		$this->transports [ $instance->getSlug() ] = $instance;
		$instance->init();
	}
	public function getTransports() {
		return $this->transports;
	}

	/**
	 * Centralized method to register all transports.
	 * Called from Postman::on_init() and ensureTransportsRegistered() (before init).
	 * Idempotent — safe to call multiple times.
	 *
	 * @param string $rootPluginFilenameAndPath The root plugin file path
	 * @return bool True if transports were registered, false if already registered
	 */
	public function registerAllTransports( $rootPluginFilenameAndPath ) {
		if ( $this->transports !== null && ! empty( $this->transports ) ) {
			return false;
		}

		if ( $this->registration_attempted ) {
			return $this->transports !== null && ! empty( $this->transports );
		}

		$this->registration_attempted = true;

		if ( ! class_exists( 'PostmanDefaultModuleTransport' ) ) {
			$pluginPath = defined( 'POST_SMTP_PATH' ) ? POST_SMTP_PATH : dirname( dirname( __FILE__ ) );

			require_once $pluginPath . '/Postman/Postman-Mail/PostmanDefaultModuleTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSmtpModuleTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanGmailApiModuleTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMandrillTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSendGridTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMailerSendTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMailgunTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSendinblueTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMailtrapTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanResendTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanCloudflareTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSmtpcomTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMailjetTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSendpulseTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanPostmarkTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSparkPostTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanElasticEmailTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSmtp2GoTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanEmailitTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanMailerooTransport.php';
			require_once $pluginPath . '/Postman/Postman-Mail/PostmanSweegoTransport.php';
		}

		$this->registerTransport( new PostmanDefaultModuleTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSmtpModuleTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanGmailApiModuleTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMandrillTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSendGridTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMailerSendTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMailgunTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSendinblueTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMailtrapTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanResendTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanCloudflareTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSmtpcomTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMailjetTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSendpulseTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanPostmarkTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSparkPostTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanElasticEmailTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSmtp2GoTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanEmailitTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanMailerooTransport( $rootPluginFilenameAndPath ) );
		$this->registerTransport( new PostmanSweegoTransport( $rootPluginFilenameAndPath ) );

		do_action( 'postsmtp_register_transport', $this );

		if ( $this->logger->isDebug() ) {
			$this->logger->debug( 'All transports registered successfully' );
		}

		return true;
	}

	/**
	 * Register transports on demand when wp_mail() runs before the init hook
	 * (e.g. Wordfence / AIOS unlock emails on blocked admin login).
	 */
	private function ensureTransportsRegistered() {
		if ( $this->transports !== null ) {
			return;
		}

		if ( class_exists( 'Postman' ) && property_exists( 'Postman', 'rootPlugin' ) && ! empty( Postman::$rootPlugin ) ) {
			$registered = $this->registerAllTransports( Postman::$rootPlugin );

			if ( $registered && $this->logger->isDebug() ) {
				$this->logger->debug( 'Transports registered on-demand before init hook' );
			}
		}
	}

	/**
	 * Retrieve a Transport by slug
	 * Look up a specific Transport use:
	 * A) when retrieving the transport saved in the database
	 * B) when querying what a theoretical scenario involving this transport is like
	 * (ie.for ajax in config screen)
	 *
	 * @param mixed $slug
	 */
	public function getTransport( $slug ) {
		$transports = $this->getTransports();
		if ( isset( $transports [ $slug ] ) ) {
			return $transports [ $slug ];
		}
	}

	/**
	 * A short-hand way of showing the complete delivery method
	 *
	 * @param PostmanModuleTransport $transport
	 * @return string
	 */
	public function getPublicTransportUri( PostmanModuleTransport $transport ) {
		return $transport->getPublicTransportUri();
	}

	/**
	 * Determine if a specific transport is registered in the directory.
	 *
	 * @param mixed $slug
	 */
	public function isRegistered( $slug ) {
		$transports = $this->getTransports();
		return isset( $transports [ $slug ] );
	}

	/**
	 * Retrieve the transport Postman is currently configured with.
	 *	
	 * @return PostmanModuleTransport|null
	 * @deprecated 2.1.4 use getActiveTransport()
	 * @see getActiveTransport()
	 */
	public function getCurrentTransport() {
		$selectedTransport = PostmanOptions::getInstance()->getTransportType() ?? '';
		$transports = $this->getTransports();
		if ( $transports !== null && isset( $transports [ $selectedTransport ] ) ) {
			return $transports [ $selectedTransport ];
		} elseif ( $transports !== null && isset( $transports ['default'] ) ) {
			return $transports ['default'];
		}
		return null;
	}

	/**
	 *
	 * @param PostmanOptions    $options
	 * @param PostmanOAuthToken $token
	 * @return PostmanModuleTransport|null
	 */
	public function getActiveTransport() {
		// Ensure transports are registered if they haven't been yet (before init).
		$this->ensureTransportsRegistered();

	    // During fallback mode, always use SMTP transport
	    $options = PostmanOptions::getInstance();
	    if ( $options->is_fallback ) {
	        $transports = $this->getTransports();
	        if ( $transports !== null && isset( $transports['smtp'] ) ) {
	            return $transports['smtp'];
	        } elseif ( $transports !== null && isset( $transports['default'] ) ) {
	            return $transports['default'];
	        }
	        return null;
	    }
	    
		$selectedTransport = PostmanOptions::getInstance()->getTransportType() ?? '';
		
		$transports = $this->getTransports();
		if ( $transports !== null && isset( $transports [ $selectedTransport ] ) ) {
			$transport = $transports [ $selectedTransport ];
			if ( $transport->getSlug() == $selectedTransport && $transport->isConfiguredAndReady() ) {
				return $transport;
			}
		}
		if ( $transports !== null && isset( $transports ['default'] ) ) {
			return $transports ['default'];
		}
		return null;
	}

	/**
	 * Retrieve the transport Postman is currently configured with.
	 *
	 * @return PostmanModuleTransport|null
	 */
	public function getSelectedTransport() {
		$selectedTransport = PostmanOptions::getInstance()->getTransportType() ?? '';
		$transports = $this->getTransports();
		if ( $transports !== null && isset( $transports [ $selectedTransport ] ) ) {
			return $transports [ $selectedTransport ];
		} elseif ( $transports !== null && isset( $transports ['default'] ) ) {
			return $transports ['default'];
		}
		return null;
	}

	/**
	 * Determine whether to show the Request Permission link on the main menu
	 *
	 * This link is displayed if
	 * 1. the current transport requires OAuth 2.0
	 * 2. the transport is properly configured
	 * 3. we have a valid Client ID and Client Secret without an Auth Token
	 *
	 * @param PostmanOptions $options
	 * @return boolean
	 */
	public function isRequestOAuthPermissionAllowed( PostmanOptions $options, PostmanOAuthToken $authToken ) {
		// does the current transport use OAuth 2.0
		$oauthUsed = self::getSelectedTransport()->isOAuthUsed( $options->getAuthenticationType() );

		// is the transport configured
		if ( $oauthUsed ) {
			$configured = self::getSelectedTransport()->isConfiguredAndReady();
		}

		return $oauthUsed && $configured;
	}

	/**
	 * Polls all the installed transports to get a complete list of sockets to probe for connectivity
	 *
	 * @param mixed $hostname
	 * @param mixed $isGmail
	 * @return multitype:
	 */
	public function getSocketsForSetupWizardToProbe( $hostname = 'localhost', $smtpServerGuess = null ) {
		$hosts = array();
		if ( $this->logger->isDebug() ) {
			$this->logger->debug( sprintf( 'Getting sockets for Port Test given hostname %s and smtpServerGuess %s', $hostname, $smtpServerGuess ) );
		}

		$transports = $this->getTransports();
		if ( $hostname !== 'smtp.gmail.com' ) {
			unset( $transports['gmail_api'] );
		}
		foreach ( $transports as $transport ) {
			$socketsToTest = $transport->getSocketsForSetupWizardToProbe( $hostname, $smtpServerGuess );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'sockets to test:' );
				$this->logger->trace( $socketsToTest );
			}
			$hosts = array_merge( $hosts, $socketsToTest );
			if ( $this->logger->isDebug() ) {
				$this->logger->debug( sprintf( 'Transport %s returns %d sockets ', $transport->getName(), sizeof( $socketsToTest ) ) );
			}
		}
		return $hosts;
	}

	/**
	 * If the host port is a possible configuration option, recommend it
	 *
	 * $hostData includes ['host'] and ['port']
	 *
	 * response should include ['success'], ['message'], ['priority']
	 *
	 * @param mixed $hostData
	 */
	public function getRecommendation( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {
		$scrubbedUserAuthOverride = $this->scrubUserOverride( $hostData, $userAuthOverride );
		$transport = $this->getTransport( $hostData->transport );
		$recommendation = $transport->getConfigurationBid( $hostData, $scrubbedUserAuthOverride, $originalSmtpServer );
		if ( $this->logger->isDebug() ) {
			$this->logger->debug( sprintf( 'Transport %s bid %s', $transport->getName(), $recommendation ['priority'] ) );
		}
		return $recommendation;
	}

	/**
	 *
	 * @param PostmanWizardSocket $hostData
	 * @param mixed             $userAuthOverride
	 * @return NULL
	 */
	private function scrubUserOverride( PostmanWizardSocket $hostData, $userAuthOverride ) {
		$this->logger->trace( 'before scrubbing userAuthOverride: ' . $userAuthOverride );

		// validate userAuthOverride
		if ( ! ($userAuthOverride == 'oauth2' || $userAuthOverride == 'password' || $userAuthOverride == 'none') ) {
			$userAuthOverride = null;
		}

		// validate the userAuthOverride
		if ( ! $hostData->auth_xoauth ) {
			if ( $userAuthOverride == 'oauth2' ) {
				$userAuthOverride = null;
			}
		}
		if ( ! $hostData->auth_crammd5 && ! $hostData->authPlain && ! $hostData->auth_login ) {
			if ( $userAuthOverride == 'password' ) {
				$userAuthOverride = null;
			}
		}
		if ( ! $hostData->auth_none ) {
			if ( $userAuthOverride == 'none' ) {
				$userAuthOverride = null;
			}
		}
		$this->logger->trace( 'after scrubbing userAuthOverride: ' . $userAuthOverride );
		return $userAuthOverride;
	}

	/**
	 */
	public function getReadyMessage() {
		
		$message = array();
		$transport = $this->getCurrentTransport();
		
		if ( $transport && $transport->isConfiguredAndReady() ) {
			if ( PostmanOptions::getInstance()->getRunMode() != PostmanOptions::RUN_MODE_PRODUCTION ) {
				$message = array(
					'error' => true,
					'message' => __( 'Postman is in <em>non-Production</em> mode and is dumping all emails.', 'post-smtp' ),
				);
			} else {
				$message = array(
					'error' => false,
					'message' => __( 'Postman is configured.', 'post-smtp' ),
				);
			}
		} else {
			$message = array(
				'error' => true,
				'message' => __( 'Postman is <em>not</em> configured and is mimicking out-of-the-box WordPress email delivery.', 'post-smtp' ),
			);
		}
	
		/**
		 * Filters Dashobard Notice
		 * 
		 * @since 2.6.0
		 * @version 1.0.0
		 */
		return apply_filters( 'post_smtp_dashboard_notice', $message );
	
	}
}
