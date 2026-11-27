import { useQuery } from '@tanstack/react-query';
import { getAdminConfig } from '../../../config/admin';
import apiFetch from '../../../api/client';

export default function useMainwpChildSites() {
	const config = getAdminConfig();

	return useQuery( {
		queryKey: [ 'mainwp-child-sites' ],
		queryFn: () => apiFetch( 'mainwp/child-sites' ),
		enabled: Boolean( config.mainwpDashboard ),
		staleTime: 5 * 60 * 1000,
	} );
}