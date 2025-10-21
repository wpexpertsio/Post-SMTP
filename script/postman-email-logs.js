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
		columns: PSEmailLogs.DTCols,
		columnDefs: [
			{ orderable: false, targets: 0 },
			{ orderable: false, targets: 5 },
		],
		order: [
			[3, 'desc']
		],
		"createdRow": function ( row, data, index ) {
			var id = data['id'];
			var status = jQuery( row ).find( 'td' )[4];

			jQuery( row ).find( 'td' ).first().html( 
				`<input type="checkbox" value="${data['id']}" class="ps-email-log-cb" />`
			);

			jQuery( row ).find( 'td:nth-child(6)' ).html( `
				<div class="ps-email-log-actions-container">
					<a href="#" class="ps-email-log-view ps-popup-btn">View</a>
					<a href="#" class="ps-email-log-resend">Resend</a>
					<a href="#" class="ps-email-log-transcript ps-popup-btn">Transcript</a>
					<a href="#" class="ps-email-log-delete">Delete</a>
				</div>
				<div class="ps-email-log-resend-container"></div>
			` );

			jQuery( row ).find( 'td:nth-child(3)').attr( 'title', data['original_to'] );
			
			// Display each email on a new line, keeping the comma			
			jQuery(row).find('td:nth-child(3)').html(
				data['original_to']
					? data['original_to'].replace(/,\s*/g, ',<br>')
					: ''
			);


			if( data['success'] == '<span title="Success">Success</span>' ) {

				jQuery( status ).addClass( 'ps-email-log-status-success' );

			}
			else if( data['success'] == '<span title="In Queue">In Queue</span>' ) {

				jQuery( status ).addClass( 'ps-email-log-status-queued' );

			} else if( data['success'] == '<span title="Sent ( ** Fallback ** )">Success</span><a href="#" class="ps-status-log ps-popup-btn">View details</a>' ) {

				jQuery( status ).addClass( 'ps-email-log-status-success' );

			}
			else {

				jQuery( status ).addClass( 'ps-email-log-status-failed' );

			}
		},

		drawCallback: function () {

            var event = new Event('postSMTPEmailLogsDTDrawn');
            document.dispatchEvent(event);

        }
		
	} );

	jQuery( '#ps-email-log_filter' ).after( `
		<div class="ps-email-log-date-filter">
			<label>From <input type="date" class="ps-email-log-from" /></label>
			<label>To <input type="date" class="ps-email-log-to" /></label>
			<span class="ps-refresh-logs" title="refresh logs"><span class="dashicons dashicons-image-rotate"></span></span>
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
		var status = jQuery( '.ps-status-btn.active' ).data( 'status' );
		status = status === 'all' ? '' : `&status=${status}`;

		if( from && to ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&from=${from}&to=${to}${status}` ).load();

		}
		else if( from ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&from=${from}${status}` ).load();

		}
		else if( to ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&to=${to}${status}` ).load();

		}
		else {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}${status}` ).load();

		}

	} );

	// Status Buttons
	jQuery( '#ps-email-log' ).before( `
		<div class="ps-email-log-status-buttons">
			<button class="button ps-status-btn active" data-status="all">All logs</button>
			<button class="button ps-status-btn" data-status="success">Success</button>
			<button class="button ps-status-btn" data-status="failed">Failed</button>
		</div>
	` );

	// Status Filter
	jQuery( document ).on( 'click', '.ps-status-btn', function() {

		jQuery( '.ps-status-btn' ).removeClass( 'active' );
		jQuery( this ).addClass( 'active' );
		var status = jQuery( this ).data( 'status' );
		var from = jQuery( '.ps-email-log-from' ).val();
		var to = jQuery( '.ps-email-log-to' ).val();
	
		if( status == 'all' ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&from=${from}&to=${to}` ).load();

		}
		else {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&status=${status}&from=${from}&to=${to}` ).load();

		}

	} );

	//Check All
	jQuery( document ).on( 'click', '.ps-email-log-select-all', function( e ) {

		var selectedValue = jQuery( '.ps-email-log-cb' ).length;

		if( this.checked ) {

			jQuery( '.ps-email-log-cb' ).prop( 'checked', true );
			jQuery( '.ps-email-log-select-all' ).prop( 'checked', true );

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export Selected (${selectedValue})` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete Selected (${selectedValue})` );
			jQuery( '#ps-aedl-bulk-resend' ).text( `Resend Selected (${selectedValue})` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn, #ps-aedl-bulk-resend' ).addClass( 'ps-selected' );

		}
		else {

			jQuery( '.ps-email-log-cb' ).prop( 'checked', false );
			jQuery( '.ps-email-log-select-all' ).prop( 'checked', false );

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export All` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete All` );
			jQuery( '#ps-aedl-bulk-resend' ).text( `Resend All` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn, #ps-aedl-bulk-resend' ).removeClass( 'ps-selected' );

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
		jQuery( '#ps-aedl-bulk-resend' ).text( `Resend Selected (${checkedCounter})` );
		jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn, #ps-aedl-bulk-resend' ).addClass( 'ps-selected' );
		
		if( checkedCounter == 0 ) {

			jQuery( '.ps-email-log-export-btn .ps-btn-text' ).text( `Export All` );
			jQuery( '.ps-email-log-delete-btn .ps-btn-text' ).text( `Delete All` );
			jQuery( '#ps-aedl-bulk-resend' ).text( `Resend All` );
			jQuery( '.ps-email-log-export-btn, .ps-email-log-delete-btn, #ps-aedl-bulk-resend' ).removeClass( 'ps-selected' );

		}

	} );

	//Export
	jQuery( document ).on( 'click', '.ps-email-log-export-btn', function( e ) { 

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
				action: 'ps-export-email-logs',
				security: logsDTSecirity,
				selected: selected
			},
			success: function( response ) {

				/**
				 * Make CSV downloadable
				 */
				var downloadLink = document.createElement( 'a' );
				var fileData = [response];
				var blobObject = new Blob( fileData, { type: 'text/csv;charset=utf-8' } );
				var url = URL.createObjectURL( blobObject );
				downloadLink.href = url;
				downloadLink.download = 'email-logs';

				/*
					* Actually download CSV
					*/
				document.body.appendChild(downloadLink);
				downloadLink.click();
				document.body.removeChild(downloadLink);

			}

		} );

	} );

	//Delete
	jQuery( document ).on( 'click', '.ps-email-log-delete-btn', function( e ) { 

		var confirmation = ( jQuery( this ).hasClass( 'ps-selected' ) ) ? confirm( 'Are you sure you want to delete the selected email logs?' ) : confirm( 'Are you sure you want to delete all email logs?' );

		if( confirmation === false ) {

			return;

		}

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

				if( response.success === true ) {

					logsDT.ajax.reload( null, false );
					if( response.deleted_all ){
						// Remove all options except "All".
						jQuery('.ps-advance-log-filter option').not('[value="all"]').remove();
					 }

				}
				else {

					alert( response.message );

				}

			}

		} );

		

	} );

	//View
	jQuery( document ).on( 'click', '.ps-email-log-view, .ps-email-log-transcript', function( e ) {

		e.preventDefault();

		var id = jQuery( this ).closest( 'tr' ).find( '.ps-email-log-cb' ).val();
		var toDo = jQuery( this ).hasClass( 'ps-email-log-view' );
		toDo = ( toDo ) ? 'original_message' : 'session_transcript';
		var heading = ( toDo == 'original_message' ) ? 'Email Message' : 'Session Transcript';
		jQuery( '.ps-popup-container' ).html( `
			<h1 style="margin: 0; padding: 0;"></h1>
			<h4>Loading...</h4>
		` );

		jQuery.ajax( {

			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ps-view-log',
				security: logsDTSecirity,
				id: id,
				type: toDo
			},
			success: function( response ) {

				if( response.success === true ) {

					jQuery( 'h4' ).hide();

					if( toDo == 'session_transcript' ) {

						jQuery( '.ps-popup-container' ).find( 'h1' ).after( `
							${response.data[toDo]}
						` );

					}
					else {

						var popupContent;
						jQuery( '.ps-popup-close' ).before( `
							<a href="${response.data.log_url}&print=1" target="_blank" class="ps-print-email"><span class="dashicons dashicons-printer"></span></a>
						` );

						popupContent = `
						<table>
							<tr>
								<td><strong>From:</strong></td>
								<td>${response.data.from_header}</td>
							</tr>
							<tr>
								<td><strong>To:</strong></td>
								<td>${response.data.to_header}</td>
							</tr>`;

							if( 
								response.data.cc_header != null 
								&& 
								response.data.cc_header != '' 
							) {

								popupContent += `
									<tr>
										<td><strong>Cc:</strong></td>
										<td>${response.data.cc_header}</td>
									</tr>
								`;

							}

							if( 
								response.data.bcc_header != null 
								&& 
								response.data.bcc_header != '' 
							) {

								popupContent += `
									<tr>
										<td><strong>Bcc:</strong></td>
										<td>${response.data.bcc_header}</td>
									</tr>
								`;

							}

							popupContent += `
								<tr>
									<td><strong>Date:</strong></td>
									<td>${response.data.time}</td>
								</tr>
								<tr>
									<td><strong>Subject:</strong></td>
									<td>${response.data.original_subject}</td>
								</tr>
								<tr>
									<td><strong>Delivery-URI:</strong></td>
									<td>${response.data.transport_uri}</td>
								</tr>
							</table>
							<hr />
							<div>
								<iframe src="${response.data.log_url}" id="ps-email-body" width="100%" height="310px"></iframe>
							</div>
						`;

						//Show Attachments
						if( response.data.attachments !== undefined ) {

							popupContent += `
									<hr />
									<div>`;

							jQuery.each( response.data.attachments, function( i, attachment ) {

								popupContent += `<a href='${response.data.path}${attachment}' target="_blank">${attachment}</a><br />`;

							} );

							popupContent += `</div>`;

						}

						jQuery( '.ps-popup-container' ).find( 'h1' ).after( popupContent );

					}

				}
				else {

					alert( response.message );

				}


			}

		} );

	} );

	//Refresh Logs
	jQuery( document ).on( 'click', '.ps-refresh-logs', function( e ) {

		e.preventDefault();
		logsDT.ajax.reload();

	} );

	//View And Session Transcript Popup
	jQuery( document ).on( 'click', '.ps-popup-btn', function( e ) {

		jQuery( '.ps-popup-wrap' ).fadeIn( 500 );
		jQuery( '.ps-popup-box' ).removeClass( 'transform-out' ).addClass( 'transform-in' );
	
		e.preventDefault();

	  } );
	
	  jQuery( document ).on( 'click', '.ps-popup-close', function( e ) {

		jQuery( '.ps-popup-wrap' ).fadeOut( 500 );
		jQuery( '.ps-popup-box' ).removeClass( 'transform-in' ).addClass( 'transform-out' );
		jQuery( '.ps-print-email' ).remove();
	
		e.preventDefault();

	});

	//Delete Log
	jQuery( document ).on( 'click', '.ps-email-log-delete', function( e ) {

	e.preventDefault();
	var id = jQuery( this ).closest( 'tr' ).find( '.ps-email-log-cb' ).val();
	var confirmation = confirm( 'Are you sure you want to delete this email log?' );
	var selected = [];
	selected.push( id );

	if( confirmation === false ) {

		return;

	}

	jQuery( this ).closest( 'tr' ).fadeOut();

		jQuery.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ps-delete-email-logs',
				security: logsDTSecirity,
				selected: selected
			},
			success: function( response ) {
				
				if( response.success === true ) {



				}
				else {

					alert( response.message );
					logsDT.ajax.reload( null, false );

				}

			}
		} );


	} );

	//MainWP | Lets do somthing on changing site
	jQuery( document ).on( 'change', '.ps-mainwp-site-selector', function() {
		
		var siteID = this.value;
		logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&site_id=${siteID}` ).load();
		
	} );
	
	//If site already selected
	jQuery( document ).on( 'click', '.ps-mainwp-site', function( e ) {
		
		e.preventDefault();
		var href = $(this).attr('href');
	
		var siteID = PostSMTPGetParameterByName( 'site_id', href );
		
		jQuery( `.ps-mainwp-site-selector option[value="${siteID}"]` ).prop( 'selected', true )

		if( siteID != null && siteID != -1 ) {

			logsDT.ajax.url( `${ajaxurl}?action=ps-get-email-logs&security=${logsDTSecirity}&site_id=${siteID}` ).load();

		}
		
	} )
	

	//Resend
	jQuery( document ).on( 'click', '.ps-email-log-resend', function( e ) {

		e.preventDefault();
		var sendTo = jQuery( this ).closest( 'tr' ).find( 'td:nth-child(3)' ).attr('title');
		var currentRow = jQuery( this ).closest( 'tr' );

		jQuery( currentRow ).find( '.ps-email-log-resend-container' ).html( `
			<div>
				<input type="text" class="ps-email-log-resend-to" value="${sendTo}" />
				<button class="button button-primary ps-email-resend-btn"><span class="ps-btn-text">Resend</span> <span class="dashicons dashicons-email"></span></button>
				<p>For multiple recipients, separate them with a comma.</p>
			</div>
		` );

	} );

	//Resend
	jQuery( document ).on( 'click', '.ps-email-resend-btn', function( e ) {

		e.preventDefault();
		var id = jQuery( this ).closest( 'tr' ).find( '.ps-email-log-cb' ).val();
		var to = jQuery( this ).closest( 'tr' ).find( '.ps-email-log-resend-to' ).val();

		jQuery.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ps-resend-email',
				security: logsDTSecirity,
				id: id,
				to: to
			},
			success: function( response ) {
				
				if( response.success === true ) {

					alert( response.message );
					logsDT.ajax.reload( null, false );

				}
				else {

					alert( response.message );
					logsDT.ajax.reload( null, false );

				}

			}
		} );


	} );


	jQuery( document ).on( 'click', '.ps-status-log', function( e ) {

		e.preventDefault();
		var _details = jQuery( this ).siblings( 'span' ).attr( 'title' );
		jQuery( '.ps-popup-container' ).html( `<h1 style="margin: 0; padding: 0;"></h1>${_details}` );

	} );

})