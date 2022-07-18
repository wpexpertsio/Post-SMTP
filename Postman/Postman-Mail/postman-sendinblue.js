jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('sendinblue_api_key', 'toggleSendinblueApiKey');

    // define the PostmanMandrill class
    var PostmanSendGrid = function() {

    }

    // behavior for handling the user's transport change
    PostmanSendGrid.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'sendinblue_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#sendinblue_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanSendGrid.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'sendinblue_api' ) {
            show( 'section.wizard_sendinblue' );
        } else {
            hide( 'section.wizard_sendinblue' );
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