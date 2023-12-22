jQuery( document ).ready( function() {
    jQuery( '.ps-theme-mode' ).change( function () {
        const checked = jQuery( this ).is( ':checked' ); 
        
        if( !checked ) {
            jQuery( '.ps-dashboard' ).addClass( 'dark' );
        }
        if( checked ) {
            jQuery( '.ps-dashboard' ).removeClass( 'dark' );
        }
     } );

    if( jQuery( '.ps-dash-sort button:eq(0)' ).hasClass( 'active' ) ) {
        jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).addClass( 'ps-after-dn' );
    }
    if( jQuery( '.ps-dash-sort button:eq(2)' ).hasClass( 'active' ) ) {
        jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).addClass( 'ps-before-dn' );
    }

    jQuery( document ).on( 'click', '.ps-dash-sort button', function() {
        jQuery( '.ps-dash-sort button' ).removeClass( 'active' );
        jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).removeClass( 'ps-after-dn' );
        jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).removeClass( 'ps-before-dn' );
        jQuery( this ).addClass( 'active' );

        if( jQuery( '.ps-dash-sort button:eq(0)' ).hasClass( 'active' ) ) {
            jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).addClass( 'ps-after-dn' );
        }
        if( jQuery( '.ps-dash-sort button:eq(2)' ).hasClass( 'active' ) ) {
            jQuery( '.ps-dash-sort button:eq(1) .ps-sort-border' ).addClass( 'ps-before-dn' );
        }
    } );

    jQuery( document ).on( 'click', '.ps-slide-toggle', function(){
        if( jQuery( this ).hasClass( 'dashicons-arrow-down-alt2' ) ) {
            jQuery( this ).removeClass( 'dashicons-arrow-down-alt2' );
            jQuery( this ).addClass( 'dashicons-arrow-up-alt2' );
        }
        else if( jQuery( this ).hasClass( 'dashicons-arrow-up-alt2' ) ) {
            jQuery( this ).removeClass( 'dashicons-arrow-up-alt2' );
            jQuery( this ).addClass( 'dashicons-arrow-down-alt2' );
        }

        jQuery( this ).closest( '.ps-slide-header' ).siblings( '.ps-slide-body' ).slideToggle();
    } );
} )