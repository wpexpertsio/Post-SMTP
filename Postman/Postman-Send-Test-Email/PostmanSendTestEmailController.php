<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PostmanSendTestEmailController {
	const EMAIL_TEST_SLUG = 'postman/email_test';
	const RECIPIENT_EMAIL_FIELD_NAME = 'postman_recipient_email';
	
	// logging
	private $logger;
	private $options;
	private $allowed_tags = array(
		'input'			=>	array(
			'type'			=>	array(),
			'id'			=>	array(),
			'name'			=>	array(),
			'value'			=>	array(),
			'class'			=>	array(),
			'placeholder'	=>	array(),
			'size'			=>	array(),
		)
	);
	
	// Holds the values to be used in the fields callbacks
	private $rootPluginFilenameAndPath;
	
	/**
	 * Constructor
	 *
	 * @param mixed $rootPluginFilenameAndPath
	 */
	public function __construct( $rootPluginFilenameAndPath ) {
		assert( ! empty( $rootPluginFilenameAndPath ) );
		assert( PostmanUtils::isAdmin() );
		assert( is_admin() );
		
		$this->logger = new PostmanLogger( get_class( $this ) );
		$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
		$this->options = PostmanOptions::getInstance();
		
		PostmanUtils::registerAdminMenu( $this, 'addEmailTestSubmenu' );
		
		// hook on the init event
		add_action( 'init', array(
			$this,
			'on_init',
		) );
		
		// initialize the scripts, stylesheets and form fields
		add_action( 'admin_init', array(
			$this,
			'on_admin_init',
		) );
	}
	
	/**
	 * Functions to execute on the init event
	 *
	 * "Typically used by plugins to initialize. The current user is already authenticated by this time."
	 * ref: http://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request
	 */
	public function on_init() {
		// register Ajax handlers
		new PostmanSendTestEmailAjaxController();
	}
	
	/**
	 * Fires on the admin_init method
	 */
	public function on_admin_init() {
		$this->registerStylesAndScripts();
	}
	
	/**
	 * Get the settings option array and print one of its values
	 */
	public function test_email_callback() {
		return sprintf(
			'<input type="text" id="%s" name="postman_test_options[test_email]" value="%s" class="ps-input required email" size="40"/>',
			esc_attr( self::RECIPIENT_EMAIL_FIELD_NAME ),
			esc_attr( wp_get_current_user()->user_email )
		);
	}
	
	/**
	 * Register and add settings
	 */
	private function registerStylesAndScripts() {
		if ( $this->logger->isTrace() ) {
			$this->logger->trace( 'registerStylesAndScripts()' );
		}
		
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		
		// register the stylesheet resource
		wp_register_style( 'postman_send_test_email', plugins_url( 'Postman/Postman-Send-Test-Email/postman_send_test_email.css', $this->rootPluginFilenameAndPath ), PostmanViewController::POSTMAN_STYLE, $pluginData ['version'] );
		
		// register the javascript resource
		wp_register_script( 'postman_test_email_wizard_script', plugins_url( 'Postman/Postman-Send-Test-Email/postman_send_test_email.js', $this->rootPluginFilenameAndPath ), array(
			PostmanViewController::JQUERY_SCRIPT,
			'jquery_validation',
			'jquery_steps_script',
			PostmanViewController::POSTMAN_SCRIPT,
		), $pluginData ['version'] );
	}
	
	/**
	 * Register the Email Test screen
	 */
	public function addEmailTestSubmenu() {
		$page = add_submenu_page(
			'',
			sprintf( '%s', esc_html__( 'Postman SMTP Setup', 'post-smtp' ) ),
			esc_html__( 'Postman SMTP', 'post-smtp' ),
			Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanSendTestEmailController::EMAIL_TEST_SLUG, array(
			$this,
			'outputTestEmailContent',
		) );
		// When the plugin options page is loaded, also load the stylesheet
		add_action( 'admin_print_styles-' . $page, array(
			$this,
			'enqueueEmailTestResources',
		) );
	}
	
	/**
	 */
	function enqueueEmailTestResources() {
		wp_enqueue_style( 'jquery_steps_style' );
		wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
		wp_enqueue_style( 'postman_send_test_email' );
		wp_enqueue_script( 'postman_test_email_wizard_script' );
		wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_email_test', array(
			'recipient' => '#' . self::RECIPIENT_EMAIL_FIELD_NAME,
			'not_started' => _x( 'In Outbox', 'Email Test Status', 'post-smtp' ),
			'sending' => _x( 'Sending...', 'Email Test Status', 'post-smtp' ),
			'success' => _x( 'Success', 'Email Test Status', 'post-smtp' ),
			//'failed' => _x( 'Failed', 'Email Test Status', 'post-smtp' ),
			'failed' => sprintf( 'Failed - Check the plugin email log for more info: %s', '<a href="' . esc_url( admin_url( 'admin.php?page=postman_email_log' ) ) . '">Here</a>' ),
			'ajax_error' => __( 'Ajax Error', 'post-smtp' ),
		) );
	}
	
	/**
	 */
	public function outputTestEmailContent() {
		$page_url = add_query_arg(
			array(
				'page' => 'postman',
			),
			admin_url( 'admin.php' )
		);
		
		$user_email = wp_get_current_user()->user_email;
		
		$nonce_field = wp_nonce_field( 'post-smtp', 'security', true, false );
		echo '
		<div class="send-test-email">
		
		<div class="ps-logo">
			<img src="https://postmansmtp.com/wp-content/uploads/2022/06/postman-smtp-mailer-1024x163.png" width="250px" alt="Post SMTP Logo">
		</div>
		
			<div class="ps-outer">

				<div class="ps-body-section">

					<div class="ps-nav float-left">

						<table>
							<tr>
								<td class="ps-circle">
									<span class="ps-tick dashicons dashicons-yes-alt birth-check"></span>
								</td>
								<td class="ps-text">
									' . esc_html__( 'Send Test Email', 'post-smtp' ) . '
								</td>
							</tr>
						</table>

					</div>
					
					<div class="ps-pages float-right">

						<form action="' . esc_attr( $page_url ) . '" method="post" id="postman_test_email_wizard">

							<div class="ps-screens-container">
								<div class="ps-step">

									<p>
										' . esc_html__( 'This step allows you to send an email message for testing. If there is a problem, Post SMTP will give up after 60 seconds.', 'post-smtp' ) . '
									</p>

									<div class="ps-form-ui">

										<div class="ps-form-control">

											<div>
												<label for="postman_test_options_test_email">
													' . esc_html__( 'Recipient Email Address', 'post-smtp' ) . '
												</label>
											</div>

											<input id="postman_test_options_test_email" type="text" class="ps-test-to" required="" data-error="Enter Recipient Email Address" name="postman_test_options[test_email]" value="' . esc_attr( $user_email ) . '" placeholder="Recipient Email Address">
											' . $nonce_field . '

											<span class="ps-form-control-info">
												' . esc_html__( 'Enter the email address where you want to send the test email.', 'post-smtp' ) . '
											</span>
											
											<p class="ps-form-control-info">
												' . esc_html__( 'Are your WordPress emails getting broken? Check out our guide on', 'post-smtp' ) . '
												<a href="https://postmansmtp.com/fix-for-broken-emails/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin" target="_blank">' . esc_html__( 'how to Fix Broken Emails', 'post-smtp' ) . '</a>
												.
											</p>
										</div>

										<span id="when-button-clicked" class="float-left spinner" style="margin-top: 10px;display: none;"></span>
										<button class="button button-primary ps-blue-button ps-next-button">
											' . esc_html__( 'Send Test Email', 'post-smtp' ) . '
											<span class="dashicons dashicons-email"></span>
										</button>
										
										<a id="ps-show-transcript" href="#" style="float:right;margin-top: 10px;display: none;">View Transcript</a>
										
										<div>
											<p class="ps-success"></p>
											<p class="ps-error"></p>
										</div>

										<div id="ps-transcript-container" style="display: none;">
											<p style="display: none;" class="ps-transcript">
											<textarea readonly cols="65" rows="8"></textarea>
</p>
										</div>
									</div>

								</div>
							</div>

						</form>
						
					</div>

					

				</div>

				<div class="ps-footer">
					<div class="ps-footer-content float-left">
						<div class="ps-nav">
							<table>

								<tr>
									<td class="ps-circle">
										<span class="ps-tick dashicons dashicons-yes-alt ps-ste-bm">
											<span class="ps-line"></span>
										</span>
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<div class="ps-footer-content float-right">
						<div class="ps-step">



						</div>
					</div>
				</div>
			</div>
		</div>
		';
	}
}

/**
 *
 * @author jasonhendriks
 */
class PostmanSendTestEmailAjaxController extends PostmanAbstractAjaxHandler {
	
	/**
	 * Constructor
	 *
	 * @param PostmanOptions          $options
	 * @param PostmanOAuthToken       $authorizationToken
	 * @param PostmanConfigTextHelper $oauthScribe
	 */
	function __construct() {
		parent::__construct();
		PostmanUtils::registerAjaxHandler( 'postman_send_test_email', $this, 'sendTestEmailViaAjax' );
	}
	
	/**
	 * Yes, this procedure is just for testing.
	 *
	 * @return boolean
	 */
	function test_mode() {
		return true;
	}
	
	/**
	 * This Ajax sends a test email
	 *
	 * @since 1.0
	 * @since 2.0.25 @filter `postman_test_email_args`
	 * @version 1.0
	 */
	function sendTestEmailViaAjax() {
		
		check_admin_referer('post-smtp', 'security');
		
		if( !current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error(
				array(
					'Message'	=>	'Unauthorized.'
				),
				401
			);
		}
		
		// get the email address of the recipient from the HTTP Request
		$email = $this->getRequestParameter( 'email' );
		
		// get the name of the server from the HTTP Request
		$serverName = PostmanUtils::postmanGetServerName();
		
		/* translators: where %s is the domain name of the site */
		$subject = sprintf( _x( 'Postman SMTP Test (%s)', 'Test Email Subject', 'post-smtp' ), $serverName );
		
		// Postman API: indicate to Postman this is just for testing
		add_filter( 'postman_test_email', array(
			$this,
			'test_mode',
		) );
		
		// this header specifies that there are many parts (one text part, one html part)
		$header = 'Content-Type: multipart/alternative;' . "\r\n";
		$header .= 'MIME-Version: 1.0' . "\r\n";
		
		// createt the message content
		$message = $this->createMessageContent();
		
		$email_args = apply_filters( 'postman_test_email_args', compact( 'email', 'subject', 'message', 'header' ) );
		extract( $email_args );
		
		// send the message
		$success = wp_mail( $email, $subject, $message, $header );
		
		// Postman API: remove the testing indicator
		remove_filter( 'postman_test_email', array(
			$this,
			'test_mode',
		) );
		
		// Postman API: retrieve the result of sending this message from Postman
		$result = apply_filters( 'postman_wp_mail_result', null );
		
		// post-handling
		if ( $success ) {
			$this->logger->debug( 'Test Email delivered to server' );
			// the message was sent successfully, generate an appropriate message for the user
			$statusMessage = sprintf( __( 'Your message was delivered (%d ms) to the SMTP server! Congratulations :)', 'post-smtp' ), $result ['time'] );
			
			$this->logger->debug( 'statusmessage: ' . $statusMessage );
			
			// compose the JSON response for the caller
			$response = array(
				'message' => $statusMessage,
				'transcript' => $result ['transcript'],
			);
			
			// log the response
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'Ajax Response:' );
				$this->logger->trace( $response );
			}
			
			// send the JSON response
			wp_send_json_success( $response );
		} else {
			$this->logger->error( 'Test Email NOT delivered to server - ' . $result ['exception']->getCode() );
			// the message was NOT sent successfully, generate an appropriate message for the user
			$statusMessage = $result ['exception']->getMessage();
			
			$this->logger->debug( 'statusmessage: ' . $statusMessage );
			
			// compose the JSON response for the caller
			$response = array(
				'message' => $statusMessage,
				'transcript' => $result ['transcript'],
			);
			
			// log the response
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'Ajax Response:' );
				$this->logger->trace( $response );
			}
			
			// send the JSON response
			wp_send_json_error( $response );
		}
	}
	
	/**
	 * Create the multipart message content
	 *
	 * @return string
	 */
	private function createMessageContent() {
		// Postman API: Get the plugin metadata
		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		
		/*
		translators: where %s is the Postman plugin version number (e.g. 1.4) */
		// English - Mandarin - French - Hindi - Spanish - Portuguese - Russian - Japanese
		// http://www.pinyin.info/tools/converter/chars2uninumbers.html
		$greeting = 'Hello! - &#20320;&#22909; - Bonjour! - &#2344;&#2350;&#2360;&#2381;&#2340;&#2375; - ¡Hola! - Ol&#225; - &#1055;&#1088;&#1080;&#1074;&#1077;&#1090;! - &#20170;&#26085;&#12399;';
		$sentBy = sprintf( _x( 'Sent by Postman %s', 'Test Email Tagline', 'post-smtp' ), $pluginData ['version'] );
		$imageSource = __( 'Image source', 'post-smtp' );
		$withPermission = __( 'Used with permission', 'post-smtp' );
		$messageArray = array(
			'Content-Type: text/plain; charset = "UTF-8"',
			'Content-Transfer-Encoding: 8bit',
			'',
			'Hello!',
			'',
			sprintf( '%s - https://wordpress.org/plugins/post-smtp/', $sentBy ),
			'',
			'Content-Type: text/html; charset=UTF-8',
			'Content-Transfer-Encoding: quoted-printable',
			'',
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
			'<html xmlns="http://www.w3.org/1999/xhtml">',
			'<head>',
			'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',
			'<style type="text/css" media="all">',
			'.wporg-notification .im {',
			'	color: #888;',
			'} /* undo a GMail-inserted style */',
			'</style>',
			'</head>',
			'<body class="wporg-notification">',
			'	<div style="background: #e8f6fe; font-family: &amp; quot; Helvetica Neue&amp;quot; , Helvetica ,Arial,sans-serif; font-size: 14px; color: #666; text-align: center; margin: 0; padding: 0">',
			'		<table border="0" cellspacing="0" cellpadding="0" bgcolor="#e8f6fe"	style="background: #e8f6fe; width: 100%;">',
			'			<tbody>',
			'				<tr>',
			'					<td>',
			'						<table border="0" cellspacing="0" cellpadding="0" align="center" style="padding: 0px; width: 100%;"">',
			'							<tbody>',
			'								<tr>',
			'									<td>',
			'										<div style="max-width: 600px; height: 400px; margin: 0 auto; overflow: hidden;background-image:url(\'https://ps.w.org/postman-smtp/assets/email/poofytoo.png\');background-repeat: no-repeat;">',
			sprintf( '											<div style="margin:50px 0 0 300px; width:300px; font-size:2em;">%s</div>', $greeting ),
			sprintf( '											<div style="text-align:right;font-size: 1.4em; color:black;margin:150px 0 0 200px;">%s', $sentBy ),
			'												<br/>',
			'											</div>',
			'										</div>',
			'									</td>',
			'								</tr>',
			'							</tbody>',
			'						</table>',
			sprintf( '						<br><span style="font-size:0.9em;color:#94c0dc;">%s: poofytoo - %s</span>', $imageSource, $withPermission ),
			'					</td>',
			'				</tr>',
			'			</tbody>',
			'		</table>',
			'</body>',
			'</html>',
		);
		return implode( PostmanMessage::EOL, $messageArray );
	}
}