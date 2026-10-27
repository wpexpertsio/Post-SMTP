import { useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export default function useConnectionMutations() {
	const queryClient = useQueryClient();

	const invalidate = () => queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );

	const reorder = useMutation( {
		mutationFn: ( order ) =>
			apiFetch( 'connections/order', {
				method: 'PATCH',
				body: { order },
			} ),
		onSuccess: () => invalidate(),
	} );

	const toggleFallback = useMutation( {
		mutationFn: ( { index, enabled } ) =>
			apiFetch( `connections/${ index }/fallback`, {
				method: 'PATCH',
				body: { enabled },
			} ),
		onSuccess: () => invalidate(),
	} );

	const create = useMutation( {
		mutationFn: ( row ) =>
			apiFetch( 'connections', {
				method: 'POST',
				body: row,
			} ),
		onSuccess: () => invalidate(),
	} );

	const update = useMutation( {
		mutationFn: ( { index, row } ) =>
			apiFetch( `connections/${ index }`, {
				method: 'PUT',
				body: row,
			} ),
		onSuccess: () => invalidate(),
	} );

	const remove = useMutation( {
		mutationFn: ( index ) =>
			apiFetch( `connections/${ index }`, {
				method: 'DELETE',
			} ),
		onSuccess: () => invalidate(),
	} );

	return { reorder, toggleFallback, create, update, remove };
}
