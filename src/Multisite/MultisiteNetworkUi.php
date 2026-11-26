<?php
/**
 * Network admin sync UI on Settings → Network Settings.
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteNetworkUi {

	public static function init(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'wpmu_options', array( self::class, 'renderSyncPanel' ), 20 );
		add_action( 'admin_footer', array( self::class, 'renderSyncAssets' ) );
	}

	public static function renderSyncPanel(): void {
		if ( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		$global_on = MultisitePolicy::isMultisiteGlobalSettingsEnabled();
		?>
		<h2><?php esc_html_e( 'Post SMTP — Sync child sites', 'post-smtp' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Copy mailer settings', 'post-smtp' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Copies connections and legacy mailer options from the main site into every child site. Child sites use their own local copy after sync; they do not read the main site at send time.', 'post-smtp' ); ?>
					</p>
					<p>
						<button type="button" class="button button-secondary" id="post-smtp-multisite-sync" <?php disabled( ! $global_on ); ?>>
							<?php esc_html_e( 'Sync sites', 'post-smtp' ); ?>
						</button>
					</p>
					<p id="post-smtp-multisite-sync-status" class="description" aria-live="polite"></p>
					<?php if ( ! $global_on ) : ?>
						<p class="description"><?php esc_html_e( 'Enable global settings above before syncing.', 'post-smtp' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function renderSyncAssets(): void {
		if ( ! is_network_admin() || ! current_user_can( 'manage_network' ) ) {
			return;
		}

		$page = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		if ( 'settings.php' !== $page ) {
			return;
		}

		self::renderSyncModalMarkup();
		self::renderSyncStyles();
		self::renderSyncScript();
	}

	private static function renderSyncModalMarkup(): void {
		?>
		<div id="post-smtp-sync-modal" class="ps-ms-sync-modal" hidden aria-hidden="true">
			<div class="ps-ms-sync-modal__backdrop" data-ps-ms-sync-dismiss tabindex="-1"></div>
			<div
				class="ps-ms-sync-modal__dialog"
				role="dialog"
				aria-modal="true"
				aria-labelledby="post-smtp-sync-modal-title"
				aria-describedby="post-smtp-sync-modal-desc"
			>
				<div class="ps-ms-sync-modal__header">
					<span class="ps-ms-sync-modal__icon" aria-hidden="true">!</span>
					<h2 id="post-smtp-sync-modal-title" class="ps-ms-sync-modal__title">
						<?php esc_html_e( 'Sync mailer to all child sites?', 'post-smtp' ); ?>
					</h2>
				</div>
				<div id="post-smtp-sync-modal-desc" class="ps-ms-sync-modal__body">
					<p class="ps-ms-sync-modal__lead">
						<?php esc_html_e( 'This replaces each child site’s mailer configuration with a copy from the main site (blog ID 1).', 'post-smtp' ); ?>
					</p>
					<ul class="ps-ms-sync-modal__list">
						<li><?php esc_html_e( 'Connections and SMTP/API credentials will be overwritten.', 'post-smtp' ); ?></li>
						<li><?php esc_html_e( 'Legacy mailer options will be updated to match the main site.', 'post-smtp' ); ?></li>
						<li><?php esc_html_e( 'Each child keeps its own email log; logs are not merged or copied.', 'post-smtp' ); ?></li>
					</ul>
					<p id="post-smtp-sync-modal-sites" class="ps-ms-sync-modal__sites" hidden></p>
				</div>
				<div class="ps-ms-sync-modal__actions">
					<button type="button" class="button button-secondary" id="post-smtp-sync-modal-cancel">
						<?php esc_html_e( 'Cancel', 'post-smtp' ); ?>
					</button>
					<button type="button" class="button button-primary" id="post-smtp-sync-modal-confirm">
						<?php esc_html_e( 'Sync now', 'post-smtp' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	private static function renderSyncStyles(): void {
		?>
		<style>
			.ps-ms-sync-modal {
				position: fixed;
				inset: 0;
				z-index: 100100;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 24px;
				box-sizing: border-box;
			}

			.ps-ms-sync-modal[hidden] {
				display: none !important;
			}

			.ps-ms-sync-modal__backdrop {
				position: absolute;
				inset: 0;
				background: rgba(15, 23, 42, 0.55);
			}

			.ps-ms-sync-modal__dialog {
				position: relative;
				width: min(480px, 100%);
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 8px;
				box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
				padding: 24px 24px 20px;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			}

			.ps-ms-sync-modal__header {
				display: flex;
				align-items: flex-start;
				gap: 12px;
				margin-bottom: 12px;
			}

			.ps-ms-sync-modal__icon {
				flex: 0 0 36px;
				width: 36px;
				height: 36px;
				border-radius: 50%;
				background: #fef3c7;
				color: #b45309;
				font-weight: 700;
				font-size: 18px;
				line-height: 36px;
				text-align: center;
			}

			.ps-ms-sync-modal__title {
				margin: 4px 0 0;
				padding: 0;
				font-size: 18px;
				font-weight: 600;
				line-height: 1.35;
				color: #1d2327;
			}

			.ps-ms-sync-modal__body {
				margin: 0 0 20px;
				color: #50575e;
				font-size: 13px;
				line-height: 1.55;
			}

			.ps-ms-sync-modal__lead {
				margin: 0 0 12px;
			}

			.ps-ms-sync-modal__list {
				margin: 0 0 12px;
				padding-left: 1.25em;
			}

			.ps-ms-sync-modal__list li {
				margin-bottom: 6px;
			}

			.ps-ms-sync-modal__sites {
				margin: 0;
				padding: 10px 12px;
				background: #f0f6fc;
				border: 1px solid #c5d9ed;
				border-radius: 6px;
				color: #1d2327;
				font-weight: 500;
			}

			.ps-ms-sync-modal__actions {
				display: flex;
				flex-wrap: wrap;
				justify-content: flex-end;
				gap: 8px;
				padding-top: 4px;
				border-top: 1px solid #f0f0f1;
			}
		</style>
		<?php
	}

	private static function renderSyncScript(): void {
		$root       = esc_url_raw( rest_url( 'post-smtp/v2/multisite/sync' ) );
		$status_url = esc_url_raw( rest_url( 'post-smtp/v2/multisite/sync/status' ) );
		$nonce      = wp_create_nonce( 'wp_rest' );
		$sites_label = __( 'Child sites that will be updated:', 'post-smtp' );
		?>
		<script>
		(function () {
			var btn = document.getElementById('post-smtp-multisite-sync');
			var out = document.getElementById('post-smtp-multisite-sync-status');
			var modal = document.getElementById('post-smtp-sync-modal');
			var modalSites = document.getElementById('post-smtp-sync-modal-sites');
			var btnCancel = document.getElementById('post-smtp-sync-modal-cancel');
			var btnConfirm = document.getElementById('post-smtp-sync-modal-confirm');
			var backdrop = modal ? modal.querySelector('[data-ps-ms-sync-dismiss]') : null;

			if (!btn || !out || !modal || !btnCancel || !btnConfirm) {
				return;
			}

			var syncStatus = null;
			var lastFocus = null;

			function setMessage(text) {
				out.textContent = text || '';
			}

			function fetchStatus() {
				return window.fetch('<?php echo esc_js( $status_url ); ?>', {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': '<?php echo esc_js( $nonce ); ?>' }
				}).then(function (r) { return r.json(); });
			}

			function updateModalSitesCount() {
				if (!modalSites) {
					return;
				}
				var count = 0;
				if (syncStatus && Array.isArray(syncStatus.sites)) {
					count = syncStatus.sites.length;
				}
				if (count > 0) {
					modalSites.textContent = '<?php echo esc_js( $sites_label ); ?> ' + count;
					modalSites.hidden = false;
				} else {
					modalSites.hidden = true;
					modalSites.textContent = '';
				}
			}

			function openModal() {
				updateModalSitesCount();
				lastFocus = document.activeElement;
				modal.hidden = false;
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('ps-ms-sync-modal-open');
				btnConfirm.focus();
			}

			function closeModal() {
				modal.hidden = true;
				modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('ps-ms-sync-modal-open');
				if (lastFocus && typeof lastFocus.focus === 'function') {
					lastFocus.focus();
				}
			}

			function onKeydown(ev) {
				if (modal.hidden) {
					return;
				}
				if (ev.key === 'Escape') {
					ev.preventDefault();
					closeModal();
				}
			}

			document.addEventListener('keydown', onKeydown);

			function runSync() {
				closeModal();
				btn.disabled = true;
				setMessage('<?php echo esc_js( __( 'Syncing…', 'post-smtp' ) ); ?>');
				window.fetch('<?php echo esc_js( $root ); ?>', {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( $nonce ); ?>',
						'Content-Type': 'application/json'
					},
					body: '{}'
				})
					.then(function (r) { return r.json().then(function (b) { return { ok: r.ok, body: b }; }); })
					.then(function (res) {
						if (res.body && res.body.message) {
							setMessage(res.body.message);
						} else if (res.body && res.body.data && res.body.data.message) {
							setMessage(res.body.data.message);
						} else {
							setMessage(res.ok ? '<?php echo esc_js( __( 'Sync complete.', 'post-smtp' ) ); ?>' : '<?php echo esc_js( __( 'Sync failed.', 'post-smtp' ) ); ?>');
						}
						return fetchStatus();
					})
					.then(function (data) {
						syncStatus = data;
						if (data && data.main_configured && Array.isArray(data.sites)) {
							var synced = data.sites.filter(function (s) { return s.synced; }).length;
							setMessage(synced + ' / ' + data.sites.length + ' <?php echo esc_js( __( 'child sites report as synced.', 'post-smtp' ) ); ?>');
						}
					})
					.catch(function () {
						setMessage('<?php echo esc_js( __( 'Sync request failed.', 'post-smtp' ) ); ?>');
					})
					.finally(function () {
						btn.disabled = false;
					});
			}

			btn.addEventListener('click', function () {
				if (!syncStatus || !syncStatus.main_configured) {
					setMessage('<?php echo esc_js( __( 'Main site mailer is not configured.', 'post-smtp' ) ); ?>');
					return;
				}
				openModal();
			});

			btnCancel.addEventListener('click', closeModal);
			if (backdrop) {
				backdrop.addEventListener('click', closeModal);
			}
			btnConfirm.addEventListener('click', runSync);

			fetchStatus().then(function (data) {
				syncStatus = data;
				if (!data || !data.main_configured) {
					setMessage('<?php echo esc_js( __( 'Main site mailer is not configured.', 'post-smtp' ) ); ?>');
					return;
				}
				if (Array.isArray(data.sites)) {
					var synced = data.sites.filter(function (s) { return s.synced; }).length;
					setMessage(synced + ' / ' + data.sites.length + ' <?php echo esc_js( __( 'child sites report as synced.', 'post-smtp' ) ); ?>');
				}
			});
		})();
		</script>
		<?php
	}
}