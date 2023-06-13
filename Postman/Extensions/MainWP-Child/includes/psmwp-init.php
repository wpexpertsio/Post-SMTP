<?php

if( !class_exists( 'Post_SMTP_MainWP_Child' ) ):

class Post_SMTP_MainWP_Child {

    private static $instance;


    /**
     * Get instance
     * 
     * @return object
     * @since 2.6.0
     * @version 2.6.0
     */
    public static function get_instance() {

        if( !isset( self::$instance ) && !( self::$instance instanceof Post_SMTP_MainWP_Child ) ) {

            self::$instance = new Post_SMTP_MainWP_Child();

        }

        return self::$instance;

    }


    /**
     * Constructor
     * 
     * @since 2.6.0
     * @version 2.6.0
     */
    public function __construct() {

        $this->validate();

    }


    /**
     * Validate
     * 
     * @since 2.6.0
     * @version 2.6.0
     */
    public function validate() {

        if( !function_exists( 'is_plugin_active' ) ) {
            
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            
        }

        if( is_plugin_active( 'mainwp-child/mainwp-child.php' ) ) {

            $this->init();

        }

    }


    /**
     * Init
     * 
     * @since 2.6.0
     * @version 2.6.0
     */
    public function init() {
    
    	$post_smtp_enabled = get_option( 'post_smtp_enabled_for_child' );

		if( $post_smtp_enabled ) {

            add_filter( 'post_smtp_declare_wp_mail', '__return_false' );
        	
            require_once 'includes/rest-api/v1/class-psmwp-rest-api.php';
            require_once 'classes/class-psmwp-request.php';
        	require_once 'psmwp-functions.php';
        
        }

    }

}

endif;