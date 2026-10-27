export const formatTransportLabel = ( type ) =>
	( type || 'default' )
		.replace( /_/g, ' ' )
		.replace( /\b\w/g, ( char ) => char.toUpperCase() );

/** Figma recent emails table — e.g. Aug 24, 2026, 11:11 AM */
export const formatEmailLogDate = ( timeString ) => {
	if ( ! timeString ) {
		return '';
	}
	const parsed = Date.parse( timeString );
	if ( Number.isNaN( parsed ) ) {
		return timeString;
	}
	const parts = new Intl.DateTimeFormat( 'en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} ).formatToParts( new Date( parsed ) );

	const get = ( type ) => parts.find( ( p ) => p.type === type )?.value ?? '';
	return `${ get( 'month' ) } ${ get( 'day' ) }, ${ get( 'year' ) }, ${ get( 'hour' ) }:${ get( 'minute' ) } ${ get( 'dayPeriod' ) }`;
};

export const relativeTime = ( timeString ) => {
	if ( ! timeString ) {
		return '';
	}
	const parsed = Date.parse( timeString );
	if ( Number.isNaN( parsed ) ) {
		return timeString;
	}
	const diff = Date.now() - parsed;
	const minutes = Math.floor( diff / 60000 );
	if ( minutes < 60 ) {
		return `${ minutes || 1 } min ago`;
	}
	const hours = Math.floor( minutes / 60 );
	if ( hours < 24 ) {
		return `${ hours } h ago`;
	}
	return `${ Math.floor( hours / 24 ) } d ago`;
};

export const domainFromSiteUrl = ( url ) => {
	try {
		return new URL( url ).hostname.replace( /^www\./, '' );
	} catch {
		return 'your-domain.com';
	}
};

export const deliveryRate = ( success, total ) => {
	if ( ! total ) {
		return '—';
	}
	return `${ ( ( success / total ) * 100 ).toFixed( 1 ) }%`;
};
