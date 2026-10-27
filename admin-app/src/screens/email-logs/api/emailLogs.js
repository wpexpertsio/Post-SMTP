import apiFetch from '../../../api/client';
import { filtersToApiParams } from '../model/emailLogFilters';

export function fetchEmailLogs( filters ) {
	const params = filtersToApiParams( filters );
	const query = new URLSearchParams();
	query.set( 'page', String( params.page ) );
	query.set( 'per_page', String( params.per_page ) );
	query.set( 'date', String( params.date || 'all' ) );
	Object.entries( params ).forEach( ( [ key, value ] ) => {
		if ( key === 'page' || key === 'per_page' || key === 'date' ) {
			return;
		}
		if ( value !== '' && value !== 'all' ) {
			query.set( key, String( value ) );
		}
	} );
	const qs = query.toString();
	return apiFetch( qs ? `email-logs?${ qs }` : 'email-logs' );
}

export function fetchEmailLogDetail( id ) {
	return apiFetch( `email-logs/${ id }` );
}

export function fetchEmailLogSettings() {
	return apiFetch( 'email-logs/settings' );
}

export function deleteEmailLogs( ids ) {
	return apiFetch( 'email-logs', {
		method: 'DELETE',
		body: { ids },
	} );
}

export function resendEmailLog( id ) {
	return apiFetch( `email-logs/${ id }/resend`, { method: 'POST', body: {} } );
}

export function exportEmailLogs( { format, ids, filters } ) {
	return apiFetch( 'email-logs/export', {
		method: 'POST',
		body: {
			format,
			ids: ids?.length ? ids : undefined,
			...filtersToApiParams( filters || {} ),
		},
	} );
}
