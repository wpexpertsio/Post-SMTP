
jQuery( document ).ready( function( $ ) {
	enablePasswordDisplayOnEntry( 'smtp2go_api_key', 'toggleSmtp2goApiKey' );

	var PostmanSmtp2go = function() {

	}

	PostmanSmtp2go.prototype.handleTransportChange = function( transportName ) {
		console.log( transportName );
		if ( transportName == 'smtp2go_api' ) {
			hide( 'div.transport_setting' );
			hide( 'div.authentication_setting' );
			show( 'div#smtp2go_settings' );
		}
	}

	PostmanSmtp2go.prototype.handleConfigurationResponse = function( response ) {
		var transportName = response.configuration.transport_type;
		if ( transportName == 'smtp2go_api' ) {
			show( 'section.wizard_smtp2go' );
		} else {
			hide( 'section.wizard_smtp2go' );
		}
	}

	var transport = new PostmanSmtp2go();
	transports.push( transport );

	var transportName = jQuery( 'select#input_transport_type' ).val();
	transport.handleTransportChange( transportName );
} );
