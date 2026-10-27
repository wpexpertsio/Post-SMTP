import { getAdminUrls } from '../../../config/admin';
import { arrowIcon, gmailIcon, lightningIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

const urls = getAdminUrls();

const ConnectionsGmailPromoCard = () => (
	<aside className="ps-v2-conn-promo" data-figma-node="672:5312">
		<div className="ps-v2-conn-promo__head">
			<div className="ps-v2-conn-promo__icon" aria-hidden="true">
				<img src={ gmailIcon } alt="" width="22" height="23" />
			</div>
			<span className="ps-v2-conn-promo__setup-tag">
				<img src={ lightningIcon } alt="" width="9" height="12" />
				20-second setup
			</span>
		</div>
		<div className="ps-v2-conn-promo__copy">
			<div className="ps-v2-conn-promo__title-row">
				<strong>Connect Gmail in one click</strong>
				<span className="ps-v2-conn-promo__pro-tag">
					<img src={ proCrownIcon } alt="" width="12" height="10" />
					PRO
				</span>
			</div>
			<p>Sign in with Google and Post SMTP does the rest.</p>
		</div>
		<a href={ urls.pro } className="ps-v2-conn-promo__cta" target="_blank" rel="noreferrer">
			Upgrade to Pro
			<img src={ arrowIcon } alt="" width="14" height="10" />
		</a>
	</aside>
);

export default ConnectionsGmailPromoCard;
