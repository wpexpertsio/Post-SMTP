/**
 * Figma connection row labels (985:17969).
 */
export function connectionDisplayLabel( connection ) {
	if ( ! connection ) {
		return '';
	}
	const map = {
		gmail_api: 'Gmail / Google Workspace',
		office365_api: 'Microsoft 365 / Outlook',
	};
	const provider = connection.provider || '';
	if ( map[ provider ] ) {
		return map[ provider ];
	}
	return connection.mailer_label || connection.label || '';
}
