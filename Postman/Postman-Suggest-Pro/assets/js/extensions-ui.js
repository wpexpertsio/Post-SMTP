( function ( $ ) {
	$( document ).ready( function() {
		$( '.post-smtp-open-popup' ).on( 'click', function( e ) {
			e.preventDefault();

			$( '.post-smtp-popup-wrapper' ).css( { 'display': 'flex' } );
		} );

		$( '.post-smtp-socket-wrapper' ).on( 'click', function() {
			$( '.post-smtp-popup-wrapper' ).css( { 'display': 'flex' } );
		} );

		$( '.post-smtp-close-button' ).on( 'click', function() {
			$( '.post-smtp-popup-wrapper' ).css( { 'display': 'none' } );
		} );

	} );
} )( jQuery );