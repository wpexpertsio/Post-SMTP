import { useEffect, useState } from '@wordpress/element';
import { getAdminConfig } from '../../../config/admin';
import { getDashboardSlot } from '../../../dashboard/registerDashboardSlot';

const SLOT = 'reportsPage';

/**
 * Mount point for Pro Reports & Analytics chart on the Reports route (Figma 601:13732).
 */
const ReportsPageTrackingSlot = () => {
	const config = getAdminConfig();
	const enabled = Boolean( config.dashboardReportsTracking?.enabled );
	const [ Panel, setPanel ] = useState( () => getDashboardSlot( SLOT ) );

	useEffect( () => {
		if ( Panel ) {
			return undefined;
		}
		const sync = () => {
			const next = getDashboardSlot( SLOT );
			if ( next ) {
				setPanel( () => next );
			}
		};
		sync();
		window.addEventListener( 'post-smtp-dashboard-slot-registered', sync );
		return () => window.removeEventListener( 'post-smtp-dashboard-slot-registered', sync );
	}, [ Panel ] );

	if ( ! enabled ) {
		return null;
	}

	if ( ! Panel ) {
		return (
			<div
				className="ps-v2-reports-pro-panel ps-v2-reports-pro-panel--loading"
				aria-busy="true"
			/>
		);
	}

	return (
		<div className="ps-v2-reports-pro-panel">
			<Panel />
		</div>
	);
};

export default ReportsPageTrackingSlot;
