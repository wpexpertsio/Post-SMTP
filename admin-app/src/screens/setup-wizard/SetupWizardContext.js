import { createContext, useContext, useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import useSetupWizard from './hooks/useSetupWizard';

const SetupWizardContext = createContext( null );

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

	useEffect( () => {
		if ( ! bootstrap ) {
			return;
		}
		const draft = bootstrap.draft;
		if ( draft?.provider ) {
			setProvider( draft.provider );
			setFormValues( { provider: draft.provider, ...draft.row } );
			setDraftMeta( {
				test_passed: Boolean( draft.test_passed ),
				last_recipient: draft.last_recipient || '',
				tested_at: draft.tested_at || '',
			} );
			const inferred = stepFromDraft( draft );
			const reached = draft.test_passed ? 3 : inferred;
			setStep( inferred );
			setMaxStep( reached );
		}
	}, [ bootstrap ] );

	const selectProvider = useCallback( ( slug ) => {
		setProvider( slug );
		setFormValues( { provider: slug } );
		setStep( 2 );
		setMaxStep( ( prev ) => Math.max( prev, 2 ) );
	}, [] );

	const goToStep = useCallback(
		( target ) => {
			if ( target <= maxStep ) {
				setStep( target );
			}
		},
		[ maxStep ]
	);

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