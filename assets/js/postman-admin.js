jQuery( document ).ready( function() {

    /**
     * Wizard And Settings Notification Hide & Seek :D
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    jQuery( '.input_notification_service' ).change(function() {
				
        var selected = jQuery( this ).val();
    
        if ( selected == 'default' ) {
    
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
            jQuery('#email_notify').slideDown();
    
        }
    
        if ( selected == 'pushover' ) {
    
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#pushover_cred').slideDown();
            
        }
    
        if ( selected == 'slack' ) {
    
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
            jQuery('#slack_cred').slideDown();
            
        }
    
        if ( selected == 'none' ) {
    
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
    
        }
    
        Hook.call( 'post_smtp_notification_change', selected );
        
    });

} );