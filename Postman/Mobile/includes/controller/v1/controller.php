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

			add_action( 'admin_action_post_smtp_disconnect_app', array( $this, 'disconnect_app' ) );

			if( $connected_devices[$this->auth_token]['enable_notification'] ) {

				add_action( 'post_smtp_on_failed', array( $this, 'push_notification' ), 10, 5 );

			}
			
		}
		
	}
	
	public function disconnect_app() {
		
		if( isset( $_GET['action'] ) && $_GET['action'] == 'post_smtp_disconnect_app' ) {
			
			$connected_devices = get_option( 'post_smtp_mobile_app_connection' );
			$auth_token = $_GET['auth_token'];
			$server_url = get_option( 'post_smtp_server_url' );
			
			if( $connected_devices && isset( $connected_devices[$auth_token] ) ) {
				
				$device = $connected_devices[$auth_token];
				$auth_key = $device['auth_key'];
				
				$response = wp_remote_post(
					"{$server_url}/disconnect-app",
					array(
						'method'	=>	'POST',
						'headers'	=>	array(
							'Content-Type'	=>	'application/json',
							'Auth-Key'		=>	$auth_key,
							'FCM-Token'		=>	$auth_token
						)
					)
				);
				
				$response_code = wp_remote_retrieve_response_code( $response );
				
				if( $response_code == 200 ) {
					
					delete_option( 'post_smtp_mobile_app_connection' );
					delete_option( 'post_smtp_server_url' );
					
				}
				
			}
			
			wp_redirect( admin_url( 'admin.php?page=postman/configuration#mobile-app' ) );
			
		}
		
	}
	
	public function push_notification( $log, $postmanMessage, $transcript, $transport, $errorMessage ) {
		
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