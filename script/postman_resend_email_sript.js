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
	//			jQuery('span#resend-' + emailId).text(postman_js_resend_label);
			} else {
				alert(sprintf(postman_js_email_not_resent, response.data.message));
			}
		}).fail(function(response) {
			ajaxFailed(response);
		});
	}

})

