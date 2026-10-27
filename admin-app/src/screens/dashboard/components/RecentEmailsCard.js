import { Link } from 'react-router-dom';
import Card, { CardHeader } from '../../../components/ui/Card';
import QueryState from '../../../components/ui/QueryState';
import { relativeTime } from '../../../utils/format';

const statusLabel = ( status ) => {
	if ( status === 'success' ) {
		return 'Success';
	}
	if ( status === 'in_queue' ) {
		return 'Queued';
	}
	return 'Failed';
};

const RecentEmailsCard = ( { logs, logsQuery } ) => {
	const recent = logs.slice( 0, 5 );

	return (
		<Card>
			<CardHeader
				title="Recent emails"
				subtitle="Last 5 emails"
				action={ <Link to="/email-logs" className="ps-text-link">View all</Link> }
			/>
			<QueryState
				isLoading={ logsQuery.isLoading }
				isError={ logsQuery.isError }
				error={ logsQuery.error }
			>
				<ul className="ps-email-list">
					{ recent.length === 0 && (
						<li className="ps-email-list__empty">No emails logged yet.</li>
					) }
					{ recent.map( ( log ) => (
						<li key={ log.id } className="ps-email-row">
							<div>
								<strong>{ log.subject || '(No subject)' }</strong>
								<span>{ log.sent_to }</span>
							</div>
							<div className="ps-email-row__meta">
								<span className={ `ps-status is-${ log.status }` }>
									{ statusLabel( log.status ) }
								</span>
								<time dateTime={ log.delivery_time }>{ relativeTime( log.delivery_time ) }</time>
							</div>
						</li>
					) ) }
				</ul>
			</QueryState>
		</Card>
	);
};

export default RecentEmailsCard;
