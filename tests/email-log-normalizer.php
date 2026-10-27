<?php
/**
 * CLI checks for EmailLogNormalizer (no WordPress bootstrap).
 *
 * Usage: php tests/email-log-normalizer.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @return string
	 */
	function get_option( $key ) {
		if ( 'date_format' === $key ) {
			return 'Y-m-d';
		}
		if ( 'time_format' === $key ) {
			return 'H:i';
		}
		return '';
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	/**
	 * @param string $format Format.
	 * @param int    $time   Timestamp.
	 * @return string
	 */
	function date_i18n( $format, $time ) {
		return gmdate( $format, $time );
	}
}

require dirname( __DIR__ ) . '/vendor/autoload.php';

use PostSMTP\EmailLogs\EmailLogNormalizer;

$row_success = array(
	'id'               => 1,
	'original_subject' => 'Hello',
	'to_header'        => 'user@example.com',
	'success'          => 1,
	'time'             => time(),
	'transport_uri'    => 'smtp://smtp.gmail.com:587',
);

$list = EmailLogNormalizer::normalize_list_row( $row_success );
assert( 'success' === $list['status'], 'Numeric success should map to success' );
assert( 'primary' === $list['route'], 'Plain success should be primary route' );

$row_fallback = array_merge( $row_success, array( 'success' => 'Sent ( ** Fallback ** )' ) );
$list_fb      = EmailLogNormalizer::normalize_list_row( $row_fallback );
assert( 'fallback' === $list_fb['route'], 'Fallback string should map to fallback route' );

$row_queued = array_merge( $row_success, array( 'success' => 'In Queue' ) );
assert( 'queued' === EmailLogNormalizer::parse_status( 'In Queue' ), 'In Queue should be queued' );

$range = EmailLogNormalizer::date_preset_to_range( 'last_7_days' );
assert( isset( $range['from'], $range['to'] ), 'Last 7 days should include from/to' );
assert( $range['to'] >= $range['from'], 'Range end should be after start' );

$all = EmailLogNormalizer::date_preset_to_range( 'all' );
assert( empty( $all ), 'All time preset should not set bounds' );

echo "email-log-normalizer: OK\n";
