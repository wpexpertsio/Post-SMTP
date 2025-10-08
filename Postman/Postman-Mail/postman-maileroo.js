jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('maileroo_api_key', 'toggleMailerooApiKey');

    // define the PostmanMaileroo class
    var PostmanMaileroo = function() {

    }   

    // behavior for handling the user's transport change
    PostmanMaileroo.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'maileroo_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#maileroo_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanMaileroo.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'maileroo_api' ) {
            show( 'section.wizard_maileroo' );
        } else {
            hide( 'section.wizard_maileroo' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanMaileroo();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )
