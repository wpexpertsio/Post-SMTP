import { getAdminUrls } from '../../../config/admin';
import { arrowIcon } from '../../dashboard/components/v2/proBannerAssets';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import { navTeams, navTwilio } from '../notificationChannelAssets';

const PROMO_ICONS = {
	teams: navTeams,
	twilio: navTwilio,
};

const DEFAULT_PROMO = {
	title: 'Teams & SMS failure alerts',
	description: 'Upgrade to configure Microsoft Teams and Twilio (SMS) notification channels.',
	upgrade_label: 'Upgrade to Pro',
};

/**
 * Single aside upsell for Pro notification channels (stacked Teams + Twilio icons).
 */
const NotificationsProChannelsAside = ( { promos = [] } ) => {
	const urls = getAdminUrls();
	const items = promos.length ? promos : [ { slug: 'teams' }, { slug: 'twilio' } ];
	const upgradeUrl =
		items.find( ( p ) => p.upgrade_url )?.upgrade_url || urls.pro || 'https://postmansmtp.com/pricing/';
	const upgradeLabel = items.find( ( p ) => p.upgrade_label )?.upgrade_label || DEFAULT_PROMO.upgrade_label;
	const title = items.find( ( p ) => p.title )?.title || DEFAULT_PROMO.title;
	const description = items.find( ( p ) => p.description )?.description || DEFAULT_PROMO.description;

	const iconSlugs = items.map( ( p ) => p.slug ).filter( ( slug ) => PROMO_ICONS[ slug ] );

	return (
		<aside className="ps-v2-notify-promo" data-figma-node="1273:18230">
			<div className="ps-v2-notify-promo__icons" aria-hidden="true">
				{ iconSlugs.includes( 'teams' ) && (
					<span className="ps-v2-notify-promo__icon ps-v2-notify-promo__icon--teams">
						<img src={ navTeams } alt="" width="29" height="29" />
					</span>
				) }
				{ iconSlugs.includes( 'twilio' ) && (
					<span className="ps-v2-notify-promo__icon ps-v2-notify-promo__icon--twilio">
						<img src={ navTwilio } alt="" width="29" height="29" />
					</span>
				) }
			</div>
			<div className="ps-v2-notify-promo__copy">
				<div className="ps-v2-notify-promo__title-row">
					<strong>{ title }</strong>
					<span className="ps-v2-notify-pro-tag">
						<img src={ proCrownIcon } alt="" width="12" height="10" />
						PRO
					</span>
				</div>
				<p>{ description }</p>
			</div>
			<a href={ upgradeUrl } className="ps-v2-notify-promo__cta" target="_blank" rel="noreferrer">
				{ upgradeLabel }
				<img src={ arrowIcon } alt="" width="14" height="10" />
			</a>
		</aside>
	);
};

export default NotificationsProChannelsAside;
