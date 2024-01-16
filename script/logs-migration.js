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

                var max = jQuery( '#ps-migration-progress' ).attr( 'max' );
                var migrated = response.data.migrated;

                jQuery( '#ps-migration-progress' ).val( migrated );
                jQuery( '#ps-progress' ).html( `${migrated}/ ${max}` );

                if( max == migrated ) {

                    jQuery( '#ps-migration-progress' ).val( max );
                    jQuery( '.ps-migration-box' ).slideUp();
                    location.reload()

                }

                else if( response.data.migrated ) {

                    jQuery('#ps-email-log').DataTable().ajax.reload();

                    migrateLogs();

                }

            }
        });

    }

} );