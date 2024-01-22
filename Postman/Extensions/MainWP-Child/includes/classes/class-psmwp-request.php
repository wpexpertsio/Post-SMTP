<?php

if( !class_exists( 'Post_SMTP_MainWP_Child_Request' ) ):

class Post_SMTP_MainWP_Child_Request {


    private $base_url = false;


    /**
     * Constructor
     * 
     * @since 2.6.0
     * @version 2.6.0
     */
    public function __construct() {

		$server = apply_filters( 'mainwp_child_get_encrypted_option', false, 'mainwp_child_server', false );
		$server = str_replace( 'wp-admin/', '', $server );
		
        if( $server ) {
            
			$this->base_url = $server . 'wp-json/post-smtp-for-mainwp/v1/send-email';
			
        }

    }


    /**
     * Process email
     * 
     * @param string|array $to Array or comma-separated list of email addresses to send message.
     * @param string $subject Email subject
     * @param string $message Message contents
     * @param string|array $headers Optional. Additional headers.
     * @param string|array $attachments Optional. Files to attach.
     * @return bool Whether the email contents were sent successfully.
     * @since 2.6.0
     * @version 2.6.0
     */
    public function process_email( $to, $subject, $message, $headers = '', $attachments = array() ) {
		
		$body = array();
		$pubkey = get_option( 'mainwp_child_pubkey' );
		$pubkey = $pubkey ? md5( $pubkey ) : '';
        $request_headers = array(
            'Site-Id'	=>	get_option( 'mainwp_child_siteid' ),
			'API-Key'	=>	$pubkey
        );
		
		//let's manage attachments
		if( !empty( $attachments ) && $attachments ) {

			$_attachments = $attachments;
			$attachments = array();
			foreach( $_attachments as $attachment ) {
				
				$attachments[$attachment] = file_get_contents( $attachment );
					
			}
			
		}
			
		$body = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
		$action_nonce = apply_filters( 'mainwp_child_create_action_nonce', false, 'post-smtp-send-mail' );
		$ping_nonce = apply_filters( 'mainwp_child_get_ping_nonce', '' );
		$this->base_url = "$this->base_url/?actionnonce={$action_nonce}&pingnonce={$ping_nonce}";

        $response = wp_remote_post(
            $this->base_url,
            array(
                'method'	=> 'POST',
                'body'		=>	$body,
                'headers'	=>	$request_headers
            )
        );
		
        if( wp_remote_retrieve_body( $response ) ) {
			
			return true;
			
		}

    }


}

endif;