jQuery( document ).ready( function() {

    var security = jQuery( '.ps-security' ).val();

    migrateLogs();

    function migrateLogs() {

        jQuery.ajax({
            method: 'POST',
            url: ajaxurl,
            data: {
                action: 'ps-migrate-logs',
                security: security
            },
            success: function( response ) {

                var max = Number( jQuery( '#ps-migration-progress' ).attr( 'max' ) );
                var migrated = Number( response.data.migrated );

                jQuery( '#ps-migration-progress' ).val( response.data.migrated );
                jQuery( '#ps-progress' ).html( `${migrated}/ ${max}` );

                if( response.data.migrated == 'all' ) {

                    jQuery( '#ps-migration-progress' ).val( max );
                    jQuery( '.ps-migration-box' ).slideUp();
                    location.reload()

                }

                if( response.data.migrated ) {

                    jQuery('#ps-email-log').DataTable().ajax.reload();

                    migrateLogs();

                }

            }
        });

    }

} );