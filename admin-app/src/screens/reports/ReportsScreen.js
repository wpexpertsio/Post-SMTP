import { useEffect } from '@wordpress/element';
import { getAdminConfig, getAdminUrls } from '../../config/admin';
import checkIcon from '../../assets/dashboard/pro-banner/check.svg';
import iconUpgradeArrow from '../../assets/help-docs/icon-upgrade-arrow.svg';
import iconReportsChart from '../../assets/reports/icon-reports-chart.svg';
import { proCrownIcon } from '../dashboard/components/v2/gmailBarAssets';
import ReportsPageTrackingSlot from './components/ReportsPageTrackingSlot';

const FEATURES = [
	'Email delivery',
	'Email quota',
	'By subject filtration',
];

const PREVIEW_SLOTS = [ 'delivery', 'quota', 'subjects' ];

const ReportsScreen = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const trackingEnabled = Boolean( config.dashboardReportsTracking?.enabled );
	const legacyAddon = Boolean( config.hasReportTracking );

	useEffect( () => {
		if ( trackingEnabled || ! legacyAddon || ! urls.reportsLegacy ) {
			return;
		}
		window.location.assign( urls.reportsLegacy );
	}, [ trackingEnabled, legacyAddon, urls.reportsLegacy ] );

	if ( ! trackingEnabled && legacyAddon ) {
		return (
			<p className="ps-v2-reports-loading" data-figma-node="672:9240">
				Opening reports…
			</p>
		);
	}

	if ( trackingEnabled ) {
		return (
			<div className="ps-v2-reports-page ps-v2-reports-page--pro" data-figma-node="601:13732">
				<div className="ps-v2-reports-layout">
					<ReportsPageTrackingSlot />
				</div>
			</div>
		);
	}

	return (
		<div className="ps-v2-reports-page" data-figma-node="672:9240">
			<div className="ps-v2-reports-stack" data-figma-node="1072:44860">
				<header className="ps-v2-reports-head" data-figma-node="1072:44861">
					<span className="ps-v2-reports-head__icon" aria-hidden="true">
						<img src={ iconReportsChart } alt="" width="20" height="20" />
					</span>
					<div className="ps-v2-reports-head__title-row" data-figma-node="1072:44871">
						<h1 className="ps-v2-reports-head__title">Reports and Analytics</h1>
						<span className="ps-v2-mailer-pro-tag" data-figma-node="1072:44873">
							<img src={ proCrownIcon } alt="" width="11" height="9" />
							PRO
						</span>
					</div>
				</header>

				<section className="ps-v2-reports-features" data-figma-node="1072:44874">
					<h2 className="ps-v2-reports-features__lead">
						Unlock these awesome logging features:
					</h2>
					<ul className="ps-v2-reports-features__list" data-figma-node="1072:44876">
						{ FEATURES.map( ( label ) => (
							<li key={ label }>
								<img src={ checkIcon } alt="" width="14" height="14" aria-hidden="true" />
								<span>{ label }</span>
							</li>
						) ) }
					</ul>
				</section>

				<div className="ps-v2-reports-previews" data-figma-node="1072:44886">
					{ PREVIEW_SLOTS.map( ( slot ) => (
						<div
							key={ slot }
							className="ps-v2-reports-preview"
							aria-hidden="true"
						/>
					) ) }
				</div>

				<div className="ps-v2-reports-cta-wrap" data-figma-node="1072:44904">
					<a
						href={ urls.pro }
						className="ps-v2-reports-cta"
						target="_blank"
						rel="noreferrer"
					>
						Upgrade to Pro
						<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
					</a>
				</div>
			</div>
		</div>
	);
};

export default ReportsScreen;
