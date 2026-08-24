import { useEffect, useState } from '@wordpress/element';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../api/client';
import FormGenerator from '../../components/form-generator/FormGenerator';
import RevertLegacyPanel from '../../components/RevertLegacyPanel';

const ConnectionsScreen = () => {
	const [ selectedProvider, setSelectedProvider ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );

	const providersQuery = useQuery( {
		queryKey: [ 'providers' ],
		queryFn: () => apiFetch( 'providers' ),
	} );

	const connectionsQuery = useQuery( {
		queryKey: [ 'connections' ],
		queryFn: () => apiFetch( 'connections' ),
	} );

	useEffect( () => {
		if ( selectedProvider && providersQuery.data?.[ selectedProvider ] ) {
			setFormValues( { provider: selectedProvider } );
		}
	}, [ selectedProvider, providersQuery.data ] );

	const saveConnection = async () => {
		const existing = connectionsQuery.data?.connections || [];
		const row = { ...formValues, provider: selectedProvider };
		await apiFetch( 'connections', {
			method: 'POST',
			body: { connections: [ ...existing, row ] },
		} );
		connectionsQuery.refetch();
	};

	const providers = providersQuery.data || {};
	const connections = connectionsQuery.data?.connections || [];

	return (
		<div>
			<div className="post-smtp-card">
				<h2>Your connections</h2>
				{ connectionsQuery.data?.is_legacy && (
					<p className="post-smtp-error">Legacy storage active — complete migration to save connections here.</p>
				) }
				<ul>
					{ connections.map( ( row, index ) => (
						<li key={ index }>
							{ row.title || row.provider } — { row.sender_email || row.from_email || 'No sender' }
						</li>
					) ) }
				</ul>
			</div>

			<div className="post-smtp-card">
				<h2>Add provider</h2>
				<div className="post-smtp-provider-list">
					{ Object.entries( providers ).map( ( [ slug, schema ] ) => (
						<button
							type="button"
							key={ slug }
							className="post-smtp-provider-item ps-btn ps-btn--secondary"
							onClick={ () => setSelectedProvider( slug ) }
						>
							{ schema.display_name || slug }
						</button>
					) ) }
				</div>
				{ selectedProvider && (
					<>
						<FormGenerator
							schema={ providers[ selectedProvider ] }
							values={ formValues }
							onChange={ setFormValues }
						/>
						<div className="post-smtp-actions">
							<button type="button" className="ps-btn ps-btn--primary" onClick={ saveConnection }>
								Save connection
							</button>
						</div>
					</>
				) }
			</div>
			<RevertLegacyPanel />
		</div>
	);
};

export default ConnectionsScreen;
