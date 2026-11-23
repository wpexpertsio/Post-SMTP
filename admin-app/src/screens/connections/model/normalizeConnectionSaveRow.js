/**
 * Align revamp configure-form fields with canonical keys before REST save.
 *
 * @param {Record<string, unknown>} row
 * @return {Record<string, unknown>}
 */
export function normalizeConnectionSaveRow( row ) {
	if ( ! row || typeof row !== 'object' ) {
		return {};
	}

	const out = { ...row };

	const connectionTitle =
		typeof out.connection_title === 'string' ? out.connection_title.trim() : '';
	if ( connectionTitle ) {
		out.title = connectionTitle;
		out.connection_title = connectionTitle;
	}

	const fromEmail = typeof out.from_email === 'string' ? out.from_email.trim() : '';
	if ( fromEmail ) {
		out.sender_email = fromEmail;
		out.from_email = fromEmail;
	}

	if ( Object.prototype.hasOwnProperty.call( out, 'from_name' ) ) {
		const fromName = typeof out.from_name === 'string' ? out.from_name.trim() : '';
		out.sender_name = fromName;
	}

	return out;
}