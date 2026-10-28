import { useMutation, useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

const QUERY_KEY = [ 'tools', 'import-export' ];

export default function useImportExport() {
	const bootstrapQuery = useQuery( {
		queryKey: QUERY_KEY,
		queryFn: () => apiFetch( 'tools/import-export' ),
	} );

	const exportMutation = useMutation( {
		mutationFn: () =>
			apiFetch( 'tools/import-export/export', {
				method: 'POST',
				body: {},
			} ),
	} );

	const importMutation = useMutation( {
		mutationFn: ( content ) =>
			apiFetch( 'tools/import-export/import', {
				method: 'POST',
				body: { content },
			} ),
	} );

	return {
		bootstrapQuery,
		exportMutation,
		importMutation,
	};
}
