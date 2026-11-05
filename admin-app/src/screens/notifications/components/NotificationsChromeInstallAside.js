import asideChromeInstall from '../../../assets/notifications/aside-chrome-install.png';

const DEFAULT_INSTALL_URL =
	'https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch';

/**
 * Figma 1527:19011 — Chrome notifications sidebar promo.
 */
const NotificationsChromeInstallAside = ( { installUrl = DEFAULT_INSTALL_URL } ) => (
	<aside className="ps-v2-notify-chrome-aside" data-figma-node="1527:19011">
		<div className="ps-v2-notify-chrome-aside__inner">
			<div className="ps-v2-notify-chrome-aside__head">
				<span className="ps-v2-notify-chrome-aside__icon" aria-hidden="true">
					<img src={ asideChromeInstall } alt="" width="30" height="30" />
				</span>
				<div className="ps-v2-notify-chrome-aside__copy">
					<strong>Install our Chrome extension</strong>
					<p>Get failure alerts in Chrome.</p>
				</div>
			</div>
			<a
				href={ installUrl }
				className="ps-v2-notify-chrome-aside__cta"
				target="_blank"
				rel="noreferrer"
			>
				Install Now
			</a>
		</div>
	</aside>
);

export default NotificationsChromeInstallAside;
