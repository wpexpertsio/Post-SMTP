<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PostmanConnectivityTestController {

		const PORT_TEST_SLUG = 'postman/port_test';

	// logging
	private $logger;

	// Holds the values to be used in the fields callbacks
	private $rootPluginFilenameAndPath;

	/**
	 * Constructor
	 *
	 * @param mixed $rootPluginFilenameAndPath
	 */
	public function __construct( $rootPluginFilenameAndPath ) {
		assert( ! empty( $rootPluginFilenameAndPath ) );
		assert( PostmanUtils::isAdmin() );
		assert( is_admin() );

		$this->logger = new PostmanLogger( get_class( $this ) );
		$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;

		PostmanUtils::registerAdminMenu( $this, 'addPortTestSubmenu' );

		// hook on the init event
		add_action( 'init', array(
				$this,
				'on_init',
		) );

		// initialize the scripts, stylesheets and form fields
		add_action( 'admin_init', array(
				$this,
				'on_admin_init',
		) );
	}

	/**
	 * Functions to execute on the init event
	 *
	 * "Typically used by plugins to initialize. The current user is already authenticated by this time."
	 * ref: http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request
	 */
	public function on_init() {
		// register Ajax handlers
		new PostmanPortTestAjaxController();
	}

	/**
	 * Fires on the admin_init method
	 */
	public function on_admin_init() {
				$this->registerStylesAndScripts();
	}

	/**
	 * Register and add settings
	 */
	private function registerStylesAndScripts() {
		if ( $this->logger->isTrace() ) {
			$this->logger->trace( 'registerStylesAndScripts()' );
		}
		// register the stylesheet and javascript external resources
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		wp_register_script( 'postman_port_test_script', plugins_url( 'Postman/Postman-Connectivity-Test/postman_port_test.js', $this->rootPluginFilenameAndPath ), array(
				PostmanViewController::JQUERY_SCRIPT,
				'jquery_validation',
				PostmanViewController::POSTMAN_SCRIPT,
				'sprintf',
		), $pluginData ['version'] );
	}

	/**
	 * Register the Email Test screen
	 */
	public function addPortTestSubmenu() {
		$page = add_submenu_page( null, sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), __( 'Postman SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanConnectivityTestController::PORT_TEST_SLUG, array(
				$this,
				'outputPortTestContent',
		) );
		// When the plugin options page is loaded, also load the stylesheet
		add_action( 'admin_print_styles-' . $page, array(
				$this,
				'enqueuePortTestResources',
		) );
	}

	/**
	 */
	function enqueuePortTestResources() {
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman_port_test_script' );
		$warning = __( 'Warning', 'post-smtp' );

        wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_email_test', array(
            'recipient' => '#' . PostmanSendTestEmailController::RECIPIENT_EMAIL_FIELD_NAME,
            'not_started' => _x( 'In Outbox', 'Email Test Status', 'post-smtp' ),
            'sending' => _x( 'Sending...', 'Email Test Status', 'post-smtp' ),
            'success' => _x( 'Success', 'Email Test Status', 'post-smtp' ),
            'failed' => _x( 'Failed', 'Email Test Status', 'post-smtp' ),
            'ajax_error' => __( 'Ajax Error', 'post-smtp' ),
        ) );
		PostmanConnectivityTestController::addLocalizeScriptForPortTest();
	}
	static function addLocalizeScriptForPortTest() {
		wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_port_test', array(
				'in_progress' => _x( 'Checking..', 'The "please wait" message', 'post-smtp' ),
				'open' => _x( 'Open', 'The port is open', 'post-smtp' ),
				'closed' => _x( 'Closed', 'The port is closed', 'post-smtp' ),
				'yes' => __( 'Yes', 'post-smtp' ),
				'no' => __( 'No', 'post-smtp' ),
			/* translators: where %d is a port number */
			'blocked' => __( 'No outbound route between this site and the Internet on Port %d.', 'post-smtp' ),
			/* translators: where %d is a port number and %s is a hostname */
			'try_dif_smtp' => __( 'Port %d is open, but not to %s.', 'post-smtp' ),
			/* translators: where %d is the port number and %s is the hostname */
			'success' => __( 'Port %d can be used for SMTP to %s.', 'post-smtp' ),
				'mitm' => sprintf( '%s: %s', __( 'Warning', 'post-smtp' ), __( 'connected to %1$s instead of %2$s.', 'post-smtp' ) ),
			/* translators: where %d is a port number and %s is the URL for the Postman Gmail Extension */
			'https_success' => __( 'Port %d can be used with the %s.', 'post-smtp' ),
		) );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function port_test_hostname_callback() {
		$hostname = PostmanTransportRegistry::getInstance()->getSelectedTransport()->getHostname();
		if ( empty( $hostname ) ) {
			$hostname = PostmanTransportRegistry::getInstance()->getActiveTransport()->getHostname();
		}
		printf( '<input type="text" id="input_hostname" name="postman_options[hostname]" value="%s" size="40" class="required"/>', $hostname );
	}

	/**
	 */
	public function outputPortTestContent() {
		print '<div class="wrap">';

		wp_nonce_field('post-smtp', 'security');

		PostmanViewController::outputChildPageHeader( __( 'Connectivity Test', 'post-smtp' ) );

		print '<p>';
		print __( 'This test determines which well-known ports are available for Postman to use.', 'post-smtp' );
		print '<form id="port_test_form_id" method="post">';

		wp_nonce_field('post-smtp', 'security' );

		printf( '<label for="hostname">%s</label>', __( 'Outgoing Mail Server Hostname', 'post-smtp' ) );
		$this->port_test_hostname_callback();
		submit_button( _x( 'Begin Test', 'Button Label', 'post-smtp' ), 'primary', 'begin-port-test', true );
		print '</form>';
		print '<table id="connectivity_test_table">';
		print sprintf( '<tr><th rowspan="2">%s</th><th rowspan="2">%s</th><th rowspan="2">%s</th><th rowspan="2">%s</th><th rowspan="2">%s</th><th colspan="5">%s</th></tr>', __( 'Transport', 'post-smtp' ), _x( 'Socket', 'A socket is the network term for host and port together', 'post-smtp' ), __( 'Status', 'post-smtp' ) . '<sup>*</sup>', __( 'Service Available', 'post-smtp' ), __( 'Server ID', 'post-smtp' ), __( 'Authentication', 'post-smtp' ) );
		print sprintf( '<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>', 'None', 'Login', 'Plain', 'CRAM-MD5', 'OAuth 2.0' );
		$sockets = PostmanTransportRegistry::getInstance()->getSocketsForSetupWizardToProbe();
		foreach ( $sockets as $socket ) {
			if ( $socket ['smtp'] ) {
				print sprintf( '<tr id="%s"><th class="name">%s</th><td class="socket">%s:%s</td><td class="firewall resettable">-</td><td class="service resettable">-</td><td class="reported_id resettable">-</td><td class="auth_none resettable">-</td><td class="auth_login resettable">-</td><td class="auth_plain resettable">-</td><td class="auth_crammd5 resettable">-</td><td class="auth_xoauth2 resettable">-</td></tr>', $socket ['id'], $socket ['transport_name'], $socket ['host'], $socket ['port'] );
			} else {
				print sprintf( '<tr id="%s"><th class="name">%s</th><td class="socket">%s:%s</td><td class="firewall resettable">-</td><td class="service resettable">-</td><td class="reported_id resettable">-</td><td colspan="5">%s</td></tr>', $socket ['id'], $socket ['transport_name'], $socket ['host'], $socket ['port'], __( 'n/a', 'post-smtp' ) );
			}
		}
		print '</table>';
		/* Translators: Where %s is the name of the service providing Internet connectivity test */
		printf( '<p class="portquiz" style="display:none; font-size:0.8em">* %s</p>', sprintf( __( 'According to %s', 'post-smtp' ), '<a target="_blank" href="http://portquiz.net">portquiz.net</a>' ) );
		printf( '<p class="ajax-loader" style="display:none"><img src="%s"/></p>', plugins_url( 'post-smtp/style/ajax-loader.gif' ) );
		print '<section id="conclusion" style="display:none">';
		print sprintf( '<h3>%s:</h3>', __( 'Summary', 'post-smtp' ) );
		print '<ol class="conclusion">';
		print '</ol>';
		print '</section>';
		print '<section id="blocked-port-help" style="display:none">';
		print sprintf( '<p><b>%s</b></p>', __( 'A test with <span style="color:red">"No"</span> Service Available indicates one or more of these issues:', 'post-smtp' ) );
		print '<ol>';
		printf( '<li>%s</li>', __( 'Your web host has placed a firewall between this site and the Internet', 'post-smtp' ) );
		printf( '<li>%s</li>', __( 'The SMTP hostname is wrong or the mail server does not provide service on this port', 'post-smtp' ) );
		/* translators: where (1) is the URL and (2) is the system */
		$systemBlockMessage = __( 'Your <a href="%1$s">%2$s configuration</a> is preventing outbound connections', 'post-smtp' );
		printf( '<li>%s</li>', sprintf( $systemBlockMessage, 'http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen', 'PHP' ) );
		printf( '<li>%s</li>', sprintf( $systemBlockMessage, 'http://wp-mix.com/disable-external-url-requests/', 'WordPress' ) );
		print '</ol></p>';
		print sprintf( '<p><b>%s</b></p>', __( 'If the issues above can not be resolved, your last option is to configure Postman to use an email account managed by your web host with an SMTP server managed by your web host.', 'post-smtp' ) );
		print '</section>';
		print '</div>';
	}
}

/**
 *
 * @author jasonhendriks
 */
class PostmanPortTestAjaxController {
	private $logger;
	/**
	 * Constructor
	 *
	 * @param PostmanOptions $options
	 */
	function __construct() {
		$this->logger = new PostmanLogger( get_class( $this ) );
		PostmanUtils::registerAjaxHandler( 'postman_get_hosts_to_test', $this, 'getPortsToTestViaAjax' );
		PostmanUtils::registerAjaxHandler( 'postman_wizard_port_test', $this, 'runSmtpTest' );
		PostmanUtils::registerAjaxHandler( 'postman_wizard_port_test_smtps', $this, 'runSmtpsTest' );
		PostmanUtils::registerAjaxHandler( 'postman_port_quiz_test', $this, 'runPortQuizTest' );
		PostmanUtils::registerAjaxHandler( 'postman_test_port', $this, 'runSmtpTest' );
		PostmanUtils::registerAjaxHandler( 'postman_test_smtps', $this, 'runSmtpsTest' );
	}

	/**
	 * This Ajax function determines which hosts/ports to test in both the Wizard Connectivity Test and direct Connectivity Test
	 *
	 * Given a single outgoing smtp server hostname, return an array of host/port
	 * combinations to run the connectivity test on
	 */
	function getPortsToTestViaAjax() {

	    check_admin_referer('post-smtp', 'security');

		$queryHostname = PostmanUtils::getRequestParameter( 'hostname' );
		// originalSmtpServer is what SmtpDiscovery thinks the SMTP server should be, given an email address
		$originalSmtpServer = PostmanUtils::getRequestParameter( 'original_smtp_server' );
		if ( $this->logger->isDebug() ) {
			$this->logger->debug( 'Probing available transports for sockets against hostname ' . $queryHostname );
		}
		$sockets = PostmanTransportRegistry::getInstance()->getSocketsForSetupWizardToProbe( $queryHostname, $originalSmtpServer );
		$response = array(
				'hosts' => $sockets,
		);
		wp_send_json_success( $response );
	}

	/**
	 * This Ajax function retrieves whether a TCP port is open or not
	 */
	function runPortQuizTest() {

	    check_admin_referer('post-smtp', 'security');

		$hostname = 'portquiz.net';
		$port = intval( PostmanUtils::getRequestParameter( 'port' ) );
		$this->logger->debug( 'testing TCP port: hostname ' . $hostname . ' port ' . $port );
		$portTest = new PostmanPortTest( $hostname, $port );
		$success = $portTest->genericConnectionTest();
		$this->buildResponse( $hostname, $port, $portTest, $success );
	}

	/**
	 * This Ajax function retrieves whether a TCP port is open or not.
	 * This is called by both the Wizard and Port Test
	 */
	function runSmtpTest() {

	    check_admin_referer('post-smtp', 'security');

		$hostname = trim( PostmanUtils::getRequestParameter( 'hostname' ) );
		$port = intval( PostmanUtils::getRequestParameter( 'port' ) );
		$transport = trim( PostmanUtils::getRequestParameter( 'transport' ) );
		$timeout = PostmanUtils::getRequestParameter( 'timeout' );
		$this->logger->trace( $timeout );
		$portTest = new PostmanPortTest( $hostname, $port );
		if ( isset( $timeout ) ) {
			$portTest->setConnectionTimeout( intval( $timeout ) );
			$portTest->setReadTimeout( intval( $timeout ) );
		}
		if ( $port != 443 ) {
			$this->logger->debug( sprintf( 'testing SMTP socket %s:%s (%s)', $hostname, $port, $transport ) );
			$success = $portTest->testSmtpPorts();
		} else {
			$this->logger->debug( sprintf( 'testing HTTPS socket %s:%s (%s)', $hostname, $port, $transport ) );
			$success = $portTest->testHttpPorts();
		}
		$this->buildResponse( $hostname, $port, $portTest, $success, $transport );
	}
	/**
	 * This Ajax function retrieves whether a TCP port is open or not
	 */
	function runSmtpsTest() {

	    check_admin_referer('post-smtp', 'security');

		$hostname = trim( PostmanUtils::getRequestParameter( 'hostname' ) );
		$port = intval( PostmanUtils::getRequestParameter( 'port' ) );
		$transport = trim( PostmanUtils::getRequestParameter( 'transport' ) );
		$transportName = trim( PostmanUtils::getRequestParameter( 'transport_name' ) );
		$this->logger->debug( sprintf( 'testing SMTPS socket %s:%s (%s)', $hostname, $port, $transport ) );
		$portTest = new PostmanPortTest( $hostname, $port );
		$portTest->transportName = $transportName;
		$success = $portTest->testSmtpsPorts();
		$this->buildResponse( $hostname, $port, $portTest, $success, $transport );
	}

	/**
	 *
	 * @param mixed $hostname
	 * @param mixed $port
	 * @param mixed $success
	 */
	private function buildResponse( $hostname, $port, PostmanPortTest $portTest, $success, $transport = '' ) {
		$this->logger->debug( sprintf( 'testing port result for %s:%s success=%s', $hostname, $port, $success ) );
		$response = array(
				'hostname' => $hostname,
				'hostname_domain_only' => $portTest->hostnameDomainOnly,
				'port' => $port,
				'protocol' => $portTest->protocol,
				'secure' => ($portTest->secure),
				'mitm' => ($portTest->mitm),
				'reported_hostname' => $portTest->reportedHostname,
				'reported_hostname_domain_only' => $portTest->reportedHostnameDomainOnly,
				'message' => $portTest->getErrorMessage(),
				'start_tls' => $portTest->startTls,
				'auth_plain' => $portTest->authPlain,
				'auth_login' => $portTest->authLogin,
				'auth_crammd5' => $portTest->authCrammd5,
				'auth_xoauth' => $portTest->authXoauth,
				'auth_none' => $portTest->authNone,
				'try_smtps' => $portTest->trySmtps,
				'success' => $success,
				'transport' => $transport,
		);
		$this->logger->trace( 'Ajax response:' );
		$this->logger->trace( $response );
		if ( $success ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}
}
