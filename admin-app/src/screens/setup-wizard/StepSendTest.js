import { useEffect, useMemo, useState } from '@wordpress/element';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../api/client';
import { getAdminConfig } from '../../config/admin';
import { domainFromSiteUrl } from '../../utils/format';
import iconChevronBack from '../../assets/dashboard/connections/icon-chevron-back.svg';
import iconContinueArrow from '../../assets/setup-wizard/icon-continue-arrow.svg';
import iconSendTest from '../../assets/tools/nav/send-test-email.svg';
import FormNotice from '../connections/components/FormNotice';
import WizardDomainAuth from './components/WizardDomainAuth';
import { useSetupWizardContext } from './SetupWizardContext';

const StepSendTest = ( { onAdvance } ) => {
	const { wizard, bootstrap, draftMeta, formValues, provider, refreshDraft, goToStep, setMaxStep } =
		useSetupWizardContext();
	const [ recipient, setRecipient ] = useState( '' );
	const [ subject, setSubject ] = useState( '' );
	const [ notice, setNotice ] = useState( null );

	const domainAuthQuery = useQuery( {
		queryKey: [ 'setup-wizard', 'domain-auth', provider ],
		queryFn: () => apiFetch( 'setup-wizard/domain-auth', { method: 'POST', body: {} } ),
		enabled: Boolean( provider ),
		staleTime: 10 * 60 * 1000,
		retry: false,
	} );

	const domainAuth = useMemo( () => {
		if ( domainAuthQuery.isPending ) {
			return null;
		}
		if ( domainAuthQuery.isError ) {
			return {
				unavailable: true,
				messages: [ domainAuthQuery.error?.message || 'Could not load domain authentication.' ],
			};
		}
		return domainAuthQuery.data ?? null;
	}, [ domainAuthQuery.data, domainAuthQuery.error, domainAuthQuery.isError, domainAuthQuery.isPending ] );

	useEffect( () => {
		const email = bootstrap?.current_user_email;
		if ( email && ! recipient ) {
			setRecipient( email );
		}
		if ( draftMeta?.last_recipient && ! recipient ) {
			setRecipient( draftMeta.last_recipient );
		}
	}, [ bootstrap?.current_user_email, draftMeta?.last_recipient, recipient ] );

	useEffect( () => {
		if ( bootstrap?.default_test_subject && ! subject ) {
			setSubject( bootstrap.default_test_subject );
		}
	}, [ bootstrap?.default_test_subject, subject ] );

	const senderDomain = useMemo( () => {
		const fromEmail = String( formValues?.from_email || formValues?.sender_email || '' ).trim();
		if ( fromEmail.includes( '@' ) ) {
			return fromEmail.split( '@' )[ 1 ];
		}
		const config = getAdminConfig();
		return domainFromSiteUrl( config.siteUrl || '' );
	}, [ formValues?.from_email, formValues?.sender_email ] );

	const testPassed = Boolean( draftMeta?.test_passed );

	const handleSend = async () => {
		setNotice( null );
		if ( ! recipient.trim() || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( recipient.trim() ) ) {
			setNotice( { type: 'error', message: 'Enter a valid recipient email address.' } );
			return;
		}
		try {
			const result = await wizard.testEmailMutation.mutateAsync( {
				recipient: recipient.trim(),
				subject: subject.trim(),
			} );
			refreshDraft( result?.draft );
			setNotice( {
				type: result?.success ? 'success' : 'error',
				message: result?.message || ( result?.success ? 'Test sent.' : 'Test failed.' ),
			} );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'The test email could not be sent.' } );
		}
	};

	const handleNext = () => {
		if ( ! testPassed ) {
			return;
		}
		setMaxStep( 4 );
		onAdvance();
	};

	const sending = wizard.testEmailMutation.isPending;

	return (
		<div className="ps-setup-wizard__step3" data-figma-node="693:21846">
			<article className="ps-setup-wizard__card ps-setup-wizard__card--step-3" data-figma-node="693:21924">
				<header className="ps-setup-wizard__step-intro" data-figma-node="693:21925">
					<div className="ps-setup-wizard__step-kicker">STEP 3</div>
					<h1 className="ps-setup-wizard__step-title">Send a test email</h1>
					<p className="ps-setup-wizard__step-lead ps-setup-wizard__step-lead--center">
						If the email arrives, your setup works.
					</p>
				</header>

				<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

				<div className="ps-setup-wizard__step3-body" data-figma-node="693:21930">
					<div className="ps-setup-wizard__test-block" data-figma-node="693:21931">
						<label className="ps-setup-wizard__test-label" htmlFor="ps-wizard-test-recipient">
							Where should we send the test?
						</label>
						<input
							id="ps-wizard-test-recipient"
							type="email"
							className="ps-setup-wizard__test-input"
							value={ recipient }
							onChange={ ( e ) => setRecipient( e.target.value ) }
							placeholder="name@example.com"
							autoComplete="email"
						/>
						<button
							type="button"
							className="ps-setup-wizard__test-send"
							disabled={ sending }
							onClick={ handleSend }
						>
							<img
								className="ps-setup-wizard__test-send-icon"
								src={ iconSendTest }
								alt=""
								width="13"
								height="12"
								aria-hidden="true"
							/>
							{ sending ? 'Sending…' : 'Send test now' }
						</button>
					</div>

					<WizardDomainAuth
						data={ domainAuth }
						loading={ domainAuthQuery.isPending }
						domain={ senderDomain }
					/>
				</div>
			</article>

			<div className="ps-setup-wizard__step-footer" data-figma-node="693:21964">
				<button type="button" className="ps-setup-wizard__btn-back" onClick={ () => goToStep( 2 ) }>
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
					disabled={ ! testPassed }
					onClick={ handleNext }
				>
					Continue
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

export default StepSendTest;