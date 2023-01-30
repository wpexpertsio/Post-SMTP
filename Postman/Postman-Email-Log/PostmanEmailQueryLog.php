<?php

if( !class_exists( 'PostmanEmailQueryLog' ) ):
class PostmanEmailQueryLog {

    private $db = '';
    private $table = 'post_smtp_logs';
    private $query = ''; 
    private $columns = array();


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
        $args['search_by'] = array(
            'original_subject',
            'success',
            'solution',
            'to_header'
        );

        if( !isset( $args['columns'] ) ) {

            $this->columns = array(
                'id',
                'original_subject',
                'original_to',
                'success',
                'solution',
                'time'
            );

        }
        else {

            $this->columns = $args['columns'];

        }

        $this->columns = implode( ',', $this->columns );

        $this->query = "SELECT {$this->columns} FROM `{$this->table}`";

        //Search
        if( !empty( $args['search'] ) ) {

            $this->query .= " WHERE";
            $counter = 1;

            foreach( $args['search_by'] as $key ) {
                
                $this->query .= " {$key} LIKE '%{$args["search"]}%'";
                $this->query .= $counter != count( $args['search_by'] ) ? ' OR' : '';
                $counter++;

            }

        }

        //Order By
        if( !empty( $args['order'] ) && !empty( $args['order_by'] ) ) {

            $this->query .= " ORDER BY {$args['order_by']} {$args['order']}";

        }

        //Lets say from 0 to 25
        if( isset( $args['start'] ) && isset( $args['end'] ) ) {
            
            $this->query .= " LIMIT {$args['start']}, {$args['end']}";

        }

        return $this->db->get_results( $this->query );

    }


    /**
     * Get Filtered Rows Count
     * Total records, after filtering (i.e. the total number of records after filtering has been applied - not just the number of records being returned for this page of data).
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_filtered_rows_count() {

        $query = str_replace( $this->columns, 'COUNT(*) as count', $this->query );

        //Remove LIMIT clouse to use COUNT clouse properly 
        $query = substr( $query, 0, strpos( $query, "LIMIT" ) );

        return $this->db->get_results( $query );

    }


    /**
     * Gets Total Rows Count
     * Total records, before filtering (i.e. the total number of records in the database)
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_total_row_count() {

        return $this->db->get_results(
            "SELECT COUNT(*) as count FROM `{$this->table}`;"
        );

    }

}
endif;