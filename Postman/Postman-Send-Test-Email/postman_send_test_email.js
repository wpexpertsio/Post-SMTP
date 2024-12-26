( function( $ ) {
	$( document ).ready( function( $e ) {

		$( document ).on( 'click', '.send-test-email .ps-next-button', function( e ) {
			e.preventDefault();

			var $this = $( this );

			$( '#when-button-clicked' ).addClass( 'is-active' ).css( 'display', 'block' );
			$( this ).attr( 'disabled', 'disabled' );

			$( '.ps-line' ).removeClass( 'need-more-line' );
			$( '.ps-transcript' ).hide();

			let security = $( '#security' ).val(),
				email = $( '#postman_test_options_test_email' ).val();

			$.ajax( {
				url	    : ajaxurl,
				method  : 'POST',
				data    : {
					action	 : 'postman_send_test_email',
					security : security,
					email	 : email,
				},
				success : function( response ) {
					$( '.show-when-email-sent' ).css( 'display', 'block' );
					$( '#when-button-clicked' ).removeClass( 'is-active' ).css( 'display', 'none' );
					$this.removeAttr( 'disabled' );
					if ( ! response.success ) {
						var message = response.data.message;
						var icon    = '<span class="dashicons dashicons-no-alt"></span>';
						$( '.send-test-email .ps-success' ).empty();
						$( '.send-test-email .ps-error' ).html( icon + message );

						$( '#ps-transcript-container' ).hide();
						$( '#ps-show-transcript' ).hide();
					}

					if ( response.success ) {
						var message = response.data.message;
						var icon    = '<span class="dashicons dashicons-yes-alt"></span>';
						$( '.send-test-email .ps-error' ).empty();
						$( '.send-test-email .ps-success' ).html( icon + message );

						$( '#ps-transcript-container' ).show();
						$( '#ps-show-transcript' ).show();

						$( '.ps-transcript textarea' ).text( response.data.transcript );
						$( '.ps-ste-bm' ).addClass( 'birth-check' )
					}
				},
			} );
		} );

		$( document ).on( 'click', '.send-test-email #ps-show-transcript', function( e ) {
			e.preventDefault();

			$( '.ps-transcript' ).toggle();
			$( '.ps-line' ).toggleClass( 'need-more-line' );
		} );
	} );
} )( jQuery );