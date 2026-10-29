import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'settings', 'email-logs' ];

export default function useSettingsEmailLogs() {
	const queryClient = useQueryClient();

	const query = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'settings/email-logs' ),
	} );

	const saveMutation = useMutation( {
		mutationFn: ( body ) =>
			apiFetch( 'settings/email-logs', {
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
