import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'settings', 'advanced' ];

export default function useSettingsAdvanced() {
	const queryClient = useQueryClient();

	const query = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'settings/advanced' ),
	} );

	const saveMutation = useMutation( {
		mutationFn: ( body ) =>
			apiFetch( 'settings/advanced', {
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
