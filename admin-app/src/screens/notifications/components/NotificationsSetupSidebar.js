import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';

const DOC_COPY = {
	admin_email: {
		title: 'Set Up Admin Email Failure Alerts',
		description: 'Get notified directly in your WordPress administrator inbox when Post SMTP encounters email delivery failures.',
		cta: 'View Admin Email Setup Docs',
	},
	chrome: {
		title: 'Set Up Chrome Extension Alerts',
		description: 'Install the Post SMTP Chrome extension and connect your UID to receive browser push alerts when email delivery fails.',
		cta: 'View Chrome Extension Docs',
	},
	slack: {
		title: 'Set Up Slack Failure Alerts',
		description: 'Connect a Slack incoming webhook so your team sees delivery failures without opening the Email Log.',
		cta: 'View Slack Setup Docs',
	},
	pushover: {
		title: 'Set Up Pushover Alerts',
		description: 'Configure Pushover user and app tokens to receive mobile failure notifications.',
		cta: 'View Pushover Setup Docs',
	},
	webhook: {
		title: 'Set Up Webhook Alerts',
		description: 'Send JSON failure payloads to your own HTTP endpoints when WordPress email stops delivering.',
		cta: 'View Webhook Setup Docs',
	},
	teams: {
		title: 'Set Up Microsoft Teams Alerts',
		description: 'Paste a Teams incoming webhook URL to post failure cards to your channel.',
		cta: 'View Teams Setup Docs',
	},
	twilio: {
		title: 'Set Up Twilio SMS Alerts',
		description: 'Configure Twilio credentials to receive SMS when Post SMTP detects a delivery failure.',
		cta: 'View Twilio Setup Docs',
	},
};

const NotificationsSetupSidebar = ( { slug, docUrl } ) => {
	const copy = DOC_COPY[ slug ] || DOC_COPY.admin_email;
	return (
		<div className="ps-v2-notify-aside__stack">
			<aside className="ps-v2-conn-helper-card">
				<div className="ps-v2-conn-helper-card__copy">
					<strong className="ps-v2-conn-helper-card__title">{ copy.title }</strong>
					<p>{ copy.description }</p>
				</div>
				{ docUrl && (
					<a href={ docUrl } className="ps-v2-conn-helper-card__doc-link" target="_blank" rel="noreferrer">
						<span>{ copy.cta }</span>
						<img src={ iconExternalLink } alt="" width="13" height="13" />
					</a>
				) }
			</aside>
		</div>
	);
};

export default NotificationsSetupSidebar;
