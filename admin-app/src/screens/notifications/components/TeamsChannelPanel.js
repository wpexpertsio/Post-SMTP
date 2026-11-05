import { useEffect, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import { iconExternalLink } from '../../connections/components/connectionHelperAssets';
import { useNotificationMutations } from '../hooks/useNotifications';
import { iconTestSend } from '../notificationChannelAssets';

const TeamsChannelPanel = ( { settings, onSaved } ) => {
	const locked = Boolean( settings?.panel_locked );
	const [ enabled, setEnabled ] = useState( false );
	const [ notifyRetryLimit, setNotifyRetryLimit ] = useState( true );
	const [ webhook, setWebhook ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const { saveChannel, testChannel } = useNotificationMutations();

	const docUrl = settings?.doc_url || 'https://postmansmtp.com/docs/alerts/microsoft-teams/';

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setWebhook( settings?.webhook_url_masked || settings?.webhook_url || '' );
	}, [ settings ] );

	const canTest =
		! locked &&
		enabled &&
		( Boolean( settings?.has_webhook ) ||
			( webhook.trim() !== '' && webhook !== settings?.webhook_url_masked ) );

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveChannel.mutateAsync( {
				slug: 'teams',
				payload: { enabled, webhook_url: webhook },
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
			const result = await testChannel.mutateAsync( { slug: 'teams' } );
			setNotice( { type: 'success', message: result?.message || 'Test notification sent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div
			className={ `ps-v2-notify-settings-card${ locked ? ' ps-v2-notify-settings-card--locked' : '' }` }
			data-figma-node="1072:44716"
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
				<span className="ps-v2-notify-toggle-row__label">Send failure alerts via Microsoft Teams</span>
			</div>

			<div className="ps-v2-form-field ps-v2-notify-field">
				<div className="ps-v2-notify-field__labels">
					<label htmlFor="ps-notify-teams-webhook" className="ps-v2-notify-field__label">
						Webhook URL
					</label>
					<p className="ps-v2-notify-field__hint">Paste your Teams incoming webhook URL.</p>
				</div>
				<input
					id="ps-notify-teams-webhook"
					className="ps-v2-notify-field__input"
					type="password"
					value={ webhook }
					placeholder="Placeholder"
					disabled={ locked }
					onChange={ ( e ) => setWebhook( e.target.value ) }
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
						Receive an alert through Microsoft Teams when an email delivery reaches the Maximum Retry
						Attempts and can no longer be delivered. Sent once per failed email.
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

export default TeamsChannelPanel;
