jQuery( document ).ready(function() {

    jQuery( '.ps-wizard-socket-check:checked' ).siblings( '.ps-wizard-socket-tick-container' ).css( { 'opacity': 1 } );

    jQuery( document ).on( 'click', '.ps-wizard-socket-radio label', function() {

        jQuery( '.ps-wizard-socket-tick-container' ).css( { 'opacity': 0 } );
        jQuery( this ).find( '.ps-wizard-socket-tick-container' ).css( { 'opacity': 1 } );
        
    } )

    /**
     * Refresh the wizard to show the current step
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const refreshWizard = function() {

        var tableRow = jQuery( '.ps-wizard-nav table tr.ps-active-nav' );
        var activeTab = jQuery( tableRow ).hasClass( 'ps-active-nav' );

        if( activeTab ) {

            var stepID = jQuery( tableRow ).find( '.dashicons-edit' ).data( 'step' );
            jQuery( '.ps-wizard-step' ).hide();
            jQuery( `.ps-wizard-step-${stepID}` ).fadeIn( 'slow' );

        }

    }


    /**
     * Validate the current step
     * 
     * @param {int} stepID Current Step ID
     * @return bool
     * @since 2.0.0
     * @version 1.0.0
     */
    const validateStep = function( stepID ) {

        //Validate step 1
        if( stepID == 1 ) {
            
            if( jQuery( '.ps-wizard-socket-check' ).is( ':checked' ) ) {

                var selectedSocket = jQuery( '.ps-wizard-socket-check:checked' ).val();
                var _element = jQuery( '.ps-wizard-outer' ).removeClass();
                jQuery( _element ).addClass( 'ps-wizard-outer' );
                jQuery( _element ).addClass( `${selectedSocket}-outer` );
                //Remove Warning (if has), and contiue to next step
                jQuery( '.ps-wizard-error' ).html( '' );
                jQuery( '.ps-wizard-page-footer' ).fadeOut();
                renderSocketSettings();
                return true;

            }
            else {

                jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step1E1}` );

            }

        }

        //Validate step 2
        if( stepID == 2 ) {

            var selectedSocket = jQuery( '.ps-wizard-socket-check:checked' ).val();
            selectedSocket = jQuery( `.${selectedSocket}` ).find( 'input' );
            var validated = false;

            jQuery( selectedSocket ).each( function( index, element ) {

                var attr = jQuery( element ).attr( 'required' );

                if( typeof attr !== 'undefined' && attr !== false && jQuery( element ).val() == '' ) {
                    
                    var error = jQuery( element ).data( 'error' );
                    jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${error}` );
                    validated = false;
                    jQuery( element ).focus();
                    return false;

                }
                else {

                    //Validate From Name, From Email
                    jQuery( '.ps-name-email-settings' ).find( 'input' ).each( function( index, element ) {

                        var attr = jQuery( element ).attr( 'required' );
        
                        if( typeof attr !== 'undefined' && attr !== false && jQuery( element ).val() == '' ) {
                            
                            var error = jQuery( element ).data( 'error' );
                            jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${error}` );
                            validated = false;
                            jQuery( element ).focus();
                            return false;
        
                        }
                        else {
        
                            //remove error, since everything is good to go :).
                            jQuery( '.ps-wizard-error' ).html( '' );
                            validated = true;
        
                        }
        
                    } );

                }

            } );

            //If everything is good to go, lets save settings.
            if( validated === true ) {

                var button = jQuery( '.ps-wizard-step-2' ).find( '.ps-wizard-next-btn' );
                var buttonHTML = jQuery( button ).html();
                jQuery( button ).html( 'Saving...' );

                //Lets AJAX request.
                jQuery.ajax( {

                    url: ajaxurl,
                    type: 'POST',
                    async: true,
                    data: {
                        action: 'ps-save-wizard',
                        FormData: jQuery( '#ps-wizard-form' ).serialize(),
                    },

                    success: function( response ) {

                        jQuery( '.ps-wizard-error' ).html( '' );
                        nextStep( stepID );
                        var _element = jQuery( '.ps-wizard-outer' ).removeClass();
                        jQuery( _element ).addClass( 'ps-wizard-outer' );

                    },
                    error: function( response ) {

                        jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step2E3}` );

                    },
                    complete: function( response ) {

                        jQuery( button ).html( buttonHTML );

                    }

                } );

            }

        }

        //Validate step 3 | No validation required, we can skip this step.
        if( stepID == 3 ) {

            return true;

        }

    }

    
    /**
     * Switch to next step
     * 
     * @param {int} stepID Current Step ID
     * @return void
     * @since 2.0.0
     * @version 1.0.0 
     */
    const nextStep = function( stepID ) {

        var nextStep = stepID + 1;

        jQuery( `*[data-step="${stepID}"]` ).closest( 'tr' ).removeClass();
        jQuery( `*[data-step="${nextStep}"]` ).closest( 'tr' ).removeClass();
        jQuery( `*[data-step="${nextStep}"]` ).closest( 'tr' ).addClass( 'ps-active-nav' );
        refreshWizard();

    }


    /**
     * Switch to step
     * 
     * @param {int} stepID Step ID to be switched to
     * @return void
     * @since 2.0.0
     * @version 1.0.0 
     */
    const switchStep = function( stepID ) {

        jQuery( '.ps-wizard-nav table tr.ps-active-nav' ).addClass( 'ps-in-active-nav' );
        jQuery( '.ps-wizard-nav table tr.ps-active-nav' ).removeClass( 'ps-active-nav' );
        jQuery( `*[data-step="${stepID}"]` ).closest( 'tr' ).addClass( 'ps-active-nav' );
        jQuery( '.ps-wizard-success' ).html( '' );
        jQuery( '.ps-wizard-error' ).html( '' );
        refreshWizard();

    }


    /**
     * Render Socket Settings
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const renderSocketSettings = function() {

        var selectedSocket = jQuery( '.ps-wizard-socket-check:checked' ).val();
        jQuery( '.ps-wizard-socket' ).hide();
        jQuery( `.${selectedSocket}` ).fadeIn( 'slow' );

    }

    /**
     * Gets URL Parameter
     * 
     * @return void
     * @since 2.0.0
     * @version 1.0.0
     */
    const getUrlParameter = function getUrlParameter(sParam) {

        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;

    };

    //Switch to next step
    jQuery( document ).on( 'click', '.ps-wizard-next-btn', function( e ) {

        e.preventDefault();

        var stepID = jQuery( this ).data( 'step' );
        
        
        if( validateStep( stepID ) === true ) {

            nextStep( stepID );

        }

    } );

    
    //Switch to step | Edit Step
    jQuery( document ).on( 'click', '.ps-wizard-nav table tr td .dashicons-edit, .ps-wizard-back', function( e ) {

        e.preventDefault();

        var stepID = jQuery( this ).data( 'step' );

        if( stepID == 1 ) {

            jQuery( '.ps-wizard-page-footer' ).fadeIn( 'slow' );

        }

        if( stepID !== 2 ) {

            var _element = jQuery( '.ps-wizard-outer' ).removeClass();
            jQuery( _element ).addClass( 'ps-wizard-outer' );
            
        }
        if( stepID == 2 ) {

            var selectedSocket = jQuery( '.ps-wizard-socket-check:checked' ).val();
            var _element = jQuery( '.ps-wizard-outer' ).removeClass();
            jQuery( _element ).addClass( 'ps-wizard-outer' );
            jQuery( _element ).addClass( `${selectedSocket}-outer` );
            
        }

        switchStep( stepID );

    } );


    //Send test email
    jQuery( document ).on( 'click', '.ps-wizard-send-test-email', function( e ) {

        e.preventDefault();

        var sendTo = jQuery( '.ps-test-to' ).val();
        var security = jQuery( '#security' ).val();

        if( sendTo == '' ) {

            jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${PostSMTPWizard.Step3E4}` );
            return;

        }

        jQuery( '.ps-wizard-error' ).html('');
        jQuery( '.ps-wizard-success' ).html( 'Sending...' );

        jQuery.ajax( {

            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'postman_send_test_email',
                email: sendTo,
                security: security,
            },
            success: function( response ) {

                jQuery( '.ps-wizard-success' ).html( '' );
                
                if( response.success === true ) {

                    jQuery( '.ps-wizard-success' ).html( `<span class="dashicons dashicons-yes"></span> ${response.data.message}` );
                    jQuery( '.ps-finish-wizard' ).html( `${PostSMTPWizard.finish} <span class="dashicons dashicons-arrow-right-alt">` );

                }
                if( response.success === false ) {

                    jQuery( '.ps-wizard-error' ).html( `<span class="dashicons dashicons-warning"></span> ${response.data.message}` );

                }

            }

        } );

    } );


    //Connect to Office 365 | Auth
    jQuery( document ).on( 'click', '#ps-wizard-connect-office365', function( e ) {

        e.preventDefault();

        var office365_app_id = jQuery( '.ps-office365-client-id' ).val();
        var office365_app_password = jQuery( '.ps-office365-client-secret' ).val();
        var _button = jQuery( this ).html();

        if( office365_app_id == '' ) {

            jQuery( '.ps-office365-client-id' ).focus();
            return;

        }
        if( office365_app_password == '' ) {

            jQuery( '.ps-office365-client-secret' ).focus();
            return;

        }

        jQuery( this ).html( 'Redirecting...' );

        var authURL = `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?state=${PostSMTPWizard.office365State}&scope=openid profile offline_access Mail.Send Mail.Send.Shared&response_type=code&approval_prompt=auto&redirect_uri=${PostSMTPWizard.adminURL}&client_id=${office365_app_id}`;
        
        jQuery.ajax( {

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery( '#ps-wizard-form' ).serialize(),
            },

            success: function( response ) {

                window.location.assign( authURL );

            },

        } );

    } );


    //Connect to Gmail API | Auth
    jQuery( document ).on( 'click', '#ps-wizard-connect-gmail', function( e ) {

        e.preventDefault();

        var clientID = jQuery( '.ps-gmail-api-client-id' ).val();
        var clientSecret = jQuery( '.ps-gmail-client-secret' ).val();
        var redirectURI = jQuery( this ).attr( 'href' );

        if( clientID == '' ) {

            jQuery( '.ps-gmail-api-client-id' ).focus();
            return;

        }
        if( clientSecret == '' ) {

            jQuery( '.ps-gmail-client-secret' ).focus();
            return;

        }

        jQuery( this ).html( 'Redirecting...' );

        jQuery.ajax( {

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery( '#ps-wizard-form' ).serialize(),
            },

            success: function( response ) {

                window.location.assign( redirectURI );

            },

        } );

    } );

    //Connect to Zoho | Auth
    jQuery( document ).on( 'click', '#ps-wizard-connect-zoho', function( e ) {

        e.preventDefault();

        var clientID = jQuery( '.ps-zoho-client-id' ).val();
        var clientSecret = jQuery( '.ps-zoho-client-secret' ).val();
        var redirectURI = jQuery( this ).attr( 'href' );

        if( clientID == '' ) {

            jQuery( '.ps-zoho-client-id' ).focus();
            return;

        }
        if( clientSecret == '' ) {

            jQuery( '.ps-zoho-client-secret' ).focus();
            return;

        }

        jQuery( this ).html( 'Redirecting...' );

        jQuery.ajax( {

            url: ajaxurl,
            type: 'POST',
            async: true,
            data: {
                action: 'ps-save-wizard',
                FormData: jQuery( '#ps-wizard-form' ).serialize(),
            },

            success: function( response ) {

                window.location.assign( redirectURI );

            },

        } );

    } );

    refreshWizard();

    if( getUrlParameter( 'socket' ) ) {

        jQuery( document ).scrollTop( jQuery( document ).height() );

    }

    jQuery( document ).on( 'click', '.ps-pro-extension-outer', function(){

        var placeholder = jQuery( this ).find( 'h4' ).text();
        var imgSrc = jQuery( this ).find( 'img' ).attr( 'src' ); 

        jQuery( '.ps-pro-for-img' ).attr( 'src', imgSrc );
        jQuery( '.ps-pro-for' ).text( placeholder );
        jQuery( '.ps-pro-popup-overlay' ).fadeIn();

    } );

    jQuery( document ).on( 'click', '.ps-pro-close-popup', function( e ){

        e.preventDefault();
        jQuery( '.ps-pro-popup-overlay' ).fadeOut();

    } );

} );