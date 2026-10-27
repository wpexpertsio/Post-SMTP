import { useQuery } from '@tanstack/react-query';
import { fetchEmailLogDetail } from '../api/emailLogs';

export default function useEmailLogDetail( logId ) {
	const id = Number( logId );
	return useQuery( {
		queryKey: [ 'email-log', id ],
		queryFn: () => fetchEmailLogDetail( id ),
		enabled: id > 0,
	} );
}
