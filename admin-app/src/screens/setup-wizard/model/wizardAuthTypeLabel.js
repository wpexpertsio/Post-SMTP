const OAUTH_PROVIDERS = new Set( [ 'gmail_api', 'office365_api', 'zohomail_api' ] );

export const wizardAuthTypeLabel = ( provider = '' ) => {
	if ( OAUTH_PROVIDERS.has( provider ) ) {
		return 'OAuth 2.0';
	}
	if ( provider === 'smtp' || provider === 'default' ) {
		return 'SMTP';
	}
	return 'API key';
};