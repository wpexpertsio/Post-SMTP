<?php
/**
 * Shared markup/styles for the legacy migration promo (Figma 950:13393).
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MigrationPromoBanner {

	public static function shouldShow(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return false;
		}

		if ( CohortResolver::current() === CohortResolver::COHORT_MIGRATED ) {
			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		return ReactAdminLoader::MIGRATION_PAGE_SLUG !== $page;
	}

	public static function migrationUrl(): string {
		return admin_url( 'admin.php?page=' . ReactAdminLoader::MIGRATION_PAGE_SLUG );
	}

	/**
	 * Main legacy dashboard: inject above Month / Week / Day tabs inside .app-wrapper.
	 */
	public static function renderLegacyDashboardPlacement(): void {
		if ( ! self::shouldShow() ) {
			return;
		}

		self::printStylesOnce();
		$migration_url = self::migrationUrl();
		?>
		<div id="ps-migration-promo-mount" class="ps-migration-promo-wrap ps-migration-promo-wrap--legacy-dashboard" hidden>
			<?php self::renderMarkup( $migration_url ); ?>
		</div>
		<script>
		(function(){
			function placeMigrationBanner(){
				var mount = document.getElementById('ps-migration-promo-mount');
				var wrapper = document.querySelector('#post-smtp-app .app-wrapper');
				if(!mount || !wrapper || wrapper.querySelector('[data-post-smtp-migration-banner]')){
					return;
				}
				mount.hidden = false;
				wrapper.insertBefore(mount, wrapper.firstChild);
			}
			if(document.readyState === 'loading'){
				document.addEventListener('DOMContentLoaded', placeMigrationBanner);
			} else {
				placeMigrationBanner();
			}
			setTimeout(placeMigrationBanner, 100);
			setTimeout(placeMigrationBanner, 600);
		})();
		</script>
		<?php
	}

	/**
	 * Other legacy Post SMTP admin screens (not the main dashboard).
	 */
	public static function renderAdminNotice(): void {
		if ( ! self::shouldShow() ) {
			return;
		}

		if ( self::isMainLegacyDashboard() ) {
			return;
		}

		self::printStylesOnce();
		echo '<div class="ps-migration-promo-wrap">';
		self::renderMarkup( self::migrationUrl() );
		echo '</div>';
	}

	private static function isMainLegacyDashboard(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return 'postman' === $page;
	}

	private static function renderMarkup( string $migration_url ): void {
		?>
		<div class="ps-migration-promo" data-post-smtp-migration-banner="1">
			<div class="ps-migration-promo__copy">
				<svg class="ps-migration-promo__icon" viewBox="0 0 20 20" aria-hidden="true">
					<path
						fill="currentColor"
						d="M10 2.5l2.47 5.01 5.53.8-4 3.9.94 5.5L10 15.4l-4.94 2.6.94-5.5-4-3.9 5.53-.8L10 2.5z"
					/>
				</svg>
				<p class="ps-migration-promo__text">
					<?php esc_html_e( 'Post SMTP has a better experience — simpler setup and advanced Settings.', 'post-smtp' ); ?>
				</p>
			</div>
			<a class="ps-migration-promo__cta" href="<?php echo esc_url( $migration_url ); ?>">
				<?php esc_html_e( 'Migrate Now', 'post-smtp' ); ?>
				<svg class="ps-migration-promo__cta-arrow" viewBox="0 0 13 10" aria-hidden="true">
					<path d="M1 5h10M7.5 1.5 11 5l-3.5 3.5" stroke="currentColor" stroke-width="1.2" fill="none" />
				</svg>
			</a>
		</div>
		<?php
	}

	public static function printStylesOnce(): void {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<style>
			.ps-migration-promo-wrap {
				margin: 0;
				padding: 0;
				box-sizing: border-box;
			}
			.ps-migration-promo-wrap--legacy-dashboard {
				width: 100%;
				margin: 0 0 20px;
			}
			.ps-migration-promo {
				background: #fff;
				border-left: 4px solid #fbb21f;
				border-radius: 4px;
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 16px;
				padding: 10px 14px;
				min-height: 54px;
				width: 100%;
				max-width: none;
				box-sizing: border-box;
				font-family: Poppins, Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			}
			.ps-migration-promo-wrap:not(.ps-migration-promo-wrap--legacy-dashboard) .ps-migration-promo {
				margin: 12px 0 16px;
				width: 100%;
				max-width: none;
			}
			.ps-migration-promo__copy {
				display: flex;
				align-items: center;
				gap: 10px;
				flex: 1;
				min-width: 0;
			}
			.ps-migration-promo__icon {
				flex-shrink: 0;
				width: 20px;
				height: 20px;
				color: #fbb21f;
			}
			.ps-migration-promo__text {
				margin: 0;
				font-size: 12px;
				font-weight: 500;
				line-height: 18px;
				color: #263e30;
			}
			.ps-migration-promo__cta {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 4px;
				flex-shrink: 0;
				padding: 8px 16px;
				background: #000;
				border: none;
				border-radius: 4px;
				color: #fff !important;
				font-size: 12px;
				font-weight: 600;
				line-height: 18px;
				text-decoration: none;
				cursor: pointer;
			}
			.ps-migration-promo__cta:hover,
			.ps-migration-promo__cta:focus {
				background: #1a1a1a;
				color: #fff !important;
			}
			.ps-migration-promo__cta-arrow {
				width: 13px;
				height: 10px;
			}
		</style>
		<?php
	}
}
