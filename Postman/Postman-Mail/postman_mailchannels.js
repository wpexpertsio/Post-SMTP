
jQuery( document ).ready( function( $ ) {
	enablePasswordDisplayOnEntry( 'mailchannels_api_key', 'toggleMailChannelsApiKey' );

	var PostmanMailChannels = function() {

	}

	PostmanMailChannels.prototype.handleTransportChange = function( transportName ) {
		if ( transportName == 'mailchannels_api' ) {
			hide( 'div.transport_setting' );
			hide( 'div.authentication_setting' );
			show( 'div#mailchannels_settings' );
		}
	}

	PostmanMailChannels.prototype.handleConfigurationResponse = function( response ) {
		var transportName = response.configuration.transport_type;
		if ( transportName == 'mailchannels_api' ) {
			show( 'section.wizard_mailchannels' );
		} else {
			hide( 'section.wizard_mailchannels' );
		}
	}

	var transport = new PostmanMailChannels();
	transports.push( transport );

	var transportName = jQuery( 'select#input_transport_type' ).val();
	transport.handleTransportChange( transportName );
} );
