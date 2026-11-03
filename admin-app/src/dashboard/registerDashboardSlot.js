const slots = {};

function dispatchRegistered( name ) {
	if ( typeof window !== 'undefined' ) {
		window.dispatchEvent(
			new CustomEvent( 'post-smtp-dashboard-slot-registered', { detail: { name } } )
		);
	}
}

export function registerDashboardSlot( name, component ) {
	if ( ! name || ! component ) {
		return;
	}
	slots[ name ] = component;
	dispatchRegistered( name );
}

export function getDashboardSlot( name ) {
	return slots[ name ] || null;
}

function flushPendingDashboardSlots() {
	if ( typeof window === 'undefined' || ! window.postSmtpPendingDashboardSlots ) {
		return;
	}
	const pending = window.postSmtpPendingDashboardSlots;
	window.postSmtpPendingDashboardSlots = null;
	Object.keys( pending ).forEach( ( name ) => {
		registerDashboardSlot( name, pending[ name ] );
	} );
}

if ( typeof window !== 'undefined' ) {
	window.postSmtpRegisterDashboardSlot = registerDashboardSlot;
	flushPendingDashboardSlots();
}
