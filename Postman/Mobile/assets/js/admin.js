jQuery( document ).ready( function() {
    jQuery( document ).on( 'click', '.ps-mobile-admin-notice .notice-dismiss', function( e ) {
        e.preventDefault();

        var dismissURL = jQuery( '.ps-mobile-notice-hidden-url' ).val();
        
        window.location.replace( dismissURL );
    } );
} )