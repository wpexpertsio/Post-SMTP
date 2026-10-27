import { domainFromSiteUrl } from '../../../../utils/format';
import { getAdminConfig } from '../../../../config/admin';
import { statusCheck, statusFail } from './sidebarAssets';

const CHECKS = [
	{ id: 'spf', label: 'SPF', pass: true },
	{ id: 'dkim', label: 'DKIM', pass: true },
	{ id: 'dmarc', label: 'DMARC', pass: false },
	{ id: 'bimi', label: 'BIMI', pass: true },
];

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-4945
 */
const DomainAuthSidebar = () => {
	const config = getAdminConfig();
	const domain = domainFromSiteUrl( config.siteUrl || '' );
	const passing = CHECKS.filter( ( c ) => c.pass ).length;
	const score = Math.round( ( passing / CHECKS.length ) * 100 );

	return (
		<section className="ps-v2-domain-card" data-figma-node="672:4945">
			<header className="ps-v2-domain-card__head">
				<h3>Domain authentication</h3>
				<span className="ps-v2-domain-card__domain">{ domain }</span>
			</header>

			<div className="ps-v2-domain-card__grid">
				{ CHECKS.map( ( check ) => (
					<div key={ check.id } className="ps-v2-domain-check">
						<span
							className={
								check.pass
									? 'ps-v2-domain-check__icon is-pass'
									: 'ps-v2-domain-check__icon is-fail'
							}
							aria-hidden="true"
						>
							<img
								src={ check.pass ? statusCheck : statusFail }
								alt=""
								width="15"
								height="15"
							/>
						</span>
						<span className="ps-v2-domain-check__label">{ check.label }</span>
					</div>
				) ) }
			</div>

			<div className="ps-v2-domain-score">
				<div className="ps-v2-domain-score__head">
					<span>Deliverability score</span>
					<strong>{ score }%</strong>
				</div>
				<div className="ps-v2-domain-score__bar" role="presentation">
					<span style={ { width: `${ score }%` } } />
				</div>
			</div>
		</section>
	);
};

export default DomainAuthSidebar;
