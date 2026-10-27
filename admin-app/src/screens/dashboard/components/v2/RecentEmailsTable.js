import { Link } from 'react-router-dom';
import QueryState from '../../../../components/ui/QueryState';
import { formatEmailLogDate } from '../../../../utils/format';
import { statusCheck, statusFail, statusQueued } from './recentEmailsAssets';

const STATUS = {
	success: {
		className: 'is-success',
		label: 'Success',
		icon: statusCheck,
	},
	in_queue: {
		className: 'is-queued',
		label: 'Queued',
		icon: statusQueued,
	},
	failed: {
		className: 'is-failed',
		label: 'Failed',
		icon: statusFail,
	},
};

const resolveStatus = ( status ) => {
	if ( status === 'success' || status === 'in_queue' ) {
		return STATUS[ status ];
	}
	return STATUS.failed;
};

const StatusBadge = ( { status } ) => {
	const config = resolveStatus( status );
	return (
		<span className={ `ps-v2-status ${ config.className }` }>
			<img src={ config.icon } alt="" width="12" height="12" />
			{ config.label }
		</span>
	);
};

const RECENT_LIMIT = 6;

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-5018
 */
const RecentEmailsTable = ( { logs, logsQuery } ) => {
	const recent = logs.slice( 0, RECENT_LIMIT );
	const countLabel = recent.length
		? `Last ${ recent.length } emails`
		: `Last ${ RECENT_LIMIT } emails`;

	return (
		<section className="ps-v2-panel ps-v2-panel--recent" data-figma-node="672:5018">
			<div className="ps-v2-panel__head">
				<div className="ps-v2-panel__title-group">
					<h2>Recent Emails</h2>
					<span className="ps-v2-recent-pill">{ countLabel }</span>
				</div>
				<Link to="/email-logs" className="ps-v2-recent-view-all">View All</Link>
			</div>
			<QueryState isLoading={ logsQuery.isLoading } isError={ logsQuery.isError } error={ logsQuery.error }>
				<div className="ps-v2-recent-table-wrap">
					<table className="ps-v2-recent-table">
						<colgroup>
							<col className="ps-v2-recent-table__col-subject" />
							<col className="ps-v2-recent-table__col-email" />
							<col className="ps-v2-recent-table__col-status" />
							<col className="ps-v2-recent-table__col-date" />
						</colgroup>
						<thead>
							<tr>
								<th>Subject</th>
								<th>Email to</th>
								<th>Status</th>
								<th>Date</th>
							</tr>
						</thead>
						<tbody>
							{ recent.length === 0 && (
								<tr>
									<td colSpan={ 4 } className="ps-v2-recent-table__empty">No emails logged yet.</td>
								</tr>
							) }
							{ recent.map( ( log ) => (
								<tr key={ log.id }>
									<td className="ps-v2-recent-table__subject">{ log.subject || '(No subject)' }</td>
									<td className="ps-v2-recent-table__email">{ log.sent_to }</td>
									<td>
										<StatusBadge status={ log.status } />
									</td>
									<td className="ps-v2-recent-table__date">{ formatEmailLogDate( log.delivery_time ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</QueryState>
		</section>
	);
};

export default RecentEmailsTable;
