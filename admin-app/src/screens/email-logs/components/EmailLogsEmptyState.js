import { Link } from 'react-router-dom';
import { getAdminConfig } from '../../../config/admin';

const EmailLogsEmptyState = ( { filtered } ) => {
	const config = getAdminConfig();
	const mailerLocked = Boolean( config.mailerConfigLocked );
	const synced = Boolean( config.syncedWithMain );

	if ( filtered ) {
		return (
			<div className="ps-el-empty">
				<h3>No emails match your filters</h3>
				<p>Try clearing filters or set the date range to <strong>All time</strong>.</p>
			</div>
		);
	}

	return (
		<div className="ps-el-empty">
			<h3>No emails yet</h3>
			<p>
				{ synced || mailerLocked
					? 'This log lists mail sent from this site only. Trigger a test or form email on this subsite to see entries here.'
					: 'Sent messages will appear here once logging is enabled and mail starts flowing.' }
			</p>
			{ ! mailerLocked && ! config.isConfigured && (
				<Link to="/connections" className="ps-el-btn ps-el-btn--primary">
					Finish setup
				</Link>
			) }
			{ ! mailerLocked && (
				<Link to="/setup-wizard" className="ps-el-btn ps-el-btn--ghost">
					Open wizard
				</Link>
			) }
		</div>
	);
};

export default EmailLogsEmptyState;
