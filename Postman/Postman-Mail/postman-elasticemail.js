jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('elasticemail_api_key', 'toggleElasticEmailApiKey');

    // define the PostmanMandrill class
    var PostmanSendGrid = function() {

    }

    // behavior for handling the user's transport change
    PostmanSendGrid.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'elasticemail_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#elasticemail_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanSendGrid.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'elasticemail_api' ) {
            show( 'section.wizard_elasticemail' );
        } else {
            hide( 'section.wizard_elasticemail' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanSendGrid();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )