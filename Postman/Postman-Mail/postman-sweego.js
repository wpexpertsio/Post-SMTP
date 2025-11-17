jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('sweego_api_key', 'toggleSweegoApiKey');

    // define the PostmanSweego class
    var PostmanSweego = function() {

    }   

    // behavior for handling the user's transport change
    PostmanSweego.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'sweego_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#sweego_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanSweego.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'sweego_api' ) {
            show( 'section.wizard_sweego' );
        } else {
            hide( 'section.wizard_sweego' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanSweego();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )

