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
		add_action( 'postman_delete_logs_successfully', array( $this, 'delete_logs' ) );
	
	}
	
	
	/**
     * Enqueue Scripts | Action Callback
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function admin_enqueue_scripts() {

        wp_enqueue_script( 'post-smtp-mainwp', plugin_dir_url( __DIR__ ) . 'assets/js/admin.js', array(), '1.0.0', true );

        wp_localize_script( 'post-smtp-mainwp', 'PSMainWP', array(
            'childSites' 	=> 	$this->get_sites(),
			'mainSite'		=>	get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Main Site', 'post-smtp' ),
			'allSites'		=>	__( 'All Sites', 'post-smtp' )
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
		
		$url = admin_url( 'admin.php?page=postman_email_log' );

		if( $row->site_id && $row->site_id != 'main_site' ) {
		
			$url .= "&site_id={$row->site_id}";
			$website = MainWP_DB::instance()->get_website_by_id( $row->site_id );
			$row->site_id = "<a href='{$url}'>{$website->name}</a>";
		}
		
		if( $row->site_id == 'main_site' ) {
			
			$url .= "&site_id=main_site";
			$row->site_id = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Main Site';
			$row->site_id = "<a href='{$url}'>{$row->site_id}</a>";
			
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
	
	
	/**
	 * Gets MainWP's Child Sites
	 * 
	 * @since 2.5.0
	 * @version 1.0.0
	 */
	public function get_sites() {
		
		$site_ids = array();
		$is_staging = 'no';
		$staging_view = $this->is_staging_view();
		$saved_sites = get_option( 'postman_mainwp_sites' );
		
		if ( $staging_view ) {
			
			$is_staging = 'yes';
			
		}
		
		$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_for_current_user( false, null, 'wp_sync.dtsSync DESC, wp.url ASC', false, false, null, false, array(), $is_staging ) );
		
      $cntr = 0;
		if ( is_array( $websites ) ) {
			
			$count = count( $websites );
			
			for ( $i = 0; $i < $count; $i ++ ) {
				
				$website = $websites[ $i ];
				
				if ( '' == $website->sync_errors ) {
					
					$cntr ++;
					$site_ids[$website->id] = $website->name;
					
				}
			}
		} 
		elseif ( false !== $websites ) {
			
			while ( $website = MainWP_DB::fetch_object( $websites ) ) {
				
				if ( '' == $website->sync_errors ) {
					
					$cntr ++;
					$site_ids[$website->id] = $website->name;
				
				}
				
			}
			
		}
		
		return empty( $site_ids ) ? false : $site_ids;
		
	}
	
	
	public function is_staging_view() {
		
		$user = get_current_user_id();

		$userdata = WP_User::get_data_by( 'id', $user );

		if ( ! $userdata ) {

			return false;

		}

		$user = new WP_User();

		$user->init( $userdata );

		if ( ! $user ) {

			return false;

		}

		global $wpdb;

		$prefix = $wpdb->get_blog_prefix();

		if ( $user->has_prop( $prefix . 'mainwp_staging_options_updates_view' ) ) { // Blog-specific.

			$result = $user->get( $prefix . 'mainwp_staging_options_updates_view' );

		} 
		elseif ( $user->has_prop( 'mainwp_staging_options_updates_view' ) ) { // User-specific and cross-blog.

			$result = $user->get( 'mainwp_staging_options_updates_view' );

		} 
		else {

			$result = false;

		}

		return $result;
		
	}
	
	
	 /**
     * Delete logs
     * 
     * @param array $ids
     * @since 2.5.0
     * @version 1.0.0
     */
	public function delete_logs( $ids ) {
		
		$ids = implode( ',', $ids );
        $ids = $ids == -1 ? '' : "WHERE log_id IN ({$ids});";
		
		global $wpdb;
		$email_logs = new PostmanEmailLogs();

        return $wpdb->query(
            "DELETE FROM `{$wpdb->prefix}{$email_logs->meta_table}` {$ids}"
        );
		
	}
	
}

new Post_SMTP_MWP_Table();

endif;