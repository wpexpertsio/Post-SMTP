import { useEffect, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import { useNotificationMutations } from '../hooks/useNotifications';

const NotificationChannelShell = ( {
	slug,
	title,
	description,
	enabled,
	onEnabledChange,
	canTest,
	testDisabledReason,
	onSave,
	buildPayload,
	children,
	onSaved,
} ) => {
	const [ notice, setNotice ] = useState( null );
	const { saveChannel, testChannel } = useNotificationMutations();

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveChannel.mutateAsync( { slug, payload: buildPayload() } );
			setNotice( { type: 'success', message: result?.message || 'Notification settings saved.' } );
			onSaved?.( result );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not save notification settings.' } );
		}
	};

	const handleTest = async () => {
		setNotice( null );
		try {
			const result = await testChannel.mutateAsync( { slug } );
			setNotice( { type: 'success', message: result?.message || 'Test notification sent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div className="ps-v2-notify-panel" data-channel={ slug }>
			<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />
			<header className="ps-v2-notify-panel__head">
				<h2 className="ps-v2-notify-panel__title">{ title }</h2>
				{ description && <p className="ps-v2-notify-panel__sub">{ description }</p> }
			</header>
			<div className="ps-v2-notify-form">
				<label className="ps-v2-notify-toggle">
					<span className="ps-v2-notify-toggle__label">Send failure alerts on this channel</span>
					<span className="ps-v2-notify-switch">
						<input
							type="checkbox"
							checked={ enabled }
							onChange={ ( e ) => onEnabledChange( e.target.checked ) }
						/>
						<span className="ps-v2-notify-switch__track" aria-hidden="true" />
					</span>
				</label>
				{ children }
				{ canTest !== false && (
					<div className="ps-v2-notify-form__actions-row">
						<button
							type="button"
							className="ps-v2-btn ps-v2-btn--ghost"
							disabled={ ! enabled || canTest === false || testChannel.isPending }
							onClick={ handleTest }
							title={ testDisabledReason || '' }
						>
							{ testChannel.isPending ? 'Sending…' : 'Send Test Notification' }
						</button>
					</div>
				) }
				<div className="ps-v2-notify-form__save-row">
					<button
						type="button"
						className="ps-v2-btn ps-v2-btn--primary"
						disabled={ saveChannel.isPending }
						onClick={ handleSave }
					>
						{ saveChannel.isPending ? 'Saving…' : 'Save Changes' }
					</button>
				</div>
			</div>
		</div>
	);
};

export const SlackChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ webhook, setWebhook ] = useState( '' );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setWebhook( settings?.webhook_masked || '' );
	}, [ settings ] );

	return (
		<NotificationChannelShell
			slug="slack"
			title="Slack"
			description="Post failure alerts to a Slack channel via incoming webhook."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			buildPayload={ () => ( { enabled, webhook } ) }
			onSaved={ onSaved }
		>
			<div className="ps-v2-form-field">
				<div className="ps-v2-form-field__head">
					<label htmlFor="ps-notify-slack-webhook" className="ps-v2-form-field__label">
						Slack webhook URL
					</label>
					<p className="ps-v2-form-field__hint">
						<a href={ settings?.webhook_helper_url || 'https://slack.postmansmtp.com/' } target="_blank" rel="noreferrer">
							Get your webhook URL here
						</a>
					</p>
				</div>
				<input
					id="ps-notify-slack-webhook"
					className="ps-v2-field-input"
					type="password"
					value={ webhook }
					placeholder="https://hooks.slack.com/..."
					onChange={ ( e ) => setWebhook( e.target.value ) }
				/>
			</div>
		</NotificationChannelShell>
	);
};

export const ChromeChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ uid, setUid ] = useState( '' );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setUid( settings?.uid_masked || '' );
	}, [ settings ] );

	return (
		<NotificationChannelShell
			slug="chrome"
			title="Chrome Extension"
			description="Push failure alerts to the Post SMTP Chrome extension."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			canTest={ false }
			buildPayload={ () => ( { enabled, uid } ) }
			onSaved={ onSaved }
		>
			<div className="ps-v2-form-field">
				<div className="ps-v2-form-field__head">
					<label htmlFor="ps-notify-chrome-uid" className="ps-v2-form-field__label">
						Chrome extension UID
					</label>
					<p className="ps-v2-form-field__hint">
						<a href={ settings?.chrome_extension_url || '#' } target="_blank" rel="noreferrer">
							Download Chrome extension
						</a>
					</p>
				</div>
				<input
					id="ps-notify-chrome-uid"
					className="ps-v2-field-input"
					type="password"
					value={ uid }
					onChange={ ( e ) => setUid( e.target.value ) }
				/>
			</div>
		</NotificationChannelShell>
	);
};

export const PushoverChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ user, setUser ] = useState( '' );
	const [ token, setToken ] = useState( '' );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setUser( settings?.user_masked || '' );
		setToken( settings?.token_masked || '' );
	}, [ settings ] );

	return (
		<NotificationChannelShell
			slug="pushover"
			title="Pushover"
			description="Send failure alerts to your Pushover devices."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			buildPayload={ () => ( { enabled, user, token } ) }
			onSaved={ onSaved }
		>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-notify-pushover-user">Pushover user key</label>
				<input id="ps-notify-pushover-user" className="ps-v2-field-input" type="password" value={ user } onChange={ ( e ) => setUser( e.target.value ) } />
			</div>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-notify-pushover-token">Pushover app token</label>
				<input id="ps-notify-pushover-token" className="ps-v2-field-input" type="password" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />
			</div>
		</NotificationChannelShell>
	);
};

export const WebhookChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ urls, setUrls ] = useState( [ '' ] );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setUrls( settings?.urls?.length ? settings.urls : [ '' ] );
	}, [ settings ] );

	const updateUrl = ( index, value ) => {
		setUrls( ( prev ) => prev.map( ( item, i ) => ( i === index ? value : item ) ) );
	};

	const addUrl = () => setUrls( ( prev ) => [ ...prev, '' ] );
	const removeUrl = ( index ) => setUrls( ( prev ) => prev.filter( ( _, i ) => i !== index ) );

	return (
		<NotificationChannelShell
			slug="webhook"
			title="Webhook"
			description="POST failure alerts to one or more HTTP webhook URLs."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			buildPayload={ () => ( { enabled, urls } ) }
			onSaved={ onSaved }
		>
			{ urls.map( ( url, index ) => (
				<div key={ index } className="ps-v2-notify-webhook-row">
					<div className="ps-v2-form-field">
						<label className="ps-v2-form-field__label" htmlFor={ `ps-notify-webhook-${ index }` }>
							Webhook URL{ urls.length > 1 ? ` ${ index + 1 }` : '' }
						</label>
						<input
							id={ `ps-notify-webhook-${ index }` }
							className="ps-v2-field-input"
							type="url"
							value={ url }
							onChange={ ( e ) => updateUrl( index, e.target.value ) }
						/>
					</div>
					{ index > 0 && (
						<button type="button" className="ps-v2-btn ps-v2-btn--ghost ps-v2-notify-webhook-remove" onClick={ () => removeUrl( index ) }>
							Remove
						</button>
					) }
				</div>
			) ) }
			<button type="button" className="ps-v2-btn ps-v2-btn--ghost" onClick={ addUrl }>
				Add another webhook URL
			</button>
		</NotificationChannelShell>
	);
};

export const TeamsChannelPanel = ( { settings, channel, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ webhookUrl, setWebhookUrl ] = useState( '' );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setWebhookUrl( settings?.webhook_url_masked || settings?.webhook_url || '' );
	}, [ settings ] );

	if ( channel?.pro_locked ) {
		return (
			<div className="ps-v2-notify-placeholder">
				<h3 className="ps-v2-notify-panel__title">Microsoft Teams</h3>
				<p className="ps-v2-notify-panel__sub">Upgrade to Post SMTP Pro and enable the Microsoft Teams extension to configure Teams failure alerts here.</p>
			</div>
		);
	}

	return (
		<NotificationChannelShell
			slug="teams"
			title="Microsoft Teams"
			description="Send failure alerts to a Microsoft Teams channel webhook."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			buildPayload={ () => ( { enabled, webhook_url: webhookUrl } ) }
			onSaved={ onSaved }
		>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-notify-teams-webhook">Teams webhook URL</label>
				<input
					id="ps-notify-teams-webhook"
					className="ps-v2-field-input"
					type="url"
					value={ webhookUrl }
					onChange={ ( e ) => setWebhookUrl( e.target.value ) }
				/>
			</div>
		</NotificationChannelShell>
	);
};

export const TwilioChannelPanel = ( { settings, channel, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ sid, setSid ] = useState( '' );
	const [ token, setToken ] = useState( '' );
	const [ sendTo, setSendTo ] = useState( '' );
	const [ msgServiceSid, setMsgServiceSid ] = useState( '' );

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		setSid( settings?.sid_masked || '' );
		setToken( settings?.token_masked || '' );
		setSendTo( settings?.send_to || '' );
		setMsgServiceSid( settings?.msg_service_sid || '' );
	}, [ settings ] );

	if ( channel?.pro_locked ) {
		return (
			<div className="ps-v2-notify-placeholder">
				<h3 className="ps-v2-notify-panel__title">Twilio (SMS)</h3>
				<p className="ps-v2-notify-panel__sub">Upgrade to Post SMTP Pro and enable the Twilio extension to configure SMS failure alerts here.</p>
			</div>
		);
	}

	return (
		<NotificationChannelShell
			slug="twilio"
			title="Twilio (SMS)"
			description="Receive failure alerts as SMS via Twilio."
			enabled={ enabled }
			onEnabledChange={ setEnabled }
			buildPayload={ () => ( {
				enabled,
				sid,
				token,
				send_to: sendTo,
				msg_service_sid: msgServiceSid,
			} ) }
			onSaved={ onSaved }
		>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-twilio-sid">Twilio SID key</label>
				<input id="ps-twilio-sid" className="ps-v2-field-input" type="password" value={ sid } onChange={ ( e ) => setSid( e.target.value ) } />
			</div>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-twilio-token">Twilio token key</label>
				<input id="ps-twilio-token" className="ps-v2-field-input" type="password" value={ token } onChange={ ( e ) => setToken( e.target.value ) } />
			</div>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-twilio-send">Send to number</label>
				<input id="ps-twilio-send" className="ps-v2-field-input" type="text" value={ sendTo } onChange={ ( e ) => setSendTo( e.target.value ) } />
			</div>
			<div className="ps-v2-form-field">
				<label className="ps-v2-form-field__label" htmlFor="ps-twilio-msg">Message service SID</label>
				<input id="ps-twilio-msg" className="ps-v2-field-input" type="text" value={ msgServiceSid } onChange={ ( e ) => setMsgServiceSid( e.target.value ) } />
			</div>
		</NotificationChannelShell>
	);
};
