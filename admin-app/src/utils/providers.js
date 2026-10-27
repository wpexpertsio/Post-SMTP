import { formatTransportLabel } from './format';

const PROVIDER_ICONS = {
	gmail_api: 'gmail-pro-feature.svg',
	office365_api: 'office-pro-feature.svg',
	sendgrid_api: 'email.svg',
	sendinblue_api: 'email.svg',
	mailgun_api: 'email.svg',
	smtp: 'connectivity.svg',
};

export function providerIconSlug( provider ) {
	return PROVIDER_ICONS[ provider ] || null;
}

export function providerInitial( provider ) {
	const label = formatTransportLabel( provider );
	return label.charAt( 0 ) || '?';
}

export function connectionLabel( connection, fallbackTransport ) {
	if ( ! connection ) {
		return formatTransportLabel( fallbackTransport );
	}
	return connection.title || formatTransportLabel( connection.provider );
}

export function connectionEmail( connection, fallbackEmail ) {
	if ( ! connection ) {
		return fallbackEmail || '';
	}
	return connection.sender_email || connection.from_email || fallbackEmail || '';
}
