import { useEffect, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import { iconExternalLink } from '../../connections/components/connectionHelperAssets';
import { useNotificationMutations } from '../hooks/useNotifications';
import { iconTestSend } from '../notificationChannelAssets';

const TwilioChannelPanel = ( { settings, onSaved } ) => {
	const locked = Boolean( settings?.panel_locked );
	const [ enabled, setEnabled ] = useState( false );
	const [ notifyRetryLimit, setNotifyRetryLimit ] = useState( true );
	const [ sid, setSid ] = useState( '' );
	const [ token, setToken ] = useState( '' );
	const [ sendTo, setSendTo ] = useState( '' );
	const [ msgServiceSid, setMsgServiceSid ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const { saveChannel, testChannel } = useNotificationMutations();

	const docUrl =
		settings?.doc_url ||
		'https://postmansmtp.com/documentation/extension/twilio-sms-notification-extension/';

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setSid( settings?.sid_masked || '' );
		setToken( settings?.token_masked || '' );
		setSendTo( settings?.send_to || '' );
		setMsgServiceSid( settings?.msg_service_sid || '' );
	}, [ settings ] );

	const hasCredentials =
		( Boolean( settings?.has_sid ) && Boolean( settings?.has_token ) ) ||
		( sid.trim() !== '' && token.trim() !== '' );

	const canTest =
		! locked &&
		enabled &&
		hasCredentials &&
		sendTo.trim() !== '' &&
		msgServiceSid.trim() !== '';

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveChannel.mutateAsync( {
				slug: 'twilio',
				payload: {
					enabled,
					sid,
					token,
					send_to: sendTo,
					msg_service_sid: msgServiceSid,
				},
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
			const result = await testChannel.mutateAsync( { slug: 'twilio' } );
			setNotice( { type: 'success', message: result?.message || 'Test notification sent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div
			className={ `ps-v2-notify-settings-card${ locked ? ' ps-v2-notify-settings-card--locked' : '' }` }
			data-figma-node="1072:44705-twilio-panel"
		>
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

			<div className="ps-v2-notify-toggle-row">
				<label className={ `ps-v2-notify-switch${ locked ? ' ps-v2-notify-switch--disabled' : '' }` }>
					<input
						type="checkbox"
						checked={ enabled }
						disabled={ locked }
						onChange={ ( e ) => setEnabled( e.target.checked ) }
					/>
					<span className="ps-v2-notify-switch__track" aria-hidden="true" />
				</label>
				<span className="ps-v2-notify-toggle-row__label">Send failure alerts via Twilio (SMS)</span>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-twilio-sid" className="ps-v2-notify-field__label">
						Twilio SID
					</label>
					<p className="ps-v2-notify-field__hint">From your Twilio account dashboard.</p>
				</div>
				<input
					id="ps-notify-twilio-sid"
					className="ps-v2-notify-field__input"
					type="password"
					value={ sid }
					placeholder="Placeholder"
					disabled={ locked }
					onChange={ ( e ) => setSid( e.target.value ) }
					autoComplete="off"
				/>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-twilio-token" className="ps-v2-notify-field__label">
						Twilio token
					</label>
					<p className="ps-v2-notify-field__hint">Your Twilio auth token.</p>
				</div>
				<input
					id="ps-notify-twilio-token"
					className="ps-v2-notify-field__input"
					type="password"
					value={ token }
					placeholder="Placeholder"
					disabled={ locked }
					onChange={ ( e ) => setToken( e.target.value ) }
					autoComplete="off"
				/>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-twilio-send-to" className="ps-v2-notify-field__label">
						Send to number
					</label>
					<p className="ps-v2-notify-field__hint">Mobile number that receives SMS alerts.</p>
				</div>
				<input
					id="ps-notify-twilio-send-to"
					className="ps-v2-notify-field__input"
					type="text"
					value={ sendTo }
					placeholder="Placeholder"
					disabled={ locked }
					onChange={ ( e ) => setSendTo( e.target.value ) }
					autoComplete="off"
				/>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-twilio-msg-sid" className="ps-v2-notify-field__label">
						Message service SID
					</label>
					<p className="ps-v2-notify-field__hint">Twilio Messaging Service SID for outbound SMS.</p>
				</div>
				<input
					id="ps-notify-twilio-msg-sid"
					className="ps-v2-notify-field__input"
					type="text"
					value={ msgServiceSid }
					placeholder="Placeholder"
					disabled={ locked }
					onChange={ ( e ) => setMsgServiceSid( e.target.value ) }
					autoComplete="off"
				/>
				{ docUrl && (
					<a href={ docUrl } className="ps-v2-notify-field__helper-link" target="_blank" rel="noreferrer">
						<span>View documentation</span>
						<img src={ iconExternalLink } alt="" width="12" height="12" />
					</a>
				) }
			</div>

			<div className="ps-v2-notify-toggle-row ps-v2-notify-toggle-row--stacked">
				<label className={ `ps-v2-notify-switch${ locked ? ' ps-v2-notify-switch--disabled' : '' }` }>
					<input
						type="checkbox"
						checked={ notifyRetryLimit }
						disabled={ locked }
						onChange={ ( e ) => setNotifyRetryLimit( e.target.checked ) }
					/>
					<span className="ps-v2-notify-switch__track" aria-hidden="true" />
				</label>
				<div className="ps-v2-notify-toggle-row__copy">
					<span className="ps-v2-notify-toggle-row__label">Notify when retry limit is reached</span>
					<p className="ps-v2-notify-toggle-row__desc">
						Receive an SMS when an email delivery reaches the Maximum Retry Attempts and can no longer be
						delivered. Sent once per failed email.
					</p>
				</div>
			</div>

			<button
				type="button"
				className="ps-v2-notify-test-btn"
				disabled={ locked || ! canTest || testChannel.isPending }
				onClick={ handleTest }
			>
				<img src={ iconTestSend } alt="" width="13" height="12" />
				<span>{ testChannel.isPending ? 'Sending…' : 'Send test notification' }</span>
			</button>

			{ ! locked && (
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
			) }
		</div>
	);
};

export default TwilioChannelPanel;
