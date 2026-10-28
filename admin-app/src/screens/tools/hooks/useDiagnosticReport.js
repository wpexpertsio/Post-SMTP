import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'tools', 'diagnostic-report' ];

export default function useDiagnosticReport() {
	const queryClient = useQueryClient();

	const bootstrapQuery = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'tools/diagnostic-report' ),
	} );

	const runMutation = useMutation( {
		mutationFn: () =>
			apiFetch( 'tools/diagnostic-report', {
				method: 'POST',
				data: {},
			} ),
		onSuccess: () => {
			queryClient.invalidateQueries( { queryKey: QUERY_KEY } );
		},
	} );

	const sendMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'tools/diagnostic-report/send', {
				method: 'POST',
				data: payload,
			} ),
	} );

	return {
		bootstrapQuery,
		runMutation,
		sendMutation,
	};
}
