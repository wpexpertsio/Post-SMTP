import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';
import { getAdminConfig } from '../../../config/admin';

export default function useSetupWizard() {
	const queryClient = useQueryClient();

	const bootstrapQuery = useQuery( {
		queryKey: [ 'setup-wizard' ],
		queryFn: () => apiFetch( 'setup-wizard' ),
	} );

	const validateMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'setup-wizard/validate', { method: 'POST', body: payload } ),
	} );

	const saveDraftMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'setup-wizard/draft', { method: 'PUT', body: payload } ),
		onSuccess: ( data ) => {
			queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
			if ( data?.connections && Array.isArray( data.connections ) ) {
				queryClient.setQueryData( [ 'connections' ], ( old ) => {
					if ( ! old || typeof old !== 'object' ) {
						return { connections: data.connections };
					}
					return { ...old, connections: data.connections };
				} );
				const hasPrimary = data.connections.some(
					( c ) =>
						c &&
						c.id === 0 &&
						String( c.provider || c.transport_type || '' ).trim() !== ''
				);
				if ( typeof window !== 'undefined' ) {
					window.postSmtpAdmin = {
						...getAdminConfig(),
						hasPrimaryConnection: hasPrimary,
						showSetupWizardPrompt: ! hasPrimary,
					};
				}
			}
		},
	} );

	const discardDraftMutation = useMutation( {
		mutationFn: () => apiFetch( 'setup-wizard/draft', { method: 'DELETE' } ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );
		},
	} );

	const testEmailMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'setup-wizard/test-email', { method: 'POST', body: payload } ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );
		},
	} );

	const domainAuthMutation = useMutation( {
		mutationFn: () => apiFetch( 'setup-wizard/domain-auth', { method: 'POST', body: {} } ),
	} );

	const completeMutation = useMutation( {
		mutationFn: () => apiFetch( 'setup-wizard/complete', { method: 'POST', body: {} } ),
		onSuccess: ( data ) => {
			queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
			if ( data?.setup_wizard_complete && typeof window !== 'undefined' ) {
				const hasPrimary = Array.isArray( data.connections )
					? data.connections.some(
							( c ) =>
								c &&
								c.id === 0 &&
								String( c.provider || c.transport_type || '' ).trim() !== ''
						)
					: Boolean( getAdminConfig().hasPrimaryConnection );
				window.postSmtpAdmin = {
					...getAdminConfig(),
					setupWizardComplete: true,
					hasPrimaryConnection: hasPrimary,
					showSetupWizardPrompt: ! hasPrimary,
				};
			}
		},
	} );

	return {
		bootstrapQuery,
		validateMutation,
		saveDraftMutation,
		discardDraftMutation,
		testEmailMutation,
		domainAuthMutation,
		completeMutation,
	};
}