<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class PostmanEmailLogs {

    private $db;

    public $db_name = 'post_smtp_logs';

    private $fields = array(
        'solution',
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
        'session_transcript',
        'time'
    );

    private static $instance;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function __construct() {

        global $wpdb;

        $this->db = $wpdb;

    }


    /**
     * Installs the Table | Creates the Table
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function install_table() {

        $indexed = array(
            'success',
            'to_header',
            'cc_header',
            'bcc_header',
            'reply_to_header',
            'transport_uri',
            'original_log',
            'original_subject',
            'time'
        );
        
        if( !function_exists( 'dbDelta' ) ) {

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->db->prefix}{$this->db_name}` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,";

        foreach ($this->fields as $field ) {

            if( in_array( $field, $indexed ) ) {

                $sql .= "INDEX ".$field." (".$field.")";

            }

            if ( $field == 'original_message' || $field == 'session_transcript' ) {

                $sql .= "`" . $field . "` longtext DEFAULT NULL,";
                continue;

            }

            if( $field == 'time' ) {

                $sql .= "`" . $field . "` BIGINT(20) DEFAULT NULL,";
                continue; 

            } 

            $sql .= "`" . $field . "` varchar(255) DEFAULT NULL,";
            
        }

        $sql .=  "PRIMARY KEY (`id`)) ENGINE=InnoDB CHARSET={$this->db->charset} COLLATE={$this->db->collate};";

        dbDelta( $sql );

        update_option( 'postman_db_version', POST_SMTP_DB_VERSION );

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


    /**
     * Get Logs
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function get_logs() {

        return $this->db->get_results(
            "SELECT * FROM `{$this->db->prefix}{$this->db_name}`"
        );

    }


    /**
     * Delete Log Items, But Keeps recent $keep
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function truncate_log_items( $keep ) {

        return $this->db->get_results(
            $this->db->prepare(
                "DELETE logs FROM `{$this->db->prefix}{$this->db_name}` logs
                LEFT JOIN 
                (SELECT id 
                FROM `{$this->db->prefix}{$this->db_name}` 
                ORDER BY id DESC
                LIMIT %d) logs2 USING(id) 
                WHERE logs2.id IS NULL;",
                $keep
            )
        );

    }


    /**
     * Insert Log Into table
     * 
     * @param array $data
     * @since 2.4.0
     * @version 1.0.0
     */
    public function save( $data ) {

        return $this->db->insert(
            $this->db->prefix . $this->db_name,
            $data  
        );

    }


    /**
     * Get Logs
     * 
     * @since 2.4.0
     * @version 1.0
     */
    public function get_logs_ajax() {

        $logs = $this->get_logs();

        return wp_send_json_success( 
            $logs, 
            200 
        );

    }

}