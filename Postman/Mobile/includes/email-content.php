<?php

class Post_SMTP_Email_Content {
	
	private $access_token = '';
	private $log_id = '';
	private $type = '';
	
	public function __construct() {
		
		if( 
			is_admin()
			&&
			isset( $_GET['access_token'] ) 
			&&
			isset( $_GET['log_id'] ) 
			&&
			isset( $_GET['type'] ) 
		) {
			
			$this->access_token = sanitize_text_field( $_GET['access_token'] );
			$this->log_id = sanitize_text_field( $_GET['log_id'] );
			$this->type = sanitize_text_field( $_GET['type'] );
			
			$this->render_html();
			
		}
		
	} 
	
	public function render_html() {
		
		$device = get_option( 'post_smtp_mobile_app_connection' );
		
		if( empty( $this->access_token ) ) {
			
			wp_send_json_error( 
				array(
					'error'	=>	'Auth token missing.'
				), 
				400 
			);
			
		}
		//Valid Request
		elseif( $device && isset( $device[$this->access_token] ) ) {
			
			if( !class_exists( 'PostmanEmailQueryLog' ) ) {

				require POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';

			}

			$logs_query = new PostmanEmailQueryLog();

			if( $this->type == 'log' ) {

					$log = $logs_query->get_log( 
						$this->log_id,
						array(
							'from_header',
							'original_to',
							'time',
							'original_subject',
							'transport_uri',
							'original_message'
						)
					);
				
					if( empty( $log ) ) {
						
						wp_send_json_error(
							array(
								'message'	=> "{$this->type} not found for id {$this->log_id}"
							),
							404
						);
						
					}
				
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
			
					?>
					<html>
						<head>
							<meta name="viewport" content="width=device-width, initial-scale=1.0">
							<style>
								* {
									box-sizing: border-box;
								}
								table {
									margin-top: 15px;
									font-size: 12px;
								}
								.container {
									margin: 0 auto;
									width: 95%;
								}
								table tbody td {
									padding: 3px;
								}
								.message-body {
									margin-top: 15px;
								}
							</style>
						</head>
						<body>
							<div class="container">
								<table width="100%">
									<tbody>
										<tr>
											<td><strong>From:</strong></td>
											<td><?php echo esc_html( $log['from_header'] ); ?></td>
										</tr>
										<tr>
											<td><strong>To:</strong></td>
											<td><?php echo esc_html( $log['original_to'] ); ?></td>
										</tr>
										<tr>
											<td><strong>Date:</strong></td>
											<td><?php echo esc_html( date( "{$date_format} {$time_format}", $log['time'] ) ); ?></td>
										</tr>
										<tr>
											<td><strong>Subject:</strong></td>
											<td><?php echo esc_html( $log['original_subject'] ); ?></td>
										</tr>
										<tr>
											<td><strong>Delivery-URI:</strong></td>
											<td><?php echo esc_html( $log['transport_uri'] ); ?></td>
										</tr>
									</tbody>
								</table>
								<div class="message-body">
									<?php echo $log['original_message']; ?>
								</div>
							</div>
						</body>
					</html>
					<?php
					die;
			}
			
			if( $this->type == 'transcript' ) {

				$log = $logs_query->get_log( 
					$this->log_id,
					array(
						'session_transcript'
					)
				);

				if( empty( $log ) ) {

					wp_send_json_error(
						array(
							'message'	=> "{$this->type} not found for id {$this->log_id}"
						),
						404
					);

				}
				?>
				<html>
					<head>
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<style>
							* {
								box-sizing: border-box;
							}
							.container {
								margin: 0 auto;
								width: 95%;
							}
							.message-body {
								margin-top: 15px;
							}
						</style>
					</head>
					<body>
						<div class="container">
							<div class="message-body">
								<?php echo $log['session_transcript']; ?>
							</div>
						</div>
					</body>
				</html>
				<?php
				die;
			}
			
			if( $this->type == 'details' ) {

				$log = $logs_query->get_log( 
					$this->log_id,
					array(
						'success'
					)
				);

				if( empty( $log ) ) {

					wp_send_json_error(
						array(
							'message'	=> "{$this->type} not found for id {$this->log_id}"
						),
						404
					);

				}
				?>
				<html>
					<head>
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<style>
							* {
								box-sizing: border-box;
							}
							.container {
								margin: 0 auto;
								width: 95%;
							}
							.message-body {
								margin-top: 15px;
							}
						</style>
					</head>
					<body>
						<div class="container">
							<div class="message-body">
								<?php echo $log['success']; ?>
							</div>
						</div>
					</body>
				</html>
				<?php
				die;
			}
			
		}
		else {
			
			wp_send_json_error( 
				array(
					'error'	=>	'Invalid Auth Token.'
				), 
				401 
			);
			
		}
		
	}
	
}

new Post_SMTP_Email_Content();