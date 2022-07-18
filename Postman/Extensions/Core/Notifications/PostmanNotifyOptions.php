<?php

class PostmanNotifyOptions {

    const DEFAULT_NOTIFICATION_SERVICE = 'default';
    const NOTIFICATION_SERVICE = 'notification_service';
    const NOTIFICATION_USE_CHROME = 'notification_use_chrome';
    const NOTIFICATION_CHROME_UID = 'notification_chrome_uid';
    const PUSHOVER_USER = 'pushover_user';
    const PUSHOVER_TOKEN = 'pushover_token';
    const SLACK_TOKEN = 'slack_token';

    private $options;

    private static $instance;

    public static function getInstance()
    {
        if ( ! self::$instance ) {
            self::$instance = new static;
        }
        
        return self::$instance;
    }

    private function __construct()
    {
        $this->options = get_option( 'postman_options' );
    }

    public function getNotificationService() {
        if ( isset( $this->options [ self::NOTIFICATION_SERVICE ] ) ) {
            return $this->options [ self::NOTIFICATION_SERVICE ];
        } else {
            return self::DEFAULT_NOTIFICATION_SERVICE;
        }
    }

    public function getPushoverUser() {
        if ( isset( $this->options [ self::PUSHOVER_USER ] ) ) {
            return base64_decode( $this->options [ self::PUSHOVER_USER ] );
        }
    }

    public function getPushoverToken() {
        if ( isset( $this->options [ self::PUSHOVER_TOKEN ] ) ) {
            return base64_decode( $this->options [ self::PUSHOVER_TOKEN ] );
        }
    }

    public function getSlackToken() {
        if ( isset( $this->options [ self::SLACK_TOKEN ] ) ) {
            return base64_decode( $this->options [ self::SLACK_TOKEN ] );
        }
    }

    public function useChromeExtension() {
        if ( isset( $this->options [ self::NOTIFICATION_USE_CHROME ] ) ) {
            return $this->options [ self::NOTIFICATION_USE_CHROME ];
        }
    }

    public function getNotificationChromeUid() {
        if ( isset( $this->options [ self::NOTIFICATION_CHROME_UID ] ) ) {
            return base64_decode( $this->options [ self::NOTIFICATION_CHROME_UID ] );
        }
    }
}