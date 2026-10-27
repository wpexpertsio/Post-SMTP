import { useQuery } from '@tanstack/react-query';
import { fetchEmailLogSettings } from '../api/emailLogs';

export default function useEmailLogSettings() {
	return useQuery( {
		queryKey: [ 'email-log-settings' ],
		queryFn: fetchEmailLogSettings,
		staleTime: 60_000,
	} );
}
