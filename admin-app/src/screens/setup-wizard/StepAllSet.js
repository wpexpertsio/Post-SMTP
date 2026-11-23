import { useState } from '@wordpress/element';
import { Link, useNavigate } from 'react-router-dom';
import FormNotice from '../connections/components/FormNotice';
import NotificationsMobileAppAside from '../notifications/components/NotificationsMobileAppAside';
import { useSetupWizardContext } from './SetupWizardContext';

const StepAllSet = () => {
	const navigate = useNavigate();
	const { wizard, bootstrap, draftMeta, formValues, provider } = useSetupWizardContext();
	const [ notice, setNotice ] = useState( null );

	const title = formValues.connection_title || formValues.title || 'Primary connection';
	const fromEmail = formValues.from_email || formValues.sender_email || '';
	const mailerLabel =
		bootstrap?.catalog?.items?.find( ( item ) => item.slug === provider )?.label || provider;

	const handleComplete = async () => {
		setNotice( null );
		try {
			await wizard.completeMutation.mutateAsync();
			navigate( '/' );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not complete setup.' } );
		}
	};

	return (
		<section className="ps-setup-wizard__panel ps-setup-wizard__panel--all-set" data-figma-node="693:22064">
			<header className="ps-setup-wizard__panel-head">
				<h1>You&apos;re all set</h1>
				<p className="ps-setup-wizard__panel-lead">Your primary connection is ready to send WordPress mail.</p>
			</header>

			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<div className="ps-setup-wizard__summary">
				<div className="ps-setup-wizard__summary-card">
					<span className="ps-setup-wizard__badge">Primary</span>
					<h2>{ title }</h2>
					<p>
						{ mailerLabel }
						{ fromEmail ? ` · ${ fromEmail }` : '' }
					</p>
					{ draftMeta?.last_recipient && (
						<p className="ps-setup-wizard__test-meta">
							Test sent to <strong>{ draftMeta.last_recipient }</strong>
						</p>
					) }
				</div>
				<NotificationsMobileAppAside />
			</div>

			<div className="ps-setup-wizard__actions ps-setup-wizard__actions--wrap">
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary"
					disabled={ wizard.completeMutation.isPending }
					onClick={ handleComplete }
				>
					{ wizard.completeMutation.isPending ? 'Finishing…' : 'Complete setup' }
				</button>
				<Link to="/" className="ps-v2-btn ps-v2-btn--ghost">
					Go to Dashboard
				</Link>
				<Link to="/connections" className="ps-v2-btn ps-v2-btn--ghost">
					View Connections
				</Link>
				<Link to="/email-logs" className="ps-v2-btn ps-v2-btn--ghost">
					Open Email Log
				</Link>
			</div>
		</section>
	);
};

export default StepAllSet;