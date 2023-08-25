<?php

class Post_SMTP_Mobile {

    private static $instance = null;
    private $qr_code = null;
    private $app_connected = false;

    /**
     * Get instance
     * 
     * @since 2.8.0
     * @version 1.0.0
     * 
     * @return Post_SMTP_Mobile
     */
    public static function get_instance() {
        
        if ( null === self::$instance ) {
            
            self::$instance = new self();
            
        }
        
        return self::$instance;

    }

    /**
     * Constructor
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function __construct() {
        
        add_action( 'admin_menu', array( $this, 'add_menu' ), 21 );
        add_action( 'post_smtp_settings_menu', array( $this, 'section' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

        add_filter( 'post_smtp_admin_tabs', array( $this, 'tabs' ), 11 );

        include_once 'includes/rest-api/v1/rest-api.php';
        
        $this->generate_qr_code();
        $this->app_connected = get_option( 'post_smtp_mobile_app_connection' );
		//delete_option( 'post_smtp_mobile_app_connection' );
        
    }

    /**
     * Enqueue scripts
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function admin_enqueue() {

        wp_enqueue_script( 'post-smtp-mobile', POST_SMTP_URL . '/Postman/Mobile/assets/js/admin.js', array( 'jquery' ), false );
        wp_enqueue_style( 'post-smtp-mobile', POST_SMTP_URL . '/Postman/Mobile/assets/css/admin.css' );

    } 

    /**
     * Add menu
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function add_menu() {
        
        add_submenu_page( 
            PostmanViewController::POSTMAN_MENU_SLUG, 
            __( 'Mobile Application', 'post-smtp' ), 
            sprintf( '%s<span class="dashicons dashicons-smartphone"></span><span class="menu-counter">%s</span>', __( 'Mobile App', 'post-smtp' ), __( 'New', 'post-smtp' ) ),
            'manage_options', 
            admin_url( 'admin.php?page=postman/configuration#mobile-app' ),
            '',
            3
        );
        
    }

    /**
     * Add tab
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function tabs( $tabs ) {
        
        $tabs['mobile-app'] = __( 'Mobile App', 'post-smtp' );

        return $tabs;
        
    }

    /**
     * Generate QR code
     *
     * @since 2.8.0
     * @version 1.0.0
     */
    public function generate_qr_code() {

        include_once 'includes/phpqrcode/qrlib.php';
        $nonce = get_transient( 'post_smtp_auth_nonce' );
		$authkey = $nonce ? $nonce : $this->generate_auth_key();
		$site_title = get_bloginfo( 'name' );
        set_transient( 'post_smtp_auth_nonce', $authkey );
        $endpoint = site_url( "?authkey={$authkey}&site_title={$site_title}" );
        ob_start();
        QRcode::png( urlencode_deep( $endpoint ) );
        $result_qr_content_in_png = ob_get_contents();
        ob_end_clean();
        // PHPQRCode change the content-type into image/png... we change it again into html
        header("Content-type: text/html");
        $this->qr_code =  base64_encode( $result_qr_content_in_png );

    }

    /**
     * Generate auth key
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    private function generate_auth_key() {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $chars .= '!@#$%^*()';
        $chars .= '-_ []{}<>~`+,.;:/|';

        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen( $chars ) - 1; //put the length -1 in cache
        for ( $i = 0; $i < 32; $i++ ) {
            $n = rand( 0, $alphaLength );
            $pass[] = $chars[$n];
        }
        return implode( $pass ); //turn the array into a string

    }

    /**
     * Section
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function section() {

        ?>
        <section id="mobile-app">
            <h2><?php _e( 'Mobile Application', 'post-smtp' ); ?></h2>
            <div class="mobile-app-box">
                <div class="mobile-app-internal-box ps-qr-box">
                    <?php 
                    if( !$this->app_connected ) {
                        
                        echo '<img src="data:image/jpeg;base64,'. $this->qr_code.'" width="300"/>'; 

                    }
					else {
						
						echo "<b>Connected Device:</b> ";
						
						foreach( $this->app_connected as $device ) {
							
							echo $device['device'];
							
						}
						
					}
                    ?>
                </div>
                <div class="mobile-app-internal-box">
                    <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/gif/qr-scan.gif' ) ?>" width="425" />	
                </div>
            </div>
        </section>
        <?php

    }

}

Post_SMTP_Mobile::get_instance();