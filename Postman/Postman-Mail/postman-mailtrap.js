jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('mailtrap_api_key', 'toggleMailtrapApiKey');

    // define the PostmanMailtrap class
    var PostmanMailtrap = function() {

    }

    // behavior for handling the user's transport change
    PostmanMailtrap.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'mailtrap_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#mailtrap_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanMailtrap.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'mailtrap_api' ) {
            show( 'section.wizard_mailtrap' );
        } else {
            hide( 'section.wizard_mailtrap' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanMailtrap();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )
