/**
 * Derive display role from index + fallback_enabled (mirrors PHP ConnectionService).
 */
export function deriveRole( index, connection, connections ) {
	if ( index === 0 ) {
		return { role: 'primary', fallbackPriority: null, roleLabel: 'Primary' };
	}
	if ( ! connection?.fallback_enabled ) {
		return { role: 'inactive', fallbackPriority: null, roleLabel: 'Inactive' };
	}
	let priority = 0;
	for ( let i = 1; i <= index; i++ ) {
		if ( connections[ i ]?.fallback_enabled ) {
			priority += 1;
		}
	}
	return {
		role: 'fallback',
		fallbackPriority: priority,
		roleLabel: `Fallback ${ priority }`,
	};
}
