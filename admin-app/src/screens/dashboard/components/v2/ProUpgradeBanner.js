import { getAdminUrls } from '../../../../config/admin';
import {
	arrowIcon,
	checkIcon,
	crownIcon,
	iconGoogle,
	iconMicrosoft,
	iconZoho,
	integrationAws,
	planeIllustration,
} from './proBannerAssets';

const FEATURES_LEFT = [
	{
		id: 'integrations',
		label: 'Integrations',
		icons: true,
	},
	{ id: 'one-click', label: 'One-Click Mailer Setup' },
	{ id: 'tracking', label: 'Email Sends & Failure Tracking' },
];

const FEATURES_RIGHT = [
	{ id: 'quotas', label: 'Email Quotas & Failure Alerts' },
	{ id: 'opens', label: 'Open Tracking & Per-Mailer Reports' },
	{ id: 'logs', label: 'Log Attachments & Delivery Reports' },
];

const IntegrationStack = () => (
	<div className="ps-v2-pro-banner__integrations" aria-hidden="true">
		<span className="ps-v2-pro-banner__int-icon ps-v2-pro-banner__int-icon--google">
			<img src={ iconGoogle } alt="" width="16" height="16" />
		</span>
		<span className="ps-v2-pro-banner__int-icon ps-v2-pro-banner__int-icon--aws">
			<img src={ integrationAws } alt="" width="14" height="14" />
		</span>
		<span className="ps-v2-pro-banner__int-icon ps-v2-pro-banner__int-icon--microsoft">
			<img src={ iconMicrosoft } alt="" width="14" height="14" />
		</span>
		<span className="ps-v2-pro-banner__int-icon ps-v2-pro-banner__int-icon--zoho">
			<img src={ iconZoho } alt="" width="23" height="8" />
		</span>
	</div>
);

const FeatureItem = ( { label, icons } ) => (
	<li
		className={
			icons
				? 'ps-v2-pro-banner__feature ps-v2-pro-banner__feature--integrations'
				: 'ps-v2-pro-banner__feature'
		}
	>
		<img className="ps-v2-pro-banner__check" src={ checkIcon } alt="" width="14" height="14" />
		{ icons ? (
			<span className="ps-v2-pro-banner__feature-text">
				<span className="ps-v2-pro-banner__feature-label">{ label }</span>
				<IntegrationStack />
			</span>
		) : (
			<span className="ps-v2-pro-banner__feature-label">{ label }</span>
		) }
	</li>
);

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 932:12637 (900×400)
 */
const ProUpgradeBanner = () => {
	const urls = getAdminUrls();

	return (
		<section className="ps-v2-pro-banner" data-figma-node="932:12637">
			<img
				className="ps-v2-pro-banner__plane"
				src={ planeIllustration }
				alt=""
				width="194"
				height="200"
			/>

			<div className="ps-v2-pro-banner__content">
				<div className="ps-v2-pro-banner__intro">
					<h2>Take Full Control of Your Email Delivery</h2>
					<p>
						Connect your favorite mailers, track email activity, and monitor delivery performance with Post SMTP Pro.
					</p>
				</div>

				<div className="ps-v2-pro-banner__features">
					<ul>
						{ FEATURES_LEFT.map( ( item ) => (
							<FeatureItem key={ item.id } label={ item.label } icons={ item.icons } />
						) ) }
					</ul>
					<ul>
						{ FEATURES_RIGHT.map( ( item ) => (
							<FeatureItem key={ item.id } label={ item.label } />
						) ) }
					</ul>
				</div>

				<div className="ps-v2-pro-banner__actions">
					<a href={ urls.pro } className="ps-v2-pro-banner__cta" target="_blank" rel="noreferrer">
						Get Post SMTP Pro
						<img src={ arrowIcon } alt="" width="14" height="10" />
					</a>
					<a href={ urls.pro } className="ps-v2-pro-banner__compare" target="_blank" rel="noreferrer">
						Compare Plans
					</a>
				</div>
			</div>

			<div className="ps-v2-pro-banner__pro-ribbon-wrap" aria-hidden="true">
				<div className="ps-v2-pro-banner__pro-ribbon-rotate">
					<div className="ps-v2-pro-banner__sash">
						<img className="ps-v2-pro-banner__sash-crown" src={ crownIcon } alt="" width="14" height="12" />
						<span>PRO FEATURES</span>
					</div>
				</div>
			</div>
		</section>
	);
};

export default ProUpgradeBanner;
