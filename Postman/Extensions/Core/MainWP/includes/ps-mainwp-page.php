<?php

use MainWP\Dashboard\MainWP_DB;

if( !class_exists( 'Post_SMTP_MWP_Page' ) ):

class Post_SMTP_MWP_Page {

	
    public function __construct() {
    
    	if( 
			isset( $_GET['page'] ) 
			&& 
			( 
				$_GET['page'] == 'Extensions-Post-Smtp/Postman/Extensions/Core/MainWP' 
			 	|| 
			 	$_GET['page'] =='postman_email_log' 
			)
		) {
        
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
				<input type="hidden" name="psmwp_security" value="<?php echo esc_attr( wp_create_nonce( 'psmwp-security' ) ); ?>" />
				<div id="mainwp-select-sites-filters">
					<div class="ui mini fluid icon input">
						<input type="text" id="post-smtp-select-sites-filter" value="" placeholder="Type to filter your sites">
						<i class="filter icon"></i>
					</div>
				</div>
		<?php

		foreach( $site_ids as $id ) {
			
			$website = MainWP_DB::instance()->get_website_by_id( $id );
			$email_address = $this->get_option( $saved_sites, $id, 'email_address' ); 
			$name = $this->get_option( $saved_sites, $id, 'name' );
			$reply_to = $this->get_option( $saved_sites, $id, 'reply_to' );
			$checked = checked( $this->get_option( $saved_sites, $id, 'enabled' ), 1, false );
			
			?>

			<div class="post-smtp-mainwp-site">
				<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
					<div class="title"><i class="dropdown icon"></i> <?php echo esc_attr( $website->name ); ?></div>
					<div class="content">
						<table>
							<tr>
								<input type="hidden" name="site_id[]" value="<?php echo esc_attr( $id ); ?>" />
								<td><label>Enable Individual Settings</label></td>
								<td><input type="checkbox" <?php echo esc_attr( $checked ); ?> value="1" name="<?php echo 'enable['.esc_attr( $id ).']'; ?>" /></td>
							</tr>
							<tr>
								<td><label>Email Address</label></td>
								<td><input type="text" value="<?php echo esc_attr( $email_address ); ?>" name="<?php echo 'email_address[]'; ?>" /></td>
							</tr>
							<tr>
								<td><label>Name</label></td>
								<td><input type="text" value="<?php echo esc_attr( $name ); ?>" name="<?php echo 'name[]'; ?>" /></td>
							</tr>
							<tr>
								<td><label>Reply-To</label></td>
								<td><input type="text" value="<?php echo esc_attr( $reply_to ); ?>" name="<?php echo 'reply_to[]'; ?>" /></td>
							</tr>
						</table>
					</div>
				</div>
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
		
		//Security Check
		if( 
			isset( $_POST['action'] ) 
			&& 
			$_POST['action'] == 'post_smtp_mwp_save_sites' 
			&& 
			wp_verify_nonce( $_POST['psmwp_security'], 'psmwp-security' ) 
		) {
			
			$site_ids = $this->sanitize_array( $_POST['site_id'] );
			$email_addresses = $this->sanitize_array( $_POST['email_address'] );
			$names = $this->sanitize_array( $_POST['name'] );
			$reply_tos = $this->sanitize_array( $_POST['reply_to'] );
			$enables = $this->sanitize_array( $_POST['enable'] );
			$sites = array();
				
			foreach( $site_ids as $key => $id ) {
				
				$sites[$id] = array(
					'enabled'		=>	isset( $enables[$id] ) ? 1 : '',
					'email_address'	=>	$email_addresses[$key],
					'name'			=>	$names[$key],
					'reply_to'		=>	$reply_tos[$key]
				);
				
			}
			
			update_option( 'postman_mainwp_sites', $sites );
			
			wp_redirect( admin_url( 'admin.php?page=Extensions-Post-Smtp/Postman/Extensions/Core/MainWP' ) );
			
		}
		
	}
	
	
	/**
	 * Sanitizes the Array
	 * 
	 * @since 2.6.0
	 * @version 1.0.0
	 */
	public function sanitize_array( $args ) {
		
		$sanitized = array();
		
		foreach( $args as $key => $value ) {
			
			$sanitized[$key] = sanitize_text_field( $value );
			
		}
			
		return $sanitized;
		
	}
	
	
	/**
	 * Gets option value by key
	 * 
	 * @since 2.6.0
	 * @version 1.0.0
	 */
	public function get_option( $option, $site_id, $key ) {
		
		if( $option && isset( $option[$site_id] ) && $option[$site_id][$key] ) {
			
			return $option[$site_id][$key];
			
		}
		
		return '';
		
	}
	
}

new Post_SMTP_MWP_Page();

endif;