/**
 * WordPress prints admin_notices after the React root in #wpbody-content.
 * Move them to the top of the SPA so they appear above the revamp UI.
 */
export function relocateWpAdminNotices() {
	const mount = document.getElementById( 'post-smtp-admin-root' );
	const wpbody = document.getElementById( 'wpbody-content' );
	if ( ! mount || ! wpbody ) {
		return;
	}

	let slot = mount.querySelector( '.ps-v2-wp-admin-notices' );
	if ( ! slot ) {
		slot = document.createElement( 'div' );
		slot.className = 'ps-v2-wp-admin-notices';
		slot.setAttribute( 'aria-live', 'polite' );
		mount.prepend( slot );
	}

	const selector = [
		'.notice',
		'.update-nag',
		'.updated',
		'.error',
		'div.fs-notice',
	].join( ', ' );

	wpbody.querySelectorAll( selector ).forEach( ( node ) => {
		if ( node.closest( '#post-smtp-admin-root' ) ) {
			return;
		}
		if ( node.closest( '.ps-v2-wp-admin-notices' ) ) {
			return;
		}
		// Skip empty wrappers left by other plugins.
		if ( node.classList.contains( 'notice' ) && ! node.textContent?.trim() ) {
			return;
		}
		slot.appendChild( node );
	} );
}
