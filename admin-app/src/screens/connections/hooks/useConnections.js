import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export default function useConnections() {
	return useQuery( {
		queryKey: [ 'connections' ],
		queryFn: () => apiFetch( 'connections' ),
	} );
}
