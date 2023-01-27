jQuery(document).ready(function($) {
	$('.postman-open-resend').on('click', function(e) {
		e.preventDefault();

		$(this).parent().next('div').fadeToggle();
	});

	$('.postman-resend').on('click', function(e) {
		e.preventDefault();

		var parent = $(this).closest('div'),
			mailField = $(this).prev('input'),
			emailId = mailField.data('id'),
			mail_to = mailField.val(),
			security = parent.find('input[name="security"]').val();


		postman_resend_email(emailId, mail_to, security);

	});

	function postman_resend_email(emailId, mail_to, security ) {
		var data = {
			'action' : 'postman_resend_mail',
			'email' : emailId,
			'mail_to' : mail_to,
			'security' : security
		};

		jQuery.post(ajaxurl, data, function(response) {
			if (response.success) {
				alert(response.data.message);
	//			jQuery('span#resend-' + emailId).text(post_smtp_localize.postman_js_resend_label);
			} else {
				alert(sprintf(post_smtp_localize.postman_js_email_not_resent, response.data.message));
			}
		}).fail(function(response) {
			ajaxFailed(response);
		});
	}

	var logsDTSecirity = jQuery( '#ps-email-log-nonce' ).val();

	var logsDT = $('#ps-email-log').DataTable( {

		processing: true,
        serverSide: true,
		ajax: {
			url: `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}`,
		},
		"lengthMenu": [25, 50, 100, 500],
		columns: [
			{ data: 'id' },
			{ data: 'original_subject' },
			{ data: 'original_to' },
			{ data: 'success' },
			{ data: 'solution' },
			{ data: 'time' }
		],
		columnDefs: [
			{ orderable: false, targets: 0 }
		],
		order: [
			[1, 'asc']
		],
		"createdRow": function ( row, data, index ) {

			jQuery( row  ).find( 'td' ).first().html( 
				`<input type="checkbox" value="${data['id']}" class="ps-email-log-cb" />`
			);

		}
	} );

	//Update Database
	jQuery( document ).on( 'click', '#ps-migrate-logs', function( e ){

		e.preventDefault();
		var security = jQuery( '#ps-migrate-logs' ).data( 'security' );

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				security: security,
				action: 'ps-migrate-logs',
			},

			success: function( data ) {
				
				jQuery( '.ps-db-update-notice' ).find( '.notice-dismiss' ).click();
				location.reload();

			}
		});

	} );

})