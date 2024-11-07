<?php

if( ! class_exists( 'Postman_Email_Tester' ) ):
class Postman_Email_Tester {

    private static $instance;

    private $x_mt_token_wp = 'WP_*#$KXMs29)&34KMa@#_-2*^%02?>":}0"!@`~\=#@#';

    //TODO: Replace URL with Production URL
    private $base_url = 'http://mailtester.local/wp-json/mail-tester/v1';

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
     * Test mail | AJAX Callback
     * 
     * @since 3.2.0
     */
    public function test_mail() {

        check_admin_referer( 'post-smtp', 'security' );

        $email = sanitize_email( $_POST['email'] );

        $args = array(
            'method'    => WP_REST_Server::READABLE,
            'headers'   => array(
                'X-MT-Token-WP' => $this->x_mt_token_wp,
                'IP'            => $_SERVER['REMOTE_ADDR'],
                'Site-URL'      => get_site_url(),
            )
        );

        $response = wp_remote_post( "{$this->base_url}/get-email?test_email={$email}", $args );
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if( $response_code == 200 ) {
            
            $test_email = json_decode( $response_body )->data->email;

            if( $test_email ) {

                $response = wp_remote_post( "{$this->base_url}/test?test_email={$email}", $args );
                $response_code = wp_remote_retrieve_response_code( $response );
                $response_body = wp_remote_retrieve_body( $response );

                var_dump( $response_code, $response_body );die;

            }

            wp_send_json_success( 
                array(),
                200
            );

        }

    }

}
Postman_Email_Tester::get_instance();
endif;