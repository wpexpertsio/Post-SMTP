<?php

use MainWP\Dashboard\MainWP_DB;

if( !class_exists( 'Post_SMTP_MWP_Page' ) ):

class Post_SMTP_MWP_Page {

	
    public function __construct() {
    
    	if( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Post-Smtp/Postman/Extensions/Core/MainWP' ) {
        
        	add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        }
		
		add_action( 'admin_post_post_smtp_mwp_save_sites', array( $this, 'save_sites' ) );
    
    }
    
    
    /**
     * Enquque Script | Action Callback
     *
     * @since 2.6.0
     * @version 1.0.0
     */
    public function enqueue_scripts() {
    
    	wp_enqueue_style( 'post-smtp-mainwp', plugin_dir_url( __DIR__ ) . 'assets/css/style.css', array(), '1.0.0' );
    
    }
	
    
    /**
     * Renders Page in MainWP
     *
     * @since 2.6.0
     * @version 1.0.0
     */
	public function page() {
		
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		
		$site_ids = array();
		$is_staging = 'no';
		$staging_view = get_user_option( 'mainwp_staging_options_updates_view' ) == 'staging' ? true : false;
		
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
					$site_ids[] = $website->id;
					
				}
			}
		} 
		elseif ( false !== $websites ) {
			
			while ( $website = MainWP_DB::fetch_object( $websites ) ) {
				
				if ( '' == $website->sync_errors ) {
					
					$cntr ++;
					$site_ids[] = $website->id;
				
				}
				
			}
			
		}
		
		?>

		<div class="post-smtp-mainwp ui form">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="post_smtp_mwp_save_sites" />
		<?php

		foreach( $site_ids as $id ){
			
			$website = MainWP_DB::instance()->get_website_by_id( $id );
			?>
			<div class="post-smtp-mainwp-site">
				<h3><?php echo esc_attr( $website->name ); ?></h3>
				<table>
					<tr>
						<td><label>Enable Individual Settings</label></td>
						<td><input type="checkbox" name="<?php echo 'enable[' . esc_attr( $id ) . ']'; ?>" /></td>
					</tr>
					<tr>
						<td><label>Email Address</label></td>
						<td><input type="text" name="<?php echo 'email_address[' . esc_attr( $id ) . ']'; ?>" /></td>
					</tr>
					<tr>
						<td><label>Name</label></td>
						<td><input type="text" name="<?php echo 'name[' . esc_attr( $id ) . ']'; ?>" /></td>
					</tr>
					<tr>
						<td><label>Reply-To</label></td>
						<td><input type="text" name="<?php echo 'reply_to[' . esc_attr( $id ) . ']'; ?>" /></td>
					</tr>
				</table>
			</div>
			<?php
			
		} 
		
		?>
		
				<input type="submit" class="ui button green" value="Save" />
			</form>
		</div>

		<?php
		
        do_action( 'mainwp_pagefooter_extensions', __FILE__ );
		
	}
	
	
	/**
	 * Save Sites | Action Callback
	 * 
	 * @since 2.6.0
	 * @version 1.0.0
	 */
	public function save_sites() {
		
		var_dump( '<pre>', $_POST );die;
		
	}
	
}

new Post_SMTP_MWP_Page();

endif;