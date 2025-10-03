jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('emailit_api_key', 'toggleEmailitApiKey');

    // define the PostmanEmailit class
    var PostmanEmailit = function() {

    }   

    // behavior for handling the user's transport change
    PostmanEmailit.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'emailit_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#emailit_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanEmailit.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'emailit_api' ) {
            show( 'section.wizard_emailit' );
        } else {
            hide( 'section.wizard_emailit' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanEmailit();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )
