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

	//Date Filter
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

	//Check All
	jQuery( document ).on( 'click', '.ps-email-log-select-all', function( e ) {

		var selectedValue = jQuery('#ps-email-log_length').find( 'select' ).find(":selected").text();

		if( this.checked ) {

			jQuery( '.ps-email-log-cb' ).prop( 'checked', true );
			jQuery( '.ps-email-log-select-all' ).prop( 'checked', true );

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export Selected (${selectedValue})` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete Selected (${selectedValue})` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn' ).addClass( 'ps-selected' );

		}
		else {

			jQuery( '.ps-email-log-cb' ).prop( 'checked', false );
			jQuery( '.ps-email-log-select-all' ).prop( 'checked', false );

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export All` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete All` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn' ).removeClass( 'ps-selected' );

		}
		

	} );

	//Check Individual
	jQuery( document ).on( 'click', '.ps-email-log-cb', function( e ) {

		var totalCheckboxes = jQuery( '.ps-email-log-cb' ).length;
		var checkboxes = jQuery( '.ps-email-log-cb' );
		var checkedCounter = 0;

		if( !this.checked ) {

			jQuery( '.ps-email-log-select-all' ).prop( 'checked', false );

		}

		for( var i = 0; i < totalCheckboxes; i++ ) {

			if( jQuery( checkboxes )[i].checked ) {

				checkedCounter = checkedCounter + 1;

			}

		}

		if( checkedCounter == totalCheckboxes ) {

			jQuery( '.ps-email-log-select-all' ).prop( 'checked', true );

		}

		jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export Selected (${checkedCounter})` );
		jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete Selected (${checkedCounter})` );
		jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn' ).addClass( 'ps-selected' );
		
		if( checkedCounter == 0 ) {

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export All` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete All` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn' ).removeClass( 'ps-selected' );

		}

	} );

	//Export
	jQuery( document ).on( 'click', '.ps-email-log-export-btn', function( e ) { 

		console.log( 'export' );

	} );

	//Delete
	jQuery( document ).on( 'click', '.ps-email-log-delete-btn', function( e ) { 

		var selected = [];

		if( jQuery( this ).hasClass( 'ps-selected' ) ) {

			jQuery( '.ps-email-log-cb' ).each( function( i, el ) {

				if( jQuery( el ).is( ':checked' ) ) {

					selected.push( jQuery( el ).val() );

				}

			} );

		}


		jQuery.ajax( {

			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ps-delete-email-logs',
				security: logsDTSecirity,
				selected: selected
			},
			success: function( response ) {

				logsDT.ajax.reload();

			}

		} );

		

	} );

})