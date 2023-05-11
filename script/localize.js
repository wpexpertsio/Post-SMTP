(function ($) {
    console.log('Post SMTP localize loaded.');
})(jQuery)

jQuery( document ).ready( function() {

    jQuery( document ).on( 'click', '.ps-db-update-notice .notice-dismiss', function( e ){

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ps-db-update-notice-dismiss',
                security: jQuery( '.ps-security' ).val()
            }
        })

    } ) 

} );