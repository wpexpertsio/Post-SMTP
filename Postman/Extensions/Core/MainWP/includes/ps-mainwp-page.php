<?php

use MainWP\Dashboard\MainWP_DB;

if( !class_exists( 'Post_SMTP_MWP_Page' ) ):

class Post_SMTP_MWP_Page {
	
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

		foreach( $site_ids as $id ){
			
			$website = MainWP_DB::instance()->get_website_by_id( $id );
			?>
			<div class="post-smtp-mainwp">
				<h3><?php echo $website->name; ?></h3>
				<table>
					<tr>
						<td><label>Enable Individual Settings</label></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><label>Email Address</label></td>
						<td><input type="text" /></td>
					</tr>
					<tr>
						<td><label>Name</label></td>
						<td><input type="text" /></td>
					</tr>
					<tr>
						<td><label>Reply-To</label></td>
						<td><input type="text" /></td>
					</tr>
				</table>
			</div>
			<?php
			
		} 
		
        do_action( 'mainwp_pagefooter_extensions', __FILE__ );
		
	}
	
}

endif;