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
		
		require_once 'rest-api/v1/class-psmwp-rest-api.php';
    
    	$post_smtp_enabled = get_option( 'post_smtp_use_from_main_site' );

		if( $post_smtp_enabled ) {
			
			add_filter( 'post_smtp_dashboard_notice', array( $this, 'update_notice' ) );
            add_filter( 'post_smtp_declare_wp_mail', '__return_false' );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

            require_once 'classes/class-psmwp-request.php';
        	require_once 'psmwp-functions.php';
        
        }

    }
	
	/**
	 * Enqueue Admin Scripts.
	 * 
	 * @since 2.6.0
	 * @version 1.0.0
	 */
	public function enqueue_scripts() {
		
		$css = '
		.ps-config-bar .dashicons-dismiss {
			display: none;
		}';
		wp_add_inline_style( 
			'postman_style',
			$css
		);
		
	}
	
	
	/**
	 * Update Dashboard Notice | Filter Callback
	 * 
	 * @since 2.6.0
	 * @version 1.0.0
	 */
	public function update_notice() {
		
		return array(
			'error'		=>	false,
			'message'	=>	__( 'Post SMTP is being used by MainWP Dashboard Site.', 'post-smtp' )
		);
		
	}

}

endif;