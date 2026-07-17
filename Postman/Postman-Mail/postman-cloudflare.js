jQuery( document ).ready( function() {

	// enable toggling of the API token field from password to plain text
	enablePasswordDisplayOnEntry( 'cloudflare_api_token', 'toggleCloudflareApiToken' );

	// define the PostmanCloudflare class
	var PostmanCloudflare = function() {
	};

	// behavior for handling the user's transport change
	PostmanCloudflare.prototype.handleTransportChange = function( transportName ) {
		if ( transportName == 'cloudflare_api' ) {
			hide( 'div.transport_setting' );
			hide( 'div.authentication_setting' );
			show( 'div#cloudflare_settings' );
		}
	};

	// behavior for handling the wizard configuration from the server (after the port test)
	PostmanCloudflare.prototype.handleConfigurationResponse = function( response ) {
		var transportName = response.configuration.transport_type;
		if ( transportName == 'cloudflare_api' ) {
			show( 'section.wizard_cloudflare' );
		} else {
			hide( 'section.wizard_cloudflare' );
		}
	};

	// add this class to the global transports
	var transport = new PostmanCloudflare();
	transports.push( transport );

	// since we are initialize the screen, check if needs to be modded by this transport
	var transportName = jQuery( 'select#input_transport_type' ).val();
	transport.handleTransportChange( transportName );
} );
