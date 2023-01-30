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

                jQuery( '#ps-migration-progress' ).val( response.data.left );

                if( response.data.left != 0 ) {

                    migrateLogs();

                }

            }
        });

    }

} );