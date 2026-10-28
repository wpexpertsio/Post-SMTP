import { useMemo, useState } from '@wordpress/element';
import { mailerConnectionIcon } from '../../connections/model/mailerConnectionIcons';
import { connectionDisplayLabel } from '../connectionDisplayLabel';
import ConnectionCheckResultRow from './ConnectionCheckResultRow';

const ConnectionCheckCard = ( {
	connection,
	lastCheck,
	onRunTest,
	isRunning,
	errorMessage,
} ) => {
	const [ localCheck, setLocalCheck ] = useState( null );
	const displayCheck = localCheck || lastCheck;

	const mailerLabel = connectionDisplayLabel( connection ) || connection.mailer_label;
	const icon = mailerConnectionIcon( connection.provider );
	const account = connection.account_identifier;
	const transport = connection.transport_label || 'API';

	const buttonLabel = useMemo( () => {
		if ( displayCheck?.checks?.length ) {
			return connection.is_primary ? 'Run test again' : 'Run API connection test';
		}
		if ( transport === 'SMTP' ) {
			return 'Run connection test';
		}
		return connection.is_primary ? 'Run connection test' : 'Run API connection test';
	}, [ connection.is_primary, displayCheck, transport ] );

	const handleRun = async () => {
		setLocalCheck( null );
		try {
			const result = await onRunTest( connection.id );
			if ( result?.result ) {
				setLocalCheck( result.result );
			}
		} catch ( err ) {
			// Parent handles error display via errorMessage prop when needed.
		}
	};

	const roleBadge =
		connection.role === 'fallback' ? (
			<span className="ps-v2-tools-role-badge ps-v2-tools-role-badge--fallback">
				{ ( connection.role_label || 'Fallback' ).toUpperCase() }
			</span>
		) : connection.is_primary || connection.role === 'primary' ? (
			<span className="ps-v2-tools-role-badge">PRIMARY</span>
		) : null;

	const subtitle = [ account, transport ].filter( Boolean ).join( ' · ' );

	return (
		<article className="ps-v2-tools-check-card" data-figma-node="641:4934">
			<div className="ps-v2-tools-check-card__head">
				<div className="ps-v2-tools-check-card__identity">
					<div className="ps-v2-tools-check-card__mailer-icon" aria-hidden="true">
						{ icon ? (
							<img src={ icon } alt="" width="22" height="22" />
						) : (
							<span className="ps-v2-tools-check-card__mailer-fallback" />
						) }
					</div>
					<div>
						<div className="ps-v2-tools-check-card__title-row">
							<h3 className="ps-v2-tools-check-card__title">{ mailerLabel }</h3>
							{ roleBadge }
						</div>
						{ subtitle && (
							<p className="ps-v2-tools-check-card__subtitle">{ subtitle }</p>
						) }
					</div>
				</div>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary ps-v2-tools-check-card__action"
					onClick={ handleRun }
					disabled={ isRunning }
				>
					{ isRunning ? 'Checking…' : buttonLabel }
				</button>
			</div>

			{ errorMessage && (
				<p className="ps-v2-field-error ps-v2-tools-check-card__error">{ errorMessage }</p>
			) }

			{ displayCheck?.checked_at_label && (
				<>
					<p className="ps-v2-tools-check-card__history">{ displayCheck.checked_at_label }</p>
					<p className="ps-v2-tools-check-card__history-note">
						Results are from your last check — run again for a current status.
					</p>
				</>
			) }

			{ ! displayCheck?.checks?.length && ! isRunning && (
				<p className="ps-v2-tools-check-card__empty">
					{ connection.is_configured
						? 'Not checked yet. Run a connection test to see results.'
						: 'Complete setup in Connections before running a check.' }
				</p>
			) }

			{ isRunning && (
				<p className="ps-v2-tools-check-card__progress">Running connection checks…</p>
			) }

			{ displayCheck?.checks?.length > 0 && (
				<div className="ps-v2-tools-check-card__results" data-figma-node="943:13335">
					{ displayCheck.checks.map( ( check ) => (
						<ConnectionCheckResultRow key={ check.id } check={ check } />
					) ) }
				</div>
			) }
		</article>
	);
};

export default ConnectionCheckCard;
