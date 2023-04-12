<?php
/**
 * Plugin Name: Post SMTP
 */

if ( !class_exists( 'Post_SMTP_MainWP' ) ):

class Post_SMTP_MainWP {

    private $child_key = false;

    /**
     * Instance of the class
     * 
     * @since 1.0.0
     * @version 1.0.0
     * @var object
     */
    private static $instance = null;


    /**
     * Get the instance of the class
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function get_instance() {

        if( null == self::$instance ) {

            self::$instance = new self;

        }

        return self::$instance;

    }


    /**
     * PostSMTP_Report_And_Tracking constructor.
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {
    
    	add_filter( 'mainwp_getextensions', array( $this, 'get_this_extension' ) );
		add_filter( 'mainwp_header_left', array( $this, 'change_title' ) );

        $mainWPActivated = apply_filters( 'mainwp_activated_check', false );

        if ( $mainWPActivated !== false ) {

            $this->start_post_smtp_mainwp();
        
        } 
        else {
        
            add_action( 'mainwp_activated', array( $this, 'start_post_smtp_mainwp' ) );
        
        }

    }


    /**
     * Get this extension | Filter Callback
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function get_this_extension() {

        $extensions[] = array(
            'plugin'    =>  __FILE__, 
            'callback'  =>  array( $this, 'post_smtp_mainwp_page' )
        );

        return $extensions;

    }


    /**
     * Start the plugin
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function start_post_smtp_mainwp() {

        global $childEnabled;
        $childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );

        if ( !$childEnabled ) {

            return;

        }

        $this->child_key = $childEnabled['key'];

        $this->init();

    }


    /**
     * Post SMTP MainWP Page
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function post_smtp_mainwp_page() {

        $page = new Post_SMTP_MWP_Page();
		$page->page();

    }
	
	
	public function change_title( $title ) {
		
		if( $title == 'Post Smtp/Postman//Core/' ) {
			
			$title = 'Post SMTP';
			
		}
		
		return $title;
		
	}


    /**
     * Initialize The Plugin
     * 
     * @since 1.0.0
     * @version 1.0.0
     */
    public function init() {

        require_once 'includes/rest-api/v1/class-psmp-rest-api.php';
        require_once 'includes/ps-mainwp-page.php';

    }

}

Post_SMTP_MainWP::get_instance();

endif;