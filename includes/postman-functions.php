<?php

if( !class_exists( 'PostmanEmailLogs' ) ) {

    require_once POST_SMTP_PATH . '/Postman/PostmanEmailLogs.php';

}


/**
 * Adds a meta field to the given log.
 * 
 * @since 2.5.0
 * @version 1.0.0
 */
if( !function_exists( 'postman_add_log_meta' ) ):
function postman_add_log_meta( $log_id, $meta_key, $meta_value ) {

    global $wpdb;
    $email_logs =  new PostmanEmailLogs();

    return $wpdb->insert(
        $wpdb->prefix . $email_logs->meta_table,
        array(
            'log_id'        =>  $log_id,
            'meta_key'      =>  $meta_key,
            'meta_value'    =>  $meta_value
        ),
        array(
            '%d',
            '%s',
            '%s'
        )
    );

}
endif;


/**
 * Updates a log meta field based on the given log ID.
 * 
 * @since 2.5.0
 * @version 1.0.0
 */
if ( ! function_exists( 'postman_update_log_meta' ) ) {
    function postman_update_log_meta( $log_id, $meta_key, $meta_value ) {
        global $wpdb;
        $email_logs = new PostmanEmailLogs();

        $existing_meta = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}{$email_logs->meta_table} WHERE log_id = %d AND meta_key = %s",
                $log_id,
                $meta_key
            )
        );

        if ( $existing_meta ) {
            return $wpdb->update(
                $wpdb->prefix . $email_logs->meta_table,
                array(
                    'meta_value' => $meta_value
                ),
                array(
                    'log_id' => $log_id,
                    'meta_key' => $meta_key
                ),
                array(
                    '%s'
                ),
                array(
                    '%d',
                    '%s'
                )
            );
        } else {
            return $wpdb->insert(
                $wpdb->prefix . $email_logs->meta_table,
                array(
                    'log_id' => $log_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value
                ),
                array(
                    '%d',
                    '%s',
                    '%s'
                )
            );
        }
    }
}



/**
 * Retrieves a log meta field for the given log ID.
 * 
 * @since 2.5.0
 * @version 1.0.0
 */
if( !function_exists( 'postman_get_log_meta' ) ):
function postman_get_log_meta( $log_id, $key = '' ) {

    global $wpdb;
    $email_logs = new PostmanEmailLogs();

    if( empty( $key ) ) {

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `meta_key`, `meta_value` FROM {$wpdb->prefix}{$email_logs->meta_table}
                WHERE `log_id` = %d",
                $log_id
            )
        );

    }

    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT `meta_value` FROM {$wpdb->prefix}{$email_logs->meta_table}
            WHERE `log_id` = %d && `meta_key` = %s",
            $log_id,
            $key
        )
    ); 

    return $result ? $result->meta_value : false;

}
endif;


/**
 * Deletes a log meta field for the given log ID.
 * 
 * @since 2.5.0
 * @version 1.0.0
 */
if( !function_exists( 'postman_delete_log_meta' ) ):
function postman_delete_log_meta( $log_id, $meta_key, $meta_value = '' ) {

    global $wpdb;
    $email_logs = new PostmanEmailLogs();

    $where = array(
        'log_id'    =>  $log_id,
        'meta_key'  =>  $meta_key
    );

    $where_format = array(
        '%d',
        '%s'
    );

    if( !empty( $meta_value ) ) {

        $where['meta_value'] = $meta_value;
        $where_format[] = '%s';

    }

    return $wpdb->delete(
        $wpdb->prefix . $email_logs->meta_table,
        $where,
        $where_format
    );

}
endif;

/**
 * Sanitizes an array of values.
 * 
 * @since 2.7.0
 * @version 1.0.0
 */
if( !function_exists( 'post_smtp_sanitize_array' ) ):
function post_smtp_sanitize_array( $_array ) {

    $array = array();

    foreach( $_array as $key => $value ) {

        $array[$key] = sanitize_text_field( $value );

    }

    return $array;

}
endif;

/**
 * Check pro extenstions is activated or not
 * 
 * @since 2.8.6
 * @version 1.0
 */

if( !function_exists( 'post_smtp_has_pro' )):
function post_smtp_has_pro(){
        
    if( is_plugin_active( 'zoho-premium/postsmtp-extension-zoho-mail.php' ) 
        ||
        is_plugin_active( 'twilio-notifications-postsmtp-extension-premium/plugin.php' ) 
        ||
        is_plugin_active( 'post-smtp-extension-amazon-ses-premium/plugin.php' ) 
        ||
        is_plugin_active( 'report-and-tracking-addon-premium/post-smtp-report-and-tracking.php' ) 
        ||
        is_plugin_active( 'post-smtp-extension-office365-premium/plugin.php' ) 
        ||
        is_plugin_active( 'attachment-support-premium/post-smtp-attachment-support.php' ) 
        ||
        is_plugin_active( 'advance-email-delivery-and-logs-premium/post-smtp-advanced-email-delivery-and-logs.php' )
        ||
        is_plugin_active( 'post-smtp-pro/post-smtp-pro.php' )
    ){
        return true;
    }
    else{

        return false;
    }

} 
endif; 

/**
 * Check if the BFCM is active.
 * 
 * @since 2.9.10
 */
if( ! function_exists( 'postman_is_bfcm' ) ):
function postman_is_bfcm() {

    if( get_transient( 'ps-skip-bfcm' ) || post_smtp_has_pro() ) {

        return false;

    }

    $promotion = Postman_Promotion_Manager::get_instance()->is_promotion_active( 'bfcm-2024' );

    return $promotion;

}
endif;

/**
 * Derive the encryption key for stored plugin secrets.
 *
 * @since 3.9.6
 * @return string
 */
if ( ! function_exists( 'post_smtp_get_secret_key' ) ) :
function post_smtp_get_secret_key() {
	return hash( 'sha256', wp_salt( 'auth' ) . 'post_smtp_secrets', true );
}
endif;

/**
 * Encrypt a secret for database storage (AES-256-CBC when OpenSSL is available).
 *
 * @since 3.9.6
 * @param string $value Plaintext secret.
 * @return string
 */
if ( ! function_exists( 'post_smtp_encode_secret' ) ) :
function post_smtp_encode_secret( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	if ( ! function_exists( 'openssl_encrypt' ) ) {
		return base64_encode( $value );
	}

	$key       = post_smtp_get_secret_key();
	$iv        = random_bytes( 16 );
	$encrypted = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

	if ( false === $encrypted ) {
		return base64_encode( $value );
	}

	return 'psenc1:' . base64_encode( $iv . $encrypted );
}
endif;

/**
 * Decrypt a stored secret. Falls back to legacy base64 values.
 *
 * @since 3.9.6
 * @param string $stored Stored secret value.
 * @return string
 */
if ( ! function_exists( 'post_smtp_decode_secret' ) ) :
function post_smtp_decode_secret( $stored ) {
	if ( '' === $stored || null === $stored ) {
		return '';
	}

	if ( 0 === strpos( $stored, 'psenc1:' ) && function_exists( 'openssl_decrypt' ) ) {
		$payload = base64_decode( substr( $stored, 7 ), true );

		if ( false === $payload || strlen( $payload ) < 17 ) {
			return '';
		}

		$iv         = substr( $payload, 0, 16 );
		$ciphertext = substr( $payload, 16 );
		$plain      = openssl_decrypt( $ciphertext, 'AES-256-CBC', post_smtp_get_secret_key(), OPENSSL_RAW_DATA, $iv );

		return false !== $plain ? $plain : '';
	}

	$decoded = base64_decode( $stored, true );

	return false !== $decoded ? $decoded : (string) $stored;
}
endif;

/**
 * Check whether a mobile FCM token is registered (non-blocking).
 *
 * @since 3.9.6
 * @param string $fcm_token Mobile device token.
 * @return bool
 */
if ( ! function_exists( 'post_smtp_mobile_is_valid' ) ) :
function post_smtp_mobile_is_valid( $fcm_token ) {
	$fcm_token = sanitize_text_field( (string) $fcm_token );

	if ( '' === $fcm_token ) {
		return false;
	}

	$device = get_option( 'post_smtp_mobile_app_connection' );

	return (bool) ( $device && isset( $device[ $fcm_token ] ) );
}
endif;

/**
 * Create a short-lived, single-use token for mobile email log viewing.
 *
 * @since 3.9.6
 * @param int    $log_id Log ID.
 * @param string $type   View type (log, transcript, details).
 * @return string
 */
if ( ! function_exists( 'post_smtp_create_mobile_log_view_token' ) ) :
function post_smtp_create_mobile_log_view_token( $log_id, $type ) {
	try {
		$view_token = bin2hex( random_bytes( 32 ) );
	} catch ( Exception $e ) {
		$view_token = wp_generate_password( 64, false, false );
	}

	set_transient(
		'post_smtp_log_view_' . $view_token,
		array(
			'log_id' => absint( $log_id ),
			'type'   => sanitize_text_field( (string) $type ),
		),
		5 * MINUTE_IN_SECONDS
	);

	return $view_token;
}
endif;

/**
 * Consume a mobile log view token (single use).
 *
 * @since 3.9.6
 * @param string $view_token Opaque view token.
 * @return array|false
 */
if ( ! function_exists( 'post_smtp_consume_mobile_log_view_token' ) ) :
function post_smtp_consume_mobile_log_view_token( $view_token ) {
	$view_token = sanitize_text_field( (string) $view_token );

	if ( '' === $view_token ) {
		return false;
	}

	$payload = get_transient( 'post_smtp_log_view_' . $view_token );

	if ( ! is_array( $payload ) || ! isset( $payload['log_id'], $payload['type'] ) ) {
		return false;
	}

	delete_transient( 'post_smtp_log_view_' . $view_token );

	return $payload;
}
endif;