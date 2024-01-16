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
	      		'go' : {
		        	text: 'Continue',
        			icons: { primary: "dashicons dashicons-update" },        	
		        	id: 'postman-feedback-dialog-go',
					class: 'button',
		        	click: function() {

		        		var dialog = $(this),
		        			go = $('#postman-feedback-dialog-go'),
		          			form = dialog.find( 'form' ).serializeArray(),
							result = {};

						$.each( form, function() {
							if ( '' !== this.value )
						    	result[ this.name ] = this.value;										
						});

						if ( ! jQuery.isEmptyObject( result ) ) {
							result.action = 'post_user_feedback';

						    $.ajax({
						        url: post_feedback.admin_ajax,
						        type: 'POST',
						        data: result,
						        error: function(){},
						        success: function(msg){},
						        beforeSend: function() { 
						        	go.addClass('postman-ajax-progress'); 
						        },
						        complete: function() { 
						        	go.removeClass('postman-ajax-progress'); 
			        	
						        	dialog.dialog( "close" );
						            location.href = deactivateLink;						        	
						        }						        
						    });		
	
						}


		        	},				      			
	      		},
	      		'cancel' : {
		        	text: 'Cancel',
		        	id: 'postman-feedback-dialog-cancel',
		        	class: 'button button-primary',
		        	click: function() {
		          		$( this ).dialog( "close" );
		        	}				      			
	      		},
	      		'skip' : {
		        	text: 'Skip',
		        	id: 'postman-feedback-dialog-skip',
		        	click: function() {
		          		$( this ).dialog( "close" );

		          		location.href = deactivateLink;
		        	}				      			
	      		},		      		
	      	}
	    });

		reason.change(function() {
			$( '.postman-reason-input' ).hide();

			if ( $( this ).hasClass( 'postman-custom-input' ) ) {
				var reason = $(this).find('input').data('reason');
				var wrap = $( '#postman-deactivate-reasons' ).next( '.postman-reason-input' );
				var input = wrap.find('input');
				console.log(input);

				if ( reason ) {
					input.attr('placeholder',reason);
				} else {
					input.attr('placeholder','Do you mind help and give more detailes?');
				}
				wrap.show();
			}

			if ( $( this ).hasClass( 'postman-support-input' ) ) {
				$( this ).find( '.postman-reason-input' ).show();
			}			
		});
				    
	});
});