const config = window.postSmtpAdmin || {};
const assets = config.assetsUrl || '';

export const IconDashboard = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<rect x="3" y="3" width="8" height="8" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
		<rect x="13" y="3" width="8" height="5" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
		<rect x="13" y="10" width="8" height="11" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
		<rect x="3" y="13" width="8" height="8" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
	</svg>
);

export const IconConnections = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M12 3v4M12 17v4M3 12h4M17 12h4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
		<circle cx="12" cy="12" r="4" stroke="currentColor" strokeWidth="1.8" />
	</svg>
);

export const IconSettings = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="1.8" />
		<path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
	</svg>
);

export const IconEmailLog = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" strokeWidth="1.8" />
		<path d="M3 7l9 6 9-6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
	</svg>
);

export const IconBell = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M12 3a5 5 0 00-5 5v3l-2 3h14l-2-3V8a5 5 0 00-5-5z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" />
		<path d="M10 20a2 2 0 004 0" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
	</svg>
);

export const IconTool = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M14 7l3 3-8 8H6v-3l8-8z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" />
		<path d="M16 5l3 3" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
	</svg>
);

export const IconSearch = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<circle cx="11" cy="11" r="6" stroke="currentColor" strokeWidth="1.8" />
		<path d="M20 20l-4-4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
	</svg>
);

export const IconBook = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M5 4h9a3 3 0 013 3v14H8a3 3 0 00-3 3V4z" stroke="currentColor" strokeWidth="1.8" />
		<path d="M8 4v17" stroke="currentColor" strokeWidth="1.8" />
	</svg>
);

export const IconHelp = () => (
	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.8" />
		<path d="M9.5 9a2.5 2.5 0 014.8 1c0 2-2.3 2-2.3 4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
		<circle cx="12" cy="17" r="1" fill="currentColor" />
	</svg>
);

export const IconCheck = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15" />
		<path d="M8 12.5l2.5 2.5L16 9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
	</svg>
);

export const IconLock = () => (
	<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" strokeWidth="1.8" />
		<path d="M8 10V8a4 4 0 118 0v2" stroke="currentColor" strokeWidth="1.8" />
	</svg>
);

export const IconPlane = () => (
	<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
		<path d="M3 12l18-7-4 8 4 8-18-7 6-2-6-2z" fill="currentColor" />
	</svg>
);

export const providerIcon = ( slug ) => `${ assets }${ slug }`;

export const PROVIDERS = [
	{ slug: 'gmail-pro-feature.svg', label: 'Gmail', tag: 'One-click' },
	{ slug: 'office-pro-feature.svg', label: 'Microsoft 365', tag: 'One-click' },
	{ slug: 'aws-pro-feature.svg', label: 'Amazon SES', tag: 'Integration' },
	{ slug: 'zoho-pro-feature.svg', label: 'Zoho Mail', tag: 'Integration' },
];

export const PRO_FEATURES = [
	'Email quotas',
	'Failure alerts',
	'Log attachments',
	'Delivery reports',
];
