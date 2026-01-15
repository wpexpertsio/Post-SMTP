<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'INotify.php';
require_once 'PostmanMailNotify.php';
require_once 'PostmanPushoverNotify.php';
require_once 'PostmanSlackNotify.php';
require_once 'PostmanWebhookAlertsNotify.php';
require_once 'PostmanNotifyOptions.php';

class PostmanNotify {

	const NOTIFICATIONS_OPTIONS            = 'postman_notifications_options';
	const NOTIFICATIONS_SECTION            = 'postman_notifications_section';
	const NOTIFICATIONS_PUSHOVER_CRED      = 'postman_pushover_cred';
	const NOTIFICATIONS_SLACK_CRED         = 'postman_slack_cred';
	const NOTIFICATIONS_WEBHOOK_ALERT_URLS = 'postman_webhook_alerts_urls';
	const CHROME_EXTENSION                 = 'postman_chrome_extension';
	const NOTIFICATION_EMAIL               = 'notification_email';

	private $options;

	public function __construct() {

		$this->options = PostmanNotifyOptions::getInstance();

		add_filter( 'post_smtp_admin_tabs', array( $this, 'tabs' ) );
		add_action( 'post_smtp_settings_menu', array( $this, 'menu' ) );
		add_action( 'post_smtp_settings_fields', array( $this, 'settings' ) );
		add_action( 'post_smtp_on_failed', array( $this, 'notify' ), 10, 5 );
		add_filter( 'post_smtp_sanitize', array( $this, 'sanitize' ), 10, 3 );
		
		// Register AJAX handler for test notification
		if ( is_admin() ) {
			add_action( 'wp_ajax_postman_send_test_notification', array( $this, 'send_test_notification_ajax' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_test_notification_scripts' ) );
		}
	}

	public function menu() {
		print '<section id="notifications">';

		do_settings_sections( self::NOTIFICATIONS_OPTIONS );

		$currentKey         = $this->options->getNotificationService();
		$pushover           = $currentKey == 'pushover' ? 'block' : 'none';
		$slack              = $currentKey == 'slack' ? 'block' : 'none';
		$webhook_alerts     = $currentKey == 'webhook_alerts' ? 'block' : 'none';
		$notification_email = $currentKey == 'default' ? 'block' : 'none';

		echo '<div id="email_notify" style="display: ' . $notification_email . ';">';
		do_settings_sections( self::NOTIFICATION_EMAIL );
		echo '</div>';

		echo '<div id="pushover_cred" style="display: ' . $pushover . ';">';
		do_settings_sections( self::NOTIFICATIONS_PUSHOVER_CRED );
		echo '</div>';

		echo '<div id="slack_cred" style="display: ' . $slack . ';">';
		do_settings_sections( self::NOTIFICATIONS_SLACK_CRED );
		echo '</div>';

		echo '<div id="webhook_alert_urls" style="display: ' . $webhook_alerts . ';">';
		do_settings_sections( self::NOTIFICATIONS_WEBHOOK_ALERT_URLS );
		echo '</div>';

		do_action( 'post_smtp_notification_settings' );

		do_settings_sections( self::CHROME_EXTENSION );

		print '</section>';
	}

	public function sanitize( $new_input, $input, $sanitizer ) {
		// Notifications
		$sanitizer->sanitizeString( 'Pushover Service', PostmanNotifyOptions::NOTIFICATION_SERVICE, $input, $new_input, $this->options->getNotificationService() );
		$sanitizer->sanitizePassword( 'Pushover Username', PostmanNotifyOptions::PUSHOVER_USER, $input, $new_input, $this->options->getPushoverUser() );
		$sanitizer->sanitizePassword( 'Pushover Token', PostmanNotifyOptions::PUSHOVER_TOKEN, $input, $new_input, $this->options->getPushoverToken() );
		$sanitizer->sanitizePassword( 'Slack Token', PostmanNotifyOptions::SLACK_TOKEN, $input, $new_input, $this->options->getSlackToken() );

		// Chrome extension
		$sanitizer->sanitizeString( 'Push Chrome Extension', PostmanNotifyOptions::NOTIFICATION_USE_CHROME, $input, $new_input );
		$sanitizer->sanitizePassword( 'Push Chrome Extension UID', PostmanNotifyOptions::NOTIFICATION_CHROME_UID, $input, $new_input, $this->options->getNotificationChromeUid() );

		// Email Notification
		$sanitizer->sanitizeString( 'Email Notification', PostmanNotifyOptions::NOTIFICATION_EMAIL, $input, $new_input, $this->options->get_notification_email() );

        //Webhook Alerts
        $webhook_urls = array();

        if( isset( $_POST['postman_options']['webhook_alerts_urls'] ) ) {
            
            foreach ( $_POST['postman_options']['webhook_alerts_urls'] as $key => $url ) {

                if( ! empty( $url ) ) {
                    $webhook_urls[] = esc_url( $url );
                }
    
            }

		}

		update_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION, $webhook_urls );

		return $new_input;
	}

	public function tabs( $tabs ) {
		$tabs['notifications'] = sprintf( '<span class="dashicons dashicons-bell"></span> %s', __( 'Notifications', 'post-smtp' ) );

		return $tabs;
	}

	public function settings() {
		// Notifications
		add_settings_section(
			self::NOTIFICATIONS_SECTION,
			_x( 'Notifications Settings', 'Configuration Section Title', 'post-smtp' ),
			array(
				$this,
				'notification_selection',
			),
			self::NOTIFICATIONS_OPTIONS
		);

		// Pushover
		add_settings_section(
			'pushover_credentials',
			_x( 'Pushover Credentials', 'Configuration Section Title', 'post-smtp' ),
			array(
				$this,
				'section',
			),
			self::NOTIFICATIONS_PUSHOVER_CRED
		);

		add_settings_field(
			PostmanNotifyOptions::PUSHOVER_USER,
			_x( 'Pushover User Key', 'Configuration Input Field', 'post-smtp' ),
			array(
				$this,
				'pushover_user_callback',
			),
			self::NOTIFICATIONS_PUSHOVER_CRED,
			'pushover_credentials'
		);

		add_settings_field(
			PostmanNotifyOptions::PUSHOVER_TOKEN,
			_x( 'Pushover App Token', 'Configuration Input Field', 'post-smtp' ),
			array(
				$this,
				'pushover_token_callback',
			),
			self::NOTIFICATIONS_PUSHOVER_CRED,
			'pushover_credentials'
		);

		// Slack
		add_settings_section(
			'slack_credentials',
			_x( 'Slack Credentials', 'Configuration Section Title', 'post-smtp' ),
			array(
				$this,
				'section',
			),
			self::NOTIFICATIONS_SLACK_CRED
		);

		add_settings_field(
			PostmanNotifyOptions::SLACK_TOKEN,
			_x( 'Slack Webhook', 'Configuration Input Field', 'post-smtp' ),
			array(
				$this,
				'slack_token_callback',
			),
			self::NOTIFICATIONS_SLACK_CRED,
			'slack_credentials'
		);

		// Webhook Alerts
		add_settings_section(
			'webhook_alert_urls',
			_x( 'Webhook Alerts', 'Configuration Section Title', 'post-smtp' ),
			array(
				$this,
				'webhook_alerts_section',
			),
			self::NOTIFICATIONS_WEBHOOK_ALERT_URLS
		);

		// Email Notification
		add_settings_section(
			'email_notification',
			'',
			array( $this, 'email_notification' ),
			self::NOTIFICATION_EMAIL
		);

		add_settings_section(
			'chrome_notification',
			'Setup Chrome extension (optional)',
			array( $this, 'chrome_extension' ),
			self::CHROME_EXTENSION
		);

		add_settings_field(
			PostmanNotifyOptions::NOTIFICATION_USE_CHROME,
			_x( 'Push to chrome extension', 'Configuration Input Field', 'post-smtp' ),
			array(
				$this,
				'notification_use_chrome_callback',
			),
			self::CHROME_EXTENSION,
			'chrome_notification'
		);

		add_settings_field(
			'notification_chrome_uid',
			_x( 'Chrome Extension UID', 'Configuration Input Field', 'post-smtp' ),
			array(
				$this,
				'notification_chrome_uid_callback',
			),
			self::CHROME_EXTENSION,
			'chrome_notification'
		);
	}

	public function notification_use_chrome_callback() {

		$value = $this->options->useChromeExtension() ? 'checked="checked"' : '';
		$id    = PostmanNotifyOptions::NOTIFICATION_USE_CHROME;
		?>
		<label class="ps-switch-1"> 
			<input type="checkbox" name="<?php echo 'postman_options[' . esc_attr( $id ) . ']'; ?>" id="<?php echo 'input_' . esc_attr( $id ); ?>" class="<?php echo 'input_' . esc_attr( $id ); ?>" <?php echo esc_attr( $value ); ?> />
			<span class="slider round"></span>
		</label> 
		<?php
	}

	public function notification_chrome_uid_callback() {
		printf( '<input type="password" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', 'postman_options', 'notification_chrome_uid', PostmanUtils::obfuscatePassword( $this->options->getNotificationChromeUid() ) );
	}

	public function pushover_user_callback() {
		printf( '<input type="password" id="pushover_user" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::PUSHOVER_USER, $this->options->getPushoverUser() );
	}

	public function pushover_token_callback() {
		printf( '<input type="password" id="pushover_token" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::PUSHOVER_TOKEN, $this->options->getPushoverToken() );
		$this->render_service_test_button( 'pushover' );
	}

	public function slack_token_callback() {
		printf( '<input type="password" id="slack_token" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::SLACK_TOKEN, $this->options->getSlackToken() );
		echo '<a target="_blank" href="https://slack.postmansmtp.com/">' . __( 'Get your webhook URL here', 'post-smtp' ) . '</a>';
		$this->render_service_test_button( 'slack' );
	}

	/**
	 * Webhook Alerts | Section call-back
	 *
	 * @since 3.1.0
	 */
	public function webhook_alerts_section() {

		$webhook_urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION );
		$webhook_urls = $webhook_urls ? $webhook_urls : array( '' );
		$i            = 0;

		echo "<table class='form-table post-smtp-webhook-urls'>";

		do {

			$remove_btn = $i == 0 ? '' : '<span class="post-smtp-remove-webhook-url dashicons dashicons-trash"></span>';

			echo "<tr class='post-smtp-webhook-url-container'>
                    <th>" . __( 'Webhook URL', 'post-smtp' ) . "</th>
                    <td><input type='text' name='postman_options[webhook_alerts_urls][]' value='" . esc_url( $webhook_urls[ $i ] ) . "' />" . $remove_btn . '</td>
                </tr>';

			++$i;

		} while ( $i < count( $webhook_urls ) );

		echo "<tr>
                <td></td>
                <td><a href='' class='button button-primary post-smtp-add-webhook-url'>" . __( 'Add Another Webhook URL', 'post-smtp' ) . '</a></td>
            </tr>
        </table>';
		$this->render_service_test_button( 'webhook_alerts' );
	}

	/**
	 * @param PostmanEmailLog  $log
	 * @param PostmanMessage   $message
	 * @param string           $transcript
	 * @param PostmanTransport $transport
	 * @param string           $errorMessage
	 */
	public function notify( $log, $postmanMessage, $transcript, $transport, $errorMessage ) {
		$message  = __( 'You getting this message because an error detected while delivered your email.', 'post-smtp' );
		$message .= "\r\n" . sprintf( __( 'For the domain: %1$s', 'post-smtp' ), get_bloginfo( 'url' ) );
		$message .= "\r\n" . __( 'The log to paste when you open a support issue:', 'post-smtp' ) . "\r\n";

		if ( $errorMessage && ! empty( $errorMessage ) ) {

			$message = $message . $errorMessage;

			$notification_service = PostmanNotifyOptions::getInstance()->getNotificationService();
			switch ( $notification_service ) {
				case 'none':
					$notifyer = false;
					break;
				case 'default':
					$notifyer = new PostmanMailNotify();
					break;
				case 'pushover':
					$notifyer = new PostmanPushoverNotify();
					break;
				case 'slack':
					$notifyer = new PostmanSlackNotify();
					break;
				case 'webhook_alerts':
					$notifyer = new PostmanWebhookAlertsNotify();
					break;
				default:
					$notifyer = new PostmanMailNotify();
			}

			$notifyer = apply_filters( 'post_smtp_notifier', $notifyer, $notification_service );
			// Notifications
			if ( $notifyer ) {
				$notifyer->send_message( $message, $log );
			}
			$this->push_to_chrome( $errorMessage );
		}
	}

	public function push_to_chrome( $message ) {
		$push_chrome = PostmanNotifyOptions::getInstance()->useChromeExtension();

		if ( $push_chrome ) {
			$uid = PostmanNotifyOptions::getInstance()->getNotificationChromeUid();

			if ( empty( $uid ) ) {
				return;
			}

			$url = 'https://chrome.postmansmtp.com/' . $uid;

			$args = array(
				'body' => array(
					'message'  => $message,
					'site_url' => get_bloginfo( 'url' ),
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				error_log( 'Chrome notification error: ' . $response->get_error_message() );
			}

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				error_log( 'Chrome notification error HTTP Error:' . wp_remote_retrieve_response_code( $response ) );
			}
		}
	}

	/**
	 * Section
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function section() {
	}

	/**
	 * Notification Selection
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function notification_selection() {

		$options    = apply_filters(
			'post_smtp_notification_service',
			array(
				'none'           => __( 'None', 'post-smtp' ),
				'default'        => __( 'Admin Email', 'post-smtp' ),
				'slack'          => __( 'Slack', 'post-smtp' ),
				'pushover'       => __( 'Pushover', 'post-smtp' ),
				'webhook_alerts' => __( 'Webhook Alerts', 'post-smtp' ),
			)
		);
		$currentKey = $this->options->getNotificationService();
		$logs_url   = admin_url( 'admin.php?page=postman_email_log' );

		echo '<p>' . sprintf(
			esc_html__( 'Select a service to notify you when an email delivery will fail. It helps keep track, so you can resend any such emails from the %s if required.', 'post-smtp' ),
			'<a href="' . $logs_url . '" target="_blank">log section</a>'
		) . '</p>';

		?>

		<div class="ps-notify-radios">
			<?php
			foreach ( $options as $key => $value ) {
				$checked = $currentKey == $key ? 'checked' : '';
				?>
				<div class="ps-notify-radio-outer">
					<div class="ps-notify-radio">
						<input type="radio" value="<?php echo esc_attr( $key ); ?>" name="postman_options[notification_service]" id="ps-notify-<?php echo esc_attr( $key ); ?>" class="input_notification_service" <?php echo esc_attr( $checked ); ?> />
						<label for="ps-notify-<?php echo esc_attr( $key ); ?>">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . "images/icons/{$key}.png" ); ?>" />
							<div class="ps-notify-tick-container">
								<div class="ps-notify-tick"><span class="dashicons dashicons-yes"></span></div>
							</div>
						</label>
					</div>
					<h4><?php echo esc_html( $value ); ?></h4>
				</div>
				<?php

			}

			if ( ! class_exists( 'PostSMTPTwilio' )  && ! post_smtp_has_pro() ) {
				?>
				<a href="https://postmansmtp.com/extensions/twilio-extension-pro/" target="_blank">
					<div class="ps-notify-radio-outer">
						<div class="ps-notify-radio pro-container">
							<label for="ps-notify-twilio-pro">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/pro.png' ); ?>" class="pro-icon" />
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/twilio.png' ); ?>" />
							</label>
						</div>
						<h4>Twilio(SMS)</h4>
					</div>
				</a>
				<?php
			}
		
			if ( ! class_exists( 'PostSMTPTwilio' ) && post_smtp_has_pro() ) {
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=post-smtp-pro' ) ); ?>" target="_blank">
					<div class="ps-notify-radio-outer">
						<div class="ps-notify-radio pro-container">
							<label for="ps-notify-twilio-pro">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/twilio.png' ); ?>" />
							</label>
						</div>
						<h4>Twilio(SMS)</h4>
					</div>
				</a>
				<?php
			}
			
			if ( ! array_key_exists( 'microsoft-teams', $options ) && post_smtp_has_pro() ) {
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=post-smtp-pro' ) ); ?>" target="_blank">
					<div class="ps-notify-radio-outer">
						<div class="ps-notify-radio pro-container">
							<label for="ps-notify-teams-pro">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/microsoft-teams.png' ); ?>" />
							</label>
						</div>
						<h4>Teams</h4>
					</div>
				</a>
				<?php
			}
			if ( ! array_key_exists( 'microsoft-teams', $options ) && ! post_smtp_has_pro() ) {
				?>
				<a href="https://postmansmtp.com/extensions/microsoft-teams-alerts" target="_blank">
					<div class="ps-notify-radio-outer">
						<div class="ps-notify-radio pro-container">
							<label for="ps-notify-teams-pro">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/pro.png' ); ?>" class="pro-icon" />
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/microsoft-teams.png' ); ?>" />
							</label>
						</div>
						<h4>Teams</h4>
					</div>
				</a>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Email Notification | Section call-back
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function email_notification() {

		$notification_emails = PostmanNotifyOptions::getInstance()->get_notification_email();

		?>
		<input type="text" name="postman_options[notification_email]" value="<?php echo esc_attr( $notification_emails ); ?>" />
		<?php
	}


	/**
	 * Chrome Extenstion | Section call-back
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function chrome_extension() {

		?>
		<div class="ps-chrome-extension">
			<p><?php _e( 'You can also get notifications in chrome for Post SMTP in case of email delivery failure.', 'post-smtp' ); ?></p>
			<a target="_blank" class="ps-chrome-download" href="https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch">
				<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/logos/chrome-24x24.png' ); ?>" />
				<?php esc_html_e( 'Download Chrome extension', 'post-smtp' ); ?>
			</a>
			<a href="https://postmansmtp.com/post-smtp-1-9-6-new-chrome-extension/" target="_blank"><?php _e( 'Detailed Documentation.', 'post-smtp' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Render Test Notification Button for specific service
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $service The notification service name.
	 */
	private function render_service_test_button( $service ) {
		$validation = $this->validate_notification_config( $service );
		$is_config_valid = $validation['valid'];
		$button_disabled = $is_config_valid ? '' : 'disabled';
		$button_id = 'postman-send-test-notification-' . $service;
		$spinner_id = 'postman-test-notification-spinner-' . $service;
		$message_id = 'postman-test-notification-message-' . $service;
		?>
		<div class="postman-service-test-notification" style="margin-top: 15px;">
			<button type="button" id="<?php echo esc_attr( $button_id ); ?>" class="button button-primary postman-send-test-notification" data-service="<?php echo esc_attr( $service ); ?>" <?php echo esc_attr( $button_disabled ); ?>>
				<?php esc_html_e( 'Send Test Notification', 'post-smtp' ); ?>
			</button>
			<span id="<?php echo esc_attr( $spinner_id ); ?>" class="spinner" style="float: none; margin-left: 10px; display: none;"></span>
			<div id="<?php echo esc_attr( $message_id ); ?>" style="margin-top: 10px;"></div>
			<?php if ( ! $is_config_valid ) : ?>
				<p class="description" style="margin-top: 10px; color: #d63638;">
					<?php echo esc_html( $validation['message'] ? $validation['message'] : __( 'Please configure all required fields and save your settings before sending a test notification.', 'post-smtp' ) ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Check if notification configuration is valid
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @return bool True if configuration is valid, false otherwise.
	 */
	private function is_notification_config_valid() {
		$service = $this->options->getNotificationService();
		
		if ( 'none' === $service ) {
			return false;
		}

		$validation = $this->validate_notification_config( $service );
		return $validation['valid'];
	}

	/**
	 * Enqueue Test Notification Scripts
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function enqueue_test_notification_scripts( $hook ) {
		// Only load on the postman settings page
		// Check for main postman page or settings page
		if ( 'toplevel_page_postman' !== $hook && false === strpos( $hook, 'postman' ) ) {
			return;
		}

		$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
		
		wp_register_script(
			'postman_test_notification',
			plugins_url( 'Postman/Extensions/Core/Notifications/postman_test_notification.js', POST_SMTP_BASE ),
			array( 'jquery' ),
			$pluginData['version'],
			true
		);

		wp_localize_script(
			'postman_test_notification',
			'postmanTestNotification',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'postman_test_notification_nonce' ),
				'strings' => array(
					'sending'     => __( 'Sending...', 'post-smtp' ),
					'success'     => __( 'Test notification sent successfully!', 'post-smtp' ),
					'error'       => __( 'Failed to send test notification. Please check your configuration.', 'post-smtp' ),
					'unauthorized' => __( 'Unauthorized. Please refresh the page and try again.', 'post-smtp' ),
				),
			)
		);

		wp_enqueue_script( 'postman_test_notification' );
	}

	/**
	 * AJAX Handler for Sending Test Notification
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 */
	public function send_test_notification_ajax() {
		check_ajax_referer( 'postman_test_notification_nonce', 'nonce' );

		if ( ! current_user_can( Postman::MANAGE_POSTMAN_CAPABILITY_NAME ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unauthorized.', 'post-smtp' ),
				),
				401
			);
		}

		// Get service from AJAX request, fallback to saved service
		$notification_service = isset( $_POST['service'] ) ? sanitize_text_field( $_POST['service'] ) : $this->options->getNotificationService();
		
		// Don't send if service is 'none' or empty
		if ( empty( $notification_service ) || 'none' === $notification_service ) {
			wp_send_json_error(
				array(
					'message' => __( 'No notification service selected. Please select a notification service first.', 'post-smtp' ),
				)
			);
		}

		// Prepare test message
		$subject = __( 'Test Notification – Post SMTP', 'post-smtp' );
		$message = __( 'This is a test notification message from Post SMTP.', 'post-smtp' ) . "\n\n";
		$message .= __( 'Your notification configuration is working correctly', 'post-smtp' ) . ' ✅';

		// Get the appropriate notifier based on selected service
		switch ( $notification_service ) {
			case 'default':
				$notifier = new PostmanMailNotify();
				break;
			case 'pushover':
				$notifier = new PostmanPushoverNotify();
				break;
			case 'slack':
				$notifier = new PostmanSlackNotify();
				break;
			case 'webhook_alerts':
				$notifier = new PostmanWebhookAlertsNotify();
				break;
			case 'twilio':
				// Check if Twilio extension is loaded
				if ( ! class_exists( 'PostSMTPTwilio\Notify' ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'Twilio extension is not loaded.', 'post-smtp' ),
						)
					);
				}
				$notifier = new \PostSMTPTwilio\Notify();
				break;
			case 'microsoft-teams':
				// Check if Microsoft Teams extension is loaded
				if ( ! class_exists( 'PSP_MSTeam_Notification' ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'Microsoft Teams extension is not loaded.', 'post-smtp' ),
						)
					);
				}
				$notifier = new \PSP_MSTeam_Notification();
				break;
			default:
				$notifier = apply_filters( 'post_smtp_notifier', null, $notification_service );
				if ( ! $notifier ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notification service not supported.', 'post-smtp' ),
						)
					);
				}
		}

		// Validate required credentials based on service
		$is_valid = $this->validate_notification_config( $notification_service );

		if ( ! $is_valid['valid'] ) {
			wp_send_json_error(
				array(
					'message' => $is_valid['message'],
				)
			);
		}

		// Don't send test notification for email (default) service
		if ( 'default' === $notification_service ) {
			wp_send_json_error(
				array(
					'message' => __( 'Test notification is not available for Admin Email service.', 'post-smtp' ),
				)
			);
		}

		// Send test notification for other services
		try {
			// Check if notification was sent successfully
			$sent = $this->send_notification_and_check_result( $notifier, $notification_service, $message );
			
			if ( $sent['success'] ) {
				// Use custom success message if provided, otherwise use default
				$success_message = ! empty( $sent['message'] ) ? $sent['message'] : __( 'Test notification sent successfully!', 'post-smtp' );
				wp_send_json_success(
					array(
						'message' => $success_message,
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => $sent['message'],
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => sprintf( __( 'Error sending test notification: %s', 'post-smtp' ), $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * Validate Notification Configuration
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $service The notification service name.
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	private function validate_notification_config( $service ) {
		// Only validate the specific service that is currently selected
		switch ( $service ) {
			case 'default':
				$email = $this->options->get_notification_email();
				if ( empty( $email ) || ! is_email( trim( explode( ',', $email )[0] ) ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure a valid notification email address.', 'post-smtp' ),
					);
				}
				// Only return true if default service has valid email
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			case 'pushover':
				$user = $this->options->getPushoverUser();
				$token = $this->options->getPushoverToken();
				if ( empty( $user ) || empty( $token ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure Pushover User Key and App Token.', 'post-smtp' ),
					);
				}
				// Only return true if pushover service has valid credentials
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			case 'slack':
				$token = $this->options->getSlackToken();
				if ( empty( $token ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure Slack Webhook URL.', 'post-smtp' ),
					);
				}
				// Only return true if slack service has valid token
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			case 'webhook_alerts':
				$webhook_urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION );
				if ( empty( $webhook_urls ) || ! is_array( $webhook_urls ) || count( array_filter( $webhook_urls ) ) === 0 ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure at least one Webhook URL.', 'post-smtp' ),
					);
				}
				// Only return true if webhook_alerts service has valid URLs
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			case 'twilio':
				// Check if Twilio extension is loaded
				if ( ! class_exists( 'PostSMTPTwilio\Settings' ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Twilio extension is not loaded.', 'post-smtp' ),
					);
				}
				
				$settings = new \PostSMTPTwilio\Settings();
				$sid = $settings->get_sid();
				$token = $settings->get_token();
				$send_to = $settings->get_send_to();
				$msg_service_sid = $settings->get_msg_service_sid();
				
				if ( empty( $sid ) || empty( $token ) || empty( $send_to ) || empty( $msg_service_sid ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure Twilio SID Key, Token Key, Send To Number, and Message Service SID.', 'post-smtp' ),
					);
				}
				
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			case 'microsoft-teams':
				// Check if Microsoft Teams extension is loaded
				if ( ! class_exists( 'PSP_MSTeams_Settings' ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Microsoft Teams extension is not loaded.', 'post-smtp' ),
					);
				}
				
				$settings = new \PSP_MSTeams_Settings();
				$webhook_url = $settings->webhook_url();
				
				if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
					return array(
						'valid'   => false,
						'message' => __( 'Please configure a valid Microsoft Teams Webhook URL.', 'post-smtp' ),
					);
				}
				
				return array(
					'valid'   => true,
					'message' => '',
				);
				
			default:
				// For any other service or invalid service, return false
				return array(
					'valid'   => false,
					'message' => __( 'Invalid notification service selected.', 'post-smtp' ),
				);
		}
	}

	/**
	 * Send notification and check result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param object $notifier The notification class instance.
	 * @param string $service The notification service name.
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function send_notification_and_check_result( $notifier, $service, $message ) {
		switch ( $service ) {
			case 'slack':
				return $this->check_slack_notification_result( $message );
				
			case 'pushover':
				return $this->check_pushover_notification_result( $message );
				
			case 'webhook_alerts':
				return $this->check_webhook_notification_result( $message );
				
			case 'twilio':
				return $this->check_twilio_notification_result( $message );
				
			case 'microsoft-teams':
				return $this->check_teams_notification_result( $message );
				
			default:
				// For unknown services, just call send_message and assume success
				$notifier->send_message( $message );
				return array(
					'success' => true,
					'message' => '',
				);
		}
	}

	/**
	 * Check Slack notification result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function check_slack_notification_result( $message ) {
		$options = PostmanNotifyOptions::getInstance();
		$api_url = $options->getSlackToken();

		if ( empty( $api_url ) ) {
			return array(
				'success' => false,
				'message' => __( 'Slack Webhook URL is not configured.', 'post-smtp' ),
			);
		}

		$headers = array(
			'content-type' => 'application/json'
		);

		$body = array(
			'text' => $message
		);

		$args = array(
			'headers' => $headers,
			'body'    => json_encode( $body ),
			'timeout' => 10,
		);

		$result = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Slack notification failed: %s', 'post-smtp' ), $result->get_error_message() ),
			);
		}

		$code = wp_remote_retrieve_response_code( $result );
		$response_message = wp_remote_retrieve_response_message( $result );
		$response_body = wp_remote_retrieve_body( $result );

		// Slack returns 200 for success
		if ( 200 === $code ) {
			return array(
				'success' => true,
				'message' => '',
			);
		} else {
			$error_msg = ! empty( $response_body ) ? $response_body : $response_message;
			return array(
				'success' => false,
				'message' => sprintf( __( 'Slack notification failed (HTTP %d): %s', 'post-smtp' ), $code, $error_msg ),
			);
		}
	}

	/**
	 * Check Pushover notification result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function check_pushover_notification_result( $message ) {
		$options = PostmanNotifyOptions::getInstance();
		$app_token = $options->getPushoverToken();
		$user_key = $options->getPushoverUser();

		if ( empty( $app_token ) || empty( $user_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Pushover credentials are not configured.', 'post-smtp' ),
			);
		}

		$api_url = 'https://api.pushover.net/1/messages.json';

		$args = array(
			'body' => array(
				'token'   => $app_token,
				'user'    => $user_key,
				'message' => $message,
			),
			'timeout' => 10,
		);

		$result = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Pushover notification failed: %s', 'post-smtp' ), $result->get_error_message() ),
			);
		}

		$code = wp_remote_retrieve_response_code( $result );
		$response_body = wp_remote_retrieve_body( $result );
		$body = json_decode( $response_body, true );

		// Pushover returns 200 and status=1 for success
		if ( 200 === $code && isset( $body['status'] ) && 1 === (int) $body['status'] ) {
			return array(
				'success' => true,
				'message' => '',
			);
		} else {
			$error_msg = isset( $body['errors'] ) ? implode( ', ', $body['errors'] ) : __( 'Unknown error', 'post-smtp' );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Pushover notification failed: %s', 'post-smtp' ), $error_msg ),
			);
		}
	}

	/**
	 * Check Webhook notification result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function check_webhook_notification_result( $message ) {
		$webhook_urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION );

		if ( empty( $webhook_urls ) || ! is_array( $webhook_urls ) ) {
			return array(
				'success' => false,
				'message' => __( 'Webhook URLs are not configured.', 'post-smtp' ),
			);
		}

		$success_count = 0;
		$failure_messages = array();

		foreach ( $webhook_urls as $webhook_url ) {
			if ( empty( $webhook_url ) ) {
				continue;
			}

			$validate = apply_filters( 'post_smtp_validate_webhook_url', true, $webhook_url );

			if ( ! $validate || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
				$failure_messages[] = sprintf( __( 'Invalid URL: %s', 'post-smtp' ), $webhook_url );
				continue;
			}

			$response = wp_remote_post( $webhook_url, array(
				'body'    => json_encode( array( 'message' => $message ) ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 10,
			) );

			if ( is_wp_error( $response ) ) {
				$failure_messages[] = sprintf( __( 'Error for %s: %s', 'post-smtp' ), $webhook_url, $response->get_error_message() );
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );
			
			// Consider 2xx status codes as success
			if ( $code >= 200 && $code < 300 ) {
				$success_count++;
			} else {
				$failure_messages[] = sprintf( __( 'HTTP %d for %s', 'post-smtp' ), $code, $webhook_url );
			}
		}

		if ( $success_count > 0 ) {
			// At least one webhook succeeded
			$message = sprintf( _n( 
				'Test notification sent successfully to %d webhook.', 
				'Test notification sent successfully to %d webhooks.', 
				$success_count, 
				'post-smtp' 
			), $success_count );
			
			if ( ! empty( $failure_messages ) ) {
				$message .= ' ' . sprintf( __( 'Some webhooks failed: %s', 'post-smtp' ), implode( ', ', $failure_messages ) );
			}
			
			return array(
				'success' => true,
				'message' => $message,
			);
		} else {
			// All webhooks failed
			$error_msg = ! empty( $failure_messages ) ? implode( ', ', $failure_messages ) : __( 'All webhook URLs failed', 'post-smtp' );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Webhook notification failed: %s', 'post-smtp' ), $error_msg ),
			);
		}
	}

	/**
	 * Check Twilio notification result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function check_twilio_notification_result( $message ) {
		// Check if Twilio extension is loaded
		if ( ! class_exists( 'PostSMTPTwilio\Settings' ) || ! class_exists( 'PostSMTPTwilio\Notify' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Twilio extension is not loaded.', 'post-smtp' ),
			);
		}

		try {
			$settings = new \PostSMTPTwilio\Settings();
			$sid = $settings->get_sid();
			$token = $settings->get_token();
			$send_to = $settings->get_send_to();
			$msg_service_sid = $settings->get_msg_service_sid();

			if ( empty( $sid ) || empty( $token ) || empty( $send_to ) || empty( $msg_service_sid ) ) {
				return array(
					'success' => false,
					'message' => __( 'Twilio credentials are not configured.', 'post-smtp' ),
				);
			}

			$notifier = new \PostSMTPTwilio\Notify();
			$notifier->send_message( $message );

			return array(
				'success' => true,
				'message' => __( 'Test notification sent successfully!', 'post-smtp' ),
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Twilio notification failed: %s', 'post-smtp' ), $e->getMessage() ),
			);
		}
	}

	/**
	 * Check Microsoft Teams notification result
	 *
	 * @since 2.4.0
	 * @version 1.0.0
	 * 
	 * @param string $message The message to send.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	private function check_teams_notification_result( $message ) {
		// Check if Microsoft Teams extension is loaded
		if ( ! class_exists( 'PSP_MSTeams_Settings' ) || ! class_exists( 'PSP_MSTeam_Notification' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Microsoft Teams extension is not loaded.', 'post-smtp' ),
			);
		}

		$settings = new \PSP_MSTeams_Settings();
		$webhook_url = $settings->webhook_url();

		if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			return array(
				'success' => false,
				'message' => __( 'Microsoft Teams Webhook URL is not configured.', 'post-smtp' ),
			);
		}

		$site_title = get_bloginfo( 'name' );
		$website_url = home_url();

		// Prepare Teams message format (simplified for test notification)
		$message_content = array(
			'type' => 'message',
			'attachments' => array(
				array(
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'content' => array(
						'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
						'type' => 'AdaptiveCard',
						'version' => '1.2',
						'body' => array(
							array(
								'type' => 'TextBlock',
								'text' => "[$site_title]",
								'weight' => 'bolder',
								'size' => 'medium',
								'wrap' => true,
							),
							array(
								'type' => 'TextBlock',
								'text' => sprintf(
									"**%s**:\n\n %s",
									esc_html__( 'Website URL', 'post-smtp' ),
									$website_url
								),
								'wrap' => true,
							),
							array(
								'type' => 'TextBlock',
								'text' => sprintf(
									"**%s**:\n\n %s",
									esc_html__( 'Test Message', 'post-smtp' ),
									$message
								),
								'wrap' => true,
							),
						),
					),
				),
			),
		);

		$args = array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $message_content ),
		);

		$response = wp_remote_post( $webhook_url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Microsoft Teams notification failed: %s', 'post-smtp' ), $response->get_error_message() ),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Teams returns 200 for success
		if ( 200 === $code ) {
			return array(
				'success' => true,
				'message' => '',
			);
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$error_msg = ! empty( $response_body ) ? $response_body : wp_remote_retrieve_response_message( $response );
			return array(
				'success' => false,
				'message' => sprintf( __( 'Microsoft Teams notification failed (HTTP %d): %s', 'post-smtp' ), $code, $error_msg ),
			);
		}
	}
}
new PostmanNotify();
