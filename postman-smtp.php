<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
 * Plugin Name: Post SMTP
 * Plugin URI: https://wordpress.org/plugins/post-smtp/
 * Description: Email not reliable? Post SMTP is the first and only WordPress SMTP plugin to implement OAuth 2.0 for Gmail, Hotmail and Yahoo Mail. Setup is a breeze with the Configuration Wizard and integrated Port Tester. Enjoy worry-free delivery even if your password changes!
 * Version: 2.8.11
 * Author: Post SMTP
 * Text Domain: post-smtp
 * Author URI: https://postmansmtp.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

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

/** 
 * Freemius initialization
 * 
 * @since 2.1.1
 * @version 1.0
 */
if ( ! function_exists( 'ps_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ps_fs() {
        global $ps_fs;

        if ( ! isset( $ps_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $ps_fs = fs_dynamic_init( array(
                'id'                  => '10461',
                'slug'                => 'post-smtp',
                'type'                => 'plugin',
                'public_key'          => 'pk_28fcefa3d0ae86f8cdf6b7f71c0cc',
                'is_premium'          => false,
                'has_addons'          => false,
				'bundle_id' 		  => '10910',
				'bundle_public_key'   => 'pk_c5110ef04ba30cd57dd970a269a1a',
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'postman',
                    'first-path'     => 'admin.php?page=postman/configuration_wizard',
                    'account'        => false,
                ),
            ) );
        }

        return $ps_fs;
    }

    // Init Freemius.
    ps_fs();
    // Signal that SDK was initiated.
    do_action( 'ps_fs_loaded' );
}

function ps_fs_custom_connect_message_on_update(
    $message,
    $user_first_name,
    $product_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
		'<div class="ps-optin-popup">' .
        '<h1>' . __( 'Stay on the safe side', 'post-smtp' ) . '</h1>' .
		'<p>'.__( 'Receive our plugin\'s alert in case of critical security and feature updates and allow non-sensitive diagnostic tracking.', 'post-smtp' ).'</p>' .
		'</div>' . 
		'<div style="clear: both;"></div>'
    );
}
 
ps_fs()->add_filter('connect_message', 'ps_fs_custom_connect_message_on_update', 10, 6);

function ps_fs_custom_icon() {
    return dirname( __FILE__ ) . '/assets/images/icons/optin.png';
}
 
ps_fs()->add_filter( 'plugin_icon' , 'ps_fs_custom_icon' );


/**
 * DO some check and Start Postman
 */

define( 'POST_SMTP_BASE', __FILE__ );
define( 'POST_SMTP_PATH', __DIR__ );
define( 'POST_SMTP_URL', plugins_url('', POST_SMTP_BASE ) );
define( 'POST_SMTP_VER', '2.8.11' );
define( 'POST_SMTP_DB_VERSION', '1.0.1' );
define( 'POST_SMTP_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );

$postman_smtp_exist = in_array( 'postman-smtp/postman-smtp.php', (array) get_option( 'active_plugins', array() ) );
$required_php_version = version_compare( PHP_VERSION, '5.6.0', '<' );

if ( $postman_smtp_exist || $required_php_version ) {
	add_action( 'admin_init', 'post_smtp_plugin_deactivate' );

	if ( $postman_smtp_exist ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice' );
	}

	if ( $required_php_version ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice_version' );
	}
} else {
	post_smtp_start( memory_get_usage() );
}


function post_smtp_plugin_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
}

function post_smtp_plugin_admin_notice_version() {
	echo '<div class="error">
				<p>
				<strong>Post SMTP</strong> plugin require at least PHP version 5.6, contact to your web hostig support to upgrade.
				</p>
				<p>
				<a href="https://secure.php.net/supported-versions.php">See supported versions on PHP.net</a>
				</p>
				</div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] ); }
}

function post_smtp_plugin_admin_notice() {
		echo '<div class="error"><p><strong>Post SMTP</strong> plugin is a fork (twin brother) of the original Postman SMTP, you must disable Postman SMTP to use this plugin.</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] ); }
}

/**
 * @todo
 */
function post_dismiss_not_configured() {
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
add_action( 'admin_footer', 'post_dismiss_not_configured' );

function post_smtp_general_scripts() {
    $localize = include( POST_SMTP_PATH . '/Postman/Localize.php' );
    wp_register_script( 'post-smtp-localize', POST_SMTP_URL . '/script/localize.js', [], false );
    wp_localize_script( 'post-smtp-localize', 'post_smtp_localize', $localize );
    wp_enqueue_script( 'post-smtp-localize' );
    wp_enqueue_script( 'post-smtp-hooks', POST_SMTP_URL . '/script/post-smtp-hooks.js', [], false );
}
add_action( 'admin_enqueue_scripts', 'post_smtp_general_scripts', 8 );

/**
 * Create the main Postman class to start Postman
 *
 * @param mixed $startingMemory
 */
function post_smtp_start( $startingMemory ) {
	post_setupPostman();
	PostmanUtils::logMemoryUse( $startingMemory, 'Postman' );
}

/**
 * Instantiate the mail Postman class
 */
function post_setupPostman() {
	require_once 'Postman/Postman.php';
	$kevinCostner = new Postman( __FILE__, POST_SMTP_VER );
	do_action( 'post_smtp_init');
}