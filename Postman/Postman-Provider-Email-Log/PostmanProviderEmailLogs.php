<?php
/**
 * Provider Email Logs
 *
 * Adds a submenu page under Post SMTP for displaying
 * mailer-specific email logs.
 *
 * @package PostSMTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class PostmanProviderEmailLogs
 *
 * Handles registration of the Provider Email Logs submenu,
 * rendering the page, enqueueing scripts, and AJAX callbacks.
 */
class PostmanProviderEmailLogs {

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_provider_logs_menu' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_get_provider_email_logs', array( $this, 'ajax_get_provider_email_logs' ) );
	}

	/**
	 * Register the Provider Logs submenu under Post SMTP.
	 *
	 * @return void
	 */
	public function register_provider_logs_menu() {
		if ( PostmanUtils::isAdmin() ) {
			$page_title = sprintf( __( '%s Mailer Logs', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) );
			$menu_title = _x( 'Mailer Log', 'The log of Emails delivered by provider', 'post-smtp' );

			add_submenu_page(
				PostmanViewController::POSTMAN_MENU_SLUG,   // Parent slug.
				$page_title,                                // Page title.
				$menu_title,                                // Menu title.
				'manage_options',                           // Capability.
				'postman_mailer_logs',                      // Menu slug.
				array( $this, 'render_postman_provider_logs' ), // Callback.
				2                                           // Position.
			);
		}
	}

	/**
	 * Enqueue scripts and styles for the Provider Logs page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
public function enqueue_scripts( $hook ) {
	$pluginData = apply_filters( 'postman_get_plugin_metadata', null );

	// Only load on our custom page.
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'postman_mailer_logs' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		// Register scripts & styles
		wp_register_script(
			'postman-datatable',
			plugins_url( 'assets/js/dataTable.min.js', POST_SMTP_BASE ),
			array( 'jquery' ),
			$pluginData['version'],
			true
		);

		wp_register_style(
			'postman-datatable',
			plugins_url( 'assets/css/dataTable.min.css', POST_SMTP_BASE ),
			array(),
			$pluginData['version']
		);

		wp_register_script(
			'postman-email-logs-script',
			plugins_url( 'script/provider-email-logs-table.js', POST_SMTP_BASE ),
			array(
				PostmanViewController::JQUERY_SCRIPT,
				PostmanViewController::POSTMAN_SCRIPT,
				'postman-datatable'
			),
			$pluginData['version'],
			true
		);

		wp_register_style(
			PostmanViewController::POSTMAN_STYLE,
			plugins_url( 'style/postman.css', POST_SMTP_BASE ),
			array(),
			'1.0.0'
		);

		// Enqueue
		wp_enqueue_style( 'postman-datatable' );
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_script( 'postman-datatable' );
		wp_enqueue_script( 'postman-email-logs-script' );

		// Localize correctly (use the same handle you enqueue)
		wp_localize_script(
			'postman-email-logs-script',
			'postman_provider_logs',
			array(
				'provider_label' => __( 'Provider:', 'post-smtp' ),
				'none_label'     => __( 'None', 'post-smtp' ),
				'loading_label'  => __( 'Loading...', 'post-smtp' ),
				'error_label'    => __( 'Error loading logs.', 'post-smtp' ),
				'nonce'          => wp_create_nonce( 'postman_provider_logs' ),
			)
		);
	}
}

	/**
	 * Render the Provider Logs page.
	 *
	 * @return void
	 */
	public function render_postman_provider_logs() {
		require 'PostmanProviderEmailLogTable.php';
	}

	/**
	 * AJAX handler to fetch provider email logs.
	 *
	 * @return void
	 */
	public function ajax_get_provider_email_logs() {
		check_ajax_referer( 'postman_provider_logs', 'security' );

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : 'none'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$logs     = array();

		// Map provider slug to class.
		$provider_map = array(
			'mailgun'      => 'PostmanMailgunTransport',
			'gmail'        => 'PostmanGmailApiModuleTransport',
			'sendinblue'   => 'PostmanSendinblueTransport',
			'mailjet'      => 'PostmanMailjetTransport',
			'mandrill'     => 'PostmanMandrillTransport',
			'sendgrid'     => 'PostmanSendgridTransport',
			'elasticemail' => 'PostmanElasticemailTransport',
			'postmark'     => 'PostmanPostmarkTransport',
			'resend'       => 'PostmanResendTransport',
			'smtp2go'      => 'PostmanSmtp2goTransport',
			'sparkpost'    => 'PostmanSparkpostTransport',
			'smtp'         => 'PostsmtpMailer',
		);

		if ( isset( $provider_map[ $provider ] ) && method_exists( $provider_map[ $provider ], 'get_provider_logs' ) ) {
			$logs = call_user_func( array( $provider_map[ $provider ], 'get_provider_logs' ) );
		}

		wp_send_json_success(
			array(
				'logs' => $logs,
			)
		);
	}
}

// Initialize the feature.
new PostmanProviderEmailLogs();
