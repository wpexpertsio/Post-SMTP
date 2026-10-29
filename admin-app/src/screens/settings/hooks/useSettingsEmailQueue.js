import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'settings', 'email-queue' ];

export default function useSettingsEmailQueue() {
	const queryClient = useQueryClient();

	const query = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'settings/email-queue' ),
	} );

	const saveMutation = useMutation( {
		mutationFn: ( body ) =>
			apiFetch( 'settings/email-queue', {
				method: 'POST',
				body,
			} ),
		onSuccess: ( data ) => {
			queryClient.setQueryData( QUERY_KEY, ( old ) => ( {
				...( old || {} ),
				values: data?.values ?? old?.values,
			} ) );
		},
	} );

	return { query, saveMutation };
}
