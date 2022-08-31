jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('sparkpost_api_key', 'toggleSparkPostApiKey');

    // define the PostmanMandrill class
    var PostmanSparkPost = function() {

    }

    // behavior for handling the user's transport change
    PostmanSparkPost.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'sparkpost_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#sparkpost_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanSparkPost.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'sparkpost_api' ) {
            show( 'section.wizard_sparkpost' );
        } else {
            hide( 'section.wizard_sparkpost' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanSparkPost();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )