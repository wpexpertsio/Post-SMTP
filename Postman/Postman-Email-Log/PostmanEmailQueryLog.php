<?php

if( !class_exists( 'PostmanEmailQueryLog' ) ):
class PostmanEmailQueryLog {

    private $start = '';
    private $end = '';
    private $search = '';
    private $order = '';
    private $order_by = '';
    private $search_by = '';
    private $db = '';
    private $table = 'post_smtp_logs';


    /**
     * The Construct PostmanEmailQueryLog
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function __construct() {

        global $wpdb;
        $this->db = $wpdb;
        $this->table = $this->db->prefix . $this->table;

        
    }


    /**
     * Get Logs
     * 
     * @param $args String
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_logs( $args = array() ) {

        $data = '';
        $query = "SELECT * FROM `{$this->table}`";
        $args['search_by'] = array(
            'original_subject',
            'success',
            'solution',
            'to_header'
        );

        //Search
        if( !empty( $args['search'] ) ) {

            $query .= " WHERE";
            $counter = 1;

            foreach( $args['search_by'] as $key ) {
                
                $query .= " {$key} LIKE '%{$args["search"]}%'";
                $query .= $counter != count( $args['search_by'] ) ? ' OR' : '';
                $counter++;

            }

        }

        //Order By
        if( !empty( $args['order'] ) && !empty( $args['order_by'] ) ) {

            $query .= " ORDER BY {$args['order_by']} {$args['order']}";

        }

        //Lets say from 0 to 25
        if( isset( $args['start'] ) && isset( $args['end'] ) ) {
            
            $query .= " LIMIT {$args['start']}, {$args['end']}";

        }

        return $this->db->get_results( $query );

    }


    /**
     * Get Rows Count
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_row_count() {
        
        return $this->db->get_results(
            "SELECT COUNT(*) as count FROM `{$this->table}`;"
        );

    }

}
endif;