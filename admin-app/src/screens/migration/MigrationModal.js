import { useEffect, useState } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../api/client';

const config = window.postSmtpAdmin || {};
const dashboardUrl = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';
const connectionsUrl = `${ dashboardUrl }#/connections`;

const LOADING_STEPS = [
	'Checking your existing Settings.',
	'Checking Post SMTP Free and Post SMTP Pro versions.',
	'Moving your Primary Connection.',
	'Moving Email Logs entries.',
	'Moving your Settings and Notifications.',
	'Opening the New Post SMTP Experience.',
];

/** All steps turn green one-by-one over ~4.5s (Figma loading state). */
const PROGRESS_TOTAL_MS = 4500;
const PROGRESS_STEP_MS = PROGRESS_TOTAL_MS / LOADING_STEPS.length;

const Badge = () => (
	<div className="ps-migration-modal__badge">
		<svg className="ps-migration-modal__badge-icon" viewBox="0 0 14 14" aria-hidden="true">
			<path
				fill="currentColor"
				d="M7 1.5l1.76 3.57 3.94.57-2.85 2.78.67 3.92L7 10.4l-3.52 1.85.67-3.92-2.85-2.78 3.94-.57L7 1.5z"
			/>
		</svg>
		<span className="ps-migration-modal__badge-label">Post SMTP Update</span>
	</div>
);

const GreenCheckIcon = () => (
	<svg className="ps-migration-modal__progress-check" viewBox="0 0 14 14" aria-hidden="true">
		<circle cx="7" cy="7" r="7" fill="#059669" />
		<path
			fill="#fff"
			d="M6.2 9.1 3.9 6.8l.65-.65 1.65 1.65 3.2-3.2.65.65-3.85 3.85z"
		/>
	</svg>
);

const BlueSyncIcon = () => (
	<svg className="ps-migration-modal__progress-sync" viewBox="0 0 14 14" aria-hidden="true">
		<path
			fill="#5279cc"
			d="M7 1.5a5.5 5.5 0 0 1 5.2 3.7h-1.4l2 2.3-2 2.3V7.8A4 4 0 1 0 11 7h1.5A5.5 5.5 0 0 1 7 12.5 5.5 5.5 0 0 1 1.5 7 5.5 5.5 0 0 1 7 1.5z"
		/>
	</svg>
);

const PendingDot = () => (
	<span className="ps-migration-modal__progress-pending" aria-hidden="true" />
);

const MigrationModal = () => {
	const [ step, setStep ] = useState( 'preview' );
	const [ completedStepCount, setCompletedStepCount ] = useState( 0 );
	const [ migrationApiDone, setMigrationApiDone ] = useState( false );
	const queryClient = useQueryClient();

	const previewQuery = useQuery( {
		queryKey: [ 'migration-preview' ],
		queryFn: () => apiFetch( 'migration/preview' ),
	} );

	const migrateMutation = useMutation( {
		mutationFn: () => apiFetch( 'migration/start', { method: 'POST' } ),
		onSuccess: () => {
			setMigrationApiDone( true );
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
		},
		onError: () => {
			setStep( 'preview' );
			setMigrationApiDone( false );
			setCompletedStepCount( 0 );
		},
	} );

	useEffect( () => {
		if ( step !== 'migrating' ) {
			return undefined;
		}

		setCompletedStepCount( 0 );
		setMigrationApiDone( false );

		const timers = LOADING_STEPS.map( ( _, index ) =>
			window.setTimeout( () => {
				setCompletedStepCount( index + 1 );
			}, ( index + 1 ) * PROGRESS_STEP_MS )
		);

		return () => {
			timers.forEach( ( id ) => window.clearTimeout( id ) );
		};
	}, [ step ] );

	useEffect( () => {
		if (
			step !== 'migrating' ||
			! migrationApiDone ||
			completedStepCount < LOADING_STEPS.length
		) {
			return undefined;
		}

		const doneTimer = window.setTimeout( () => {
			setStep( 'done' );
		}, 400 );

		return () => window.clearTimeout( doneTimer );
	}, [ step, migrationApiDone, completedStepCount ] );

	const preview = previewQuery.data || {};
	const connections = preview.connections || [];
	const alreadyMigrated = Boolean( preview.already_migrated );
	const canMigrate = preview.can_migrate !== false && ! alreadyMigrated;

	const startMigration = () => {
		setStep( 'migrating' );
		migrateMutation.mutate();
	};

	const modalShell = ( content, figmaNode ) => (
		<div
			className="ps-migration-modal ps-migration-modal--animate"
			data-figma-node={ figmaNode }
			role="dialog"
			aria-modal="true"
			aria-labelledby="ps-migration-modal-title"
		>
			<div className="ps-migration-modal__inner">{ content }</div>
		</div>
	);

	if ( previewQuery.isLoading ) {
		return modalShell(
			<>
				<Badge />
				<p className="ps-migration-modal__body">Loading migration preview…</p>
			</>,
			'952:15291'
		);
	}

	if ( step === 'done' || alreadyMigrated ) {
		return modalShell(
			<>
				<div className="ps-migration-modal__header">
					<Badge />
					<h2 id="ps-migration-modal-title" className="ps-migration-modal__title">
						Your settings have been moved successfully!
					</h2>
					<p className="ps-migration-modal__body">
						Your existing Post SMTP settings are now available in the new experience. Review your
						migrated settings or explore the updated dashboard.
					</p>
				</div>
				<div className="ps-migration-modal__check-grid">
					<div className="ps-migration-modal__check-item">
						<GreenCheckIcon />
						Primary Connection
					</div>
					<div className="ps-migration-modal__check-item">
						<GreenCheckIcon />
						Email Logs
					</div>
					<div className="ps-migration-modal__check-item">
						<GreenCheckIcon />
						Reports &amp; Analytic
					</div>
					<div className="ps-migration-modal__check-item">
						<GreenCheckIcon />
						Settings
					</div>
				</div>
				<div className="ps-migration-modal__actions ps-migration-modal__actions--end">
					<a href={ connectionsUrl } className="ps-migration-modal__btn-outline">
						View Connections
					</a>
					<a href={ dashboardUrl } className="ps-migration-modal__btn-primary">
						Go to Dashboard
						<svg width="16" height="12" viewBox="0 0 16 12" aria-hidden="true">
							<path fill="currentColor" d="M1 6h12M9 2l4 4-4 4" stroke="currentColor" strokeWidth="1.5" fill="none" />
						</svg>
					</a>
				</div>
			</>,
			'985:15598'
		);
	}

	if ( step === 'migrating' ) {
		return modalShell(
			<>
				<div className="ps-migration-modal__header">
					<Badge />
					<h2 id="ps-migration-modal-title" className="ps-migration-modal__title">
						Moving your existing Post SMTP settings
					</h2>
					<p className="ps-migration-modal__body">This may take a moment.</p>
				</div>
				<ul className="ps-migration-modal__progress" aria-live="polite">
					{ LOADING_STEPS.map( ( label, index ) => {
						const isDone = index < completedStepCount;
						const isActive =
							! isDone &&
							index === completedStepCount &&
							completedStepCount < LOADING_STEPS.length;
						const state = isDone ? 'done' : isActive ? 'active' : 'pending';

						return (
							<li
								key={ label }
								className={ `ps-migration-modal__progress-item is-${ state }` }
							>
								{ isDone && <GreenCheckIcon /> }
								{ isActive && <BlueSyncIcon /> }
								{ ! isDone && ! isActive && <PendingDot /> }
								<span>{ label }</span>
							</li>
						);
					} ) }
				</ul>
				{ migrateMutation.error && (
					<p className="ps-migration-modal__error">{ migrateMutation.error.message }</p>
				) }
				<div className="ps-migration-modal__actions ps-migration-modal__actions--end">
					<button type="button" className="ps-migration-modal__btn-outline" disabled>
						Cancel
					</button>
					<button type="button" className="ps-migration-modal__btn-primary" disabled>
						Migrate Now
					</button>
				</div>
			</>,
			'1523:18642'
		);
	}

	return modalShell(
		<>
			<div className="ps-migration-modal__header">
				<Badge />
				<h2 id="ps-migration-modal-title" className="ps-migration-modal__title">
					Upgrade to the new Post SMTP experience
				</h2>
				<p className="ps-migration-modal__body">
					Review the connections we will create from your current settings. Your credentials stay on this
					site — nothing is sent externally.
				</p>
			</div>
			{ preview.pro_error && <p className="ps-migration-modal__error">{ preview.pro_error }</p> }
			<ul className="ps-migration-modal__connections">
				{ connections.map( ( row, i ) => (
					<li key={ i }>
						<strong>{ row.title || row.provider }</strong>
						{ row.sender_email ? ` — ${ row.sender_email }` : '' }
					</li>
				) ) }
			</ul>
			<p className="ps-migration-modal__note">
				After migration, <strong>Revert to Legacy</strong> stays available under the Post SMTP menu.
			</p>
			<div className="ps-migration-modal__actions ps-migration-modal__actions--end">
				<a href={ dashboardUrl } className="ps-migration-modal__btn-outline">
					Cancel
				</a>
				<button
					type="button"
					className="ps-migration-modal__btn-primary"
					disabled={ ! canMigrate }
					onClick={ startMigration }
				>
					Migrate Now
				</button>
			</div>
		</>,
		'952:15291'
	);
};

export default MigrationModal;
