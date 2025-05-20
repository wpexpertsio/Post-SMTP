jQuery(document).ready(function() {

	// enable toggling of the API field from password to plain text
	enablePasswordDisplayOnEntry('mailersend_api_key', 'toggleMailerSendApiKey');

	// define the PostmanMailerSend class
	var PostmanMailerSend = function() {

	}

	// behavior for handling the user's transport change
	PostmanMailerSend.prototype.handleTransportChange = function(transportName) {
		if (transportName == 'mailersend_api') {
			hide('div.transport_setting');
			hide('div.authentication_setting');
			show('div#mailersend_settings');
		}
	}

	// behavior for handling the wizard configuration from the
	// server (after the port test)
	PostmanMailerSend.prototype.handleConfigurationResponse = function(response) {
		var transportName = response.configuration.transport_type;
		if (transportName == 'mailersend_api') {
			show('section.wizard_mailersend');
		} else {
			hide('section.wizard_mailersend');
		}
	}

	// add this class to the global transports
	var transport = new PostmanMailerSend();
	transports.push(transport);

	// since we are initialize the screen, check if needs to be modded by this
	// transport
	var transportName = jQuery('select#input_transport_type').val();
	transport.handleTransportChange(transportName);

});
