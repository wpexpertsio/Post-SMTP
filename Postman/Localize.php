<?php
$warning = __( 'Warning', 'post-smtp' );

return array(
    /* translators: where %s is the name of the SMTP server */
    'postman_smtp_mitm' => sprintf( '%s: %s', $warning, __( 'connected to %1$s instead of %2$s.', 'post-smtp' ) ),
    /* translators: where %d is a port number */
    'postman_wizard_bad_redirect_url' => __( 'You are about to configure OAuth 2.0 with an IP address instead of a domain name. This is not permitted. Either assign a real domain name to your site or add a fake one in your local host file.', 'post-smtp' ),
    'postman_input_sender_email' => '#input_' . PostmanOptions::MESSAGE_SENDER_EMAIL,
    'postman_input_sender_name' => '#input_' . PostmanOptions::MESSAGE_SENDER_NAME,
    'postman_port_element_name' => '#input_' . PostmanOptions::PORT,
    'postman_hostname_element_name' => '#input_' . PostmanOptions::HOSTNAME,
    'postman_enc_for_password_el' => '#input_enc_type_password',
    'postman_input_basic_username' => '#input_' . PostmanOptions::BASIC_AUTH_USERNAME,
    'postman_input_basic_password' => '#input_' . PostmanOptions::BASIC_AUTH_PASSWORD,
    'postman_redirect_url_el' => '#input_oauth_redirect_url',
    'postman_input_auth_type' => '#input_' . PostmanOptions::AUTHENTICATION_TYPE,
    'postman_js_email_was_resent' => __( 'Email was successfully resent (but without attachments)', 'post-smtp' ),
    /* Translators: Where %s is an error message */
    'postman_js_email_not_resent' => __( 'Email could not be resent. Error: %s', 'post-smtp' ),
    'postman_js_resend_label' => __( 'Resend', 'post-smtp' ),
    'steps_current_step' => 'steps_current_step',
    'steps_pagination' => 'steps_pagination',
    'steps_finish' => _x( 'Finish', 'Press this button to Finish this task', 'post-smtp' ),
    'steps_next' => _x( 'Next', 'Press this button to go to the next step', 'post-smtp' ),
    'steps_previous' => _x( 'Previous', 'Press this button to go to the previous step', 'post-smtp' ),
    'steps_loading' => 'steps_loading'
);

