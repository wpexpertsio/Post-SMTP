jQuery(document).ready(function($) {

	$( '#the-list #postman-plugin-disbale-link' ).click(function(e) {
		e.preventDefault();

		var reason = $( '#postman-feedback-dialog-content .postman-reason' ),
			deactivateLink = $( this ).attr( 'href' );

	    $( "#postman-feedback-dialog-content" ).dialog({
	    	title: 'Post SMTP Feedback Form',
	    	dialogClass: 'postman-feedback-dialog-form',
	      	resizable: false,
	      	minWidth: 400,
	      	minHeight: 300,
	      	modal: true,
	      	buttons: {
	      		'skip' : {
		        	text: 'Skip',
		        	id: 'postman-feedback-dialog-skip',
		        	click: function() {
		          		$( this ).dialog( "close" );

		          		location.href = deactivateLink;
		        	}				      			
	      		},	      		
	      		'go' : {
		        	text: 'Continue',
		        	id: 'postman-feedback-dialog-go',
					class: 'button',
		        	click: function() {
		          		$( this ).dialog( "close" );

		          		var form = $( this ).find( 'form' ).serializeArray(),
							result = {};

						$.each( form, function() {
							if ( '' !== this.value )
						    	result[ this.name ] = this.value;										
						});

						if ( ! jQuery.isEmptyObject( result ) ) {
							result.action = 'post_user_feedback';

							$.post( post_feedback.admin_ajax, result, function(result) {

							});
						}

		          		// Remove this comment to deactivate plugin
		          		location.href = deactivateLink;
		        	},				      			
	      		},
	      		'cancel' : {
		        	text: 'Cancel',
		        	id: 'postman-feedback-dialog-cancel',
		        	class: 'button button-primary',
		        	click: function() {
		          		$( this ).dialog( "close" );
		        	}				      			
	      		}
	      	}
	    });

		reason.change(function() {
			$( '.postman-reason-input' ).hide();

			if ( $( this ).hasClass( 'postman-custom-input' ) ) {
				$( this ).find( '.postman-reason-input' ).show();
			}
		});
				    
	});
});