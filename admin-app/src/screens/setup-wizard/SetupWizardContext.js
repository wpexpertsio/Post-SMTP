import { createContext, useContext, useMemo, useState, useCallback, useEffect, useRef } from '@wordpress/element';
import useSetupWizard from './hooks/useSetupWizard';

const SetupWizardContext = createContext( null );

/** Set when user exits the wizard — next entry starts at step 1 (no draft resume). */
export const WIZARD_SKIP_DRAFT_RESTORE_KEY = 'post_smtp_wizard_skip_draft';

const stepFromDraft = ( draft ) => {
	if ( ! draft?.provider ) {
		return 1;
	}
	if ( ! draft?.test_passed ) {
		return draft?.row && Object.keys( draft.row ).length > 1 ? 3 : 2;
	}
	return 3;
};

export const SetupWizardProvider = ( { children } ) => {
	const wizard = useSetupWizard();
	const bootstrap = wizard.bootstrapQuery.data;

	const [ step, setStep ] = useState( 1 );
	const [ maxStep, setMaxStep ] = useState( 1 );
	const [ provider, setProvider ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );
	const [ draftMeta, setDraftMeta ] = useState( null );
	const didRestoreFromBootstrap = useRef( false );
	const activeProviderRef = useRef( null );

	useEffect( () => {
		activeProviderRef.current = provider;
	}, [ provider ] );

	useEffect( () => {
		if ( ! bootstrap ) {
			return;
		}

		let skipRestore = false;
		try {
			skipRestore = window.sessionStorage.getItem( WIZARD_SKIP_DRAFT_RESTORE_KEY ) === '1';
			if ( skipRestore ) {
				window.sessionStorage.removeItem( WIZARD_SKIP_DRAFT_RESTORE_KEY );
			}
		} catch {
			// sessionStorage unavailable
		}

		if ( skipRestore ) {
			if ( bootstrap.draft?.provider ) {
				wizard.discardDraftMutation.mutate();
			}
			didRestoreFromBootstrap.current = true;
			return;
		}

		const draft = bootstrap.draft;

		// Sync test metadata when server draft matches the mailer the user is configuring.
		if ( draft?.provider ) {
			const active = activeProviderRef.current;
			if ( ! active || draft.provider === active ) {
				setDraftMeta( {
					test_passed: Boolean( draft.test_passed ),
					last_recipient: draft.last_recipient || '',
					tested_at: draft.tested_at || '',
				} );
			}
		}

		if ( didRestoreFromBootstrap.current ) {
			return;
		}
		didRestoreFromBootstrap.current = true;

		if ( draft?.provider ) {
			setProvider( draft.provider );
			setFormValues( { provider: draft.provider, ...draft.row } );
			const inferred = stepFromDraft( draft );
			const reached = draft.test_passed ? 3 : inferred;
			setStep( inferred );
			setMaxStep( reached );
		}
	}, [ bootstrap, wizard.discardDraftMutation ] );

	const selectProvider = useCallback( ( slug ) => {
		setProvider( slug );
		setFormValues( { provider: slug } );
		setDraftMeta( {
			test_passed: false,
			last_recipient: '',
			tested_at: '',
		} );
		setStep( 2 );
		setMaxStep( ( prev ) => Math.max( prev, 2 ) );
	}, [] );

	const goToStep = useCallback( ( target ) => {
		setMaxStep( ( prev ) => Math.max( prev, target ) );
		setStep( target );
	}, [] );

	const refreshDraft = useCallback( ( draft ) => {
		if ( ! draft ) {
			setDraftMeta( null );
			return;
		}
		setDraftMeta( {
			test_passed: Boolean( draft.test_passed ),
			last_recipient: draft.last_recipient || '',
			tested_at: draft.tested_at || '',
		} );
	}, [] );

	const value = useMemo(
		() => ( {
			step,
			maxStep,
			setStep,
			setMaxStep,
			provider,
			selectProvider,
			formValues,
			setFormValues,
			draftMeta,
			refreshDraft,
			goToStep,
			wizard,
			bootstrap,
		} ),
		[ step, maxStep, provider, selectProvider, formValues, draftMeta, refreshDraft, goToStep, wizard, bootstrap ]
	);

	return <SetupWizardContext.Provider value={ value }>{ children }</SetupWizardContext.Provider>;
};

export const useSetupWizardContext = () => {
	const ctx = useContext( SetupWizardContext );
	if ( ! ctx ) {
		throw new Error( 'useSetupWizardContext must be used within SetupWizardProvider' );
	}
	return ctx;
};

export default SetupWizardContext;