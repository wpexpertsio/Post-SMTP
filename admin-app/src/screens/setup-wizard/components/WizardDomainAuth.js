import { statusCheck, statusFail } from '../../dashboard/components/v2/sidebarAssets';

const statusIcon = ( status ) => {
	if ( status === 'pass' ) {
		return statusCheck;
	}
	if ( status === 'fail' ) {
		return statusFail;
	}
	return statusFail;
};

const WizardDomainAuth = ( { data, loading } ) => {
	if ( loading ) {
		return <p className="ps-setup-wizard__domain-loading">Checking domain authentication…</p>;
	}

	if ( ! data ) {
		return null;
	}

	if ( data.unavailable ) {
		const messages = data.messages || [];
		return (
			<div className="ps-setup-wizard__domain ps-setup-wizard__domain--muted">
				<h3>Domain authentication</h3>
				{ messages.map( ( message, i ) => (
					<p key={ i }>{ message }</p>
				) ) }
			</div>
		);
	}

	const checks = [
		{ id: 'spf', ...( data.spf || { status: 'unknown', label: 'SPF' } ) },
		{ id: 'dkim', ...( data.dkim || { status: 'unknown', label: 'DKIM' } ) },
		{ id: 'dmarc', ...( data.dmarc || { status: 'unknown', label: 'DMARC' } ) },
	];

	return (
		<section className="ps-setup-wizard__domain" data-figma-node="693:21846">
			<h3>Domain authentication</h3>
			<div className="ps-setup-wizard__domain-grid">
				{ checks.map( ( check ) => (
					<div key={ check.id } className="ps-setup-wizard__domain-check">
						<span
							className={
								check.status === 'pass'
									? 'ps-setup-wizard__domain-icon is-pass'
									: 'ps-setup-wizard__domain-icon is-fail'
							}
							aria-hidden="true"
						>
							<img src={ statusIcon( check.status ) } alt="" width="15" height="15" />
						</span>
						<div>
							<strong>{ check.label || check.id.toUpperCase() }</strong>
							{ check.detail && <p>{ check.detail }</p> }
						</div>
					</div>
				) ) }
			</div>
		</section>
	);
};

export default WizardDomainAuth;