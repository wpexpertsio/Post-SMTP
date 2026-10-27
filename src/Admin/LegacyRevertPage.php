<?php
/**
 * Admin page to revert migrated sites back to the legacy experience.
 *
 * @package PostSMTP\Admin
 */

namespace PostSMTP\Admin;

use PostSMTP\Kernel\Migration\ConnectionMigrator;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyRevertPage {

	public const PAGE_SLUG = 'post_smtp_revert_legacy';

	public static function init(): void {
		if ( CohortResolver::current() !== CohortResolver::COHORT_MIGRATED ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'registerPage' ), 99 );
		add_action( 'admin_post_post_smtp_revert_legacy', array( self::class, 'handleRevert' ) );
	}

	public static function registerPage(): void {
		add_submenu_page(
			'postman',
			__( 'Revert to Legacy', 'post-smtp' ),
			__( 'Revert to Legacy', 'post-smtp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'renderPage' )
		);
	}

	public static function handleRevert(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'post-smtp' ) );
		}

		check_admin_referer( 'post_smtp_revert_legacy' );

		$force  = ! empty( $_POST['force_ui_only'] );
		$result = ( new ConnectionMigrator() )->revertToLegacy( $force );

		$redirect = add_query_arg(
			array(
				'page'    => self::PAGE_SLUG,
				'reverted' => ! empty( $result['success'] ) ? '1' : '0',
				'message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = ( new ConnectionMigrator() )->rollbackStatus();

		if ( isset( $_GET['reverted'] ) && '1' === $_GET['reverted'] ) {
			$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $message ?: __( 'Reverted successfully.', 'post-smtp' ) )
			);
			printf(
				'<p><a class="button button-primary" href="%s">%s</a></p>',
				esc_url( admin_url( 'admin.php?page=postman' ) ),
				esc_html__( 'Open Post SMTP dashboard', 'post-smtp' )
			);
			return;
		}

		if ( CohortResolver::current() !== CohortResolver::COHORT_MIGRATED ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Revert to Legacy', 'post-smtp' ) . '</h1>';
			echo '<p>' . esc_html__( 'This site is already using the legacy experience.', 'post-smtp' ) . '</p></div>';
			return;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Revert to Legacy', 'post-smtp' ); ?></h1>
			<p><?php esc_html_e( 'Switch back to the classic Post SMTP admin and storage layout.', 'post-smtp' ); ?></p>

			<?php if ( ! empty( $status['can_full_revert'] ) ) : ?>
				<div class="notice notice-info inline" style="padding:12px;margin:16px 0;">
					<p>
						<strong><?php esc_html_e( 'Full restore available', 'post-smtp' ); ?></strong><br>
						<?php
						printf(
							/* translators: %s: UTC datetime */
							esc_html__( 'A settings snapshot is available until %s UTC.', 'post-smtp' ),
							esc_html( (string) $status['expires_at'] )
						);
						?>
					</p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'post_smtp_revert_legacy' ); ?>
					<input type="hidden" name="action" value="post_smtp_revert_legacy">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Restore legacy settings & admin', 'post-smtp' ); ?>
					</button>
				</form>
			<?php else : ?>
				<div class="notice notice-warning inline" style="padding:12px;margin:16px 0;">
					<p>
						<strong><?php esc_html_e( 'No rollback snapshot', 'post-smtp' ); ?></strong><br>
						<?php esc_html_e( 'The 5-day migration backup has expired or was already used. You can still switch to the legacy admin, but you may need to re-enter mail credentials.', 'post-smtp' ); ?>
					</p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Switch to legacy admin without restoring credentials?', 'post-smtp' ) ); ?>');">
					<?php wp_nonce_field( 'post_smtp_revert_legacy' ); ?>
					<input type="hidden" name="action" value="post_smtp_revert_legacy">
					<input type="hidden" name="force_ui_only" value="1">
					<button type="submit" class="button">
						<?php esc_html_e( 'Switch to legacy admin only', 'post-smtp' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
