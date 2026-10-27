import { useQuery } from '@tanstack/react-query';
import apiFetch from '../api/client';
import { fetchEmailStats, fetchRecentLogs } from '../api/dashboard';

export function useDashboardData( period ) {
	const statsQuery = useQuery( {
		queryKey: [ 'dashboard-stats', period ],
		queryFn: () => fetchEmailStats( period ),
	} );

	const logsQuery = useQuery( {
		queryKey: [ 'dashboard-logs' ],
		queryFn: fetchRecentLogs,
	} );

	const connectionsQuery = useQuery( {
		queryKey: [ 'connections' ],
		queryFn: () => apiFetch( 'connections' ),
	} );

	const stats = statsQuery.data?.count || { total: 0, success: 0, failed: 0 };
	const logs = logsQuery.data?.logs || [];
	const connections = connectionsQuery.data?.connections || [];

	return {
		stats,
		logs,
		connections,
		isLegacyStorage: Boolean( connectionsQuery.data?.is_legacy ),
		queries: {
			stats: statsQuery,
			logs: logsQuery,
			connections: connectionsQuery,
		},
	};
}
