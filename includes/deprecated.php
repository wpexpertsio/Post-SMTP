<?php
/**
 * Deprecated functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'ps_fs' ) ) {
	/**
	 * @deprecated 3.0.0 Use post_smtp_fs instead.
	 */
	function ps_fs() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'post_smtp_fs' );
		return post_smtp_fs();
	}
}

/**
 * @deprecated 3.0.0 Use post_smtp_setup_postman instead.
 */
function post_setupPostman() {
	_deprecated_function( __FUNCTION__, '3.0.0', 'post_smtp_setup_postman' );
	post_smtp_setup_postman();
}

/**
 * @deprecated 3.0.0 Use post_smtp_dismiss_not_configured instead.
 */
function post_dismiss_not_configured() {
	_deprecated_function( __FUNCTION__, '3.0.0', 'post_smtp_dismiss_not_configured' );
	post_smtp_dismiss_not_configured();
}

/**
 * @deprecated 3.0.0 Use post_smtp_fs_custom_icon instead.
 */
function ps_fs_custom_icon(): string {
	_deprecated_function( __FUNCTION__, '3.0.0', 'post_smtp_fs_custom_icon' );
	return post_smtp_fs_custom_icon();
}

/**
 * @deprecated 3.0.0 Use post_smtp_fs_custom_connect_message_on_update instead.
 */
function ps_fs_custom_connect_message_on_update(
	$message,
	$user_first_name,
	$product_title,
	$user_login,
	$site_link,
	$freemius_link
): string {
	_deprecated_function( __FUNCTION__, '3.0.0', 'post_smtp_fs_custom_connect_message_on_update' );
	return post_smtp_fs_custom_connect_message_on_update();
}
