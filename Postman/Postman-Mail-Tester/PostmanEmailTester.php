<?php

if( ! class_exists( 'Postman_Email_Tester' ) ):
class Postman_Email_Tester {

    private static $instance;

    private $x_mt_token_wp = 'WP_*#$KXMs29)&34KMa@#_-2*^%02?>":}0"!@`~\=#@#';

    //TODO: Replace URL with Production URL
    private $base_url = 'https://smtper.postmansmtp.com/wp-json/mail-tester/v1';


    /**
     * class constructor
     * 
     * @since 3.2.0
     */
    public function __construct() {

        add_action( 'wp_ajax_ps-mail-test', array( $this, 'test_mail' ) );

    }

    /**
     * Get the instance of this class
     * 
     * @since 3.2.0
     */
    public static function get_instance() {
        
        if ( ! self::$instance ) {

            self::$instance = new self();

        }

        return self::$instance;

    }

    /**
     * AJAX callback to test sending an email.
     *
     * Uses either:
     * 1. Socket verification (/email-auth-check), or
     * 2. Legacy method (/get-email → wp_mail → /test)
     *
     * @since 3.2.0
     */
    public function test_mail() {
        check_admin_referer( 'post-smtp', 'security' );

        $email  = sanitize_email( $_POST['email'] );
        $from_email = sanitize_email( $_POST['from'] );
        $socket = sanitize_text_field( $_POST['socket'] );
        $apikey = sanitize_text_field( $_POST['apikey'] );
        $args = array(
            'method'  => WP_REST_Server::READABLE,
            'headers' => array(
                'X-MT-Token-WP' => $this->x_mt_token_wp,
                'IP'            => $_SERVER['REMOTE_ADDR'],
                'Site-URL'      => get_site_url(),
            ),
        );

        if ( !empty( $apikey ) ) {
            $args['headers']['API-KEY'] = $apikey;
        }
   
        if ( $this->requires_test_api_verification( $socket ) ) {
            $this->handle_sockets_check( $from_email, $args, $socket  );
        } else {
            $this->handle_legacy_test( $email, $args );
        }
    }

    /**
     * Handles modern auto-verification test logic.
     */
    private function handle_sockets_check( $email, $args, $socket ) {

        // Check if it's Gmail API or Office API with default domains
        if ( $this->is_default_domain_provider( $email, $socket ) ) {
            // Return success directly for default domains
            $success_body = json_encode( array(
                'success' => true,
                'message' => 'Test email verification skipped for default domain provider',
                'provider' => $socket,
                'email_domain' => $this->get_email_domain( $email )
            ) );
            $this->send_success_response( 'default_mailer', $success_body );
            return;
        }

        $verify_response = wp_remote_post( "{$this->base_url}/email-auth-check?test_email={$email}&selector={$socket}", $args );
        $verify_code     = wp_remote_retrieve_response_code( $verify_response );
        $verify_body     = wp_remote_retrieve_body( $verify_response );
        if ( $verify_code == 429 ) {
            $this->send_error_response( 'Too many requests', 429 );
        }

        if ( $verify_code === 200 ) {
            $this->send_success_response( 'test_email_sent', $verify_body );
        } else {
            $this->send_error_response( 'test_email_not_sent', 400 );
        }
    }

    /**
     * Handles legacy test logic (/get-email, wp_mail, /test).
     */
    private function handle_legacy_test( $email, $args ) {
        $response      = wp_remote_post( "{$this->base_url}/get-email?test_email={$email}", $args );
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( $response_code == 429 ) {
            $this->send_error_response( 'Too many requests', 429 );
        }

        if ( $response_code != 200 ) {
            $this->send_error_response( 'test_email_not_sent', 400 );
        }

        $test_email = json_decode( $response_body )->data->email;
        if ( empty( $test_email ) ) {
            $this->send_error_response( 'test_email_not_sent', 400 );
        }

        // Send the test email
        $email_sent = wp_mail( $test_email, 'Test Email', 'This is a test email.' );

        if ( ! $email_sent ) {
            $this->send_error_response( 'test_email_not_sent', 400 );
        }

        // Remove postfix used for verification
        $clean_test_email = str_replace( '@smtper.postmansmtp.com', '', $test_email );

        // Wait for remote system to process incoming mail
        sleep( 5 );

        // Check result using /test API
        $result = wp_remote_post( "{$this->base_url}/test?test_email={$clean_test_email}&email={$email}", $args );
        $result_code = wp_remote_retrieve_response_code( $result );
        $result_body = wp_remote_retrieve_body( $result );
        if ( $result_code === 200 ) {
            $this->send_success_response( 'test_email_sent', $result_body );
        } else {
            $this->send_error_response( 'test_email_not_sent', 400 );
        }
    }

    /**
     * Helper to send JSON success response.
     */
    private function send_success_response( $message, $body ) {
        wp_send_json_success(
            array(
                'message' => $message,
                'data'    => json_decode( $body ),
            ),
            200
        );
    }

    /**
     * Helper to send JSON error response.
     */
    private function send_error_response( $message, $code = 400 ) {
        wp_send_json_error(
            array(
                'message' => $message,
            ),
            $code
        );
    }

    /**
     * Determine if the given provider requires a follow-up email delivery check via the /test API.
     *
     * Some API-based SMTP providers require a delayed verification call to confirm successful delivery
     * of the test email. This function helps identify those providers.
     *
     * @param string $provider The SMTP provider identifier (e.g., 'sendgrid_api', 'sparkpost_api').
     * @return bool True if the provider requires a /test API call after sending a test email.
     */
    private function requires_test_api_verification( $provider ) {
        $providers_requiring_test_api = array(
            'sendinblue_api',
            'sendgrid_api',
            'mandrill_api',
            'sparkpost_api',
            'smtp2go_api',
            'sendpulse_api',
            'gmail_api',
            'office365_api'
        );

        return in_array( $provider, $providers_requiring_test_api, true );
    }

    /**
     * Check if the provider is Gmail API or Office API with default email domains.
     *
     * @param string $email The email address to check.
     * @param string $socket The provider/socket identifier.
     * @return bool True if it's a default domain provider that should skip endpoint verification.
     */
    private function is_default_domain_provider( $email, $socket ) {
        $email_domain = $this->get_email_domain( $email );
        
        // Gmail API default domains
        $gmail_default_domains = array(
            'gmail.com',
            'googlemail.com'
        );
        
        // Office/Outlook API default domains
        $office_default_domains = array(
            'outlook.com',
            'outlook.co.uk',
            'outlook.fr',
            'outlook.de',
            'outlook.jp',
            'hotmail.com',
            'hotmail.co.uk',
            'hotmail.fr',
            'hotmail.de',
            'hotmail.it',
            'live.com',
            'live.co.uk',
            'live.fr',
            'live.de',
            'msn.com'
        );

        // Check Gmail API with default domains
        if ( in_array( $socket, array( 'gmail_api' ) ) && in_array( $email_domain, $gmail_default_domains ) ) {
            return true;
        }

        // Check Office API with default domains
        if ( in_array( $socket, array( 'office365_api' ) ) && in_array( $email_domain, $office_default_domains ) ) {
            return true;
        }

        return false;
    }

    /**
     * Extract the domain part from an email address.
     *
     * @param string $email The email address.
     * @return string The domain part of the email address.
     */
    private function get_email_domain( $email ) {
        $parts = explode( '@', $email );
        return isset( $parts[1] ) ? strtolower( trim( $parts[1] ) ) : '';
    }


}
Postman_Email_Tester::get_instance();
endif;