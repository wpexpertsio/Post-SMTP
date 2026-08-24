const config = window.postSmtpAdmin || {};

export async function apiFetch( path, options = {} ) {
	const url = ( config.apiRoot || '/wp-json/post-smtp/v2/' ) + path.replace( /^\//, '' );
	const headers = {
		'Content-Type': 'application/json',
		'X-WP-Nonce': config.nonce || '',
		...( options.headers || {} ),
	};

	const response = await fetch( url, {
		...options,
		headers,
		body: options.body ? JSON.stringify( options.body ) : undefined,
	} );

	const data = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		throw new Error( data.message || 'Request failed' );
	}
	return data;
}

export default apiFetch;
