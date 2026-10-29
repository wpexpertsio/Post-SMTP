const UTM = 'utm_source=plugin&utm_medium=help_docs';

/**
 * @param {string} path Path after /documentation/ (no leading slash).
 */
export function helpDocUrl( path ) {
	const trimmed = path.replace( /^\/+/, '' );
	return `https://postmansmtp.com/documentation/${ trimmed }?${ UTM }`;
}

export const QUICK_SUPPORT_LINKS = [
	{
		id: 'documentation',
		title: 'Documentation',
		description: 'Every feature, explained',
		icon: 'documentation',
		url: `https://postmansmtp.com/documentation/?${ UTM }`,
		external: true,
	},
	{
		id: 'community',
		title: 'Community Support',
		description: 'Free forum on WordPress.org',
		icon: 'community',
		url: 'https://wordpress.org/support/plugin/post-smtp/',
		external: true,
	},
	{
		id: 'contact',
		title: 'Contact Support',
		description: 'Included with Pro',
		icon: 'contact',
		url: 'https://postmansmtp.com/pricing/',
		external: true,
	},
];

export const GUIDES = [
	{
		id: 'getting-started',
		title: 'Getting started with Post SMTP',
		description: 'Install, activate, and send your first email.',
		badge: '5 min read',
		url: helpDocUrl(
			'postman-smtp-documentation/activation-and-installation/'
		),
	},
	{
		id: 'gmail-oauth',
		title: 'Connect Gmail with secure sign-in',
		description: 'Use OAuth instead of app passwords.',
		badge: 'Video · 4 min',
		url: helpDocUrl( 'sockets-addons/gmail/' ),
	},
	{
		id: 'microsoft-365',
		title: 'Connect Microsoft 365',
		description: 'Send through the Microsoft Graph API.',
		badge: 'Pro · 8 min',
		url: helpDocUrl(
			'sockets-addons/how-to-configure-post-smtp-with-office-365/'
		),
	},
	{
		id: 'fix-failed',
		title: 'Fix failed emails',
		description: 'Read the email log and find the cause.',
		badge: '10 min read',
		url: helpDocUrl( 'postman-smtp-documentation/email-logs/' ),
	},
	{
		id: 'fallback',
		title: 'Add a fallback connection',
		description: 'Keep sending if your mailer goes down.',
		badge: 'Pro · 6 min',
		url: helpDocUrl(
			'postman-smtp-documentation/configure-fallback-connection/'
		),
	},
	{
		id: 'spf-dkim-dmarc',
		title: 'SPF, DKIM & DMARC',
		description: 'The three records that keep you out of spam.',
		badge: '12 min read',
		url: helpDocUrl(
			'deliverability/how-to-setup-spf-dkim-and-dmarc-records/'
		),
	},
];

export const EXPERT_SESSION = {
	title: 'Expert configuration session',
	body: 'Get 1:1 help setting up your mailer.',
	cta: 'Book now',
	url: 'https://postmansmtp.com/pricing/',
};
