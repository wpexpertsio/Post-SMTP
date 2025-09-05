// Provider Email Logs Table (client-side DataTables)
jQuery(document).ready(function($) {
    let providerLogs = [];
    let table = null;

    function renderTable(logs) {
        // Hide or show table and no-logs row
        if (!logs || logs.length === 0) {
            $('#ps-email-log').hide();
            $('#ps-email-log tbody .ps-no-logs-row').show();
            return;
        }
        $('#ps-email-log').show();
        $('#ps-email-log tbody .ps-no-logs-row').hide();

        if (table) {
            table.clear().rows.add(logs).draw();
            return;
        }
        table = $('#ps-email-log-provider').DataTable({
            data: logs,
            columns: [
                { data: 'id', title: 'Id' },
                { data: 'subject', title: 'Subject' },
                { data: 'from', title: 'Sent From' },
                { data: 'to', title: 'Sent To' },
                { data: 'date', title: 'Delivery Time' },
                { data: 'status', title: 'Status' }
            ],
            order: [[4, 'desc']], // Order by Delivery Time
            pageLength: 25,
            lengthMenu: [25, 50, 100, 500],
            searching: true,
            dom: 'lfrtip',
            destroy: true,
        });
    }

    function fetchProviderLogs(provider, filters = {}) {
        // Show loading state
        $('#ps-email-log').hide();
        $('#ps-email-log tbody .ps-no-logs-row').show().text(postman_provider_logs.loading_label || 'Loading...');
		if (provider === 'none') {
			// Just clear tbody, leave table intact
			$('#ps-email-log tbody').empty().append('<tr class="ps-no-logs-row"><td colspan="6">' + (postman_provider_logs.none_label || 'No provider selected.') + '</td></tr>');
			return;
		}
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_provider_email_logs',
                provider: provider,
                filters: filters,
                security: postman_provider_logs.nonce
            },
            success: function(response) {
	
                if (response.success && response.data.logs && response.data.logs.length > 0) {
                    providerLogs = response.data.logs;
                    renderTable(providerLogs);
                } else {
                    providerLogs = [];
                     if (table) {
						table.clear().draw(); // ✅ clears old rows
					}
					$('#ps-email-log tbody').append(
						'<tr class="ps-no-logs-row"><td colspan="6">' + 
						(postman_provider_logs.none_label || 'No logs found.') + 
						'</td></tr>'
					);
                }
            },
            error: function() {
				  if (table) {
					table.clear().draw(); // ✅ clears old rows
				}
				$('#ps-email-log tbody').append(
					'<tr class="ps-no-logs-row"><td colspan="6">' + 
					(postman_provider_logs.error_label || 'Error loading logs.') + 
					'</td></tr>'
				);
            }
        });
    }

    // Listen for provider dropdown change
    $('#ps-provider-log-select').on('change', function() {
        let provider = $(this).val();
        fetchProviderLogs(provider);
    });

    // Initial load (default to 'none')
    fetchProviderLogs($('#ps-provider-log-select').val());
});
