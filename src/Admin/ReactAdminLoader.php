<?php
/**
 * React SPA admin loader.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReactAdminLoader {

	public const MIGRATION_PAGE_SLUG = 'post_smtp_v4_migration';

	public static function init(): void {
		if ( ! CohortResolver::usesReactAdmin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'in_admin_header', array( self::class, 'renderRoot' ), 99 );
		add_filter( 'post_smtp__new_dashboard', '__return_true' );
		add_action( 'admin_head', array( self::class, 'hideLegacyChrome' ) );
	}

	/**
	 * Legacy cohort: load React only on the hidden guided-migration page.
	 */
	public static function initMigrationWizard(): void {
		if ( ! \PostSMTP\Rollout\CohortResolver::needsMigration() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'registerMigrationPage' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueMigrationWizard' ) );
	}

	public static function registerMigrationPage(): void {
		add_submenu_page(
			null,
			__( 'Post SMTP Migration', 'post-smtp' ),
			__( 'Migration', 'post-smtp' ),
			'manage_options',
			self::MIGRATION_PAGE_SLUG,
			array( self::class, 'renderMigrationPage' )
		);
	}

	public static function renderMigrationPage(): void {
		echo '<div class="wrap"><div id="post-smtp-admin-root"></div></div>';
	}

	public static function enqueueMigrationWizard( string $hook ): void {
		if ( 'admin_page_' . self::MIGRATION_PAGE_SLUG !== $hook ) {
			return;
		}

		self::enqueueAssets( true );
	}

	/**
	 * Main dashboard SPA only (not wizard, settings, or email log subpages).
	 */
	public static function isDashboardShellPage(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return 'postman' === $page;
	}

	public static function hideLegacyChrome(): void {
		if ( ! self::isDashboardShellPage() ) {
			return;
		}
		echo '<style>
			#post-smtp-app,
			.wrap > .ps-main-container-wrap,
			.wrap > .post-smtp-welcome-panel,
			#wpbody-content > .wrap { display: none !important; }
			#post-smtp-admin-root {
				min-height: calc(100vh - 32px);
				margin: -10px -20px 0 -2px;
			}
			#wpbody-content { padding-bottom: 0; }
		</style>';
	}

	public static function getAdminConfig( bool $migration_only = false ): array {
		$configured = false;
		$transport  = 'default';
		$sender     = '';

		if ( class_exists( '\PostmanTransportRegistry' ) && class_exists( '\PostmanOptions' ) ) {
			$registry  = \PostmanTransportRegistry::getInstance();
			$active    = $registry->getActiveTransport();
			$configured = $active && $active->isConfiguredAndReady();
			$transport  = \PostmanOptions::getInstance()->getTransportType();
			$sender     = \PostmanOptions::getInstance()->getMessageSenderEmail();
		}

		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		return array(
			'apiRoot'        => esc_url_raw( rest_url( 'post-smtp/v2/' ) ),
			'dashboardApi'   => esc_url_raw( rest_url( 'psd/v1/' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'cohort'         => get_option( 'post_smtp_cohort', 'legacy' ),
			'isLegacy'       => \PostSMTP\Kernel\Data\ConnectionRepositoryFactory::isLegacyMode(),
			'isPro'          => (bool) $has_pro,
			'isConfigured'   => (bool) $configured,
			'transport'      => sanitize_text_field( (string) $transport ),
			'senderEmail'    => sanitize_email( (string) $sender ),
			'siteName'       => sanitize_text_field( get_bloginfo( 'name' ) ),
			'siteUrl'        => esc_url_raw( home_url( '/' ) ),
			'version'        => defined( 'POST_SMTP_VER' ) ? POST_SMTP_VER : '4.0.0',
			'assetsUrl'      => esc_url_raw( POST_SMTP_URL . '/Postman/Dashboard/assets/images/' ),
			'migrationOnly'  => $migration_only,
			'adminUrls'      => array(
				'dashboard'   => admin_url( 'admin.php?page=postman' ),
				'connections' => admin_url( 'admin.php?page=postman#/connections' ),
				'settings'    => admin_url( 'admin.php?page=postman/configuration' ),
				'emailLog'    => admin_url( 'admin.php?page=postman#/email-logs' ),
				'reports'     => admin_url( 'admin.php?page=postman_email_log' ),
				'wizard'      => admin_url( 'admin.php?page=postman/configuration_wizard' ),
				'portTest'    => admin_url( 'admin.php?page=postman/port_test' ),
				'diagnostics' => admin_url( 'admin.php?page=postman/diagnostics' ),
				'migration'   => admin_url( 'admin.php?page=' . self::MIGRATION_PAGE_SLUG ),
				'revertLegacy' => admin_url( 'admin.php?page=' . \PostSMTP\Admin\LegacyRevertPage::PAGE_SLUG ),
				'pro'         => 'https://postmansmtp.com/pricing/',
				'docs'        => 'https://postmansmtp.com/documentation/',
				'review'      => 'https://wordpress.org/support/plugin/post-smtp/reviews/#new-post',
			),
		);
	}

	public static function renderRoot(): void {
		if ( ! self::isDashboardShellPage() ) {
			return;
		}
		echo '<div id="post-smtp-admin-root"></div>';
	}

	public static function enqueue( string $hook ): void {
		if ( 'toplevel_page_postman' !== $hook ) {
			return;
		}

		self::enqueueAssets( false );
	}

	private static function enqueueAssets( bool $migration_only ): void {
		$asset_file = POST_SMTP_PATH . '/admin-app/build/index.asset.php';
		$script     = POST_SMTP_URL . '/admin-app/build/index.js';
		$style      = POST_SMTP_URL . '/admin-app/build/index.css';

		$asset = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ),
			'version'      => POST_SMTP_VER,
		);

		wp_enqueue_style(
			'post-smtp-admin-inter',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'post-smtp-admin-satoshi',
			'https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'post-smtp-admin-app',
			$style,
			array( 'post-smtp-admin-inter', 'post-smtp-admin-satoshi' ),
			$asset['version'] ?? POST_SMTP_VER
		);
		wp_dequeue_script( 'post-smtp-dashboard' );
		wp_dequeue_style( 'post-smtp-dashboard' );
		wp_dequeue_style( 'post-smtp-dashboard-responsive' );

		wp_enqueue_script(
			'post-smtp-admin-app',
			$script,
			$asset['dependencies'] ?? array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ),
			$asset['version'] ?? POST_SMTP_VER,
			true
		);

		wp_localize_script(
			'post-smtp-admin-app',
			'postSmtpAdmin',
			self::getAdminConfig( $migration_only )
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'post-smtp-admin-app', 'post-smtp', POST_SMTP_PATH . '/Postman/languages' );
		}
	}
}
