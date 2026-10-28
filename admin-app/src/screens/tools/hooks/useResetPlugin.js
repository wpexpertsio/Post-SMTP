import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'tools', 'reset-plugin' ];

export default function useResetPlugin() {
	const queryClient = useQueryClient();

	const bootstrapQuery = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'tools/reset-plugin' ),
	} );

	const resetMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'tools/reset-plugin/reset', {
				method: 'POST',
				body: payload,
			} ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
			queryClient.invalidateQueries( { queryKey: [ 'tools' ] } );
		},
	} );

	return {
		bootstrapQuery,
		resetMutation,
	};
}
