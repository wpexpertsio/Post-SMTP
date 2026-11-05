<?php
/**
 * CLI helper: inspect AEDL / revamp queue readiness.
 * Usage: php scripts/check-aedl-flow.php (from post-smtp plugin dir, with WP loaded)
 */

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	$wp_load = dirname( __DIR__, 3 ) . '/wp-load.php';
}
require $wp_load;

echo 'siteurl: ' . get_option( 'siteurl' ) . PHP_EOL;
echo 'revamp_pipeline: ' . ( \PostSMTP\Rollout\CohortResolver::usesRevampMailPipeline() ? 'yes' : 'no' ) . PHP_EOL;
echo 'run_mode: ' . \PostmanOptions::getInstance()->getRunMode() . PHP_EOL;
echo 'mail_logging: ' . ( \PostmanOptions::getInstance()->isMailLoggingEnabled() ? 'yes' : 'no' ) . PHP_EOL;
echo 'ps_aedl_option: ' . wp_json_encode( get_option( 'ps_aedl_option', array() ) ) . PHP_EOL;
echo 'batch_cron_next: ' . ( wp_next_scheduled( 'post_smtp_batch_cron_hook' ) ?: 'not scheduled' ) . PHP_EOL;
echo 'queued_count: ';
global $wpdb;
if ( class_exists( 'PostmanEmailLogs' ) ) {
	$logs = new PostmanEmailLogs();
	$n    = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}{$logs->db_name} WHERE success = 'In Queue'" );
	echo (int) $n . PHP_EOL;
} else {
	echo 'n/a' . PHP_EOL;
}
