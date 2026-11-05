import { useEffect, useState } from '@wordpress/element';
import { getDashboardSlot } from '../../../dashboard/registerDashboardSlot';

const SLOT = 'reportsQuota';

/**
 * Mount point for Pro Email Quota card (Figma 658:5722).
 */
const ReportsQuotaSidebarSlot = () => {
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

	if ( ! Panel ) {
		return null;
	}

	return <Panel />;
};

export default ReportsQuotaSidebarSlot;
