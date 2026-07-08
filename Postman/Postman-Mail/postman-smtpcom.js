jQuery( document ).ready( function(){

    enablePasswordDisplayOnEntry('smtpcom_api_key', 'toggleSmtpcomApiKey');

    var PostmanSmtpcom = function() {

    }

    PostmanSmtpcom.prototype.handleTransportChange = function( transportName ) {
        if ( transportName == 'smtpcom_api' ) {
            hide( 'div.transport_setting' );
            hide( 'div.authentication_setting' );
            show( 'div#smtpcom_settings' );
        }
    }

    PostmanSmtpcom.prototype.handleConfigurationResponse = function( response ) {
        var transportName = response.configuration.transport_type;
        if ( transportName == 'smtpcom_api' ) {
            show( 'section.wizard_smtpcom' );
        } else {
            hide( 'section.wizard_smtpcom' );
        }
    }

    var transport = new PostmanSmtpcom();
    transports.push( transport );

    var transportName = jQuery( 'select#input_transport_type' ).val();
    transport.handleTransportChange( transportName );

} )
