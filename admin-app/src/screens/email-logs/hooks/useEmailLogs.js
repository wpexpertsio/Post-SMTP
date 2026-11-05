import { useQuery } from '@tanstack/react-query';
import { fetchEmailLogs } from '../api/emailLogs';

const QUEUED_POLL_MS = 5000;

export default function useEmailLogs( filters ) {
	return useQuery( {
		queryKey: [ 'email-logs', filters ],
		queryFn: () => fetchEmailLogs( filters ),
		placeholderData: ( previous ) => previous,
		refetchInterval: ( query ) => {
			const items = query.state.data?.items;
			if ( ! Array.isArray( items ) ) {
				return false;
			}
			return items.some( ( row ) => row.status === 'queued' ) ? QUEUED_POLL_MS : false;
		},
	} );
}
