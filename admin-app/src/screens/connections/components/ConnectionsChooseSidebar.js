import { useEffect, useRef } from '@wordpress/element';
import ConnectionsGmailPromoCard from './ConnectionsGmailPromoCard';
import ConnectionsMailerHelpSidebar from './ConnectionsMailerHelpSidebar';
import ActiveMailersSidebar from '../../dashboard/components/v2/ActiveMailersSidebar';
import useConnections from '../hooks/useConnections';

const ConnectionsChooseSidebar = ( { selectedProvider = null } ) => {
	const asideRef = useRef( null );
	const connectionsQuery = useConnections();
	const connections = connectionsQuery.data?.connections || [];

	useEffect( () => {
		const aside = asideRef.current;
		if ( ! aside ) {
			return;
		}
		aside.scrollTop = 0;
	}, [ selectedProvider ] );

	return (
		<div
			ref={ asideRef }
			className="ps-v2-conn-choose__aside"
			data-figma-node="672:5901"
		>
			{ selectedProvider !== 'gmail_api' && <ConnectionsGmailPromoCard /> }
			<ActiveMailersSidebar
				connections={ connections }
				connectionsQuery={ connectionsQuery }
				hideCta
				compactBadges
			/>
			{ selectedProvider && (
				<ConnectionsMailerHelpSidebar providerSlug={ selectedProvider } />
			) }
		</div>
	);
};

export default ConnectionsChooseSidebar;
