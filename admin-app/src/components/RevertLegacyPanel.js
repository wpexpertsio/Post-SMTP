import { useQuery, useMutation } from '@tanstack/react-query';
import apiFetch from '../api/client';

const config = window.postSmtpAdmin || {};
const revertUrl = config.adminUrls?.revertLegacy || '/wp-admin/admin.php?page=post_smtp_revert_legacy';

const RevertLegacyPanel = () => {
	if ( config.cohort !== 'migrated' ) {
		return null;
	}

	const statusQuery = useQuery( {
		queryKey: [ 'rollback-status' ],
		queryFn: () => apiFetch( 'migration/rollback-status' ),
	} );

	const revertMutation = useMutation( {
		mutationFn: ( forceUiOnly ) =>
			apiFetch( 'migration/revert-legacy', {
				method: 'POST',
				body: { force_ui_only: forceUiOnly },
			} ),
		onSuccess: ( data ) => {
			if ( data?.success ) {
				window.location.href = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';
			}
		},
	} );

	const status = statusQuery.data || {};

	return (
		<div className="post-smtp-card ps-revert-panel">
			<h2>Revert to legacy admin</h2>
			{ status.can_full_revert ? (
				<p>
					A full settings backup is available until <strong>{ status.expires_at } UTC</strong>.
					You can restore the classic Post SMTP experience and your previous mail settings.
				</p>
			) : (
				<p>
					No migration backup is available. You can still switch to the legacy admin, but you may
					need to reconfigure your mailer afterward.
				</p>
			) }
			<div className="post-smtp-actions">
				<button
					type="button"
					className="ps-btn ps-btn--primary"
					disabled={ revertMutation.isPending }
					onClick={ () => revertMutation.mutate( ! status.can_full_revert ) }
				>
					{ revertMutation.isPending ? 'Reverting…' : 'Revert to legacy' }
				</button>
				<a href={ revertUrl } className="ps-text-link">Open revert page</a>
			</div>
			{ revertMutation.error && (
				<p className="post-smtp-error">{ revertMutation.error.message }</p>
			) }
			{ revertMutation.data && ! revertMutation.data.success && (
				<p className="post-smtp-error">{ revertMutation.data.message }</p>
			) }
		</div>
	);
};

export default RevertLegacyPanel;
