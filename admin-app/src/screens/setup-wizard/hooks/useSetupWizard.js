import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

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
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: [ 'setup-wizard' ] } );
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