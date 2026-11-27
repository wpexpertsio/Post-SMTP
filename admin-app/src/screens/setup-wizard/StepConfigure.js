import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { OAUTH_PS_RETURN_ROUTE_SETUP_WIZARD } from '../../utils/oauthReturnRoute';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../api/client';
import iconChevronBack from '../../assets/dashboard/connections/icon-chevron-back.svg';
import iconContinueArrow from '../../assets/setup-wizard/icon-continue-arrow.svg';
import FormNotice from '../connections/components/FormNotice';
import ConfigureMailerForm from '../connections/components/ConfigureMailerForm';
import { buildGmailOAuthSaveRow } from '../connections/components/GmailConfigurePanel';
import { buildOffice365OAuthSaveRow } from '../connections/components/Office365ConfigurePanel';
import { normalizeConnectionSaveRow } from '../connections/model/normalizeConnectionSaveRow';
import { useSetupWizardContext, WIZARD_OAUTH_RETURN_KEY } from './SetupWizardContext';

const wizardConfigureSubtitle = ( label, schema ) => {
	if ( schema?.description ) {
		return schema.description;
	}
	return `Add your ${ label } details and how your emails should look.`;
};

const StepConfigure = () => {
	const {
		provider,
		formValues,
		setFormValues,
		setStep,
		setMaxStep: bumpMaxStep,
		wizard,
		refreshDraft,
		bootstrap,
		oauthReturnNotice,
		clearOauthReturnNotice,
	} = useSetupWizardContext();
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		if ( oauthReturnNotice?.message ) {
			setNotice( {
				type: oauthReturnNotice.type || 'success',
				message: oauthReturnNotice.message,
			} );
			clearOauthReturnNotice();
		}
	}, [ oauthReturnNotice, clearOauthReturnNotice ] );

	const providersQuery = useQuery( {
		queryKey: [ 'providers', provider ],
		queryFn: () => apiFetch( 'providers' ),
		enabled: Boolean( provider ),
	} );

	const schema = provider ? providersQuery.data?.[ provider ] : null;

	const mailerLabel = useMemo( () => {
		const fromCatalog = bootstrap?.catalog?.items?.find( ( item ) => item.slug === provider );
		return schema?.display_name || fromCatalog?.label || provider || 'Mailer';
	}, [ bootstrap?.catalog?.items, provider, schema?.display_name ] );

	const buildPayload = () => {
		let payload = { ...formValues, provider };
		if ( provider === 'gmail_api' ) {
			payload = buildGmailOAuthSaveRow( payload, provider );
		} else if ( provider === 'office365_api' ) {
			payload = buildOffice365OAuthSaveRow( payload, provider );
		}
		return normalizeConnectionSaveRow( payload );
	};

	const onBeforeOAuthSave = useCallback( async () => {
		const row = buildPayload();
		await wizard.validateMutation.mutateAsync( { provider, row } );
		const result = await wizard.saveDraftMutation.mutateAsync( { provider, row } );
		refreshDraft( result?.draft );
		return 0;
	}, [ provider, wizard.validateMutation, wizard.saveDraftMutation, refreshDraft, formValues ] );

	const handleNext = async () => {
		setNotice( null );
		const row = buildPayload();
		try {
			await wizard.validateMutation.mutateAsync( { provider, row } );
			const result = await wizard.saveDraftMutation.mutateAsync( { provider, row } );
			refreshDraft( result?.draft );
			try {
				window.sessionStorage.removeItem( WIZARD_OAUTH_RETURN_KEY );
			} catch {
				// sessionStorage unavailable
			}
			bumpMaxStep( ( prev ) => Math.max( prev, 3 ) );
			setStep( 3 );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save connection settings.' } );
		}
	};

	const pending = wizard.validateMutation.isPending || wizard.saveDraftMutation.isPending;

	return (
		<div className="ps-setup-wizard__step2" data-figma-node="693:21456">
			<article className="ps-setup-wizard__card ps-setup-wizard__card--step-2" data-figma-node="693:21534">
				<header className="ps-setup-wizard__step-intro" data-figma-node="693:21840">
					<div className="ps-setup-wizard__step-kicker">STEP 2</div>
					<h1 className="ps-setup-wizard__step-title">Configure { mailerLabel }</h1>
					{ schema && (
						<p className="ps-setup-wizard__step-lead ps-setup-wizard__step-lead--center">
							{ wizardConfigureSubtitle( mailerLabel, schema ) }
						</p>
					) }
				</header>

				<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

				{ schema && (
					<div className="ps-setup-wizard__configure-form" data-figma-node="693:21784">
						<ConfigureMailerForm
							key={ provider }
							schema={ schema }
							values={ formValues }
							onChange={ setFormValues }
							provider={ provider }
							connectionId={ 0 }
							layoutVariant="wizard"
							oauthReturnRoute={ OAUTH_PS_RETURN_ROUTE_SETUP_WIZARD }
							onBeforeOAuthSave={ onBeforeOAuthSave }
							onGrantError={ ( message ) => setNotice( { type: 'error', message } ) }
						/>
					</div>
				) }
			</article>

			<div className="ps-setup-wizard__step-footer" data-figma-node="693:21681">
				<button type="button" className="ps-setup-wizard__btn-back" onClick={ () => setStep( 1 ) }>
					<img
						className="ps-setup-wizard__btn-back-icon"
						src={ iconChevronBack }
						alt=""
						width="6"
						height="10"
						aria-hidden="true"
					/>
					Back
				</button>
				<button
					type="button"
					className="ps-setup-wizard__btn-continue"
					disabled={ pending || ! schema }
					onClick={ handleNext }
				>
					{ pending ? 'Saving…' : 'Continue' }
					<img
						className="ps-setup-wizard__btn-continue-icon"
						src={ iconContinueArrow }
						alt=""
						width="16"
						height="12"
						aria-hidden="true"
					/>
				</button>
			</div>
		</div>
	);
};

export default StepConfigure;