<?php

if( !class_exists( 'PostmanEmailQueryLog' ) ):
class PostmanEmailQueryLog {

    private $db = '';
    public $table = 'post_smtp_logs';
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

        /**
         * Filter the query arguments
         * 
         * @param $args Array
         * @since 2.5.0
         * @version 1.0.0
         */
        $args = apply_filters( 'post_smtp_get_logs_args', $args );

        $clause_for_date = empty( $args['search'] ) ? $this->query .= " WHERE" : $this->query .= " AND";

        $args['search_by'] = array(
            'original_subject',
            'success',
            'to_header'
        );

        if( !isset( $args['columns'] ) ) {

            $this->columns = array(
                'id',
                'original_subject',
                'to_header',
                'success',
                'time'
            );

        }
        else {

            $this->columns = $args['columns'];

        }

        $this->columns = array_map( function( $column ) {
            return "pl.`{$column}`";
        }, $this->columns );

        $this->columns = implode( ',', $this->columns );

        /**
         * Filter the query columns
         * 
         * @param $query String
         * @param $args Array
         * @since 2.5.0
         * @version 1.0.0
         */
        $this->columns = apply_filters( 'post_smtp_get_logs_query_cols', $this->columns, $args );

        $this->query = $this->db->prepare(
            "SELECT {$this->columns} FROM %i AS pl",
            $this->table
        );

        /**
         * Filter the query after the table name
         * 
         * @param $query String
         * @param $args Array
         * @since 2.5.0
         * @version 1.0.0
         */
        $this->query = apply_filters( 'post_smtp_get_logs_query_after_table', $this->query, $args );

        //Search
        if( !empty( $args['search'] ) ) {

            $this->query .= " WHERE";
            $counter = 1;

            foreach( $args['search_by'] as $key ) {
                
                $this->query .= " {$key} LIKE '%{$this->db->esc_like( $args["search"] )}%'";
                $this->query .= $counter != count( $args['search_by'] ) ? ' OR' : '';
                $counter++;

            }

        }

        //Date Filter :)
        if( isset( $args['from'] ) ) {
                
            $this->query .= $this->db->prepare( 
                " {$clause_for_date} pl.`time` >= %d",
                $args['from']
            );

        }

        if( isset( $args['to'] ) ) {

            $clause_for_date = ( empty( $args['search'] ) && !isset( $args['from'] ) ) ? " WHERE" : " AND";

            $this->query .= $this->db->prepare(
                " {$clause_for_date} pl.`time` <= %d",
                $args['to']
            );

        }
		
		if( isset( $args['site_id'] ) && $args['site_id'] != -1 ) {

            $clause_for_site = ( empty( $args['search'] ) ) ? " WHERE" : " AND";
			
            $this->query .= " {$clause_for_site} lm.meta_value = '{$args['site_id']}'";

        }

        //Order By
        if( !empty( $args['order'] ) && !empty( $args['order_by'] ) ) {

            $orderby_sql = sanitize_sql_orderby( "`{$args['order_by']}` {$args['order']}" );

            //If no alias added, add one
            if( !strpos( $args['order_by'], '.' ) ) {
                    
                $orderby_sql = "pl.{$orderby_sql}";

            }
            
            $this->query .= " ORDER BY {$orderby_sql}";

        }

        //Lets say from 0 to 25
        if( isset( $args['start'] ) && isset( $args['end'] ) ) {
            
            $this->query .= $this->db->prepare(
                " LIMIT %d, %d",
                $args['start'],
                $args['end']
            );

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


    /**
     * Get Last Log ID
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_last_log_id() {

        $result = $this->db->get_results(
            "SELECT id FROM `{$this->table}` ORDER BY id DESC LIMIT 1;"
        );

        return empty( $result ) ? false : $result[0]->id;

    }


    /**
     * Delete Logs
     * 
     * @param $ids Array
     * @since 2.5.0
     * @version 1.0.0
     */
    public function delete_logs( $ids = array() ) {
        
        $ids = implode( ',', $ids );
        $ids = $ids == -1 ? '' : "WHERE id IN ({$ids});";

        return $this->db->query(
            "DELETE FROM `{$this->table}` {$ids}"
        );

    }


    /**
     * Get All Logs
     * 
     * @param $ids Array
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_all_logs( $ids = array() ) {

        $ids = implode( ',', $ids );
        $ids = $ids == -1 ? '' : "WHERE id IN ({$ids});";

        return $this->db->get_results(
            "SELECT * FROM `{$this->table}` {$ids}"
        );


    }


    /**
     * Get Log
     * 
     * @param $id Int
     * @param $columns Array
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_log( $id, $columns = array() ) {

        $columns = empty( $columns ) ? '*' : implode( ',', $columns );

        return $this->db->get_row(
            $this->db->prepare(
                "SELECT {$columns} FROM %i WHERE id = %d",
                $this->table,
                $id
            ),
            ARRAY_A
        );


    }

}
endif;