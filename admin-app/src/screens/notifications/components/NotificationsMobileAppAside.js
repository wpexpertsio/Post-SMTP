import badgeAppStore from '../../../assets/mobile-app/badge-app-store.png';
import badgeGooglePlay from '../../../assets/mobile-app/badge-google-play.png';
import iconRecommended from '../../../assets/notifications/icon-recommended.svg';

const DEFAULT_STORE_LINKS = {
	google_play:
		'https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Dsettings%26anid%3Dadmob',
	app_store:
		'https://apps.apple.com/us/app/post-smtp/id6473368559?utm_source=plugin&utm_medium=dashboard&utm_campaign=plugin',
};

/**
 * Figma 1527:19035 — Pushover (and mobile-friendly channels) sidebar app promo.
 */
const NotificationsMobileAppAside = ( { storeLinks = DEFAULT_STORE_LINKS } ) => {
	const googlePlay = storeLinks?.google_play || DEFAULT_STORE_LINKS.google_play;
	const appStore = storeLinks?.app_store || DEFAULT_STORE_LINKS.app_store;

	return (
		<aside className="ps-v2-notify-mobile-aside" data-figma-node="1527:19035">
			<div className="ps-v2-notify-mobile-aside__inner">
				<div className="ps-v2-notify-mobile-aside__head">
					<div className="ps-v2-notify-mobile-aside__title-row">
						<strong>Get Post SMTP App</strong>
						<span className="ps-v2-notify-mobile-aside__badge">
							<img src={ iconRecommended } alt="" width="8" height="11" />
							Recommended
						</span>
					</div>
					<p>Check email delivery &amp; failure alerts on your phone.</p>
				</div>
				<div className="ps-v2-notify-mobile-aside__stores">
					<a href={ googlePlay } target="_blank" rel="noreferrer" className="ps-v2-notify-mobile-aside__store">
						<img src={ badgeGooglePlay } alt="Get it on Google Play" width="120" height="40" />
					</a>
					<a href={ appStore } target="_blank" rel="noreferrer" className="ps-v2-notify-mobile-aside__store">
						<img src={ badgeAppStore } alt="Download on the App Store" width="120" height="40" />
					</a>
				</div>
			</div>
		</aside>
	);
};

export default NotificationsMobileAppAside;
