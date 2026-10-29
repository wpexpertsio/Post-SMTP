import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'settings', 'general' ];

export default function useSettingsGeneral() {
	const queryClient = useQueryClient();

	const query = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'settings/general' ),
	} );

	const saveMutation = useMutation( {
		mutationFn: ( body ) =>
			apiFetch( 'settings/general', {
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
