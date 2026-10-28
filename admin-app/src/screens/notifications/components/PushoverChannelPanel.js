import { useEffect, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import { useNotificationMutations } from '../hooks/useNotifications';
import { iconTestSend } from '../notificationChannelAssets';

const PushoverChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ notifyRetryLimit, setNotifyRetryLimit ] = useState( true );
	const [ user, setUser ] = useState( '' );
	const [ token, setToken ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const { saveChannel, testChannel } = useNotificationMutations();

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setUser( settings?.user_masked || '' );
		setToken( settings?.token_masked || '' );
	}, [ settings ] );

	const canTest =
		enabled &&
		( ( Boolean( settings?.has_user ) && Boolean( settings?.has_token ) ) ||
			( user.trim() !== '' && token.trim() !== '' ) );

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveChannel.mutateAsync( {
				slug: 'pushover',
				payload: { enabled, user, token },
			} );
			setNotice( { type: 'success', message: result?.message || 'Notification settings saved.' } );
			onSaved?.( result );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save notification settings.' } );
		}
	};

	const handleTest = async () => {
		setNotice( null );
		try {
			const result = await testChannel.mutateAsync( { slug: 'pushover' } );
			setNotice( { type: 'success', message: result?.message || 'Test notification sent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div className="ps-v2-notify-settings-card" data-figma-node="1072:44112">
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
				<span className="ps-v2-notify-toggle-row__label">Send failure alerts via Pushover</span>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-pushover-user" className="ps-v2-notify-field__label">
						User key
					</label>
					<p className="ps-v2-notify-field__hint">From your Pushover dashboard.</p>
				</div>
				<input
					id="ps-notify-pushover-user"
					className="ps-v2-notify-field__input"
					type="password"
					value={ user }
					placeholder="Placeholder"
					onChange={ ( e ) => setUser( e.target.value ) }
					autoComplete="off"
				/>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-pushover-token" className="ps-v2-notify-field__label">
						App token
					</label>
					<p className="ps-v2-notify-field__hint">From your Pushover application.</p>
				</div>
				<input
					id="ps-notify-pushover-token"
					className="ps-v2-notify-field__input"
					type="password"
					value={ token }
					placeholder="Placeholder"
					onChange={ ( e ) => setToken( e.target.value ) }
					autoComplete="off"
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
						Receive an alert through Pushover when an email delivery reaches the Maximum Retry Attempts
						and can no longer be delivered. Sent once per failed email.
					</p>
				</div>
			</div>

			<button
				type="button"
				className="ps-v2-notify-test-btn"
				disabled={ ! canTest || testChannel.isPending }
				onClick={ handleTest }
			>
				<img src={ iconTestSend } alt="" width="13" height="12" />
				<span>{ testChannel.isPending ? 'Sending…' : 'Send test notification' }</span>
			</button>

			<div className="ps-v2-notify-settings-card__save">
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary ps-v2-notify-save-btn"
					disabled={ saveChannel.isPending }
					onClick={ handleSave }
				>
					{ saveChannel.isPending ? 'Saving…' : 'Save Changes' }
				</button>
			</div>
		</div>
	);
};

export default PushoverChannelPanel;
