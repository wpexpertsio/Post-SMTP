<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'INotify.php';
require_once 'PostmanMailNotify.php';
require_once 'PostmanPushoverNotify.php';
require_once 'PostmanSlackNotify.php';
require_once 'PostmanNotifyOptions.php';

class PostmanNotify {

    const NOTIFICATIONS_OPTIONS = 'postman_notifications_options';
    const NOTIFICATIONS_SECTION = 'postman_notifications_section';
    const NOTIFICATIONS_PUSHOVER_CRED = 'postman_pushover_cred';
    const NOTIFICATIONS_SLACK_CRED = 'postman_slack_cred';

    private $options;

    public function __construct() {

        $this->options = PostmanNotifyOptions::getInstance();

        add_filter( 'post_smtp_admin_tabs', array( $this, 'tabs' ) );
        add_action( 'post_smtp_settings_menu', array( $this, 'menu' ) );
        add_action( 'post_smtp_settings_fields', array( $this, 'settings' ) );
        add_action( 'post_smtp_on_failed', array( $this, 'notify' ), 10, 5 );
        add_filter( 'post_smtp_sanitize', array( $this, 'sanitize' ), 10, 3 );
    }

    public function menu() {
        print '<section id="notifications">';
        do_settings_sections( self::NOTIFICATIONS_OPTIONS );

        $currentKey = $this->options->getNotificationService();
        $pushover = $currentKey == 'pushover' ? 'block' : 'none';
        $slack = $currentKey == 'slack' ? 'block' : 'none';

        echo '<div id="pushover_cred" style="display: ' . $pushover . ';">';
        do_settings_sections( self::NOTIFICATIONS_PUSHOVER_CRED );
        echo '</div>';

        echo '<div id="slack_cred" style="display: ' . $slack . ';">';
        do_settings_sections( self::NOTIFICATIONS_SLACK_CRED );
        echo '</div>';

        do_action( 'post_smtp_notification_settings' );

        print '</section>';
    }

    public function sanitize($new_input, $input, $sanitizer) {
        // Notifications
        $sanitizer->sanitizeString( 'Pushover Service', PostmanNotifyOptions::NOTIFICATION_SERVICE, $input, $new_input, $this->options->getNotificationService() );
        $sanitizer->sanitizePassword( 'Pushover Username', PostmanNotifyOptions::PUSHOVER_USER, $input, $new_input, $this->options->getPushoverUser() );
        $sanitizer->sanitizePassword( 'Pushover Token', PostmanNotifyOptions::PUSHOVER_TOKEN, $input, $new_input, $this->options->getPushoverToken() );
        $sanitizer->sanitizePassword( 'Slack Token', PostmanNotifyOptions::SLACK_TOKEN, $input, $new_input, $this->options->getSlackToken() );

        // Chrome extension
        $sanitizer->sanitizeString( 'Push Chrome Extension', PostmanNotifyOptions::NOTIFICATION_USE_CHROME, $input, $new_input );
        $sanitizer->sanitizePassword( 'Push Chrome Extension UID', PostmanNotifyOptions::NOTIFICATION_CHROME_UID, $input, $new_input, $this->options->getNotificationChromeUid() );

        return $new_input;
    }

    public function tabs($tabs) {
        $tabs['notifications'] = __( 'Notifications', 'post-smtp' );

        return $tabs;
    }

    public function settings() {
        // Notifications
        add_settings_section( self::NOTIFICATIONS_SECTION, _x( 'Notifications Settings', 'Configuration Section Title', 'post-smtp' ), array(
            $this,
            'printNotificationsSectionInfo',
        ), self::NOTIFICATIONS_OPTIONS );

        add_settings_field( PostmanNotifyOptions::NOTIFICATION_SERVICE, _x( 'Notification Service', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'notification_service_callback',
        ), self::NOTIFICATIONS_OPTIONS, self::NOTIFICATIONS_SECTION );

        // Pushover
        add_settings_section( 'pushover_credentials', _x( 'Pushover Credentials', 'Configuration Section Title', 'post-smtp' ), array(
            $this,
            'printNotificationsSectionInfo',
        ), self::NOTIFICATIONS_PUSHOVER_CRED );

        add_settings_field( PostmanNotifyOptions::PUSHOVER_USER, _x( 'Pushover User Key', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'pushover_user_callback',
        ), self::NOTIFICATIONS_PUSHOVER_CRED, 'pushover_credentials' );

        add_settings_field( PostmanNotifyOptions::PUSHOVER_TOKEN, _x( 'Pushover App Token', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'pushover_token_callback',
        ), self::NOTIFICATIONS_PUSHOVER_CRED, 'pushover_credentials' );

        // Slack
        add_settings_section( 'slack_credentials', _x( 'Slack Credentials', 'Configuration Section Title', 'post-smtp' ), array(
            $this,
            'printNotificationsSectionInfo',
        ), self::NOTIFICATIONS_SLACK_CRED );

        add_settings_field( PostmanNotifyOptions::SLACK_TOKEN, _x( 'Slack Webhook', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'slack_token_callback',
        ), self::NOTIFICATIONS_SLACK_CRED, 'slack_credentials' );

        add_settings_field( PostmanNotifyOptions::NOTIFICATION_USE_CHROME, _x( 'Push to chrome extension', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'notification_use_chrome_callback',
        ), self::NOTIFICATIONS_OPTIONS, self::NOTIFICATIONS_SECTION );

        add_settings_field( 'notification_chrome_uid', _x( 'Chrome Extension UID', 'Configuration Input Field', 'post-smtp' ), array(
            $this,
            'notification_chrome_uid_callback',
        ), self::NOTIFICATIONS_OPTIONS, self::NOTIFICATIONS_SECTION );
    }

    /**
     * Print the Section text
     */
    public function printNotificationsSectionInfo() {
    }

    public function notification_service_callback() {
        $inputDescription =  __( 'Select the notification service you want to recieve alerts about failed emails.' );

        $options = apply_filters('post_smtp_notification_service', array(
            'none' => __( 'None', 'post-smtp' ),
            'default' => __( 'WP Admin Email', 'post-smtp' ),
            'pushover' => __( 'Pushover', 'post-smtp' ),
            'slack' => __( 'Slack', 'post-smtp' ),
        ));

        printf( '<select id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]">', 'postman_options', PostmanNotifyOptions::NOTIFICATION_SERVICE );
        $currentKey = $this->options->getNotificationService();

        foreach ( $options as $key => $label ) {
            $this->printSelectOption( $label, $key, $currentKey );
        }

        printf( '</select><br/><span class="postman_input_description">%s</span>', $inputDescription );
    }

    public function notification_use_chrome_callback() {
        $value = $this->options->useChromeExtension();
        printf( '<input type="checkbox" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" %3$s />', 'postman_options', PostmanNotifyOptions::NOTIFICATION_USE_CHROME, $value ? 'checked="checked"' : '' );
    }

    public function notification_chrome_uid_callback() {
        printf( '<input type="password" id="input_%2$s" class="input_%2$s" name="%1$s[%2$s]" value="%3$s" />', 'postman_options', 'notification_chrome_uid', PostmanUtils::obfuscatePassword( $this->options->getNotificationChromeUid() ) );
    }

    public function pushover_user_callback() {
        printf( '<input type="password" id="pushover_user" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::PUSHOVER_USER, $this->options->getPushoverUser() );
    }

    public function pushover_token_callback() {
        printf( '<input type="password" id="pushover_token" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::PUSHOVER_TOKEN, $this->options->getPushoverToken() );
    }

    public function slack_token_callback() {
        printf( '<input type="password" id="slack_token" name="%s[%s]" value="%s" />', 'postman_options', PostmanNotifyOptions::SLACK_TOKEN, $this->options->getSlackToken() );
        echo '<a target="_blank" href="https://slack.postmansmtp.com/">' . __( 'Get your webhook URL here', 'post-smtp' ) . '</a>';

    }

    /**
     * @param PostmanEmailLog $log
     * @param PostmanMessage $message
     * @param string $transcript
     * @param PostmanTransport $transport
     * @param string $errorMessage
     */
    public function notify ($log, $postmanMessage, $transcript, $transport, $errorMessage ) {
        $message = __( 'You getting this message because an error detected while delivered your email.', 'post-smtp' );
        $message .= "\r\n" . sprintf( __( 'For the domain: %1$s','post-smtp' ), get_bloginfo('url') );
        $message .= "\r\n" . __( 'The log to paste when you open a support issue:', 'post-smtp' ) . "\r\n";

        if ( $errorMessage && ! empty( $errorMessage ) ) {

            $message = $message . $errorMessage;

            $notification_service = PostmanNotifyOptions::getInstance()->getNotificationService();
            switch ($notification_service) {
                case 'none':
                    $notifyer = false;
                    break;
                case 'default':
                    $notifyer = new PostmanMailNotify;
                    break;
                case 'pushover':
                    $notifyer = new PostmanPushoverNotify;
                    break;
                case 'slack':
                    $notifyer = new PostmanSlackNotify;
                    break;
                default:
                    $notifyer = new PostmanMailNotify;
            }

            $notifyer = apply_filters('post_smtp_notifier', $notifyer, $notification_service);

            // Notifications
            if ( $notifyer ) {
                $notifyer->send_message($message, $log);
            }

            $this->push_to_chrome($errorMessage);
        }
    }

    public function push_to_chrome($message) {
        $push_chrome = PostmanNotifyOptions::getInstance()->useChromeExtension();

        if ( $push_chrome ) {
            $uid = PostmanNotifyOptions::getInstance()->getNotificationChromeUid();

            if ( empty( $uid ) ) {
                return;
            }

            $url = 'https://chrome.postmansmtp.com/' . $uid;

            $args = array(
                'body' => array(
                    'message' => $message
                )
            );

            $response = wp_remote_post( $url , $args );

            if ( is_wp_error( $response ) ) {
                error_log( 'Chrome notification error: ' . $response->get_error_message() );
            }

            if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                error_log( 'Chrome notification error HTTP Error:' . wp_remote_retrieve_response_code( $response ) );
            }
        }
    }

    private function printSelectOption( $label, $optionKey, $currentKey ) {
        $optionPattern = '<option value="%1$s" %2$s>%3$s</option>';
        printf( $optionPattern, $optionKey, $optionKey == $currentKey ? 'selected="selected"' : '', $label );
    }
}
new PostmanNotify();
