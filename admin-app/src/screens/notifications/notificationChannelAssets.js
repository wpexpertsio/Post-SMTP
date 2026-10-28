import navAdminEmail from '../../assets/notifications/nav-admin-email.svg';
import navChrome from '../../assets/notifications/nav-chrome.png';
import navSlack from '../../assets/notifications/nav-slack.png';
import navPushover from '../../assets/notifications/nav-pushover.png';
import navWebhook from '../../assets/notifications/nav-webhook.png';
import navTeams from '../../assets/notifications/nav-teams.png';
import navTwilio from '../../assets/notifications/nav-twilio.png';
import headerAdminEmail from '../../assets/notifications/header-admin-email.svg';
import iconTestSend from '../../assets/notifications/icon-test-send.svg';

export const CHANNEL_NAV_ICONS = {
	admin_email: navAdminEmail,
	chrome: navChrome,
	slack: navSlack,
	pushover: navPushover,
	webhook: navWebhook,
};

export const CHANNEL_PAGE_HEADERS = {
	admin_email: {
		icon: headerAdminEmail,
		title: 'Admin Email',
		subtitle: 'Email your WordPress admin when a send fails.',
	},
	chrome: {
		icon: navChrome,
		iconFramed: true,
		title: 'Chrome Extension',
		subtitle: 'Get failure alerts in Chrome with the Post SMTP extension.',
	},
	slack: {
		icon: navSlack,
		iconFramed: true,
		title: 'Slack',
		subtitle: 'Send failure alerts into a Slack channel.',
	},
	pushover: {
		icon: navPushover,
		iconFramed: true,
		title: 'Pushover',
		subtitle: 'Push failure alerts to your phone with Pushover.',
	},
	webhook: {
		icon: navWebhook,
		iconFramed: true,
		title: 'Webhook',
		subtitle: 'Send alerts to any URL (Zapier, Make, or your app).',
	},
};

export { iconTestSend, navTeams, navTwilio };
