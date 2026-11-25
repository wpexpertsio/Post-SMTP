/** Value for `ps_return_route` when OAuth started from setup wizard step 2. */
export const OAUTH_PS_RETURN_ROUTE_SETUP_WIZARD = 'setup-wizard';

/** Value for `ps_return_route` when OAuth started from Connections edit/add. */
export const OAUTH_PS_RETURN_ROUTE_CONNECTION_EDIT = 'connection-edit';

const VALID_PS_RETURN_ROUTES = new Set( [
	OAUTH_PS_RETURN_ROUTE_SETUP_WIZARD,
	OAUTH_PS_RETURN_ROUTE_CONNECTION_EDIT,
] );

export function appendOAuthReturnRouteToUrl( baseUrl, oauthReturnRoute ) {
	if ( ! baseUrl || ! VALID_PS_RETURN_ROUTES.has( oauthReturnRoute ) ) {
		return baseUrl;
	}
	try {
		const url = new URL( baseUrl, window.location.origin );
		url.searchParams.set( 'ps_return_route', oauthReturnRoute );
		return url.toString();
	} catch {
		const separator = baseUrl.includes( '?' ) ? '&' : '?';
		return `${ baseUrl }${ separator }ps_return_route=${ encodeURIComponent( oauthReturnRoute ) }`;
	}
}

export function appendOAuthReturnRouteToParams( params, oauthReturnRoute ) {
	if ( ! VALID_PS_RETURN_ROUTES.has( oauthReturnRoute ) || ! params ) {
		return;
	}
	if ( typeof params.append === 'function' ) {
		params.append( 'ps_return_route', oauthReturnRoute );
	} else if ( typeof params.set === 'function' ) {
		params.set( 'ps_return_route', oauthReturnRoute );
	}
}