import { createContext, useContext, useMemo, useState, useCallback, useEffect, useRef } from '@wordpress/element';
import { useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../api/client';
import useSetupWizard from './hooks/useSetupWizard';

const SetupWizardContext = createContext( null );

/** Set when user exits the wizard — next entry starts at step 1 (no draft resume). */
export const WIZARD_SKIP_DRAFT_RESTORE_KEY = 'post_smtp_wizard_skip_draft';

/** After OAuth, resume configure (step 2) even when draft row is fully saved. */
export const WIZARD_OAUTH_RETURN_KEY = 'post_smtp_wizard_oauth_return';

const stepFromDraft = ( draft ) => {
	if ( ! draft?.provider ) {
		return 1;
	}
	if ( draft?.test_passed ) {
		return 3;
	}
	return 2;
};

export const SetupWizardProvider = ( { children } ) => {
	const wizard = useSetupWizard();
	const queryClient = useQueryClient();
	const bootstrap = wizard.bootstrapQuery.data;

	const [ step, setStep ] = useState( 1 );
	const [ oauthReturnNotice, setOauthReturnNotice ] = useState( null );
	const [ maxStep, setMaxStep ] = useState( 1 );
	const [ provider, setProvider ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );
	const [ draftMeta, setDraftMeta ] = useState( null );
	const didRestoreFromBootstrap = useRef( false );
	const activeProviderRef = useRef( null );
	/** OAuth redirect landed on setup-wizard — stay on step 2 until user continues (once). */
	const oauthReturnPendingRef = useRef(
		typeof window !== 'undefined' &&
			new URLSearchParams( window.location.search ).get( 'ps-route' ) === 'setup-wizard'
	);

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
			let inferred = stepFromDraft( draft );
			let reached;
			if ( oauthReturnPendingRef.current ) {
				inferred = 2;
				reached = 2;
				oauthReturnPendingRef.current = false;
			} else {
				reached = draft.test_passed ? 3 : Math.max( 2, inferred );
			}
			setStep( inferred );
			setMaxStep( ( prev ) => Math.max( prev, reached ) );
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

	const clearOauthReturnNotice = useCallback( () => {
		setOauthReturnNotice( null );
	}, [] );

	useEffect( () => {
		const params = new URLSearchParams( window.location.search );
		const msg = params.get( 'msg' );
		const success = params.get( 'success' );
		const psRoute = params.get( 'ps-route' );
		if ( psRoute !== 'setup-wizard' ) {
			return;
		}

		oauthReturnPendingRef.current = true;
		try {
			window.sessionStorage.setItem( WIZARD_OAUTH_RETURN_KEY, '1' );
		} catch {
			// sessionStorage unavailable
		}

		if ( msg ) {
			setOauthReturnNotice( {
				type: success === '1' ? 'success' : 'error',
				message: msg,
			} );
		}

		setStep( 2 );
		setMaxStep( ( prev ) => Math.max( prev, 2 ) );

		const oauthTab = params.get( 'office365_connect_tab' );
		const gmailTab = params.get( 'gmail_connect_tab' );

		const syncAfterOAuth = async () => {
			let connectionsData = null;
			let wizardData = null;

			try {
				await queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
				await queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );

				connectionsData = await queryClient.fetchQuery( {
					queryKey: [ 'connections' ],
					queryFn: () => apiFetch( 'connections' ),
				} );
				wizardData = await queryClient.fetchQuery( {
					queryKey: [ 'setup-wizard' ],
					queryFn: () => apiFetch( 'setup-wizard' ),
				} );
			} catch {
				connectionsData = null;
				wizardData = null;
			}

			const primary = connectionsData?.connections?.find( ( c ) => c?.id === 0 );
			const draftRow = wizardData?.draft?.row;

			if ( primary || draftRow ) {
				const source = primary || draftRow;
				setProvider( ( prev ) => source.provider || prev );
				setFormValues( ( prev ) => ( {
					...prev,
					...( draftRow || {} ),
					...( primary || {} ),
					provider: source.provider || prev?.provider,
					...( oauthTab === 'manual' || oauthTab === 'one_click'
						? { office365_connect_tab: oauthTab }
						: {} ),
					...( gmailTab === 'manual' || gmailTab === 'one_click'
						? { gmail_connect_tab: gmailTab }
						: {} ),
				} ) );
			} else {
				if ( oauthTab === 'manual' || oauthTab === 'one_click' ) {
					setFormValues( ( prev ) => ( { ...prev, office365_connect_tab: oauthTab } ) );
				}
				if ( gmailTab === 'manual' || gmailTab === 'one_click' ) {
					setFormValues( ( prev ) => ( { ...prev, gmail_connect_tab: gmailTab } ) );
				}
			}

			try {
				window.sessionStorage.removeItem( WIZARD_OAUTH_RETURN_KEY );
			} catch {
				// sessionStorage unavailable
			}
		};

		syncAfterOAuth();

		params.delete( 'msg' );
		params.delete( 'success' );
		params.delete( 'ps-route' );
		params.delete( 'office365_connect_tab' );
		params.delete( 'gmail_connect_tab' );
		const query = params.toString();
		const clean =
			window.location.pathname + ( query ? `?${ query }` : '' ) + window.location.hash;
		window.history.replaceState( null, '', clean );
	}, [ queryClient ] );

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
			oauthReturnNotice,
			clearOauthReturnNotice,
		} ),
		[
			step,
			maxStep,
			provider,
			selectProvider,
			formValues,
			draftMeta,
			refreshDraft,
			goToStep,
			wizard,
			bootstrap,
			oauthReturnNotice,
			clearOauthReturnNotice,
		]
	);

	return <SetupWizardContext.Provider value={ value }>{ children }</SetupWizardContext.Provider>;
};

export const useSetupWizardContext = () => {
	const context = useContext( SetupWizardContext );
	if ( ! context ) {
		throw new Error( 'useSetupWizardContext must be used within SetupWizardProvider' );
	}
	return context;
};