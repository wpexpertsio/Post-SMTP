/**
 * WordPress-localized admin bootstrap (postSmtpAdmin).
 */
export function getAdminConfig() {
	return window.postSmtpAdmin || {};
}

export function getAdminUrls() {
	return getAdminConfig().adminUrls || {};
}
