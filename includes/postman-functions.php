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

if( !function_exists( 'post_smtp_check_extensions' )):
function post_smtp_check_extensions(){
        
        if( 
            ( !is_plugin_active( 'zoho-premium/postsmtp-extension-zoho-mail.php' ) 
            &&
            !is_plugin_active( 'twilio-notifications-postsmtp-extension-premium/plugin.php' ) 
            &&
            !is_plugin_active( 'post-smtp-extension-amazon-ses-premium/plugin.php' ) 
            &&
            !is_plugin_active( 'report-and-tracking-addon-premium/post-smtp-report-and-tracking.php' ) 
            &&
            !is_plugin_active( 'post-smtp-extension-office365-premium/plugin.php' ) 
            &&
            !is_plugin_active( 'attachment-support-premium/post-smtp-attachment-support.php' ) 
            &&
            !is_plugin_active( 'advance-email-delivery-and-logs-premium/post-smtp-advanced-email-delivery-and-logs.php' ) 
             )
        ){
            return true;
        }
        else{

            return false;
        }

    } 
endif; 