import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'mobile-app' ];

export default function useMobileApp() {
	const queryClient = useQueryClient();

	const bootstrapQuery = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'mobile-app' ),
	} );

	const invalidate = () => queryClient.invalidateQueries( { queryKey: QUERY_KEY } );

	const regenerateMutation = useMutation( {
		mutationFn: () => apiFetch( 'mobile-app/regenerate', { method: 'POST', body: {} } ),
		onSuccess: ( result ) => {
			if ( result?.bootstrap ) {
				queryClient.setQueryData( QUERY_KEY, result.bootstrap );
			} else {
				invalidate();
			}
		},
	} );

	const disconnectMutation = useMutation( {
		mutationFn: ( fcmToken ) =>
			apiFetch( 'mobile-app/disconnect', {
				method: 'POST',
				body: { fcm_token: fcmToken },
			} ),
		onSuccess: ( result ) => {
			if ( result?.bootstrap ) {
				queryClient.setQueryData( QUERY_KEY, result.bootstrap );
			} else {
				invalidate();
			}
		},
	} );

	const notificationsMutation = useMutation( {
		mutationFn: ( enabledTokens ) =>
			apiFetch( 'mobile-app/notifications', {
				method: 'POST',
				body: { enabled_tokens: enabledTokens },
			} ),
		onSuccess: ( result ) => {
			if ( result?.bootstrap ) {
				queryClient.setQueryData( QUERY_KEY, result.bootstrap );
			} else {
				invalidate();
			}
		},
	} );

	return {
		bootstrapQuery,
		regenerateMutation,
		disconnectMutation,
		notificationsMutation,
	};
}
