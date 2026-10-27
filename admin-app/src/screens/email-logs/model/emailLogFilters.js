export const DEFAULT_FILTERS = {
	q: '',
	status: 'all',
	mailer: 'all',
	date: 'all',
	form: 'all',
	page: '1',
	perPage: '50',
	logId: '',
};

const FILTER_KEYS = [ 'q', 'status', 'mailer', 'date', 'form', 'page', 'perPage', 'logId' ];

export function parseFiltersFromSearchParams( searchParams ) {
	const next = { ...DEFAULT_FILTERS };
	FILTER_KEYS.forEach( ( key ) => {
		const value = searchParams.get( key );
		if ( value !== null && value !== '' ) {
			next[ key ] = value;
		}
	} );
	return next;
}

export function filtersToQueryParams( filters ) {
	const params = new URLSearchParams();
	FILTER_KEYS.forEach( ( key ) => {
		const value = filters[ key ];
		const defaultValue = DEFAULT_FILTERS[ key ];
		if ( value && value !== defaultValue ) {
			params.set( key, String( value ) );
		}
	} );
	return params;
}

export function hasActiveFilters( filters ) {
	return FILTER_KEYS.some( ( key ) => {
		if ( key === 'page' || key === 'perPage' || key === 'logId' ) {
			return false;
		}
		return filters[ key ] && filters[ key ] !== DEFAULT_FILTERS[ key ];
	} );
}

export function filtersToApiParams( filters ) {
	return {
		search: filters.q || '',
		status: filters.status || 'all',
		mailer: filters.mailer || 'all',
		form: filters.form || 'all',
		date: filters.date || 'all',
		page: Number( filters.page ) || 1,
		per_page: Number( filters.perPage ) || 50,
	};
}

export function resetFilters( current ) {
	return {
		...DEFAULT_FILTERS,
		perPage: current.perPage || DEFAULT_FILTERS.perPage,
	};
}
