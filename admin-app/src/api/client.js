import { getAdminConfig } from '../config/admin';

const config = getAdminConfig();

export async function apiFetch( path, options = {} ) {
	const url = ( config.apiRoot || '/wp-json/post-smtp/v2/' ) + path.replace( /^\//, '' );
	const headers = {
		'Content-Type': 'application/json',
		'X-WP-Nonce': config.nonce || '',
		...( options.headers || {} ),
	};

	const { method = 'GET', body, data: requestData, ...rest } = options;
	const payload = body !== undefined ? body : requestData;
	const response = await fetch( url, {
		...rest,
		method,
		headers,
		body: payload !== undefined ? JSON.stringify( payload ) : undefined,
	} );

	const responseData = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		const errMessage =
			( typeof responseData.message === 'string' && responseData.message ) ||
			( responseData.success === false && typeof responseData.message === 'string'
				? responseData.message
				: '' ) ||
			'Request failed';
		throw new Error( errMessage );
	}
	return responseData;
}

export default apiFetch;
