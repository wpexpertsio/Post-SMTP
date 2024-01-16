jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('mailjet_api_key', 'toggleMailjetApiKey');
    enablePasswordDisplayOnEntry('mailjet_secret_key', 'toggleMailjetSecretKey');

    // define the PostmanMandrill class
    var PostmanMailjet = function() {

    }

    // behavior for handling the user's transport change
    PostmanMailjet.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'mailjet_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#mailjet_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanMailjet.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'mailjet_api' ) {
            show( 'section.wizard_mailjet' );
        } else {
            hide( 'section.wizard_mailjet' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanMailjet();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )