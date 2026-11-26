<?php
/**
 * Per-site email log tables (multisite).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteEmailLogSupport {

	public static function init(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'admin_init', array( self::class, 'maybeEnsureTablesInAdmin' ), 5 );
	}

	public static function maybeEnsureTablesInAdmin(): void {
		if ( ! is_admin() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( '' === $page || strpos( $page, 'postman' ) !== 0 ) {
			return;
		}

		self::ensureTablesForCurrentBlog();
	}

	public static function ensureTablesForCurrentBlog(): bool {
		global $wpdb;

		if ( ! class_exists( 'PostmanEmailLogs' ) ) {
			$base = defined( 'POST_SMTP_PATH' ) ? POST_SMTP_PATH : dirname( dirname( __DIR__ ) );
			require_once $base . '/Postman/PostmanEmailLogs.php';
		}

		$email_logs = new \PostmanEmailLogs();
		$logs_table = $wpdb->prefix . $email_logs->db_name;
		$meta_table = $wpdb->prefix . $email_logs->meta_table;

		$logs_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $logs_table ) );
		$meta_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) );

		if ( $logs_exists === $logs_table && $meta_exists === $meta_table ) {
			return true;
		}

		$email_logs->install_table();

		return true;
	}

	/**
	 * When network global mailer is on, align logging toggle with the main site so children still record logs locally.
	 *
	 * @param string $local_value mail_log_enabled on this blog.
	 */
	public static function filterLoggingEnabledValue( string $local_value ): string {
		if ( ! is_multisite()
			|| ! MultisitePolicy::isMultisiteGlobalSettingsEnabled()
			|| MultisitePolicy::isNetworkMainSite() ) {
			return $local_value;
		}

		$main_value = self::readMainSiteLoggingEnabled();
		if ( '' === $main_value ) {
			return $local_value;
		}

		return $main_value;
	}

	private static function readMainSiteLoggingEnabled(): string {
		static $cached = null;

		if ( null !== $cached ) {
			return $cached;
		}

		$main_id = MultisitePolicy::getMainSiteBlogId();
		$cached  = '';

		if ( get_current_blog_id() === $main_id ) {
			return $cached;
		}

		switch_to_blog( $main_id );
		$options = get_option( \PostmanOptions::POSTMAN_OPTIONS, array() );
		restore_current_blog();

		if ( is_array( $options ) && isset( $options[ \PostmanOptions::MAIL_LOG_ENABLED_OPTION ] ) ) {
			$cached = (string) $options[ \PostmanOptions::MAIL_LOG_ENABLED_OPTION ];
		} elseif ( ! is_array( $options ) || ! array_key_exists( \PostmanOptions::MAIL_LOG_ENABLED_OPTION, $options ) ) {
			$cached = \PostmanOptions::DEFAULT_MAIL_LOG_ENABLED;
		}

		return $cached;
	}
}