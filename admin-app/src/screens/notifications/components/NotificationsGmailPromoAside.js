import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import { arrowIcon, gmailIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

/**
 * Figma 1273:18312 — Gmail Pro upsell in notifications Chrome sidebar.
 */
const NotificationsGmailPromoAside = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const isPro = Boolean( config.isPro );

	if ( isPro ) {
		return null;
	}

	return (
		<aside className="ps-v2-notify-gmail-aside" data-figma-node="1273:18312">
			<div className="ps-v2-notify-gmail-aside__icons" aria-hidden="true">
				<span className="ps-v2-notify-gmail-aside__icon ps-v2-notify-gmail-aside__icon--gmail">
					<img src={ gmailIcon } alt="" width="29" height="29" />
				</span>
			</div>
			<div className="ps-v2-notify-gmail-aside__copy">
				<div className="ps-v2-notify-gmail-aside__title-row">
					<strong>Connect Gmail in one click</strong>
					<span className="ps-v2-notify-pro-tag">
						<img src={ proCrownIcon } alt="" width="12" height="10" />
						PRO
					</span>
				</div>
				<p>Sign in with Google and Post SMTP does the rest.</p>
			</div>
			<a href={ urls.pro || 'https://postmansmtp.com/pricing/' } className="ps-v2-notify-gmail-aside__cta" target="_blank" rel="noreferrer">
				Upgrade to Pro
				<img src={ arrowIcon } alt="" width="14" height="10" />
			</a>
		</aside>
	);
};

export default NotificationsGmailPromoAside;
