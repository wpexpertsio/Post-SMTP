import { useQuery } from '@tanstack/react-query';
import { fetchEmailLogs } from '../api/emailLogs';

export default function useEmailLogs( filters ) {
	return useQuery( {
		queryKey: [ 'email-logs', filters ],
		queryFn: () => fetchEmailLogs( filters ),
		placeholderData: ( previous ) => previous,
	} );
}
