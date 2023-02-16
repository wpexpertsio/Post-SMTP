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

		language: {
			"search": "_INPUT_", 
			searchPlaceholder: "Search"
		},

		"fnDrawCallback":function(){
            $("input[type='search']").attr("id", "searchBox");
            $('#dialPlanListTable').css('cssText', "margin-top: 0px !important;");
            $("select[name='dialPlanListTable_length'], #searchBox").removeClass("input-sm");
            $('#searchBox').css("width", "300px").focus();
            $('#dialPlanListTable_filter').removeClass('dataTables_filter');
        },

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
			{ data: 'time' },
			{ data: 'solution' },
			{ data: 'success' },
			{ data: 'actions' }
		],
		columnDefs: [
			{ orderable: false, targets: 0 },
			{ orderable: false, targets: 6 },
		],
		order: [
			[0, 'desc']
		],
		"createdRow": function ( row, data, index ) {
			
			var id = data['id'];
			var status = jQuery( row ).find( 'td' )[5];

			jQuery( row ).find( 'td' ).first().html( 
				`<input type="checkbox" value="${data['id']}" class="ps-email-log-cb" />`
			);

			jQuery( row ).find( 'td' ).last().html( `
				<div class="ps-email-log-actions">
					<a href="#" class="ps-email-log-view">View</a>
					<a href="#" class="ps-email-log-resend">Resend</a>
					<a href="#" class="ps-email-log-transcript">Transcript</a>
					<a href="#" class="ps-email-log-delete">Delete</a>
				</div>
			` );

			if( data['success'] == '<span>Success</span>' ) {

				jQuery( status ).addClass( 'ps-email-log-status-success' );

			}
			else {

				jQuery( status ).addClass( 'ps-email-log-status-failed' );

			}
		}
	} );

	jQuery( '#ps-email-log_filter' ).after( `
		<div class="ps-email-log-date-filter">
			<label>From <input type="date" class="ps-email-log-from" /></label>
			<label>To <input type="date" class="ps-email-log-to" /></label>
		</div>
	` );

	jQuery( '.ps-email-log-date-filter' ).after( `
		<div class="ps-email-log-top-buttons">
			<button class="button button-primary ps-email-log-export-btn"><span class="ps-btn-text">Export All</span> <span class="dashicons dashicons-admin-page"></span></button>
			<button class="button button-primary ps-email-log-delete-btn"><span class="ps-btn-text">Delete All</span> <span class="dashicons dashicons-trash"></span></button>
		</div>
		<div class="clear"></div>
	` );

	jQuery( document ).on( 'change', '.ps-email-log-from, .ps-email-log-to', function() {

		var from = jQuery( '.ps-email-log-from' ).val();
		var to = jQuery( '.ps-email-log-to' ).val();

		if( from && to ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&from=${from}&to=${to}` ).load();

		}
		else if( from ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&from=${from}` ).load();

		}
		else if( to ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&to=${to}` ).load();

		}
		else {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}` ).load();

		}

	} );

})