<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanEmailLogs {

    private $db;

    public $db_name = 'post_smtp_logs';

    private static $fields = array(
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

    private static $instance;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instanc = new static();
        }

        return self::$instance;
    }

    private function __construct() {
        global $wpdb;

        $this->db = $wpdb;
    }

    function install_table() {

        global $wpdb;

        $sql = "CREATE TABLE `{$wpdb->prefix}_{$this->db_name}` ( 
                `id` bigint(20) NOT NULL AUTO_INCREMENT, ";

        foreach ($this->fields as $field ) {
            if ( $field == 'original_message' || $field == 'session_transcript' ) {
                $sql .= "`" . $field . "` longtext DEFAULT NULL,";
                continue;
            }
            $sql .= "`" . $field . "` varchar(255) DEFAULT NULL,";
        }
        $sql .=  "PRIMARY KEY (`id`)) ENGINE=InnoDB CHARSET={$wpdb->charset} COLLATE={$wpdb->collate}; ";

        dbDelta( $sql );
    }

    public static function get_data( $post_id ) {
        $fields = array();
        foreach ( self::$fields as $field ) {
            $fields[$field][0] = get_post_meta( $post_id, $field, true );
        }

        return $fields;
    }

    public static function get_fields() {
        return self::$fields;
    }

    function migrate_data() {
        $args = array(
            'post_type' => 'postman_sent_mail',
            'posts_per_page' => -1,
        );

        $logs = new WP_Query($args);

        $failed_records = 0;
        foreach ( $logs->posts as $log ) {

            foreach ($this->fields as $key ) {
                $value = $this->get_meta( $log->ID, $key, true );

                if ( $this->add_meta( $log->ID, $key, $value ) ) {
                    delete_post_meta( $log->ID, $key );
                } else {
                    $failed_records++;
                }
            }
        }
    }

    function load() {
        $this->db->select();
    }

    /**
     * @param array $data
     */
    function save( $data ) {
        $this->db->query( $this->db->prepare(
            "
		INSERT INTO $this->db_name 
		( " . implode( ',', array_keys( $data ) ) . " )
		VALUES ( " . str_repeat( '%s', count( $data ) ) . " )", array_values( $data )
        ) );
    }

}