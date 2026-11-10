import { Link } from 'react-router-dom';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import { arrowIcon, gmailIcon, lightningIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

/**
 * Free: Figma 672:5312 — Pro active: Figma 810:13801
 */
const ConnectionsGmailPromoCard = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const oneClick = config.gmailConfigure?.oneClick || {};
	const isPro = Boolean( config.isPro || oneClick.hasPro );
	const showProTag = ! isPro && oneClick.showProTag !== false;
	const enableLabel = oneClick.dashboardEnableLabel || 'Enable one-click';
	const enablePath = oneClick.dashboardEnablePath || '/connections/add?provider=gmail_api';

	return (
		<aside
			className={ `ps-v2-conn-promo${ isPro ? ' ps-v2-conn-promo--pro' : '' }` }
			data-figma-node={ isPro ? '810:13801' : '672:5312' }
		>
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
					{ showProTag && (
						<span className="ps-v2-conn-promo__pro-tag">
							<img src={ proCrownIcon } alt="" width="12" height="10" />
							PRO
						</span>
					) }
				</div>
				<p>Sign in with Google and Post SMTP does the rest.</p>
			</div>
			{ isPro ? (
				<Link
					to={ enablePath }
					className="ps-v2-conn-promo__cta ps-v2-conn-promo__cta--enable"
				>
					{ enableLabel }
				</Link>
			) : (
				<a href={ urls.pro } className="ps-v2-conn-promo__cta" target="_blank" rel="noreferrer">
					Upgrade to Pro
					<img src={ arrowIcon } alt="" width="14" height="10" />
				</a>
			) }
		</aside>
	);
};

export default ConnectionsGmailPromoCard;
