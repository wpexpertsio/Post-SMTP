<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanDiagnosticTestController {
	const DIAGNOSTICS_SLUG = 'postman/diagnostics';

	// logging
	private $logger;
	private $options;

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
		$this->options = PostmanOptions::getInstance();

		// register the admin menu
		PostmanUtils::registerAdminMenu( $this, 'addDiagnosticsSubmenu' );

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
		new PostmanGetDiagnosticsViaAjax();
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

		// register the javascript resource
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		wp_register_script( 'postman_diagnostics_script', plugins_url( 'Postman/Postman-Diagnostic-Test/postman_diagnostics.js', $this->rootPluginFilenameAndPath ), array(
				PostmanViewController::JQUERY_SCRIPT,
				PostmanViewController::POSTMAN_SCRIPT,
		), $pluginData ['version'] );
	}

	/**
	 * Register the Diagnostics screen
	 */
	public function addDiagnosticsSubmenu() {
		$page = add_submenu_page( ' ', sprintf( __( '%s Setup', 'post-smtp' ), __( 'Postman SMTP', 'post-smtp' ) ), __( 'Postman SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanDiagnosticTestController::DIAGNOSTICS_SLUG, array(
				$this,
				'outputDiagnosticsContent',
		) );
		// When the plugin options page is loaded, also load the stylesheet
		add_action( 'admin_print_styles-' . $page, array(
				$this,
				'enqueueDiagnosticsScreenStylesheet',
		) );
	}
	function enqueueDiagnosticsScreenStylesheet() {
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman_diagnostics_script' );
	}

	/**
	 */
	public function outputDiagnosticsContent() {
		// test features
		print '<div class="wrap">';

		PostmanViewController::outputChildPageHeader( __( 'Diagnostic Test', 'post-smtp' ) );

		?>
        <form>
            <?php wp_nonce_field('post-smtp', 'security' ); ?>
        </form>

		<div class="post_smtp_diagnostic_container">
			<form action="#" id="post_smtp_diagnostic_form" method="POST">
				<h3><?php echo __( 'Send Diagnostic Report via Email', 'post-smtp' ); ?></h3>
				<div class="form-group">
					<label for="post-smtp-diagnostic-email-address"><?php echo __( 'Email Address', 'post-smtp' ); ?></label>
					<input type="email" id="post-smtp-diagnostic-email-address" name="post_smtp_diagnostic_email_address" value="support@wpexperts.io" required>
				</div>
				<div class="form-group">
					<label for="post-smtp-diagnostic-ticket-number"><?php echo __( 'Ticket Number / Forum Username', 'post-smtp' ); ?></label>
					<input type="text" id="post-smtp-diagnostic-ticket-number" name="post_smtp_diagnostic_ticket_number" required placeholder="<?php echo __( 'Enter your ticket number or forum username', 'post-smtp' ); ?>">
				</div>
				<div class="form-group">
					<p class="report_validation_error"><?php echo __( 'Please fill in all required fields.', 'post-smtp' ); ?></p>
					<button type="submit" class="post-smtp-diagnostic-submit-btn"><?php echo __( 'Send', 'post-smtp' ); ?></button>
					<p class="report_sent_message"><?php echo __( 'Diagnostic Report Sent Successfully.', 'post-smtp' ); ?></p>
				</div>
				<hr>
				<div class="form-group">
					<label for="post-smtp-diagnostic-ticket-number"><?php echo __( 'Are you having issues with Postman?', 'post-smtp' ); ?></label>
					<?php
					printf( '<p>%s</p>', sprintf( __( 'Please check the <a href="%1$s">troubleshooting and error messages</a> page and the <a href="%2$s">support forum</a>.', 'post-smtp' ), 'https://wordpress.org/plugins/post-smtp/other_notes/', 'https://wordpress.org/support/plugin/post-smtp' ) );
					?>
				</div>
				<hr>
				<div class="form-group copy_div">
					<h3 class="diagnostic-report-heading">
						<?php echo __( 'Diagnostic Test Report', 'post-smtp' ); ?>
						<button type="submit" class="copy_diagnostic_report"><?php echo __( 'Copy Report', 'post-smtp' ); ?></button>
					</h3>
					<table id="diagnostic-text" border='1' cellpadding='10' cellspacing='0'>
						<p class="fetching_diagnostic_report">Checking...</p>
					</table>
					<input type="hidden" class="diagnostic_report" value="<?php echo get_option( 'post_smtp_clean_diagnostic_report_data' ); ?>" >
				</div>
			</form>
		</div>

        <?php
		print '</div>';
	}
}

/**
 *
 * @author jasonhendriks
 */
class PostmanGetDiagnosticsViaAjax {
	private $diagnostics;
	private $options;
	private $authorizationToken;
	/**
	 * Constructor
	 *
	 * @param PostmanOptions $options
	 */
	function __construct() {
		$this->options = PostmanOptions::getInstance();
		$this->authorizationToken = PostmanOAuthToken::getInstance();
		$this->diagnostics = '';
		$this->cleanDiagnostics = '';
		PostmanUtils::registerAjaxHandler( 'postman_diagnostics', $this, 'getDiagnostics' );
		PostmanUtils::registerAjaxHandler( 'send_postman_diagnostics_data', $this, 'sendDiagnosticsReportViaEmail' );
	}
	private function addToDiagnostics( $header, $data ) {
		if ( isset( $data ) ) {
			$this->cleanDiagnostics .= sprintf( '%s: %s%s', $header, $data, PHP_EOL );
			$this->diagnostics .= "<tr><td><strong>" . esc_html( $header ) . ":</strong></td><td>" . esc_html( $data ) . "</td></tr>" . PHP_EOL;
		}
	}
	private function getActivePlugins() {
		// from http://stackoverflow.com/questions/20488264/how-do-i-get-activated-plugin-list-in-wordpress-plugin-development
		$apl = get_option( 'active_plugins' );
		$plugins = get_plugins();
		$pluginText = array();
		foreach ( $apl as $p ) {
			if ( isset( $plugins [ $p ] ) ) {
				array_push( $pluginText, $plugins [ $p ] ['Name'] );
			}
		}
		return implode( ', ', $pluginText );
	}
	private function getPhpDependencies() {
		$apl = PostmanPreRequisitesCheck::getState();
		$pluginText = array();
		foreach ( $apl as $p ) {
			array_push( $pluginText, $p ['name'] . '=' . ($p ['ready'] ? 'Yes' : 'No') );
		}
		return implode( ', ', $pluginText );
	}
	private function getTransports() {
		$transports = '';
		foreach ( PostmanTransportRegistry::getInstance()->getTransports() as $transport ) {
			$transports .= ' : ' . $transport->getName();
		}
		return $transports;
	}

	/**
	 * Diagnostic Data test to current SMTP server
	 *
	 * @return string
	 */
	private function testConnectivity( PostmanModuleTransport $transport ) {
		$hostname = $transport->getHostname( $this->options );
		$port = $transport->getPort( $this->options );
		if ( ! empty( $hostname ) && ! empty( $port ) ) {
			$portTest = new PostmanPortTest( $transport->getHostname( $this->options ), $transport->getPort( $this->options ) );
			$result = $portTest->genericConnectionTest( $this->options->getConnectionTimeout() );
			if ( $result ) {
				return 'Yes';
			} else {
				return 'No';
			}
		}
		return 'n/a';
	}

	/**
	 * Inspects the $wp_filter variable and returns the plugins attached to it
	 * From: http://stackoverflow.com/questions/5224209/wordpress-how-do-i-get-all-the-registered-functions-for-the-content-filter
	 */
	private function getFilters( $hook = '' ) {
		global $wp_filter;
		if ( empty( $hook ) || ! isset( $wp_filter [ $hook ] ) ) {
			return null; }
		$functionArray = array();
		foreach ( $wp_filter [ $hook ] as $functions ) {
			foreach ( $functions as $function ) {
				$thing = $function ['function'];
				if ( is_array( $thing ) ) {
                    $name = get_class($thing [0]) . '->' . $thing [1];
                    array_push($functionArray, $name);
                } elseif ( is_object( $thing ) ) {
                    array_push( $functionArray, 'Anonymous Function' );
				} else {
					array_push( $functionArray, $thing );
				}
			}
		}

		return implode( ', ', $functionArray );
	}

	/**
	 */
	public function sendDiagnosticsReportViaEmail() {
		check_admin_referer('post-smtp', 'security');
		$message = '';
		$username_or_ticket_number = sanitize_text_field( wp_unslash( $_POST[ 'username_or_ticket_number' ] ) );
		$to = sanitize_text_field( wp_unslash( $_POST[ 'email' ] ) );
		$subject = __( '[Post SMTP] Diagnostic Test Report (' . $username_or_ticket_number . ')', 'post-smtp' );
		$message .= '<p><strong>Ticket Number/Forum Username: </strong>'. $username_or_ticket_number .'</p>';
		$message .= '<h3>'. __( 'Diagnostic Test Report', 'post-smtp' ) .'</h3>
					<table id="diagnostic-text" border="1" cellpadding="10" cellspacing="0">';
		$message .= get_option( 'post_smtp_diagnostic_report_data' );
		$message .= '</table>';
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $to, $subject, $message, $headers );
		wp_send_json_success();
	}

	/**
	 */
	public function getDiagnostics() {

	    check_admin_referer('post-smtp', 'security');

		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error( 
				array(
					'Message'	=>	'Unauthorized.'
				), 
				401
			);
		}

	    $curl = curl_version();
		$transportRegistry = PostmanTransportRegistry::getInstance();
        $this->addToDiagnostics( 'Mailer', PostmanOptions::getInstance()->getSmtpMailer() );
		$this->addToDiagnostics( 'HostName', PostmanUtils::getServerName() );
		$this->addToDiagnostics( 'cURL Version', $curl['version'] );
		$this->addToDiagnostics( 'OpenSSL Version', $curl['ssl_version'] );
		$this->addToDiagnostics( 'OS', php_uname() );
		$this->addToDiagnostics( 'PHP', PHP_OS . ' ' . PHP_VERSION . ' ' . setlocale( LC_CTYPE, 0 ) );
		$this->addToDiagnostics( 'PHP Dependencies', $this->getPhpDependencies() );
		$this->addToDiagnostics( 'WordPress', (is_multisite() ? 'Multisite ' : '') . get_bloginfo( 'version' ) . ' ' . get_locale() . ' ' . get_bloginfo( 'charset', 'display' ) );
		$this->addToDiagnostics( 'WordPress Theme', wp_get_theme() );
		$this->addToDiagnostics( 'WordPress Plugins', $this->getActivePlugins() );

        $bindResult = apply_filters( 'postman_wp_mail_bind_status', null );
        $wp_mail_file_name = 'n/a';
		if ( class_exists( 'ReflectionFunction' ) ) {
			$wp_mail = new ReflectionFunction( 'wp_mail' );
			$wp_mail_file_name = realpath( $wp_mail->getFileName() );
		}

		$this->addToDiagnostics( 'WordPress wp_mail Owner', $wp_mail_file_name );
		$this->addToDiagnostics( 'WordPress wp_mail Filter(s)', $this->getFilters( 'wp_mail' ) );
		$this->addToDiagnostics( 'WordPress wp_mail_from Filter(s)', $this->getFilters( 'wp_mail_from' ) );
		$this->addToDiagnostics( 'WordPress wp_mail_from_name Filter(s)', $this->getFilters( 'wp_mail_from_name' ) );
		$this->addToDiagnostics( 'WordPress wp_mail_content_type Filter(s)', $this->getFilters( 'wp_mail_content_type' ) );
		$this->addToDiagnostics( 'WordPress wp_mail_charset Filter(s)', $this->getFilters( 'wp_mail_charset' ) );
		$this->addToDiagnostics( 'WordPress phpmailer_init Action(s)', $this->getFilters( 'phpmailer_init' ) );
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		$this->addToDiagnostics( 'Postman', $pluginData ['version'] );
		{
			$s1 = $this->options->getEnvelopeSender();
			$s2 = $this->options->getMessageSenderEmail();
		if ( ! empty( $s1 ) || ! empty( $s2 ) ) {
			$this->addToDiagnostics( 'Postman Sender Domain (Envelope|Message)', ($hostname = substr( strrchr( $this->options->getEnvelopeSender(), '@' ), 1 )) . ' | ' . ($hostname = substr( strrchr( $this->options->getMessageSenderEmail(), '@' ), 1 )) );
		}
		}
		$this->addToDiagnostics( 'Postman Prevent Message Sender Override (Email|Name)', ($this->options->isSenderEmailOverridePrevented() ? 'Yes' : 'No') . ' | ' . ($this->options->isSenderNameOverridePrevented() ? 'Yes' : 'No') );
		{
			// status of the active transport
			$transport = $transportRegistry->getActiveTransport();
			$this->addToDiagnostics( 'Postman Active Transport', sprintf( '%s (%s)', $transport->getName(), $transportRegistry->getPublicTransportUri( $transport ) ) );
			$this->addToDiagnostics( 'Postman Active Transport Status (Ready|Connected)', ($transport->isConfiguredAndReady() ? 'Yes' : 'No') . ' | ' . ($this->testConnectivity( $transport )) );
		}
		if ( $transportRegistry->getActiveTransport() != $transportRegistry->getSelectedTransport() && $transportRegistry->getSelectedTransport() != null ) {
			// status of the selected transport
			$transport = $transportRegistry->getSelectedTransport();
			$this->addToDiagnostics( 'Postman Selected Transport', sprintf( '%s (%s)', $transport->getName(), $transportRegistry->getPublicTransportUri( $transport ) ) );
			$this->addToDiagnostics( 'Postman Selected Transport Status (Ready|Connected)', ($transport->isConfiguredAndReady() ? 'Yes' : 'No') . ' | ' . ($this->testConnectivity( $transport )) );
		}
		$this->addToDiagnostics( 'Postman Deliveries (Success|Fail)', (PostmanState::getInstance()->getSuccessfulDeliveries()) . ' | ' . (PostmanState::getInstance()->getFailedDeliveries()) );
		if ( $this->options->getConnectionTimeout() != PostmanOptions::DEFAULT_TCP_CONNECTION_TIMEOUT || $this->options->getReadTimeout() != PostmanOptions::DEFAULT_TCP_READ_TIMEOUT ) {
			$this->addToDiagnostics( 'Postman TCP Timeout (Connection|Read)', $this->options->getConnectionTimeout() . ' | ' . $this->options->getReadTimeout() );
		}
		if ( $this->options->isMailLoggingEnabled() != PostmanOptions::DEFAULT_MAIL_LOG_ENABLED || $this->options->getMailLoggingMaxEntries() != PostmanOptions::DEFAULT_MAIL_LOG_ENTRIES || $this->options->getTranscriptSize() != PostmanOptions::DEFAULT_TRANSCRIPT_SIZE ) {
			$this->addToDiagnostics( 'Postman Email Log (Enabled|Limit|Transcript Size)', ($this->options->isMailLoggingEnabled() ? 'Yes' : 'No') . ' | ' . $this->options->getMailLoggingMaxEntries() . ' | ' . $this->options->getTranscriptSize() );
		}
		$this->addToDiagnostics( 'Postman Run Mode', $this->options->getRunMode() == PostmanOptions::RUN_MODE_PRODUCTION ? null : $this->options->getRunMode() );
		$this->addToDiagnostics( 'Postman PHP LogLevel', $this->options->getLogLevel() == PostmanLogger::ERROR_INT ? null : $this->options->getLogLevel() );
		$this->addToDiagnostics( 'Postman Stealth Mode', $this->options->isStealthModeEnabled() ? 'Yes' : null );
		$this->addToDiagnostics( 'Postman File Locking (Enabled|Temp Dir)', PostmanState::getInstance()->isFileLockingEnabled() ? null : 'No' . ' | ' . $this->options->getTempDirectory() );
		update_option( 'post_smtp_diagnostic_report_data', $this->diagnostics );
		update_option( 'post_smtp_clean_diagnostic_report_data', $this->cleanDiagnostics );
		$response = array(
				'message' => $this->diagnostics,
		);
		wp_send_json_success( $response );
	}
}
