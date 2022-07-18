<?php
class PostmanLogFields {

    private $fields = array(
        'success' => 'sanitize_text_field',
        'solution' => [ 'PostmanLogFields', 'sanitize_message' ],
        'from_header' => [ 'PostmanLogFields', 'email_header_sanitize' ],
        'to_header' => [ 'PostmanLogFields', 'email_header_sanitize' ],
        'cc_header' => [ 'PostmanLogFields', 'email_header_sanitize' ],
        'bcc_header' => [ 'PostmanLogFields', 'email_header_sanitize' ],
        'reply_to_header' => [ 'PostmanLogFields', 'email_header_sanitize' ],
        'transport_uri' => 'sanitize_text_field',
        'original_to' => 'sanitize_text_field',
        'original_subject' => 'sanitize_text_field',
        'original_message' => '', // only sent to viewed
        'original_headers' => '', // only sent to viewed
        'session_transcript' => '', // escaped when viewed
    );

    private static $instance = null;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function get( $post_id ) {
        $data = [];
        foreach ( $this->fields as $key => $sanitize_callback ) {
            $meta = get_post_meta( $post_id, $key, true );
            $data[$key][] = $this->maybe_json( $meta );
        }

        return $data;
    }

    public function get_fields() {
        return $this->fields;
    }

    public function update( $post_id, $key, $value ) {
        $sanitized = $this->sanitize( $key, $value );
        $encode = $this->encode( $sanitized );

        update_post_meta( $post_id, $key, $encode );
    }

    private function maybe_json( $json ) {

        if ( is_array( $json ) ) {
            return implode( ',', $json );
        }

        if ( $this->isJson( $json ) ) {
            return implode( ',', json_decode( $json, true ) );
        }

        // Fallback
        return maybe_unserialize( $json );
    }

    private function isJson($string) {
        $result = json_decode($string, true);
        $error = json_last_error();
        return ( $error == JSON_ERROR_NONE && ! is_null($result) && $result != $string );
    }

    private function sanitize( $key, $value ) {

        $callback = is_array( $value ) ? 'array_map' : 'call_user_func';

        if ( ! empty( $this->fields[$key] ) ) {
            return $callback( $this->fields[$key], $value );
        }

        return $value;
    }

    private function sanitize_message( $message ) {
        $allowed_tags = wp_kses_allowed_html( 'post' );
        $allowed_tags['style'] = array();

        return wp_kses( $message, $allowed_tags );
    }

	private function sanitize_html( $value ) {
		$allowed_html = array(
			'a' => array(
				'href' => array(),
			),
			'br' => array(),
		);

		return wp_kses( $value, $allowed_html );
	}

    private function encode( $value ) {
        if ( is_array( $value ) ) {
            return wp_json_encode( $value );
        }

        return $value;
    }

    public function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);

        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    public function email_header_sanitize($value) {

        $parts = explode( ',', $value );

        $sanitized = [];
        foreach ( $parts as $part ) {

            if ( strpos( $part, '<' ) !== false ) {
                $email = $this->get_string_between( $part, '<', '>' );
                $clean_email  = $this->sanitize_email($email);
                preg_match('/(.*)</', $part, $output_array);
                $name = filter_var( trim( $output_array[1] ), FILTER_SANITIZE_STRING );

                $sanitized[] = "{$name} <{$clean_email}>";
            }
        }

        return ! empty( $sanitized ) ? implode( ',', $sanitized ) : implode( ',', array_map( [ $this, 'sanitize_email'], $parts ) );
    }

    public function sanitize_email( $email ) {
        return filter_var( $email, FILTER_SANITIZE_EMAIL );
    }
}

