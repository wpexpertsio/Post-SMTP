import { useState, useEffect } from '@wordpress/element';
import { useNavigate, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';
import { getAdminUrls } from '../../../config/admin';
import useConnectionMutations from '../hooks/useConnectionMutations';
import MailerGrid from '../components/MailerGrid';
import ConfigureMailerForm from '../components/ConfigureMailerForm';
import FormNotice from '../components/FormNotice';
import useMailerCatalog from '../hooks/useMailerCatalog';
import ConnectionsChooseSidebar from '../components/ConnectionsChooseSidebar';
import iconChevronBack from '../../../assets/dashboard/connections/icon-chevron-back.svg';

const urls = getAdminUrls();

const AddConnectionView = () => {
	const navigate = useNavigate();
	const [ selectedProvider, setSelectedProvider ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );
	const [ notice, setNotice ] = useState( null );

	const catalogQuery = useMailerCatalog();
	const { create } = useConnectionMutations();

	const providersQuery = useQuery( {
		queryKey: [ 'providers' ],
		queryFn: () => apiFetch( 'providers' ),
		enabled: Boolean( selectedProvider ),
	} );

	const items = catalogQuery.data?.items || [];
	const schema = selectedProvider ? providersQuery.data?.[ selectedProvider ] : null;
	const guideUrl = urls.docs || 'https://postmansmtp.com/documentation/';

	useEffect( () => {
		if ( selectedProvider ) {
			setFormValues( { provider: selectedProvider } );
		}
	}, [ selectedProvider ] );

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await create.mutateAsync( { ...formValues, provider: selectedProvider } );
			setNotice( { type: 'success', message: result?.message || 'Connection saved.' } );
			setTimeout( () => navigate( '/connections' ), 600 );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save connection.' } );
		}
	};

	return (
		<div className="ps-v2-conn-page" data-figma-node="672:5745">
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<div className="ps-v2-conn-layout">
				<div className="ps-v2-conn-choose__main" data-figma-node="1310:16808">
					<section className="ps-v2-conn-panel ps-v2-conn-panel--choose" data-figma-node="672:5748">
						<header className="ps-v2-conn-choose__head">
							<div className="ps-v2-conn-choose__intro">
								<h2 className="ps-v2-conn-panel__title">Choose a mailer</h2>
								<p className="ps-v2-conn-choose__guide">
									Not sure which one?{' '}
									<a href={ guideUrl } target="_blank" rel="noreferrer">
										Read the mailer guide
									</a>
								</p>
							</div>
							<Link to="/connections" className="ps-v2-conn-choose__back">
								<img src={ iconChevronBack } alt="" width="6" height="10" />
								Back to Connections
							</Link>
						</header>

						<MailerGrid
							items={ items }
							selectedSlug={ selectedProvider }
							onSelect={ setSelectedProvider }
						/>
					</section>

					{ selectedProvider && schema && (
						<section className="ps-v2-conn-panel ps-v2-conn-panel--configure">
							<div className="ps-v2-conn-choose__configure ps-v2-conn-choose__configure--figma">
								<ConfigureMailerForm
									schema={ schema }
									values={ formValues }
									onChange={ setFormValues }
									provider={ selectedProvider }
								/>
								<div className="ps-v2-conn-form-actions">
									<Link to="/connections" className="ps-v2-btn ps-v2-btn--ghost">Cancel</Link>
									<button
										type="button"
										className="ps-v2-btn ps-v2-btn--primary"
										disabled={ create.isPending }
										onClick={ handleSave }
									>
										{ create.isPending ? 'Saving…' : 'Save Changes' }
									</button>
								</div>
							</div>
						</section>
					) }
				</div>

				<ConnectionsChooseSidebar selectedProvider={ selectedProvider } />
			</div>
		</div>
	);
};

export default AddConnectionView;
