jQuery( document ).ready( function(){

    // enable toggling of the API field from password to plain text
    enablePasswordDisplayOnEntry('resend_api_key', 'toggleResendApiKey');

    // define the PostmanResend class
    var PostmanResend = function() {

    }

    // behavior for handling the user's transport change
    PostmanResend.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'resend_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#resend_settings' );
        }
    }

    // behavior for handling the wizard configuration from the
    // server (after the port test)
    PostmanResend.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'resend_api' ) {
            show( 'section.wizard_resend' );
        } else {
            hide( 'section.wizard_resend' );
        }
    }

    // add this class to the global transports
    var transport = new PostmanResend();
    transports.push( transport );

    // since we are initialize the screen, check if needs to be modded by this
    // transport
    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )
