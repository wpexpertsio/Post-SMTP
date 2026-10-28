const NotificationsChannelPlaceholder = ( { channel } ) => (
	<div className="ps-v2-notify-placeholder">
		<h3 className="ps-v2-notify-panel__title">{ channel?.label }</h3>
		<p className="ps-v2-notify-panel__sub">
			{ channel?.pro_locked
				? 'Upgrade to Post SMTP Pro to configure this notification channel in the revamp dashboard.'
				: 'This channel will be configurable here in an upcoming release. Your existing settings still apply to failure alerts when enabled in legacy settings.' }
		</p>
	</div>
);

export default NotificationsChannelPlaceholder;
