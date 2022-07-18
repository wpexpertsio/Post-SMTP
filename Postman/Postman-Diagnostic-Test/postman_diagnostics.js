jQuery(document).ready(function() {
	getDiagnosticData();
});

/**
 */
function getDiagnosticData() {
	var data = {
		'action' : 'postman_diagnostics',
		'security' : jQuery('#security').val()
	};
	jQuery.post(ajaxurl, data, function(response) {
		if (response.success) {
			jQuery('#diagnostic-text').val(response.data.message);
		}
	}).fail(function(response) {
		ajaxFailed(response);
	});
}
