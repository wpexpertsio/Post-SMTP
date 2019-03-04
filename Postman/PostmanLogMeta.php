<?php

class PostmanLogMeta {

    public $log_meta_type = 'post_smtp_logs';

    private $meta_fields = array(
        'success',
        'from_header',
        'to_header',
        'cc_header',
        'bcc_header',
        'reply_to_header',
        'transport_uri',
        'original_to',
        'original_subject',
        'original_message',
        'original_headers',
        'session_transcript'
    );

    function install_table() {

        global $wpdb;

        $sql = "CREATE TABLE `{$this->log_meta_type}_{$wpdb->prefix}_{$this->log_meta_type}` ( 
                `meta_id` bigint(20) NOT NULL AUTO_INCREMENT,  
                `post_smtp_id` bigint(20) NOT NULL DEFAULT '0', 
                `meta_key` varchar(255) DEFAULT NULL,   `meta_value` longtext, 
                PRIMARY KEY (`meta_id`), 
                KEY `post_smtp_id` (`post_smtp_id`), 
                KEY `meta_key` (`meta_key`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ";

        dbDelta( $sql );
    }

    function migrate_data() {
        $args = array(
            'post_type' => 'postman_sent_mail',
            'posts_per_page' => -1,
        );

        $logs = new WP_Query($args);

        $failed_records = 0;
        foreach ( $logs->posts as $log ) {

            foreach ( $this->meta_fields as $key ) {
                $value = $this->get_meta( $log->ID, $key, true );

                if ( $this->add_meta( $log->ID, $key, $value ) ) {
                    delete_post_meta( $log->ID, $key );
                } else {
                    $failed_records++;
                }
            }
        }
    }

    function add_meta( $post_id = 0, $meta_key = '', $meta_value = '' ) {
        return add_metadata( $this->log_meta_type, $post_id, $meta_key, $meta_value );
    }

    function update_meta( $post_id = 0, $meta_key = '', $meta_value = '' ) {
        return update_metadata( $this->log_meta_type, $post_id, $meta_key, $meta_value );
    }

    function get_meta( $post_id = 0, $meta_key = '', $single = false ) {
        return get_metadata( $this->log_meta_type, $post_id, $meta_key, $single );
    }

    function delete_meta( $post_id = 0, $meta_key = '' ) {
        return delete_metadata( $this->log_meta_type, $post_id, $meta_key );
    }

}