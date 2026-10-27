import { Link } from 'react-router-dom';
import QueryState from '../../../../components/ui/QueryState';
import { getAdminUrls } from '../../../../config/admin';
import { STATS_PERIODS } from '../../constants';

const METRICS = [
	{
		id: 'sent',
		dotClass: 'ps-v2-metric__dot--blue',
		label: 'Email Sent',
		valueKey: 'total',
		hint: 'Total attempts',
	},
	{
		id: 'delivered',
		dotClass: 'ps-v2-metric__dot--green',
		label: 'Email Delivered',
		valueKey: 'success',
		hint: 'Accepted by the mailer',
	},
	{
		id: 'failed',
		dotClass: 'ps-v2-metric__dot--red',
		label: 'Email Failed',
		valueKey: 'failed',
		hint: 'Resend from the Email Log',
	},
];

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-4936
 */
const OverviewSection = ( { period, onPeriodChange, stats, statsQuery, isPro } ) => {
	const urls = getAdminUrls();

	return (
		<section className="ps-v2-panel ps-v2-panel--overview" data-figma-node="672:4936">
			<div className="ps-v2-panel__head">
				<h2>Overview</h2>
				<select
					className="ps-v2-select ps-v2-select--overview"
					value={ period }
					onChange={ ( e ) => onPeriodChange( e.target.value ) }
					aria-label="Overview period"
				>
					{ STATS_PERIODS.map( ( item ) => (
						<option key={ item.id } value={ item.id }>{ item.label }</option>
					) ) }
				</select>
			</div>
			<QueryState isLoading={ statsQuery.isLoading } isError={ statsQuery.isError } error={ statsQuery.error }>
				<div className="ps-v2-overview-grid">
					{ METRICS.map( ( metric ) => {
						const inner = (
							<div className="ps-v2-metric__inner">
								<div className="ps-v2-metric__head">
									<span className={ `ps-v2-metric__dot ${ metric.dotClass }` } aria-hidden="true" />
									<span className="ps-v2-metric__label">{ metric.label }</span>
								</div>
								<strong className="ps-v2-metric__value">
									{ Number( stats[ metric.valueKey ] || 0 ).toLocaleString() }
								</strong>
								<small className="ps-v2-metric__hint">{ metric.hint }</small>
							</div>
						);
						if ( metric.id === 'failed' ) {
							return (
								<Link
									key={ metric.id }
									to="/email-logs?status=failed"
									className="ps-v2-metric ps-v2-metric--link"
								>
									{ inner }
								</Link>
							);
						}
						return (
							<article key={ metric.id } className="ps-v2-metric">
								{ inner }
							</article>
						);
					} ) }
					<article className="ps-v2-metric ps-v2-metric--opens">
						<div className="ps-v2-metric__inner">
							<div className="ps-v2-metric__head">
								<span className="ps-v2-metric__dot ps-v2-metric__dot--amber" aria-hidden="true" />
								<span className="ps-v2-metric__label">Open Emails</span>
							</div>
							{ isPro ? (
								<>
									<strong className="ps-v2-metric__value">—</strong>
									<small className="ps-v2-metric__hint">Pro tracking</small>
								</>
							) : (
								<>
									<strong className="ps-v2-metric__value ps-v2-metric__value--blur" aria-hidden="true">
										465
									</strong>
									<a
										href={ urls.pro }
										className="ps-v2-metric__unlock"
										target="_blank"
										rel="noreferrer"
									>
										Upgrade to Unlock
									</a>
								</>
							) }
						</div>
					</article>
				</div>
			</QueryState>
		</section>
	);
};

export default OverviewSection;
