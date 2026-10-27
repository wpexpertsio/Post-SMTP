import { formatTransportLabel } from '../../../utils/format';

/**
 * Secondary line under connection title (Figma 672:5182 rows).
 */
export function connectionSubtitle( connection ) {
	if ( ! connection ) {
		return '';
	}

	const email = connection.sender_email || connection.from_email || '';
	const host = connection.hostname || connection.host || '';
	const port = connection.port || '';
	const provider = connection.provider || '';

	if ( email ) {
		const mode = provider.includes( 'api' ) || provider.includes( 'mailgun' ) ? 'API' : '';
		if ( mode ) {
			return `${ email } · ${ mode }`;
		}
		return email;
	}

	if ( host ) {
		return port ? `${ host } · Port ${ port }` : host;
	}

	if ( provider.includes( 'api' ) || provider.includes( 'sendgrid' ) ) {
		return 'API key';
	}

	return formatTransportLabel( provider );
}
