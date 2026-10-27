import Card from '../../../components/ui/Card';
import { domainFromSiteUrl } from '../../../utils/format';
import { DOMAIN_CHECKS_PLACEHOLDER } from '../constants';
import { getAdminConfig } from '../../../config/admin';

const DomainAuthenticationCard = () => {
	const config = getAdminConfig();
	const domain = domainFromSiteUrl( config.siteUrl || '' );
	const passing = DOMAIN_CHECKS_PLACEHOLDER.filter( ( check ) => check.status === 'pass' ).length;

	return (
		<Card className="ps-domain-card">
			<div className="ps-domain-card__head">
				<h2>Domain authentication</h2>
				<span>{ domain } · { passing } of { DOMAIN_CHECKS_PLACEHOLDER.length } checks passing</span>
			</div>
			<div className="ps-domain-grid">
				{ DOMAIN_CHECKS_PLACEHOLDER.map( ( check ) => (
					<article
						key={ check.id }
						className={ `ps-domain-box is-${ check.status === 'pass' ? 'pass' : 'fail' }` }
					>
						<div className="ps-domain-box__head">
							<strong>{ check.label }</strong>
							<span>{ check.status === 'pass' ? 'PASS' : 'FAIL' }</span>
						</div>
						<p>{ check.description }</p>
					</article>
				) ) }
			</div>
		</Card>
	);
};

export default DomainAuthenticationCard;
