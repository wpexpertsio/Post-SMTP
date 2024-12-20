<?php
/**
 * Post SMTP
 *
 * @package           PostSMTP
 * @author            Post SMTP
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Post SMTP
 * Plugin URI:        https://wordpress.org/plugins/post-smtp/
 * Description:       Email not reliable? Post SMTP is the first and only WordPress SMTP plugin to implement OAuth 2.0 for Gmail, Hotmail and Yahoo Mail. Setup is a breeze with the Configuration Wizard and integrated Port Tester. Enjoy worry-free delivery even if your password changes!
 * Version:           3.0.0-beta.1
 * Requires at least: 5.6
 * Requires PHP:      7.0
 * Author:            Post SMTP
 * Author URI:        https://postmansmtp.com
 * Text Domain:       post-smtp
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Post SMTP (aka Postman SMTP) was originally developed by Jason Hendriks
 */
// The Postman Mail API
//
// filter postman_test_email: before calling wp_mail, implement this filter and return true to disable the success/fail counters
// filter postman_wp_mail_result: apply this filter after calling wp_mail for an array containing the SMTP error, transcript and time
// filter postman_get_plugin_metadata: apply this filter to get plugin metadata
// filter postman_wp_mail_bind_status: apply this filter to get wp_mail bind status
// filter print_postman_status: apply this filter to print the human-readable plugin state
// filter postman_module: implement this filter and return the instance of the module
// filter postman_register_modules: apply this filter to register the module

if ( ! function_exists( 'post_smtp_fs' ) ) {
	/**
	 * Initializes and returns the Freemius instance, storing it in the global `$ps_fs`.
	 *
	 * @since 2.1.1
	 * @version 1.0
	 *
	 * @return \Freemius The initialized Freemius instance.
	 * @throws \Freemius_Exception If there is an error during Freemius initialization.
	 */
	function post_smtp_fs(): Freemius {
		global $ps_fs;

		if ( isset( $ps_fs ) ) {
			return $ps_fs;
		}

		// Include Freemius SDK.
		require_once __DIR__ . '/freemius/start.php';

		$ps_fs = fs_dynamic_init(
			array(
				'id'                => '10461',
				'slug'              => 'post-smtp',
				'type'              => 'plugin',
				'public_key'        => 'pk_28fcefa3d0ae86f8cdf6b7f71c0cc',
				'is_premium'        => false,
				'has_addons'        => false,
				'bundle_id'         => '10910',
				'bundle_public_key' => 'pk_c5110ef04ba30cd57dd970a269a1a',
				'has_paid_plans'    => false,
				'menu'              => array(
					'slug'       => 'postman',
					'first-path' => 'admin.php?page=postman/configuration_wizard',
					'account'    => false,
				),
			)
		);

		return $ps_fs;
	}

	// Init Freemius.
	post_smtp_fs();

	// Signal that SDK was initiated.
	do_action_deprecated( 'ps_fs_loaded', array(), '3.0.0', 'post_smtp_fs_loaded' );
	do_action( 'post_smtp_fs_loaded' );
}

/**
 * Retrieves the 'Stay on the safe side' message.
 *
 * @since 3.0.0
 *
 * @return string Escaped message.
 */
function post_smtp_fs_custom_connect_message_on_update(): string {
	return sprintf(
		'<div class="ps-optin-popup"><h1>%1$s</h1><p>%2$s</p></div><div style="clear: both;"></div>' .
		esc_html__( 'Stay on the safe side', 'post-smtp' ),
		esc_html__( 'Receive our plugin\'s alert in case of critical security and feature updates and allow non-sensitive diagnostic tracking.', 'post-smtp' )
	);
}

post_smtp_fs()->add_filter( 'connect_message', 'ps_fs_custom_connect_message_on_update', 10, 0 );

/**
 * Retrieves the opt-in icon.
 *
 * @since 3.0.0
 *
 * @return string
 */
function post_smtp_fs_custom_icon(): string {
	return __DIR__ . '/assets/images/icons/optin.png';
}

post_smtp_fs()->add_filter( 'plugin_icon', 'post_smtp_fs_custom_icon' );

/*
 * DO some check and Start Postman
 */

define( 'POST_SMTP_BASE', __FILE__ );
define( 'POST_SMTP_PATH', __DIR__ );
define( 'POST_SMTP_URL', plugins_url( '', POST_SMTP_BASE ) );
define( 'POST_SMTP_VER', '3.0.0' );
define( 'POST_SMTP_DB_VERSION', '1.0.1' );
define( 'POST_SMTP_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );

$postman_smtp_exist = in_array(
	'postman-smtp/postman-smtp.php',
	(array) get_option( 'active_plugins', array() ),
	true
);

$post_smtp_required_php_version = version_compare( PHP_VERSION, '7.0', '<' );

if ( $postman_smtp_exist || $post_smtp_required_php_version ) {
	add_action( 'admin_init', 'post_smtp_plugin_deactivate' );

	if ( $postman_smtp_exist ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice' );
	}

	if ( $post_smtp_required_php_version ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice_version' );
	}
} else {
	post_smtp_start( memory_get_usage() );
}

/**
 * Deactivates the Post SMTP plugin.
 */
function post_smtp_plugin_deactivate() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/**
 * Displays an admin notice about the required PHP version.
 */
function post_smtp_plugin_admin_notice_version() {
	echo '
		<div class="error">
			<p><strong>Post SMTP</strong> plugin require at least PHP version 7.0, contact to your web hostig support to upgrade.</p>
			<p><a href="https://secure.php.net/supported-versions.php">See supported versions on PHP.net</a></p>
		</div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Displays an admin notice that the original Postman SMTP plugin must be disabled first.
 */
function post_smtp_plugin_admin_notice() {
	echo '
		<div class="error">
			<p><strong>Post SMTP</strong> plugin is a fork (clone) of the original Postman SMTP, you must disable Postman SMTP to use this plugin.</p>
		</div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Prints script to dismiss the notice that the plugin is not configured.
 *
 * @todo
 * @since 3.0.0
 */
function post_smtp_dismiss_not_configured() {
	?>
	<script>
		(function($) {
			$(document).on('click','.postman-not-configured-notice .notice-dismiss', function(e) {
				e.preventDefault();

				var $this = $(this);
				var args = {
					action: 'dismiss_version_notify',
					security: $('.postman-not-configured-notice').find('.security').val(),
					version: 'not_configured',
				};

				$.post(ajaxurl, args, function() {
					$this.parent().slideUp();
				});
			});
		})(jQuery);
	</script>
	<?php
}
add_action( 'admin_footer', 'post_smtp_dismiss_not_configured' );

/**
 * Enqueues scripts used in the admin area.
 */
function post_smtp_general_scripts() {
	$localize = include POST_SMTP_PATH . '/Postman/Localize.php';
	$args     = version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ? true : array( 'in_footer' => true );
	wp_register_script( 'post-smtp-localize', POST_SMTP_URL . '/script/localize.js', array(), POST_SMTP_VER, $args );
	wp_localize_script( 'post-smtp-localize', 'post_smtp_localize', $localize );
	wp_enqueue_script( 'post-smtp-localize' );
	wp_enqueue_script( 'post-smtp-hooks', POST_SMTP_URL . '/script/post-smtp-hooks.js', array(), POST_SMTP_VER, $args );
}
add_action( 'admin_enqueue_scripts', 'post_smtp_general_scripts', 8 );

/**
 * Create the main Postman class to start Postman
 *
 * @param int $starting_memory The amount of memory, in bytes, that's being
 *                             allocated to the PHP script when initialising Postman.
 */
function post_smtp_start( $starting_memory ) {
	post_smtp_setup_postman();
	PostmanUtils::logMemoryUse( $starting_memory, 'Postman' );
}

/**
 * Instantiate the mail Postman class
 *
 * @since 3.0.0
 */
function post_smtp_setup_postman() {
	require_once 'Postman/Postman.php';
	$postman = new Postman( __FILE__, POST_SMTP_VER );
	do_action( 'post_smtp_init' );
}

require_once __DIR__ . 'includes/deprecated.php';
