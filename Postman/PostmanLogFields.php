<?php
class PostmanLogFields {

    private $fields = array(
        'success' => 'sanitize_text_field',
        'from_header' => 'sanitize_text_field',
        'to_header' => 'sanitize_text_field',
        'cc_header' => 'sanitize_text_field',
        'bcc_header' => 'sanitize_text_field',
        'reply_to_header' => 'sanitize_text_field',
        'transport_uri' => 'sanitize_text_field',
        'original_to' => 'sanitize_text_field',
        'original_subject' => 'sanitize_text_field',
        'original_message' => null,
        'original_headers' => 'sanitize_text_field',
        'session_transcript' => 'sanitize_textarea_field',
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
        $this->fields['original_message'] = array( $this, 'sanitize_message' );
    }

    public function get( $post_id ) {
        $data = [];
        foreach ( $this->fields as $key => $sanitize_callback ) {
            $meta = get_post_meta( $post_id, $key, true );
            $data[$key][] = $this->maybe_json( $meta );
        }

        return $data;
    }

    public function update( $post_id, $key, $value ) {
        $sanitized = $this->sanitize( $key, $value );
        $encode = $this->encode( $sanitized );

        update_post_meta( $post_id, $key, $encode );
    }

    private function maybe_json( $json ) {
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

        return $callback( $this->fields[$key], $value );
    }

    private function sanitize_message( $message ) {
        $allowed_tags = wp_kses_allowed_html( 'post' );
        $allowed_tags['style'] = array();

        return wp_kses( $message, $allowed_tags );
    }

    private function encode( $value ) {
        if ( is_array( $value ) ) {
            return wp_json_encode( $value );
        }

        return $value;
    }
}
