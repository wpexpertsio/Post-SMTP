jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('postmark_api_key', 'togglePostmarkApiKey');

    // define the PostmanMandrill class
    var PostmanPostmark = function() {

    }

    // behavior for handling the user's transport change
    PostmanPostmark.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'postmark_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#postmark_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanPostmark.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'postmark_api' ) {
            show( 'section.wizard_postmark' );
        } else {
            hide( 'section.wizard_postmark' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanPostmark();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )