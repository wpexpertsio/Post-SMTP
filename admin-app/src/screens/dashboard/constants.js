export const STATS_PERIODS = [
	{ id: 'day', label: 'Today' },
	{ id: 'week', label: 'Last 7 Days' },
	{ id: 'month', label: 'Last 30 Days' },
];

export const DOMAIN_CHECKS_PLACEHOLDER = [
	{ id: 'spf', label: 'SPF', status: 'pass', description: 'Your domain is set up to send mail.' },
	{ id: 'dkim', label: 'DKIM', status: 'pass', description: 'Your messages are signed.' },
	{ id: 'dmarc', label: 'DMARC', status: 'fail', description: 'Add a DMARC record to protect your domain.' },
];
