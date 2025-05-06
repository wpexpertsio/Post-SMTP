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
		 // Check if API has rate-limited the request
		if( $response_code == 429 ) {
			wp_send_json_error( array(
				'message' => 'Too many requests',
			), 429 );
		}

        if( $response_code == 200 ) {
            $test_email = json_decode( $response_body )->data->email;
            if( $test_email ) {
                $email_sent = wp_mail( $test_email, 'Test Email', 'This is a test email.' );
                $email_sent = true;
                $test_email = str_replace( '@smtper.postmansmtp.com', '', $test_email );

                // Wait for 3 seconds
                sleep( 5 );
                if( $email_sent ) {
                    $response = wp_remote_post( "{$this->base_url}/test?test_email={$test_email}&email={$email}", $args );
                    $response_code = wp_remote_retrieve_response_code( $response );
                    $response_body = wp_remote_retrieve_body( $response );
                    if( $response_code == 200 ) {
                        wp_send_json_success( 
                            array(
                                'message' => 'test_email_sent',
                                'data'  => json_decode( $response_body )
                            ),
                            200
                        );

                    }else{
						 wp_send_json_error( array(
							'message' => 'test_email_not_sent',
						), 400 );
					}

                }

            }

        }

    }

}
Postman_Email_Tester::get_instance();
endif;