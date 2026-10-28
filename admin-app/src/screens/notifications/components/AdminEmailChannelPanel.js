import { useEffect, useMemo, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import useNotifications, { useNotificationMutations } from '../hooks/useNotifications';
import { iconTestSend } from '../notificationChannelAssets';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const AdminEmailChannelPanel = ( { adminEmail, wpAdminEmail, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ notifyRetryLimit, setNotifyRetryLimit ] = useState( true );
	const [ email, setEmail ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const { saveAdminEmail, testAdminEmail } = useNotificationMutations();

	useEffect( () => {
		setEnabled( Boolean( adminEmail?.enabled ) );
		setEmail( adminEmail?.email || '' );
	}, [ adminEmail?.enabled, adminEmail?.email ] );

	const effectiveEmail = useMemo( () => {
		const trimmed = email.trim();
		if ( trimmed && emailPattern.test( trimmed ) ) {
			return trimmed;
		}
		return wpAdminEmail || adminEmail?.effective_email || '';
	}, [ email, wpAdminEmail, adminEmail?.effective_email ] );

	const canSave = ! email.trim() || emailPattern.test( email.trim() );
	const canTest = enabled && Boolean( effectiveEmail ) && emailPattern.test( effectiveEmail );

	const handleSave = async () => {
		setNotice( null );
		if ( ! canSave ) {
			setNotice( { type: 'error', message: 'Please enter a valid admin email address.' } );
			return;
		}
		try {
			const result = await saveAdminEmail.mutateAsync( { enabled, email: email.trim() } );
			setNotice( { type: 'success', message: result?.message || 'Notification settings saved.' } );
			onSaved?.( result );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save notification settings.' } );
		}
	};

	const handleTest = async () => {
		setNotice( null );
		try {
			const result = await testAdminEmail.mutateAsync();
			setNotice( {
				type: 'success',
				message: result?.message || 'Test notification sent. Check your admin inbox.',
			} );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div className="ps-v2-notify-settings-card" data-figma-node="1065:43857">
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<div className="ps-v2-notify-toggle-row">
				<label className="ps-v2-notify-switch">
					<input
						type="checkbox"
						checked={ enabled }
						onChange={ ( e ) => setEnabled( e.target.checked ) }
					/>
					<span className="ps-v2-notify-switch__track" aria-hidden="true" />
				</label>
				<span className="ps-v2-notify-toggle-row__label">Send alerts to admin email</span>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-admin-email" className="ps-v2-notify-field__label">
						Admin email
					</label>
					<p className="ps-v2-notify-field__hint">Leave blank to use your WordPress admin email.</p>
				</div>
				<input
					id="ps-notify-admin-email"
					className="ps-v2-notify-field__input"
					type="email"
					value={ email }
					placeholder="Placeholder"
					onChange={ ( e ) => setEmail( e.target.value ) }
				/>
			</div>

			<div className="ps-v2-notify-toggle-row ps-v2-notify-toggle-row--stacked">
				<label className="ps-v2-notify-switch">
					<input
						type="checkbox"
						checked={ notifyRetryLimit }
						onChange={ ( e ) => setNotifyRetryLimit( e.target.checked ) }
					/>
					<span className="ps-v2-notify-switch__track" aria-hidden="true" />
				</label>
				<div className="ps-v2-notify-toggle-row__copy">
					<span className="ps-v2-notify-toggle-row__label">Notify when retry limit is reached</span>
					<p className="ps-v2-notify-toggle-row__desc">
						Receive an alert through Admin Email when an email delivery reaches the Maximum Retry
						Attempts and can no longer be delivered. Sent once per failed email.
					</p>
				</div>
			</div>

			<button
				type="button"
				className="ps-v2-notify-test-btn"
				disabled={ ! canTest || testAdminEmail.isPending }
				onClick={ handleTest }
			>
				<img src={ iconTestSend } alt="" width="13" height="12" />
				<span>{ testAdminEmail.isPending ? 'Sending…' : 'Send test notification' }</span>
			</button>

			<div className="ps-v2-notify-settings-card__save">
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary ps-v2-notify-save-btn"
					disabled={ saveAdminEmail.isPending || ! canSave }
					onClick={ handleSave }
				>
					{ saveAdminEmail.isPending ? 'Saving…' : 'Save Changes' }
				</button>
			</div>
		</div>
	);
};

export default AdminEmailChannelPanel;
