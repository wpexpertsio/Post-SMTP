import { useState } from '@wordpress/element';
import { Link } from 'react-router-dom';
import FormNotice from '../../connections/components/FormNotice';
import useCheckConnection from '../hooks/useCheckConnection';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';
import ConnectionCheckCard from './ConnectionCheckCard';

const CheckConnectionPanel = () => {
	const { bootstrapQuery, runMutation } = useCheckConnection();
	const data = bootstrapQuery.data;

	const [ runningId, setRunningId ] = useState( null );
	const [ cardErrors, setCardErrors ] = useState( {} );
	const [ notice, setNotice ] = useState( null );

	const connections = data?.connections || [];

	const handleRun = async ( connectionId ) => {
		setNotice( null );
		setCardErrors( ( prev ) => ( { ...prev, [ connectionId ]: '' } ) );
		setRunningId( connectionId );

		try {
			const result = await runMutation.mutateAsync( connectionId );
			return result;
		} catch ( err ) {
			const message =
				err?.data?.message ||
				err?.message ||
				'Could not complete the connection check.';
			setCardErrors( ( prev ) => ( { ...prev, [ connectionId ]: message } ) );
			throw err;
		} finally {
			setRunningId( null );
		}
	};

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-tools-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Check Connection." />;
	}

	if ( connections.length === 0 ) {
		return (
			<div className="ps-v2-tools-empty" data-figma-node="547:7070">
				<header className="ps-v2-tools-panel__hero">
					<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
						<img src={ TOOLS_NAV_ICONS[ 'check-connection' ] } alt="" width="24" height="24" />
					</div>
					<div>
						<h2 className="ps-v2-tools-panel__title">Check connection</h2>
						<p className="ps-v2-tools-panel__lead">
							Check that your mailer&apos;s API accepts your site.
						</p>
					</div>
				</header>
				<p className="ps-v2-tools-panel__lead">
					Set up a mailer in Connections before running connection checks.
				</p>
				<Link to="/connections" className="ps-v2-btn ps-v2-btn--primary">
					Open Connections
				</Link>
			</div>
		);
	}

	return (
		<div className="ps-v2-tools-check" data-figma-node="547:7070">
			<header className="ps-v2-tools-panel__hero" data-figma-node="547:7072">
				<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
					<img src={ TOOLS_NAV_ICONS[ 'check-connection' ] } alt="" width="24" height="24" />
				</div>
				<div>
					<h2 className="ps-v2-tools-panel__title">Check connection</h2>
					<p className="ps-v2-tools-panel__lead">
						Check that your mailer&apos;s API accepts your site.
					</p>
				</div>
			</header>

			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			<div className="ps-v2-tools-check-stack" data-figma-node="547:7082">
				{ connections.map( ( connection ) => (
					<ConnectionCheckCard
						key={ connection.id }
						connection={ connection }
						lastCheck={ connection.last_check }
						onRunTest={ handleRun }
						isRunning={ runningId === connection.id }
						errorMessage={ cardErrors[ connection.id ] }
					/>
				) ) }
			</div>

			{ data?.copy?.disclaimer && (
				<p className="ps-v2-tools-check-disclaimer">{ data.copy.disclaimer }</p>
			) }
		</div>
	);
};

export default CheckConnectionPanel;
