<?php
class Postsmtp_ContactForm7 {

    private $result_error;

    public function __construct() {
        add_action( 'wpcf7_mail_failed', array( $this, 'save_error' ) );
        add_filter( 'wpcf7_ajax_json_echo', array( $this, 'change_rest_response' ), 10, 2 );
    }

    public function save_error($contact_form) {
        $this->result_error = apply_filters( 'postman_wp_mail_result', null );
    }

    public function change_rest_response( $response ) {
        if ( $response['status'] == 'mail_failed' ) {
            $message = $this->result_error ['exception']->getMessage();

            if ( ! $message || $message == '' ) {
                return $response;
            }

            $currentTransport = PostmanOptions::getInstance()->getTransportType();
            $result = json_decode($message);
            $is_json = (json_last_error() == JSON_ERROR_NONE);

            switch ($currentTransport) {
                case 'gmail_api':
                    $response['message'] = $is_json ? $result->error->message : $message;
                    break;
                default:
                    $response['message'] = $is_json ? json_encode(json_decode($message), JSON_PRETTY_PRINT) : $message;
            }
        }

        return $response;
    }

}
