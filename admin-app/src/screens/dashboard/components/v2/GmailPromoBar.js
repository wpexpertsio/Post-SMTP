import { getAdminUrls } from '../../../../config/admin';
import { arrowIcon, gmailIcon, lightningIcon, proCrownIcon } from './gmailBarAssets';

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-4989
 */
const GmailPromoBar = () => {
	const urls = getAdminUrls();

	return (
		<section className="ps-v2-gmail-bar" data-figma-node="672:4989">
			<div className="ps-v2-gmail-bar__inner">
				<div className="ps-v2-gmail-bar__left">
					<div className="ps-v2-gmail-bar__icon" aria-hidden="true">
						<img src={ gmailIcon } alt="" width="22" height="23" />
					</div>
					<div className="ps-v2-gmail-bar__copy">
						<div className="ps-v2-gmail-bar__title-row">
							<strong>Connect Gmail in one click</strong>
							<span className="ps-v2-gmail-bar__pro-tag">
								<img src={ proCrownIcon } alt="" width="12" height="10" />
								PRO
							</span>
							<span className="ps-v2-gmail-bar__setup-tag">
								<img src={ lightningIcon } alt="" width="9" height="12" />
								20-second setup
							</span>
						</div>
						<p>Sign in with Google and Post SMTP does the rest.</p>
					</div>
				</div>
				<div className="ps-v2-gmail-bar__actions">
					<a href={ urls.docs } className="ps-v2-gmail-bar__learn" target="_blank" rel="noreferrer">
						Learn more
					</a>
					<a href={ urls.pro } className="ps-v2-gmail-bar__cta" target="_blank" rel="noreferrer">
						Upgrade to Pro
						<img src={ arrowIcon } alt="" width="14" height="10" />
					</a>
				</div>
			</div>
		</section>
	);
};

export default GmailPromoBar;
