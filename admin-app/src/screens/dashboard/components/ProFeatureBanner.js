import Button from '../../../components/ui/Button';
import { PRO_FEATURES, PROVIDERS, providerIcon } from '../../../components/ui/Icons';
import { getAdminUrls } from '../../../config/admin';

const ProFeatureBanner = () => {
	const urls = getAdminUrls();

	return (
		<section className="ps-pro-banner">
			<div className="ps-pro-banner__content">
				<span className="ps-pro-banner__badge">Pro features</span>
				<h2>Do more with Post SMTP Pro</h2>
				<p>One-click mailers, failure alerts, quotas and reports.</p>
				<div className="ps-provider-grid">
					{ PROVIDERS.map( ( item ) => (
						<div key={ item.label } className="ps-provider-tile">
							<img src={ providerIcon( item.slug ) } alt="" />
							<div>
								<strong>{ item.label }</strong>
								<span>{ item.tag }</span>
							</div>
						</div>
					) ) }
				</div>
				<ul className="ps-pro-features">
					{ PRO_FEATURES.map( ( feature ) => (
						<li key={ feature }>✓ { feature }</li>
					) ) }
				</ul>
			</div>
			<div className="ps-pro-banner__cta">
				<Button variant="green" href={ urls.pro } target="_blank" rel="noreferrer">
					Get Post SMTP Pro →
				</Button>
				<Button variant="outlineLight" href={ urls.pro } target="_blank" rel="noreferrer">
					Compare plans
				</Button>
			</div>
		</section>
	);
};

export default ProFeatureBanner;
