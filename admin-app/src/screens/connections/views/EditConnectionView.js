import { useState, useEffect } from '@wordpress/element';
import { useNavigate, Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';
import useConnections from '../hooks/useConnections';
import useConnectionMutations from '../hooks/useConnectionMutations';
import ConnectionsSubnav from '../components/ConnectionsSubnav';
import ConnectionsChooseSidebar from '../components/ConnectionsChooseSidebar';
import ConfigureMailerForm from '../components/ConfigureMailerForm';
import FormNotice from '../components/FormNotice';
import QueryState from '../../../components/ui/QueryState';

const EditConnectionView = () => {
	const { id } = useParams();
	const index = parseInt( id, 10 );
	const navigate = useNavigate();
	const [ formValues, setFormValues ] = useState( {} );
	const [ notice, setNotice ] = useState( null );

	const connectionsQuery = useConnections();
	const { update } = useConnectionMutations();

	const connection = connectionsQuery.data?.connections?.find( ( c ) => c.id === index );

	const providersQuery = useQuery( {
		queryKey: [ 'providers' ],
		queryFn: () => apiFetch( 'providers' ),
		enabled: Boolean( connection?.provider ),
	} );

	const schema = connection?.provider ? providersQuery.data?.[ connection.provider ] : null;

	useEffect( () => {
		if ( connection ) {
			setFormValues( { ...connection } );
		}
	}, [ connection ] );

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await update.mutateAsync( { index, row: formValues } );
			setNotice( { type: 'success', message: result?.message || 'Connection saved.' } );
			setTimeout( () => navigate( '/connections' ), 600 );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save connection.' } );
		}
	};

	return (
		<div className="ps-v2-conn-page" data-figma-node="672:5368">
			<ConnectionsSubnav />
			<Link to="/connections" className="ps-v2-conn-back">← Back to Connections</Link>
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<QueryState
				isLoading={ connectionsQuery.isLoading }
				isError={ connectionsQuery.isError }
				error={ connectionsQuery.error }
			>
				{ ! connection && ! connectionsQuery.isLoading && (
					<FormNotice type="error" message="Connection not found." />
				) }
				{ connection && schema && (
					<div className="ps-v2-conn-layout">
						<section className="ps-v2-conn-panel ps-v2-conn-panel--choose">
							<div className="ps-v2-conn-choose__configure ps-v2-conn-choose__configure--figma">
								<ConfigureMailerForm
									schema={ schema }
									values={ formValues }
									onChange={ setFormValues }
									provider={ connection.provider }
								/>
								<div className="ps-v2-conn-form-actions">
									<Link to="/connections" className="ps-v2-btn ps-v2-btn--ghost">Cancel</Link>
									<button
										type="button"
										className="ps-v2-btn ps-v2-btn--primary"
										disabled={ update.isPending }
										onClick={ handleSave }
									>
										{ update.isPending ? 'Saving…' : 'Save Changes' }
									</button>
								</div>
							</div>
						</section>
						<ConnectionsChooseSidebar selectedProvider={ connection.provider } />
					</div>
				) }
			</QueryState>
		</div>
	);
};

export default EditConnectionView;
