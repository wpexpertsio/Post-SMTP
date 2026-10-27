import { useState } from '@wordpress/element';
import { Link } from 'react-router-dom';
import QueryState from '../../../components/ui/QueryState';
import useConnections from '../hooks/useConnections';
import useConnectionMutations from '../hooks/useConnectionMutations';
import ConnectionEmptyState from '../components/ConnectionEmptyState';
import ConnectionsChooseSidebar from '../components/ConnectionsChooseSidebar';
import SortableConnectionList from '../components/dnd/SortableConnectionList';
import FormNotice from '../components/FormNotice';

const ConnectionsManageView = () => {
	const [ notice, setNotice ] = useState( null );
	const connectionsQuery = useConnections();
	const { reorder, toggleFallback, remove } = useConnectionMutations();

	const connections = connectionsQuery.data?.connections || [];
	const isLegacy = connectionsQuery.data?.is_legacy;

	const handleReorder = async ( order ) => {
		setNotice( null );
		try {
			await reorder.mutateAsync( order );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save order.' } );
		}
	};

	const handleToggle = async ( index, enabled ) => {
		setNotice( null );
		try {
			await toggleFallback.mutateAsync( { index, enabled } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not update fallback.' } );
		}
	};

	const handleDelete = async ( index ) => {
		if ( ! window.confirm( 'Delete this connection?' ) ) {
			return;
		}
		setNotice( null );
		try {
			await remove.mutateAsync( index );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not delete connection.' } );
		}
	};

	return (
		<div className="ps-v2-conn-page" data-figma-node="672:5182">
			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			<div className="ps-v2-conn-layout">
				<section className="ps-v2-conn-panel ps-v2-conn-panel--main">
					<header className="ps-v2-conn-panel__head">
						<div className="ps-v2-conn-panel__intro">
							<h2 className="ps-v2-conn-panel__title">Your Connections</h2>
							<p className="ps-v2-conn-panel__helper">
								Primary sends mail. Fallback is backup. Inactive stays ready to switch.
							</p>
						</div>
						<Link to="/connections/add" className="ps-v2-conn-add-btn">
							Add connection
						</Link>
					</header>

					<QueryState
						isLoading={ connectionsQuery.isLoading }
						isError={ connectionsQuery.isError }
						error={ connectionsQuery.error }
					>
						{ isLegacy && (
							<FormNotice
								type="error"
								message="Legacy storage active — complete migration to manage connections here."
							/>
						) }
						{ connections.length === 0 ? (
							<ConnectionEmptyState />
						) : (
							<>
								<SortableConnectionList
									connections={ connections }
									onReorder={ handleReorder }
									onToggleFallback={ handleToggle }
									togglePending={ toggleFallback.isPending }
									onDelete={ handleDelete }
								/>
								<p className="ps-v2-conn-legend">
									<strong>Primary</strong> sends every email · <strong>Fallback</strong> takes over if
									it fails · <strong>Inactive</strong> is saved for later.
								</p>
							</>
						) }
					</QueryState>
				</section>

				<ConnectionsChooseSidebar />
			</div>
		</div>
	);
};

export default ConnectionsManageView;
