import { useEffect, useMemo, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import useNotifications, { useNotificationMutations } from '../hooks/useNotifications';

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const AdminEmailChannelPanel = ( { adminEmail, wpAdminEmail, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
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
		<div className="ps-v2-notify-panel" data-figma-node="672:7890">
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />
			<header className="ps-v2-notify-panel__head">
				<h2 className="ps-v2-notify-panel__title">Admin Email</h2>
				<p className="ps-v2-notify-panel__sub">
					Receive failure alerts in your administrator inbox when WordPress email stops delivering.
				</p>
			</header>

			<div className="ps-v2-notify-form">
				<label className="ps-v2-notify-toggle">
					<span className="ps-v2-notify-toggle__label">Send alerts to admin email</span>
					<span className="ps-v2-notify-switch">
						<input
							type="checkbox"
							checked={ enabled }
							onChange={ ( e ) => setEnabled( e.target.checked ) }
						/>
						<span className="ps-v2-notify-switch__track" aria-hidden="true" />
					</span>
				</label>

				<div className="ps-v2-form-field">
					<div className="ps-v2-form-field__head">
						<label htmlFor="ps-notify-admin-email" className="ps-v2-form-field__label">
							Admin Email
						</label>
						<p className="ps-v2-form-field__hint">
							Leave blank to use your WordPress administrator email address.
						</p>
					</div>
					<input
						id="ps-notify-admin-email"
						className="ps-v2-field-input"
						type="email"
						value={ email }
						placeholder="Enter admin email address"
						onChange={ ( e ) => setEmail( e.target.value ) }
					/>
				</div>

				<div className="ps-v2-notify-form__actions-row">
					<button
						type="button"
						className="ps-v2-btn ps-v2-btn--ghost"
						disabled={ ! canTest || testAdminEmail.isPending }
						onClick={ handleTest }
					>
						{ testAdminEmail.isPending ? 'Sending…' : 'Send Test Notification' }
					</button>
				</div>

				<div className="ps-v2-notify-form__save-row">
					<button
						type="button"
						className="ps-v2-btn ps-v2-btn--primary"
						disabled={ saveAdminEmail.isPending || ! canSave }
						onClick={ handleSave }
					>
						{ saveAdminEmail.isPending ? 'Saving…' : 'Save Changes' }
					</button>
				</div>
			</div>
		</div>
	);
};

export default AdminEmailChannelPanel;
