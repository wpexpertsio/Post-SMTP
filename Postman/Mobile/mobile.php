<?php

class Post_SMTP_Mobile {

    private static $instance = null;
    private $qr_code = null;
    private $app_connected = false;

    /**
     * Get instance
     * 
     * @since 2.7.0
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {
        
        add_action( 'plugins_loaded', array( $this, 'remove_device' ) );
        add_action( 'admin_menu', array( $this, 'add_menu' ), 21 );
        add_action( 'post_smtp_settings_menu', array( $this, 'section' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_action( 'admin_action_post_smtp_disconnect_app', array( $this, 'disconnect_app' ) );
        add_action( 'admin_post_ps_dimiss_app_notice', array( $this, 'dismiss_app_notice' ) );
        add_action( 'admin_post_regenerate-qrcode', array( $this, 'regenerate_qrcode' ) );
		
		add_filter( 'post_smtp_sanitize', array( $this, 'sanitize' ), 10, 3 );
        add_filter( 'post_smtp_admin_tabs', array( $this, 'tabs' ), 11 );

        include_once 'includes/rest-api/v1/rest-api.php';
        include_once 'includes/controller/v1/controller.php';
        include_once 'includes/email-content.php';
        
        if( isset( $_GET['page'] ) && $_GET['page'] == 'postman/configuration' ) {
			
            //Incompatible server
            if( function_exists( 'ImageCreate' ) ) {

                $this->generate_qr_code();
                $this->app_connected = get_option( 'post_smtp_mobile_app_connection' );

            }
			
		}
        
    }

    /**
     * Enqueue scripts
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function admin_enqueue() {

        wp_enqueue_script( 'post-smtp-mobile', POST_SMTP_URL . '/Postman/Mobile/assets/js/admin.js', array( 'jquery' ), POST_SMTP_VER );
        wp_enqueue_style( 'post-smtp-mobile', POST_SMTP_URL . '/Postman/Mobile/assets/css/admin.css', array(), POST_SMTP_VER );

    } 

    /**
     * Add menu
     * 
     * @since 2.7.0
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function tabs( $tabs ) {
        
        $tabs['mobile-app'] = __( 'Mobile App', 'post-smtp' );

        return $tabs;
        
    }

    /**
     * Generate QR code
     *
     * @since 2.7.0
     * @version 1.0.0
     */
    public function generate_qr_code() {

        include_once 'includes/phpqrcode/qrlib.php';
        $nonce = get_transient( 'post_smtp_auth_nonce' );
		$authkey = $nonce ? $nonce : $this->generate_auth_key();
		$site_title = get_bloginfo( 'name' );
        set_transient( 'post_smtp_auth_nonce', $authkey, 1800 );
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
     * @since 2.7.0
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function section() {

        //Incompatible server
        if( function_exists( 'ImageCreate' ) ):
        ?>
        <section id="mobile-app">
            <h2><?php _e( 'Mobile Application', 'post-smtp' ); ?></h2>
            <div class="download-app">
                <div style="float: left;">
                    <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/mobile.png' ) ?>" width="55px" />
                </div>
                <div style="display: inline-block; text-align: center;">
                    <h3>Download Post SMTP Mobile Application</h3>
                </div>
                <div style="float: right; margin: 19px 0;">
                    <a href="https://play.google.com/store/apps/details?id=com.postsmtp" target="_blank" /><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/google-play.png' ) ?>" class="google-logo" /></a>
                    <a href="https://apps.apple.com/us/app/post-smtp/id6473368559" target="_blank" /><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/apple-store.png' ) ?>" class="apple-logo" /></a>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div class="mobile-app-box">
                <div class="mobile-app-internal-box">
                    <ol style="line-height: 30px;">
                        <li>Open Post SMTP <b>mobile app</b> 📱 on your android or iOS device.</li>
                        <li>Tap on <b>Scan QR Code</b> button to link your mobile device with Post SMTP plugin.</li>
                        <li>Point your mobile device to this screen to <b>capture</b> the QR Code.</li>
                    </ol>
                    <p>
                        And you are done👍.
                    </p>
                    <p>
                        Want more details? Check out our complete guide <a href="https://postmansmtp.com/documentation/advance-functionality/postsmtp-mobile-app" target="_blank">Post SMTP Plugin with Mobile App</a>
                    </p>
                </div>
                <div class="mobile-app-internal-box ps-qr-box" style="line-height: 30px;">
                    <?php 
                    if( !$this->app_connected ) {
                        
                        echo '<img src="data:image/jpeg;base64,'. $this->qr_code.'" width="300"/>'; 
                        ?>
                        <div>
                            <a href="<?php echo esc_url( admin_url('admin-post.php?action=regenerate-qrcode') ); ?>"><?php _e( 'Regenerate QR Code', 'post-smtp' ) ?></a>
                        </div>
                        <?php

                    }
					else {
						
						echo "<b>Connected Device:</b> ";
						
						$nonce = wp_create_nonce( 'ps-disconnect-app-nonce' );
						
						foreach( $this->app_connected as $device ) {
							
							$url = admin_url( "admin.php?action=post_smtp_disconnect_app&auth_token={$device['fcm_token']}&ps_disconnect_app_nonce={$nonce}" );
							$checked = $device['enable_notification'] == 1 ? 'checked="checked"' : '';
							
							echo  esc_html( $device['device'] ) . "<a href='{$url}' style='color: red'>Disconnect</a>";
							echo '<br />';
							echo sprintf(
								'<label for="enable-app-notice">%s <input type="checkbox" id="enable-app-notice" name="postman_app_connection[%s]" %s /></label>',
								__( 'Send failed email notification' ),
								$device['fcm_token'],
								$checked
							);
							
						}
						
					}
                    ?>
                </div>
            </div>
        </section>
        <?php
        endif;

        //Incompatible server
        if( !function_exists( 'ImageCreate' ) ):
            ?>
            <section id="mobile-app">
                <h2><?php _e( 'Mobile Application', 'post-smtp' ); ?></h2>
                <?php 
                    printf(
                        '%s <a href="%s" target="_blank">%s</a>',
                        __( 'Your server does not have GD Library Installed/ Enabled, talk to your host provider to enable to enjoy Post SMTP Mobile Application', 'post-smtp' ),
                        esc_url( 'https://www.php.net/manual/en/image.installation.php' ),
                        __( 'learn more.', 'post-smtp' )
                    ) 
                ?>
            </section>
            <?php
        endif;

    }
	
	/**
     * Sanitize the Settings | Filter Callback
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function sanitize( $input, $option, $section ) {
			
		$connected_devices = get_option( 'post_smtp_mobile_app_connection' );
		$devices = !isset( $_POST['postman_app_connection'] ) ? array() : array_keys( $_POST['postman_app_connection'] );

		if( $connected_devices ) {

			foreach( $connected_devices as $key => $device ) {

				if( in_array( $device['fcm_token'], $devices ) ) {

					$connected_devices[$key]['enable_notification'] = 1;

				}
				else {

					$connected_devices[$key]['enable_notification'] = 0;

				}

			}

		}

		update_option( 'post_smtp_mobile_app_connection', $connected_devices );

        return $input;

    }
	
    /**
     * Disconnects the mobile application :(
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
	public function disconnect_app() {

		if( !isset( $_GET['ps_disconnect_app_nonce'] ) || !wp_verify_nonce( $_GET['ps_disconnect_app_nonce'], 'ps-disconnect-app-nonce' ) ) {
			
			die( 'Security Check' );
			
		}

		if( isset( $_GET['action'] ) && $_GET['action'] == 'post_smtp_disconnect_app' ) {
			
			$connected_devices = get_option( 'post_smtp_mobile_app_connection' );
			$auth_token = $_GET['auth_token'];
			$server_url = get_option( 'post_smtp_server_url' );
			
			if( $connected_devices && isset( $connected_devices[$auth_token] ) ) {
				
				$device = $connected_devices[$auth_token];
				$auth_key = $device['auth_key'];
				
				$response = wp_remote_post(
					"{$server_url}/disconnect-app",
					array(
						'method'	=>	'PUT',
						'headers'	=>	array(
							'Content-Type'	=>	'application/json',
							'Auth-Key'		=>	$auth_key,
							'FCM-Token'		=>	$auth_token
						)
					)
				);
				
				$response_code = wp_remote_retrieve_response_code( $response );
				
				if( $response_code == 200 ) {
					
					delete_option( 'post_smtp_mobile_app_connection' );
					delete_option( 'post_smtp_server_url' );
					
				}
				
			}
			
			wp_redirect( admin_url( 'admin.php?page=postman/configuration#mobile-app' ) );
			
		}
		
	}

    /**
     * Dismiss App Notice | Action Call-back
     * 
     * @since 2.7.1
     * @version 1.0.0
     */
    public function dismiss_app_notice() {

        if( isset( $_GET['action'] ) && $_GET['action'] === 'ps_dimiss_app_notice' ) {

            update_option( 'ps_dismissed_mobile_notice', 1 );

            wp_redirect( admin_url( 'admin.php?page=postman' ) );

        }

    }

    /**
     * Regenerates QR Code | Action Call-back
     * 
     * @since 2.8.2
     * @version 1.0.0
     */
    public function regenerate_qrcode() {

        if( isset( $_GET['action'] ) && $_GET['action'] === 'regenerate-qrcode' ) {

            delete_transient( 'post_smtp_auth_nonce' );

            wp_redirect( admin_url( 'admin.php?page=postman/configuration#mobile-app' ) );

        }

    }

    /**
     * Remove Device With Incomplete Information
     * 
     * @since 2.8.10
     * @version 1.0.0
     */
    public function remove_device() {
		
        if( !isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && $_GET['page'] !== 'postman/configuration' ) ) {
			
			$device = get_option( 'post_smtp_mobile_app_connection' );
			$device = $device ? reset( $device ) : $device;

			if( $device && !isset( $device['auth_key'] ) || $device && empty( $device['auth_key'] ) ) {

				delete_option( 'post_smtp_mobile_app_connection' );
				delete_option( 'post_smtp_server_url' );
				delete_transient( 'post_smtp_auth_nonce' );

				return;

			}

			return;
			
		}
        
    }

}

Post_SMTP_Mobile::get_instance();