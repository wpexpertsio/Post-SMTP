import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'settings', 'security' ];

export default function useSettingsSecurity() {
	const queryClient = useQueryClient();

	const query = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'settings/security' ),
	} );

	const saveMutation = useMutation( {
		mutationFn: ( body ) =>
			apiFetch( 'settings/security', {
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
