(function( $ ) {
	'use strict';

	$( document ).ready( function() {
		// Handle button click for all service-specific test notification buttons
		$( document ).on( 'click', '.postman-send-test-notification', function( e ) {
			e.preventDefault();

			var $button = $( this );
			var service = $button.data( 'service' );
			
			if ( ! service || $button.prop( 'disabled' ) ) {
				return;
			}

			// Get service-specific spinner and message elements
			var $spinner = $( '#postman-test-notification-spinner-' + service );
			var $message = $( '#postman-test-notification-message-' + service );

			// Disable button and show spinner
			$button.prop( 'disabled', true );
			$spinner.css( 'display', 'inline-block' ).addClass( 'is-active' );
			$message.empty();

			// Send AJAX request
			$.ajax( {
				url: postmanTestNotification.ajaxUrl,
				type: 'POST',
				data: {
					action: 'postman_send_test_notification',
					nonce: postmanTestNotification.nonce,
					service: service,
				},
				success: function( response ) {
					$spinner.css( 'display', 'none' ).removeClass( 'is-active' );
					$button.prop( 'disabled', false );

					if ( response.success ) {
						$message.html(
							'<div class="notice notice-success is-dismissible"><p><span class="dashicons dashicons-yes-alt"></span> ' +
							( response.data && response.data.message ? response.data.message : postmanTestNotification.strings.success ) +
							'</p></div>'
						);
					} else {
						var errorMessage = response.data && response.data.message ? response.data.message : postmanTestNotification.strings.error;
						$message.html(
							'<div class="notice notice-error is-dismissible"><p></span> ' +
							errorMessage +
							'</p></div>'
						);
					}
				},
				error: function( xhr, status, error ) {
					$spinner.css( 'display', 'none' ).removeClass( 'is-active' );
					$button.prop( 'disabled', false );

					var errorMessage = postmanTestNotification.strings.error;
					if ( xhr.status === 401 ) {
						errorMessage = postmanTestNotification.strings.unauthorized;
					}

					$message.html(
						'<div class="notice notice-error is-dismissible"><p></span> ' +
						errorMessage +
						'</p></div>'
					);
				},
			} );
		} );
	} );
})( jQuery );
