/**
 * Setup wizard step 1 mailer grid (Figma 693:20457). Separate from Connections layout.
 *
 * @type {string[][]}
 */
export const WIZARD_MAILER_GRID_ROWS = [
	[ 'office365_api', 'amazon_ses_api', 'zohomail_api' ],
	[ 'gmail_api', 'postmark_api', 'sendinblue_api' ],
	[ 'smtp_com_api', 'sendgrid_api', 'maileroo_api' ],
	[ 'emailit_api', 'sweego_api', 'resend_api', 'mailtrap_api' ],
	[ 'sendpulse_api', 'mailersend_api', 'smtp2go_api', 'smtp' ],
	[ 'elasticemail_api', 'sparkpost_api', 'cloudflare_email_api', 'mailgun_api' ],
];

/**
 * @param {Array<{slug:string}>} items Catalog from REST.
 * @return {Array<Array<object>>}
 */
export function buildWizardMailerGrid( items ) {
	const bySlug = Object.fromEntries( items.map( ( item ) => [ item.slug, item ] ) );
	const resolve = ( slugs ) => slugs.map( ( slug ) => bySlug[ slug ] ).filter( Boolean );

	return WIZARD_MAILER_GRID_ROWS.map( ( slugRow ) => resolve( slugRow ) ).filter( ( row ) => row.length > 0 );
}