import {
	DEFAULT_FILTERS,
	filtersToQueryParams,
	hasActiveFilters,
	parseFiltersFromSearchParams,
	resetFilters,
} from './emailLogFilters';

describe( 'emailLogFilters', () => {
	it( 'parses search params with defaults', () => {
		const params = new URLSearchParams( 'q=test&status=failed&page=2' );
		const filters = parseFiltersFromSearchParams( params );
		expect( filters.q ).toBe( 'test' );
		expect( filters.status ).toBe( 'failed' );
		expect( filters.page ).toBe( '2' );
		expect( filters.perPage ).toBe( DEFAULT_FILTERS.perPage );
		expect( filters.date ).toBe( 'all' );
	} );

	it( 'omits default values from query string', () => {
		const qs = filtersToQueryParams( { ...DEFAULT_FILTERS, q: 'hello' } );
		expect( qs.get( 'q' ) ).toBe( 'hello' );
		expect( qs.has( 'status' ) ).toBe( false );
	} );

	it( 'detects active filters', () => {
		expect( hasActiveFilters( DEFAULT_FILTERS ) ).toBe( false );
		expect( hasActiveFilters( { ...DEFAULT_FILTERS, mailer: 'smtp' } ) ).toBe( true );
	} );

	it( 'resets filters but keeps perPage', () => {
		const next = resetFilters( { ...DEFAULT_FILTERS, q: 'x', perPage: '100' } );
		expect( next.q ).toBe( '' );
		expect( next.perPage ).toBe( '100' );
	} );
} );
