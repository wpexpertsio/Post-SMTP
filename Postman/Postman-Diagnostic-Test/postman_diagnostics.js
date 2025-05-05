jQuery(document).ready(function() {
	getDiagnosticData();
	jQuery('.post-smtp-diagnostic-submit-btn').on('click', function(event){
		event.preventDefault();
		var $button = jQuery(this);
		jQuery('.report_sent_message').hide();
		jQuery('.report_validation_error').hide();
		var email = jQuery('#post-smtp-diagnostic-email-address').val();
        var ticketNumber = jQuery('#post-smtp-diagnostic-ticket-number').val();
        
        // Check if required fields are filled
        if (email === '' || ticketNumber === '') {
            jQuery('.report_validation_error').show();
            return; // Stop the form submission
        }
		$button.attr('disabled', true);
		sendDiagnosticDataViaEmail($button, email, ticketNumber);
	})
	jQuery('.copy_diagnostic_report').on('click', function(event){
		event.preventDefault();
		
        var $button = jQuery(this);
		var originalText = $button.text();  // Save the original text
		$button.attr('disabled', true);
		var tableContent = jQuery('.diagnostic_report').val();
        
        // Create a temporary textarea element to hold the table content
        var tempTextArea = jQuery('<textarea>');
        jQuery('body').append(tempTextArea);
        
        // Set the table content as the textarea's value
        tempTextArea.val(tableContent).select();
        
        // Execute the copy command
        document.execCommand('copy');
        
        // Remove the temporary textarea
        tempTextArea.remove();
        
		$button.text('Copied to clipboard!');
		
		// Change it back to "Copy" after 2 seconds
		setTimeout(function() {
			$button.text(originalText);
			$button.attr('disabled', false);	
		}, 2000); // 2000 milliseconds = 2 seconds
	})
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
			jQuery('.fetching_diagnostic_report').hide();
			jQuery('#diagnostic-text').append(response.data.message);
		}
	}).fail(function(response) {
		ajaxFailed(response);
	});
}
function sendDiagnosticDataViaEmail($button, email, ticketNumber) {
	var originalText = $button.text();
	$button.text('Sending...');
	var data = {
		'action' : 'send_postman_diagnostics_data',
		'security' : jQuery('#security').val(),
		'email' : email,
		'username_or_ticket_number' : ticketNumber,
	};
	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			$button.text(originalText);
			$button.attr('disabled', false);
			jQuery('.report_sent_message').show();
		}
	}).fail(function(response) {
		ajaxFailed(response);
	});
}
