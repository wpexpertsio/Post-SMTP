jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('sendpulse_api_key', 'toggleSendpulseApiKey');

    // enable toggling of the Secret Key field from password to plain text
    enablePasswordDisplayOnEntry('sendpulse_secret_key', 'toggleSendpulseSecretKey');

    // define the PostmanSendPulse class
    var PostmanSendPulse = function() {

    }

    // behavior for handling the user's transport change
    PostmanSendPulse.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'sendpulse_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#sendpulse_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanSendPulse.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'sendpulse_api' ) {
            show( 'section.wizard_sendpulse' );
        } else {
            hide( 'section.wizard_sendpulse' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanSendPulse();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )