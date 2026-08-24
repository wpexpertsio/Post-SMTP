<?php
/**
 * PHP-only admin notice linking to React migration wizard.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MigrationNotice {

	public static function init(): void {
		add_action( 'admin_notices', array( self::class, 'render' ) );
		add_action( 'wp_ajax_post_smtp_dismiss_migration_notice', array( self::class, 'dismiss' ) );
	}

	public static function dismiss(): void {
		check_ajax_referer( 'post_smtp_dismiss_migration' );
		update_user_meta( get_current_user_id(), 'post_smtp_migration_notice_dismissed', 1 );
		wp_send_json_success();
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			return;
		}

		if ( CohortResolver::current() === CohortResolver::COHORT_MIGRATED ) {
			return;
		}

		$dismissed = get_user_meta( get_current_user_id(), 'post_smtp_migration_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		$migration_url = admin_url( 'admin.php?page=post_smtp_v4_migration' );
		?>
		<div class="notice notice-info is-dismissible" data-post-smtp-migration-notice="1">
			<p>
				<strong><?php esc_html_e( 'A new Post SMTP experience is available', 'post-smtp' ); ?></strong>
			</p>
			<p><?php esc_html_e( 'Migrate to multi-connection storage and the new React admin when you are ready.', 'post-smtp' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $migration_url ); ?>">
					<?php esc_html_e( 'Start guided migration', 'post-smtp' ); ?>
				</a>
			</p>
		</div>
		<script>
		(function(){
			document.addEventListener('click', function(e){
				var n = e.target.closest('[data-post-smtp-migration-notice] .notice-dismiss');
				if(!n) return;
				wp.ajax.post('post_smtp_dismiss_migration_notice', { _wpnonce: '<?php echo esc_js( wp_create_nonce( 'post_smtp_dismiss_migration' ) ); ?>' });
			});
		})();
		</script>
		<?php
	}
}
