import { useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export default function useConnectionMutations() {
	const queryClient = useQueryClient();

	const invalidate = () => queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );

	const mergeConnectionsCache = ( data ) => {
		if ( ! data?.connections || ! Array.isArray( data.connections ) ) {
			invalidate();
			return;
		}
		queryClient.setQueryData( [ 'connections' ], ( old ) => {
			if ( ! old || typeof old !== 'object' ) {
				return { ...data, connections: data.connections };
			}
			return { ...old, connections: data.connections };
		} );
	};

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
		onSuccess: ( data ) => mergeConnectionsCache( data ),
	} );

	const update = useMutation( {
		mutationFn: ( { index, row } ) =>
			apiFetch( `connections/${ index }`, {
				method: 'PUT',
				body: row,
			} ),
		onSuccess: ( data ) => mergeConnectionsCache( data ),
	} );

	const remove = useMutation( {
		mutationFn: ( index ) =>
			apiFetch( `connections/${ index }`, {
				method: 'DELETE',
			} ),
		onSuccess: () => invalidate(),
	} );

	const disconnectOffice365OAuth = useMutation( {
		mutationFn: ( index ) =>
			apiFetch( `connections/${ index }/office365-oauth`, {
				method: 'DELETE',
			} ),
		onSuccess: () => invalidate(),
	} );

	const disconnectGmailOAuth = useMutation( {
		mutationFn: ( index ) =>
			apiFetch( `connections/${ index }/gmail-oauth`, {
				method: 'DELETE',
			} ),
		onSuccess: ( data ) => {
			mergeConnectionsCache( data );
		},
	} );

	return { reorder, toggleFallback, create, update, remove, disconnectOffice365OAuth, disconnectGmailOAuth };
}
