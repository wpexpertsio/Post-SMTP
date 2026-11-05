import { useQuery } from '@tanstack/react-query';
import { fetchEmailLogDetail } from '../api/emailLogs';

const QUEUED_POLL_MS = 5000;

export default function useEmailLogDetail( logId ) {
	const id = Number( logId );
	return useQuery( {
		queryKey: [ 'email-log', id ],
		queryFn: () => fetchEmailLogDetail( id ),
		enabled: id > 0,
		refetchInterval: ( query ) =>
			query.state.data?.status === 'queued' ? QUEUED_POLL_MS : false,
	} );
}
