import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'tools', 'check-connection' ];

export default function useCheckConnection() {
	const queryClient = useQueryClient();

	const bootstrapQuery = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'tools/check-connection' ),
	} );

	const runMutation = useMutation( {
		mutationFn: ( connectionId ) =>
			apiFetch( `tools/check-connection/${ connectionId }/run`, {
				method: 'POST',
				body: {},
			} ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: QUERY_KEY } );
		},
	} );

	return {
		bootstrapQuery,
		runMutation,
	};
}
