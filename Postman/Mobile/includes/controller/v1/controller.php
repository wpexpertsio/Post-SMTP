<?php

class Post_SMTP_Mobile_Controller {
	
	private $baseurl = '';
	private $auth_key = '';
	private $auth_token = '';
	
	public function __construct() {
		
		$this->baseurl = get_option( 'post_smtp_server_url' );
		$connected_devices = get_option( 'post_smtp_mobile_app_connection' );
		
		if( $connected_devices ) {
			
			$this->auth_token = array_keys( $connected_devices );
			$this->auth_token = $this->auth_token[0];
			$this->auth_key = $connected_devices[$this->auth_token]['auth_key'];

			if( $connected_devices[$this->auth_token]['enable_notification'] ) {

				add_action( 'post_smtp_on_failed', array( $this, 'push_notification' ), 10, 5 );

			}
			
		}
		
	}
	
	public function push_notification( $log, $postmanMessage, $transcript, $transport, $errorMessage ) {

		if ( ! class_exists( 'PostmanNotify' ) || ! PostmanNotify::should_send_failure_notification( $postmanMessage ) ) {
			return;
		}

		$site_title = get_bloginfo( 'name' );
		$title = '🚫 Email failed';
		$title = !empty( $site_title ) ? "{$title} - {$site_title}" : $title;

		$response = wp_remote_post(
			"{$this->baseurl}/push-notification",
			array(
				'method'	=>	'POST',
				'headers'	=>	array(
					'Auth-Key'		=>	$this->auth_key,
					'FCM-Token'		=>	$this->auth_token
				),
				'body'		=>	array(
					'title'		=>	$title,
					'body'		=>	$errorMessage
				)
			)
		);
	}
	
}

new Post_SMTP_Mobile_Controller;