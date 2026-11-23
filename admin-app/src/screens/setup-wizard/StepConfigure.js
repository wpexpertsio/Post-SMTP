import { useState } from '@wordpress/element';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../api/client';
import FormNotice from '../connections/components/FormNotice';
import ConfigureMailerForm from '../connections/components/ConfigureMailerForm';
import { buildGmailOAuthSaveRow } from '../connections/components/GmailConfigurePanel';
import { buildOffice365OAuthSaveRow } from '../connections/components/Office365ConfigurePanel';
import { normalizeConnectionSaveRow } from '../connections/model/normalizeConnectionSaveRow';
import { useSetupWizardContext } from './SetupWizardContext';

const StepConfigure = () => {
	const {
		provider,
		formValues,
		setFormValues,
		setStep,
		setMaxStep: bumpMaxStep,
		wizard,
		refreshDraft,
	} = useSetupWizardContext();
	const [ notice, setNotice ] = useState( null );

	const providersQuery = useQuery( {
		queryKey: [ 'providers', provider ],
		queryFn: () => apiFetch( 'providers' ),
		enabled: Boolean( provider ),
	} );

	const schema = provider ? providersQuery.data?.[ provider ] : null;

	const buildPayload = () => {
		let payload = { ...formValues, provider };
		if ( provider === 'gmail_api' ) {
			payload = buildGmailOAuthSaveRow( payload, provider );
		} else if ( provider === 'office365_api' ) {
			payload = buildOffice365OAuthSaveRow( payload, provider );
		}
		return normalizeConnectionSaveRow( payload );
	};

	const handleNext = async () => {
		setNotice( null );
		const row = buildPayload();
		try {
			await wizard.validateMutation.mutateAsync( { provider, row } );
			const result = await wizard.saveDraftMutation.mutateAsync( { provider, row } );
			refreshDraft( result?.draft );
			bumpMaxStep( ( prev ) => Math.max( prev, 3 ) );
			setStep( 3 );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save connection settings.' } );
		}
	};

	const pending = wizard.validateMutation.isPending || wizard.saveDraftMutation.isPending;

	return (
		<section className="ps-setup-wizard__panel" data-figma-node="693:21457">
			<header className="ps-setup-wizard__panel-head">
				<h1>Configure your mailer</h1>
				<p className="ps-setup-wizard__panel-lead">Enter connection details for { provider }.</p>
			</header>

			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			{ schema && (
				<div className="ps-setup-wizard__configure">
					<ConfigureMailerForm
						schema={ schema }
						values={ formValues }
						onChange={ setFormValues }
						provider={ provider }
						onGrantError={ ( message ) => setNotice( { type: 'error', message } ) }
					/>
				</div>
			) }

			<div className="ps-setup-wizard__actions">
				<button type="button" className="ps-v2-btn ps-v2-btn--ghost" onClick={ () => setStep( 1 ) }>
					Back
				</button>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary"
					disabled={ pending || ! schema }
					onClick={ handleNext }
				>
					{ pending ? 'Saving…' : 'Next' }
				</button>
			</div>
		</section>
	);
};

export default StepConfigure;