<?php
if( !function_exists( 'wp_mail' ) ):

/**
 * Send an email | Override wp_mail function
 * 
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 * @see wp_mail()
 * @since 2.6.0
 * @version 2.6.0
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
    
    $request = new Post_SMTP_MainWP_Child_Request();
    $response = $request->process_email( $to, $subject, $message, $headers, $attachments );
    
    if( is_wp_error( $response ) ) {
        
        return false;
        
    }
    
    return true;
    
}
	
endif;