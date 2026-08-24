import { useState } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '../../api/client';

const STEPS = [ 'preview', 'confirm', 'done' ];
const config = window.postSmtpAdmin || {};
const dashboardUrl = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';
const revertUrl = config.adminUrls?.revertLegacy || '/wp-admin/admin.php?page=post_smtp_revert_legacy';

const MigrationScreen = () => {
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

	const rollbackMutation = useMutation( {
		mutationFn: () => apiFetch( 'migration/rollback', { method: 'POST' } ),
		onSuccess: () => {
			setStep( 'preview' );
			previewQuery.refetch();
		},
	} );

	const preview = previewQuery.data || {};
	const connections = preview.connections || [];

	return (
		<div className="post-smtp-card">
			<h2>Guided migration</h2>
			<p>Step { STEPS.indexOf( step ) + 1 } of { STEPS.length }</p>

			{ step === 'preview' && (
				<>
					<p>Review connections that will be created from your current settings.</p>
					{ preview.already_migrated ? (
						<p className="post-smtp-success">This site already uses modern storage.</p>
					) : (
						<ul>
							{ connections.map( ( row, i ) => (
								<li key={ i }>
									<strong>{ row.title || row.provider }</strong>
									{ row.sender_email ? ` — ${ row.sender_email }` : '' }
								</li>
							) ) }
						</ul>
					) }
					{ preview.pro_error && <p className="post-smtp-error">{ preview.pro_error }</p> }
					<div className="post-smtp-actions">
						<button
							type="button"
							className="ps-btn ps-btn--primary"
							disabled={ preview.already_migrated || preview.can_migrate === false }
							onClick={ () => setStep( 'confirm' ) }
						>
							Continue
						</button>
					</div>
				</>
			) }

			{ step === 'confirm' && (
				<>
					<p>Confirm migration to multi-connection storage. You can roll back within 5 days.</p>
					<div className="post-smtp-actions">
						<button type="button" className="ps-btn ps-btn--secondary" onClick={ () => setStep( 'preview' ) }>
							Back
						</button>
						<button
							type="button"
							className="ps-btn ps-btn--primary"
							disabled={ migrateMutation.isPending }
							onClick={ () => migrateMutation.mutate() }
						>
							{ migrateMutation.isPending ? 'Migrating…' : 'Start migration' }
						</button>
					</div>
					{ migrateMutation.error && (
						<p className="post-smtp-error">{ migrateMutation.error.message }</p>
					) }
				</>
			) }

			{ step === 'done' && (
				<>
					<p className="post-smtp-success">Migration completed. Welcome to the new Post SMTP experience.</p>
					<div className="post-smtp-actions">
						<a href={ dashboardUrl } className="ps-btn ps-btn--primary">
							Open new dashboard
						</a>
						<a href={ revertUrl } className="ps-btn ps-btn--secondary">
							Revert to legacy later
						</a>
						<button
							type="button"
							className="ps-btn ps-btn--secondary"
							disabled={ rollbackMutation.isPending }
							onClick={ () => rollbackMutation.mutate() }
						>
							Roll back
						</button>
					</div>
				</>
			) }
		</div>
	);
};

export default MigrationScreen;
