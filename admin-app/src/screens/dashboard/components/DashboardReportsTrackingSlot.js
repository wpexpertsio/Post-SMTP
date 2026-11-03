import { useEffect, useState } from '@wordpress/element';
import { getAdminConfig } from '../../../config/admin';
import { getDashboardSlot } from '../../../dashboard/registerDashboardSlot';

const SLOT = 'reportsTracking';

/**
 * Mount point for Pro “Reports & Tracking” dashboard panel (Figma 346:6649).
 */
const DashboardReportsTrackingSlot = () => {
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
			<div className="ps-dashboard-reports-tracking-slot ps-dashboard-reports-tracking-slot--loading" aria-busy="true" />
		);
	}

	return (
		<div className="ps-dashboard-reports-tracking-slot">
			<Panel />
		</div>
	);
};

export default DashboardReportsTrackingSlot;
