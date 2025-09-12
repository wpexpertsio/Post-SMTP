// Provider Email Logs Table (client-side DataTables)
jQuery(document).ready(function($) {
    let providerLogs = [];
    let table = null;

    function renderTable(logs) {
        // Hide or show table and no-logs row
        $('#ps-provider-log-loader').hide();
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
            order: [[4, 'desc']],
            pageLength: parseInt($("select[name='ps-email-log_length']").val()) || 25,
            lengthMenu: [25, 50, 100, 500],
            searching: true, // enable for API, but hide default
            dom: 'rtip',
            destroy: true,
            drawCallback: function() {
                // Hide default DataTables controls (force)
                $('#ps-email-log-provider_length').hide();
                $('#ps-email-log-provider_filter').hide();
            }
        });

        // Also hide default controls right after table is created (in case drawCallback missed)
        $('#ps-email-log-provider_length').hide();
        $('#ps-email-log-provider_filter').hide();

        // Sync custom search input
        $("#ps-provider-log-search").off('input keyup').on('input keyup', function() {
            table.search(this.value).draw();
        });

        // Sync custom entries dropdown
        $("select[name='ps-email-log_length']").off('change').on('change', function() {
            var val = parseInt($(this).val());
            table.page.len(val).draw();
        });
    }

    function fetchProviderLogs(provider, filters = {}) {
		//$('#ps-provider-log-loader').show();
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

    // Listen for provider dropdown change (always reload, show loader)
    function reloadProviderLogs(showLoader = false) {
        let provider = $('#ps-provider-log-select').val();
        let from = $('.ps-email-log-from').val();
        let to = $('.ps-email-log-to').val();
        if (showLoader) {
            $('#ps-provider-log-loader').show();
            if (table) table.clear().draw();
            $('#ps-email-log').hide();
            $('#ps-email-log tbody').empty().append('<tr class="ps-no-logs-row"><td colspan="6">' + (postman_provider_logs.loading_label || 'Loading...') + '</td></tr>');
        } else {
            $('#ps-provider-log-loader').hide();
        }
        fetchProviderLogs(provider, { from, to });
    }

    // Only show loader when provider changes
    $('#ps-provider-log-select').on('change', function() {
        reloadProviderLogs(true);
    });
    // For from/to date changes, just reload logs (no loader)
    $('.ps-email-log-from, .ps-email-log-to').on('change', function() {
        reloadProviderLogs(false);
    });

    // Initial load (default to 'none'), no loader
    reloadProviderLogs(false);
});
