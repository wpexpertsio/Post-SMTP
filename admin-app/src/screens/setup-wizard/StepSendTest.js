import { useEffect, useState } from '@wordpress/element';
import FormNotice from '../connections/components/FormNotice';
import WizardDomainAuth from './components/WizardDomainAuth';
import { useSetupWizardContext } from './SetupWizardContext';

const StepSendTest = ( { onAdvance } ) => {
	const { wizard, bootstrap, draftMeta, refreshDraft, setStep, setMaxStep } = useSetupWizardContext();
	const [ recipient, setRecipient ] = useState( '' );
	const [ subject, setSubject ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ domainAuth, setDomainAuth ] = useState( null );

	useEffect( () => {
		const email = bootstrap?.current_user_email;
		if ( email && ! recipient ) {
			setRecipient( email );
		}
	}, [ bootstrap?.current_user_email, recipient ] );

	useEffect( () => {
		if ( bootstrap?.default_test_subject && ! subject ) {
			setSubject( bootstrap.default_test_subject );
		}
	}, [ bootstrap?.default_test_subject, subject ] );

	useEffect( () => {
		const run = async () => {
			try {
				const result = await wizard.domainAuthMutation.mutateAsync();
				setDomainAuth( result );
			} catch {
				setDomainAuth( { unavailable: true, messages: [ 'Could not load domain authentication.' ] } );
			}
		};
		run();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

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

	return (
		<section className="ps-setup-wizard__panel" data-figma-node="693:21846">
			<header className="ps-setup-wizard__panel-head">
				<h1>Send a test email</h1>
				<p className="ps-setup-wizard__panel-lead">Confirm delivery before we save this as your primary connection.</p>
			</header>

			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<div className="ps-setup-wizard__test-form">
				<div className="ps-v2-form-field">
					<span className="ps-v2-form-field__label">Send to</span>
					<input
						type="email"
						className="ps-v2-field-input"
						value={ recipient }
						onChange={ ( e ) => setRecipient( e.target.value ) }
						placeholder="name@example.com"
					/>
				</div>
				<div className="ps-v2-form-field">
					<span className="ps-v2-form-field__label">Subject</span>
					<input
						type="text"
						className="ps-v2-field-input"
						value={ subject }
						onChange={ ( e ) => setSubject( e.target.value ) }
					/>
				</div>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary"
					disabled={ wizard.testEmailMutation.isPending }
					onClick={ handleSend }
				>
					{ wizard.testEmailMutation.isPending ? 'Sending…' : 'Send Test Now' }
				</button>
			</div>

			<WizardDomainAuth data={ domainAuth } loading={ wizard.domainAuthMutation.isPending } />

			<div className="ps-setup-wizard__actions">
				<button type="button" className="ps-v2-btn ps-v2-btn--ghost" onClick={ () => setStep( 2 ) }>
					Back
				</button>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary"
					disabled={ ! testPassed }
					onClick={ handleNext }
				>
					Next
				</button>
			</div>
		</section>
	);
};

export default StepSendTest;