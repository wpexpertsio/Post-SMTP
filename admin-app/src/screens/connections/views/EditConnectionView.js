import { useState, useEffect } from '@wordpress/element';
import { useNavigate, Link, useParams } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../../api/client';
import useConnections from '../hooks/useConnections';
import useConnectionMutations from '../hooks/useConnectionMutations';
import ConnectionsSubnav from '../components/ConnectionsSubnav';
import ConnectionsChooseSidebar from '../components/ConnectionsChooseSidebar';
import ConfigureMailerForm from '../components/ConfigureMailerForm';
import { buildGmailOAuthSaveRow } from '../components/GmailConfigurePanel';
import FormNotice from '../components/FormNotice';
import QueryState from '../../../components/ui/QueryState';
import { normalizeConnectionSaveRow } from '../model/normalizeConnectionSaveRow';

const EditConnectionView = () => {
	const { id } = useParams();
	const index = parseInt( id, 10 );
	const navigate = useNavigate();
	const [ formValues, setFormValues ] = useState( {} );
	const [ notice, setNotice ] = useState( null );

	const queryClient = useQueryClient();
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
		const params = new URLSearchParams( window.location.search );
		const msg = params.get( 'msg' );
		const success = params.get( 'success' );
		if ( msg ) {
			setNotice( {
				type: success === '1' ? 'success' : 'error',
				message: msg,
			} );
		}
		if ( success === '1' || msg ) {
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
		}
		const oauthTab = params.get( 'office365_connect_tab' );
		if ( oauthTab === 'manual' || oauthTab === 'one_click' ) {
			setFormValues( ( prev ) => ( {
				...prev,
				office365_connect_tab: oauthTab,
			} ) );
		}
		const gmailTab = params.get( 'gmail_connect_tab' );
		if ( gmailTab === 'manual' || gmailTab === 'one_click' ) {
			setFormValues( ( prev ) => ( {
				...prev,
				gmail_connect_tab: gmailTab,
			} ) );
		}
		if ( msg || oauthTab || gmailTab ) {
			params.delete( 'msg' );
			params.delete( 'success' );
			params.delete( 'office365_connect_tab' );
			params.delete( 'gmail_connect_tab' );
			const query = params.toString();
			const clean =
				window.location.pathname +
				( query ? `?${ query }` : '' ) +
				window.location.hash;
			window.history.replaceState( null, '', clean );
		}
	}, [ queryClient ] );

	useEffect( () => {
		if ( ! connection ) {
			return;
		}
		const resolveConnectTab = ( savedOnRow, prevTab ) => {
			if ( savedOnRow === 'one_click' || savedOnRow === 'manual' ) {
				return savedOnRow;
			}
			if ( prevTab === 'one_click' || prevTab === 'manual' ) {
				return prevTab;
			}
			return savedOnRow;
		};

		setFormValues( ( prev ) => ( {
			...connection,
			office365_connect_tab: resolveConnectTab(
				connection.office365_connect_tab,
				prev?.office365_connect_tab
			),
			gmail_connect_tab: resolveConnectTab(
				connection.gmail_connect_tab,
				prev?.gmail_connect_tab
			),
		} ) );
	}, [ connection ] );

	const handleSave = async () => {
		setNotice( null );
		try {
			let row =
				connection.provider === 'gmail_api'
					? buildGmailOAuthSaveRow( formValues, connection.provider )
					: formValues;
			row = normalizeConnectionSaveRow( row );
			const result = await update.mutateAsync( { index, row } );
			setNotice( { type: 'success', message: result?.message || 'Connection saved.' } );
			const savedRow = result?.connections?.find( ( c ) => c.id === index );
			if ( savedRow ) {
				setFormValues( ( prev ) => ( {
					...prev,
					...savedRow,
					gmail_connect_tab:
						savedRow.gmail_connect_tab === 'one_click' ||
						savedRow.gmail_connect_tab === 'manual'
							? savedRow.gmail_connect_tab
							: prev?.gmail_connect_tab,
					office365_connect_tab:
						savedRow.office365_connect_tab === 'one_click' ||
						savedRow.office365_connect_tab === 'manual'
							? savedRow.office365_connect_tab
							: prev?.office365_connect_tab,
				} ) );
			}
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
									connectionId={ index }
									onGrantError={ ( message ) =>
										setNotice( { type: 'error', message } )
									}
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
