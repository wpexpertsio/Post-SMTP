import Card, { CardHeader } from '../../../components/ui/Card';
import Badge from '../../../components/ui/Badge';
import QueryState from '../../../components/ui/QueryState';
import { IconLock } from '../../../components/ui/Icons';
import { deliveryRate } from '../../../utils/format';
import { STATS_PERIODS } from '../constants';

const DeliveryOverview = ( { period, onPeriodChange, stats, statsQuery, showOpensMetric } ) => (
	<Card className="ps-delivery">
		<CardHeader
			title="Delivery at a glance"
			action={
				<select
					className="ps-select"
					value={ period }
					onChange={ ( event ) => onPeriodChange( event.target.value ) }
					aria-label="Stats period"
				>
					{ STATS_PERIODS.map( ( item ) => (
						<option key={ item.id } value={ item.id }>{ item.label }</option>
					) ) }
				</select>
			}
		/>
		<QueryState isLoading={ statsQuery.isLoading } isError={ statsQuery.isError } error={ statsQuery.error }>
			<div className="ps-stat-row">
				<article className="ps-stat">
					<div className="ps-stat__top">
						<span>Emails sent</span>
					</div>
					<strong>{ stats.total }</strong>
					<small>Total attempts</small>
				</article>
				<article className="ps-stat">
					<div className="ps-stat__top">
						<span>Delivered</span>
					</div>
					<strong>{ deliveryRate( stats.success, stats.total ) }</strong>
					<small>{ stats.success } of { stats.total }</small>
				</article>
				<article className="ps-stat">
					<div className="ps-stat__top">
						<span>Failed</span>
					</div>
					<strong>{ stats.failed }</strong>
					<small>{ stats.failed ? 'Needs attention' : 'All clear' }</small>
				</article>
				{ showOpensMetric ? (
					<article className="ps-stat">
						<div className="ps-stat__top">
							<span>Opened emails</span>
						</div>
						<strong>—</strong>
						<small>Pro tracking</small>
					</article>
				) : (
					<article className="ps-stat ps-stat--locked">
						<div className="ps-stat__top">
							<span>Opened emails</span>
							<Badge variant="pro">Pro</Badge>
						</div>
						<div className="ps-stat__lock">
							<IconLock />
							<div>
								<strong>Locked</strong>
								<p>Track email opens — unlock with Pro →</p>
							</div>
						</div>
					</article>
				) }
			</div>
		</QueryState>
	</Card>
);

export default DeliveryOverview;
