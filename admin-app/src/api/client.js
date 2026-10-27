import { getAdminConfig } from '../config/admin';

const config = getAdminConfig();

export async function apiFetch( path, options = {} ) {
	const url = ( config.apiRoot || '/wp-json/post-smtp/v2/' ) + path.replace( /^\//, '' );
	const headers = {
		'Content-Type': 'application/json',
		'X-WP-Nonce': config.nonce || '',
		...( options.headers || {} ),
	};

	const { method = 'GET', body, ...rest } = options;
	const response = await fetch( url, {
		...rest,
		method,
		headers,
		body: body !== undefined ? JSON.stringify( body ) : undefined,
	} );

	const data = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		throw new Error( data.message || 'Request failed' );
	}
	return data;
}

export default apiFetch;
