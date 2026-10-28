import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export const notificationsQueryKey = [ 'notifications' ];

export function useNotifications() {
	return useQuery( {
		queryKey: notificationsQueryKey,
		queryFn: () => apiFetch( 'notifications' ),
	} );
}

export function useNotificationMutations() {
	const queryClient = useQueryClient();

	const saveChannel = useMutation( {
		mutationFn: ( { slug, payload } ) =>
			apiFetch( `notifications/channels/${ slug }`, {
				method: 'PATCH',
				body: payload,
			} ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: notificationsQueryKey } );
		},
	} );

	const testChannel = useMutation( {
		mutationFn: ( { slug } ) =>
			apiFetch( `notifications/channels/${ slug }/test`, {
				method: 'POST',
			} ),
	} );

	const saveAdminEmail = useMutation( {
		mutationFn: ( payload ) =>
			saveChannel.mutateAsync( { slug: 'admin_email', payload } ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: notificationsQueryKey } );
		},
	} );

	const testAdminEmail = useMutation( {
		mutationFn: () => testChannel.mutateAsync( { slug: 'admin_email' } ),
	} );

	return { saveChannel, testChannel, saveAdminEmail, testAdminEmail };
}

export default useNotifications;
