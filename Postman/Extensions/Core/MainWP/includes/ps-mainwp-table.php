<?php

use MainWP\Dashboard\MainWP_DB;

if( !class_exists( 'Post_SMTP_MWP_Table' ) ):

class Post_SMTP_MWP_Table {
	
	public function __construct() {
	
		add_action( 'post_smtp_email_logs_table_header', array( $this, 'email_logs_table_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'post_smtp_get_logs_query_after_table', array( $this, 'query_join' ), 10, 2 );
		add_filter( 'post_smtp_get_logs_query_cols', array( $this, 'query_columns' ), 10, 2 );
		add_filter( 'post_smtp_email_logs_localize', array( $this, 'email_logs_localize' ) );
		add_filter( 'ps_email_logs_row', array( $this, 'filter_row' ) );
		add_filter( 'post_smtp_get_logs_args', array( $this, 'logs_args' ) );

		//$message = new PostmanMessage();
		//$message->setReplyTo( 'yayyy@gmail.com' );
	
	}
	
	
	/**
     * Enqueue Scripts | Action Callback
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function admin_enqueue_scripts() {

        wp_enqueue_script( 'post-smtp-mainwp', plugin_dir_url( __DIR__ ) . 'assets/js/admin.js', array(), '1.0.0', true );

        wp_localize_script( 'post-smtp-mainwp', 'psrat', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            ),
        );

    }
	
	
	/**
     * Add Opened Column | Action Callback
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function email_logs_table_header() {

        echo '<th>' . __( 'Site', 'post-smtp' ) . '</th>';

    }
	
	
	/**
     * Add Opened Column | Filter Callback
     * 
     * @param $join String
     * @param $args Array
     * @since 1.0.0
     * @version 1.0.0
     */
    public function query_join( $join, $args ) {

		global $wpdb;
        $email_logs =  new PostmanEmailLogs();

        $join .= " LEFT JOIN {$wpdb->prefix}{$email_logs->meta_table} AS lm ON lm.log_id = pl.id AND lm.meta_key = 'mainwp_child_site_id'";
        
        return $join;

    }
	
	/**
	 * Add Opened Column | Filter Callback
     * 
     * @param $columns String
     * @param $args Array
     * @since 1.0.0
     * @version 1.0.0
     */
    public function query_columns( $columns, $args ) {

        $columns .= ', lm.meta_value AS site_id';

        return $columns;

    }
	
	
	/**
     * Localize the strings | Filter Callback
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function email_logs_localize( $localize ) {

        $localize['DTCols'][] = array(
            'data'    => 'site_id',
        );

        return $localize;

    }
	
	
	/**
	 * Fitler Log's Row | Filter Callback
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function filter_row( $row ) {
		
		if( $row->site_id ) {
		
			$website = MainWP_DB::instance()->get_website_by_id( $row->site_id );
			$row->site_id = "<a href='{$website->url}' target='_blank'>{$website->name}</a>";
		}
		if( empty( $row->site_id ) ) {
			
			$row->site_id = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Main Site';
			
		}
		
		return $row;
		
	}
	
	
	/**
     * Add Opened Column | Filter Callback
     * 
     * @param $args Array
     * @since 1.0.0
     * @version 1.0.0
     * 
     */
    public function logs_args( $args ) {

        if( $args['order_by'] == 'site_id' ) {

            $args['order_by'] = 'lm.meta_value';

        }

        return $args;

    }
	
}

new Post_SMTP_MWP_Table();

endif;