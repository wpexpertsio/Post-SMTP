import { Link } from 'react-router-dom';
import { getAdminUrls } from '../../../config/admin';

const EmailLogsEmptyState = ( { filtered } ) => {
	const urls = getAdminUrls();
	const config = window.postSmtpAdmin || {};

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
			<p>Sent messages will appear here once logging is enabled and mail starts flowing.</p>
			{ ! config.isConfigured && (
				<Link to="/connections" className="ps-el-btn ps-el-btn--primary">
					Finish setup
				</Link>
			) }
			<a href={ urls.wizard } className="ps-el-btn ps-el-btn--ghost">
				Open wizard
			</a>
		</div>
	);
};

export default EmailLogsEmptyState;
