const config = window.postSmtpAdmin || {};

async function dashboardFetch( path, options = {} ) {
	const base = ( config.dashboardApi || '/wp-json/psd/v1/' ).replace( /\/?$/, '/' );
	const url = base + path.replace( /^\//, '' );
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

export function fetchEmailStats( period = 'day' ) {
	return dashboardFetch( `email-count?period=${ period }` );
}

export function fetchRecentLogs() {
	return dashboardFetch( 'get-logs' );
}

export default dashboardFetch;
