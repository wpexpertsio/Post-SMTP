import { useState } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../api/client';

const config = window.postSmtpAdmin || {};
const dashboardUrl = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';

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

const CheckIcon = () => (
	<svg className="ps-migration-modal__check-icon" viewBox="0 0 14 14" aria-hidden="true">
		<circle cx="7" cy="7" r="7" fill="currentColor" opacity="0.15" />
		<path
			fill="currentColor"
			d="M6.2 9.4 3.8 7l.7-.7 1.7 1.7 3.3-3.3.7.7-4 4z"
		/>
	</svg>
);

const MigrationModal = () => {
	const [ step, setStep ] = useState( 'preview' );
	const queryClient = useQueryClient();

	const previewQuery = useQuery( {
		queryKey: [ 'migration-preview' ],
		queryFn: () => apiFetch( 'migration/preview' ),
	} );

	const migrateMutation = useMutation( {
		mutationFn: () => apiFetch( 'migration/start', { method: 'POST' } ),
		onSuccess: () => {
			setStep( 'done' );
			queryClient.invalidateQueries( { queryKey: [ 'connections' ] } );
		},
	} );

	const preview = previewQuery.data || {};
	const connections = preview.connections || [];
	const alreadyMigrated = Boolean( preview.already_migrated );
	const canMigrate = preview.can_migrate !== false && ! alreadyMigrated;

	if ( previewQuery.isLoading ) {
		return (
			<div className="ps-migration-modal" role="dialog" aria-modal="true" aria-labelledby="ps-migration-modal-title">
				<div className="ps-migration-modal__inner">
					<Badge />
					<p className="ps-migration-modal__body">Loading migration preview…</p>
				</div>
			</div>
		);
	}

	if ( step === 'done' || alreadyMigrated ) {
		return (
			<div className="ps-migration-modal" role="dialog" aria-modal="true" aria-labelledby="ps-migration-modal-title">
				<div className="ps-migration-modal__inner">
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
							<CheckIcon />
							Primary Connection
						</div>
						<div className="ps-migration-modal__check-item">
							<CheckIcon />
							Email Logs
						</div>
						<div className="ps-migration-modal__check-item">
							<CheckIcon />
							Reports &amp; Analytic
						</div>
						<div className="ps-migration-modal__check-item">
							<CheckIcon />
							Settings
						</div>
					</div>
					<div className="ps-migration-modal__actions">
						<a href={ dashboardUrl } className="ps-migration-modal__btn-primary">
							Go to Dashboard
							<svg width="16" height="12" viewBox="0 0 16 12" aria-hidden="true">
								<path fill="currentColor" d="M1 6h12M9 2l4 4-4 4" stroke="currentColor" strokeWidth="1.5" fill="none" />
							</svg>
						</a>
						<a href={ `${ dashboardUrl }#/connections` } className="ps-migration-modal__btn-secondary">
							View Connections
						</a>
					</div>
				</div>
			</div>
		);
	}

	if ( step === 'confirm' ) {
		return (
			<div className="ps-migration-modal" role="dialog" aria-modal="true" aria-labelledby="ps-migration-modal-title">
				<div className="ps-migration-modal__inner">
					<div className="ps-migration-modal__header">
						<Badge />
						<h2 id="ps-migration-modal-title" className="ps-migration-modal__title">
							Ready to migrate?
						</h2>
						<p className="ps-migration-modal__body">
							We will move your mailer settings into multi-connection storage and switch you to the new
							Post SMTP dashboard. You can roll back within 5 days if needed.
						</p>
					</div>
					{ migrateMutation.error && (
						<p className="ps-migration-modal__error">{ migrateMutation.error.message }</p>
					) }
					<div className="ps-migration-modal__actions">
						<button type="button" className="ps-migration-modal__btn-ghost" onClick={ () => setStep( 'preview' ) }>
							Back
						</button>
						<button
							type="button"
							className="ps-migration-modal__btn-primary"
							disabled={ migrateMutation.isPending }
							onClick={ () => migrateMutation.mutate() }
						>
							{ migrateMutation.isPending ? 'Migrating…' : 'Start migration' }
						</button>
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="ps-migration-modal" role="dialog" aria-modal="true" aria-labelledby="ps-migration-modal-title">
			<div className="ps-migration-modal__inner">
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
				<div className="ps-migration-modal__actions">
					<button
						type="button"
						className="ps-migration-modal__btn-primary"
						disabled={ ! canMigrate }
						onClick={ () => setStep( 'confirm' ) }
					>
						Continue
					</button>
				</div>
			</div>
		</div>
	);
};

export default MigrationModal;
