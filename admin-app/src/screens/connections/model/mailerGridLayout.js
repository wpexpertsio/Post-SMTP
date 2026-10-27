/**
 * Mailer grid order for Connections → Choose a mailer (Figma 672:5745).
 *
 * Reorder by moving slugs between rows or within a row. Each inner array is one
 * visual row (3 columns). "More" mailers appear after expanding Show more mailers.
 */

/** @type {string[][]} Primary grid — edit row arrays to rearrange the layout. */
export const MAILER_GRID_ROWS = [
	[ 'office365_api', 'amazon_ses_api', 'zohomail_api' ],
	[ 'gmail_api', 'postmark_api', 'sendinblue_api' ],
	[ 'smtp_com_api', 'sendgrid_api', 'maileroo_api' ],
	[ 'emailit_api', 'sweego_api', 'resend_api' ],
];

/**
 * Slugs revealed by "Show more mailers" (order = display order, chunked by 3).
 *
 * @type {string[]}
 */
export const MAILER_MORE_SLUGS = [
	'mailgun_api',
	'smtp',
	'mailtrap_api',
	'elasticemail_api',
	'smtp2go_api',
	'sparkpost_api',
	'sendpulse_api',
	'mailersend_api',
	'mandrill_api', // slug matches wizard / legacy `mandrill` transport mapping
];

/** Flat list derived from rows (for sorting API catalog items). */
export const MAILER_PRIMARY_SLUGS = MAILER_GRID_ROWS.flat();

const COLS = 3;

/**
 * @param {string[]} slugs
 * @return {string[][]}
 */
export function chunkSlugs( slugs ) {
	const rows = [];
	for ( let i = 0; i < slugs.length; i += COLS ) {
		rows.push( slugs.slice( i, i + COLS ) );
	}
	return rows;
}

/**
 * @param {Array<{slug:string}>} items Catalog from REST.
 * @param {boolean} expanded Whether "more" mailers are visible.
 * @return {{ rows: Array<Array<object>>, moreItems: Array<object>, hiddenMore: Array<object> }}
 */
export function buildMailerGrid( items, expanded ) {
	const bySlug = Object.fromEntries( items.map( ( item ) => [ item.slug, item ] ) );

	const resolve = ( slugs ) =>
		slugs.map( ( slug ) => bySlug[ slug ] ).filter( Boolean );

	const primaryItems = resolve( MAILER_PRIMARY_SLUGS );
	const moreItems = resolve( MAILER_MORE_SLUGS );

	const visibleSlugs = expanded
		? [ ...MAILER_PRIMARY_SLUGS, ...MAILER_MORE_SLUGS ]
		: MAILER_PRIMARY_SLUGS;

	const rows = chunkSlugs( visibleSlugs )
		.map( ( slugRow ) => resolve( slugRow ) )
		.filter( ( row ) => row.length > 0 );

	return {
		rows,
		moreItems,
		hiddenMore: expanded ? [] : moreItems,
	};
}
