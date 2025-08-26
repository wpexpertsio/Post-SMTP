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
            jQuery('#webhook_alert_urls').slideUp( 'fast' );
            jQuery('#email_notify').slideDown();
    
        }
    
        if ( selected == 'pushover' ) {
    
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#webhook_alert_urls').slideUp( 'fast' );
            jQuery('#pushover_cred').slideDown();
            
        }
    
        if ( selected == 'slack' ) {
    
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
            jQuery('#webhook_alert_urls').slideUp( 'fast' );
            jQuery('#slack_cred').slideDown();
            
        }

        if ( selected == 'webhook_alerts' ) {
    
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#webhook_alert_urls').slideDown();
            
        }
    
        if ( selected == 'none' ) {
    
            jQuery('#email_notify').slideUp( 'fast' );
            jQuery('#slack_cred').slideUp( 'fast' );
            jQuery('#pushover_cred').slideUp( 'fast' );
            jQuery('#webhook_alert_urls').slideDown();
    
        }
    
        Hook.call( 'post_smtp_notification_change', selected );
        
    });
    

    /**
     * Webhook Alerts
     * 
     * @since 3.0.0
     */
    jQuery( document ).on( 'click', '.post-smtp-add-webhook-url', function( e ) {
        e.preventDefault();

        var webhookElement = jQuery( '.post-smtp-webhook-urls' ).find( '.post-smtp-webhook-url-container' ).first().clone();
        webhookElement.find( 'input' ).val( '' );
        jQuery( webhookElement ).find( 'input' ).after( '<span class="post-smtp-remove-webhook-url dashicons dashicons-trash"></span>' );
    
        jQuery( '.post-smtp-webhook-urls' ).find( '.post-smtp-webhook-url-container' ).last().after( webhookElement );
    
    });

    jQuery( document ).on( 'click', '.post-smtp-remove-webhook-url', function() {
        jQuery( this ).closest( '.post-smtp-webhook-url-container' ).remove();
    } ); 

    jQuery( '.postman_sent_mail-count' ).closest( 'li' ).css( 'display', 'none' );

    //Discard less secure notification
	jQuery( document ).on( 'click', '#discard-less-secure-notification', function( e ) {
		e.preventDefault();

		jQuery.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'ps-discard-less-secure-notification',
				_wp_nonce: postmanPro.lessSecureNotice
			},
			success: function(data) {
				jQuery( '.ps-less-secure-notice .notice-dismiss' ).click();
            }
		} )

		jQuery( '.ps-less-secure-notice .notice-dismiss' ).click();
	} )


} );