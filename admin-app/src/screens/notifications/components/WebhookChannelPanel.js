import { useEffect, useMemo, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import iconAddAnother from '../../../assets/notifications/icon-add-another.svg';
import iconWebhookRemove from '../../../assets/notifications/icon-webhook-remove.svg';
import { useNotificationMutations } from '../hooks/useNotifications';
import { iconTestSend } from '../notificationChannelAssets';

const URL_PLACEHOLDER = 'https://hooks.zapier.com/hooks/catch/000000/abcdef';

const WebhookChannelPanel = ( { settings, onSaved } ) => {
	const [ enabled, setEnabled ] = useState( false );
	const [ notifyRetryLimit, setNotifyRetryLimit ] = useState( true );
	const [ urls, setUrls ] = useState( [ '' ] );
	const [ notice, setNotice ] = useState( null );
	const { saveChannel, testChannel } = useNotificationMutations();

	useEffect( () => {
		setEnabled( Boolean( settings?.enabled ) );
		const next = settings?.urls?.filter( ( u ) => u && String( u ).trim() ) || [];
		setUrls( next.length ? next : [ '' ] );
	}, [ settings ] );

	const cleanUrls = useMemo(
		() => urls.map( ( u ) => u.trim() ).filter( ( u ) => u !== '' ),
		[ urls ]
	);

	const canTest = enabled && cleanUrls.length > 0;

	const updateUrl = ( index, value ) => {
		setUrls( ( prev ) => prev.map( ( item, i ) => ( i === index ? value : item ) ) );
	};

	const addUrl = () => setUrls( ( prev ) => [ ...prev, '' ] );

	const removeUrl = ( index ) => {
		setUrls( ( prev ) => {
			if ( prev.length <= 1 ) {
				return [ '' ];
			}
			return prev.filter( ( _, i ) => i !== index );
		} );
	};

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveChannel.mutateAsync( {
				slug: 'webhook',
				payload: { enabled, urls },
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
			const result = await testChannel.mutateAsync( { slug: 'webhook' } );
			setNotice( { type: 'success', message: result?.message || 'Test notification sent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Test notification failed.' } );
		}
	};

	return (
		<div className="ps-v2-notify-settings-card" data-figma-node="1072:44021">
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
				<span className="ps-v2-notify-toggle-row__label">Send failure alerts via Webhook</span>
			</div>

			<div className="ps-v2-notify-webhook-fields">
				<p className="ps-v2-notify-webhook-fields__hint">
					Send alerts to any URL (Zapier, Make, or your app).
				</p>
				{ urls.map( ( url, index ) => (
					<div key={ `webhook-url-${ index }` } className="ps-v2-notify-webhook-url-row">
						<input
							id={ index === 0 ? 'ps-notify-webhook-0' : undefined }
							className="ps-v2-notify-field__input ps-v2-notify-webhook-url-row__input"
							type="url"
							value={ url }
							placeholder={ URL_PLACEHOLDER }
							onChange={ ( e ) => updateUrl( index, e.target.value ) }
							aria-label={ `Webhook URL${ urls.length > 1 ? ` ${ index + 1 }` : '' }` }
						/>
						<button
							type="button"
							className="ps-v2-notify-webhook-url-row__remove"
							onClick={ () => removeUrl( index ) }
							aria-label="Remove webhook URL"
						>
							<img src={ iconWebhookRemove } alt="" width="13" height="15" />
						</button>
					</div>
				) ) }
				<button type="button" className="ps-v2-notify-webhook-add" onClick={ addUrl }>
					<img src={ iconAddAnother } alt="" width="10" height="10" />
					<span>Add another</span>
				</button>
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
						Receive an alert through Webhook when an email delivery reaches the Maximum Retry Attempts
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

export default WebhookChannelPanel;
