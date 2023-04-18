<?php

if( !class_exists( 'Post_SMTP_MWP_Table' ) ):

class Post_SMTP_MWP_Table {
	
	public function __construct() {
	
		//add_action( 'post_smtp_email_logs_table_header', array( $this, 'email_logs_table_header' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
	
}

new Post_SMTP_MWP_Table();

endif;