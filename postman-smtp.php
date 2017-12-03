<?php

/*
 * Plugin Name: Post SMTP
 * Plugin URI: https://wordpress.org/plugins/post-smtp/
 * Description: Email not reliable? Post SMTP is the first and only WordPress SMTP plugin to implement OAuth 2.0 for Gmail, Hotmail and Yahoo Mail. Setup is a breeze with the Configuration Wizard and integrated Port Tester. Enjoy worry-free delivery even if your password changes!
 * Version: 1.7.7
 * Author: Jason Hendriks, Yehuda Hassine
 * Text Domain: post-smtp
 * Author URI: https://postmansmtp.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
// TODO v1.7
// -- Postmark API http://plugins.svn.wordpress.org/postmark-approved-wordpress-plugin/trunk/postmark.php
// -- Amazon SES API http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-email-api.html
// TODO v2.0
// -- PHP7 compatibility
// -- class autoloading
// -- Add dismiss option for "unconfigured message" .. for multisites
// -- customize sent-mail icon WordPress dashboard
// -- multisite support for site-wide email configuration. allow network admin to choose whether subdomains may override with their own settings. subdomains may override with their own settings.
// -- multiple mailbox support
/**
 * DO some check and Start Postman
 */


if ( in_array( 'postman-smtp/postman-smtp.php', (array) get_option( 'active_plugins', array() ) ) ) {
	add_action( 'admin_init', 'post_smtp_plugin_deactivate' );
	add_action( 'admin_notices', 'post_smtp_plugin_admin_notice' );
} else {
	post_start( memory_get_usage() );
}


function post_smtp_plugin_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
}

function post_smtp_plugin_admin_notice() {
		echo '<div class="error"><p><strong>Post SMTP</strong> plugin is a fork of the original Postman SMTP, you must disable Postman SMTP to use this plugin.</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] ); }
}

/**
 * Create the main Postman class to start Postman
 *
 * @param unknown $startingMemory
 */
function post_start( $startingMemory ) {
	post_setupPostman();
	PostmanUtils::logMemoryUse( $startingMemory, 'Postman' );
}

/**
 * Instantiate the mail Postman class
 */
function post_setupPostman() {
	require_once 'Postman/Postman.php';
	$kevinCostner = new Postman( __FILE__, '1.7.7' );
}
