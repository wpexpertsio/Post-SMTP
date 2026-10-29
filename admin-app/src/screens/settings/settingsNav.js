export const SETTINGS_NAV = [
	{
		slug: 'general',
		label: 'General',
		figmaNode: '672:7182',
	},
	{
		slug: 'email-logs',
		label: 'Email Logs',
		figmaNode: '672:7610',
	},
	{
		slug: 'email-queue',
		label: 'Email Queue',
		figmaNode: '672:7781',
	},
	{
		slug: 'security',
		label: 'Security',
		figmaNode: '672:7296',
	},
	{
		slug: 'advanced',
		label: 'Advanced',
		figmaNode: '672:7495',
	},
	{
		slug: 'email-reporting',
		label: 'Email Reporting',
		figmaNode: '672:7419',
	},
];

export const DEFAULT_SETTINGS_SLUG = 'general';

export const PLACEHOLDER_COPY = {
	'email-logs': {
		title: 'Email Log',
		description: 'Save a copy of every email your site sends.',
	},
	'email-queue': {
		title: 'Email Queue',
		description: 'Queue outgoing mail and retry failed sends.',
	},
	security: {
		title: 'Security',
		description: 'Control how Post SMTP protects your mail configuration.',
	},
	advanced: {
		title: 'Advanced',
		description: 'Timeouts, logging level, and expert options.',
	},
	'email-reporting': {
		title: 'Email Reporting',
		description: 'Scheduled delivery reports and analytics.',
	},
};
