import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import { DOMAIN_CHECKS_PLACEHOLDER } from '../../dashboard/constants';

const defaultDetail = ( id, status ) => {
	if ( status === 'pass' ) {
		if ( id === 'spf' ) {
			return 'Your domain is set up to send mail.';
		}
		if ( id === 'dkim' ) {
			return 'Your messages are signed.';
		}
		return 'Record looks good.';
	}
	if ( id === 'dmarc' ) {
		return 'Set up but not enforced yet.';
	}
	const placeholder = DOMAIN_CHECKS_PLACEHOLDER.find( ( row ) => row.id === id );
	return placeholder
		? `Check your ${ placeholder.label } record for this domain.`
		: 'Review your DNS records.';
};

const WizardDomainAuth = ( { data, loading, domain } ) => {
	if ( loading ) {
		return (
			<div className="ps-setup-wizard__domain-auth" data-figma-node="693:21995">
				<p className="ps-setup-wizard__domain-loading">Checking domain authentication…</p>
			</div>
		);
	}

	if ( ! data ) {
		return null;
	}

	const hasChecks = Boolean( data.spf || data.dkim || data.dmarc );
	const showUnavailable = Boolean( data.unavailable ) || ( ! hasChecks && ( data.messages?.length ?? 0 ) > 0 );

	if ( showUnavailable ) {
		const messages = data.messages || [];
		return (
			<div className="ps-setup-wizard__domain-auth ps-setup-wizard__domain-auth--muted" data-figma-node="693:21995">
				<div className="ps-setup-wizard__domain-auth-head">
					<div className="ps-setup-wizard__domain-auth-copy">
						<p className="ps-setup-wizard__domain-auth-title">Domain authentication</p>
						<p className="ps-setup-wizard__domain-auth-desc">Helps your emails land in the inbox, not spam.</p>
					</div>
				</div>
				{ messages.map( ( message, i ) => (
					<p key={ i } className="ps-setup-wizard__domain-auth-unavail">
						{ message }
					</p>
				) ) }
			</div>
		);
	}

	const checks = [
		{ id: 'spf', ...( data.spf || { status: 'unknown', label: 'SPF' } ) },
		{ id: 'dkim', ...( data.dkim || { status: 'unknown', label: 'DKIM' } ) },
		{ id: 'dmarc', ...( data.dmarc || { status: 'unknown', label: 'DMARC' } ) },
	];

	const domainHost = domain || 'your-domain.com';
	const domainHref = `https://${ domainHost }`;

	return (
		<section className="ps-setup-wizard__domain-auth" data-figma-node="693:21995">
			<div className="ps-setup-wizard__domain-auth-head" data-figma-node="693:21996">
				<div className="ps-setup-wizard__domain-auth-copy" data-figma-node="693:21997">
					<p className="ps-setup-wizard__domain-auth-title">Domain authentication</p>
					<p className="ps-setup-wizard__domain-auth-desc">Helps your emails land in the inbox, not spam.</p>
				</div>
				<a
					className="ps-setup-wizard__domain-auth-link"
					href={ domainHref }
					target="_blank"
					rel="noreferrer"
					data-figma-node="693:22000"
				>
					<span>{ domainHost }</span>
					<img src={ iconExternalLink } alt="" width="13" height="13" aria-hidden="true" />
				</a>
			</div>

			<div className="ps-setup-wizard__domain-auth-grid" data-figma-node="693:22015">
				{ checks.map( ( check ) => {
					const passed = check.status === 'pass';
					const label = check.label || check.id.toUpperCase();
					const detail = ( check.detail || '' ).trim() || defaultDetail( check.id, check.status );
					return (
						<article
							key={ check.id }
							className={ `ps-setup-wizard__domain-auth-card${ passed ? ' is-pass' : ' is-fail' }` }
							data-figma-node={ `693:2201${ check.id === 'spf' ? '7' : check.id === 'dkim' ? '7' : '8' }` }
						>
							<div className="ps-setup-wizard__domain-auth-card-head">
								<span className="ps-setup-wizard__domain-auth-card-marker" aria-hidden="true" />
								<strong className="ps-setup-wizard__domain-auth-card-label">{ label }</strong>
							</div>
							{ detail && <p className="ps-setup-wizard__domain-auth-card-detail">{ detail }</p> }
						</article>
					);
				} ) }
			</div>
		</section>
	);
};

export default WizardDomainAuth;