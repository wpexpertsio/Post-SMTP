import { useEffect } from '@wordpress/element';

/**
 * Blocks interaction with the preview dashboard until migration finishes.
 * Uses capture listeners so removing overlay CSS in devtools does not re-enable the UI.
 */
export function useMigrationWizardLock( isLocked ) {
	useEffect( () => {
		const root = document.getElementById( 'post-smtp-admin-root' );
		if ( ! isLocked || ! root ) {
			root?.classList.remove( 'ps-migration-wizard-root--locked' );
			return undefined;
		}

		root.classList.add( 'ps-migration-wizard-root--locked' );

		const allowInteraction = ( target ) => {
			return target instanceof Element && Boolean( target.closest( '.ps-migration-overlay' ) );
		};

		const blockPointer = ( event ) => {
			if ( ! root.contains( event.target ) ) {
				return;
			}
			if ( allowInteraction( event.target ) ) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			event.stopImmediatePropagation();
		};

		const blockKeys = ( event ) => {
			if ( ! root.contains( event.target ) && ! root.contains( document.activeElement ) ) {
				return;
			}
			if ( allowInteraction( event.target ) || allowInteraction( document.activeElement ) ) {
				return;
			}
			if (
				event.key === 'Tab' ||
				event.key === 'Enter' ||
				event.key === ' ' ||
				event.key === 'Escape'
			) {
				event.preventDefault();
				event.stopPropagation();
			}
		};

		const pointerEvents = [ 'click', 'mousedown', 'mouseup', 'touchstart', 'touchend', 'pointerdown' ];
		pointerEvents.forEach( ( name ) => {
			root.addEventListener( name, blockPointer, true );
		} );
		root.addEventListener( 'keydown', blockKeys, true );

		return () => {
			root.classList.remove( 'ps-migration-wizard-root--locked' );
			pointerEvents.forEach( ( name ) => {
				root.removeEventListener( name, blockPointer, true );
			} );
			root.removeEventListener( 'keydown', blockKeys, true );
		};
	}, [ isLocked ] );
}
