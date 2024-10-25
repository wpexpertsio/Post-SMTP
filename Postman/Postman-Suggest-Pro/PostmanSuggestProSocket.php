<?php

/**
 * class PostmanSuggestProSocket
 * 
 * @since 2.2
 * @version 1.0
 */
if( !class_exists( 'PostmanSuggestProSocket' ) ):
class PostmanSuggestProSocket {

    public $data = array();
    private $fs = null;
    
    /**
     * class constructor PostmanSuggestProSocket
     * 
     * @since 2.2
     * @version 1.0
     */
    public function __construct() {

        $this->pro_extenstions();
        $this->fs = freemius( 10461 );
        $hide_notice = get_transient( 'post_smtp_skip_banner' );

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        $this->fs->add_action( 'addons/after_addons', array( $this, 'promote_bundles_fs' ) );
        
        
        if( !post_smtp_has_pro() ) {

            add_action( 'admin_menu', array( $this, 'add_menu' ), 22 );
        
        }
        if( !post_smtp_has_pro() && !$hide_notice ){

            add_action( 'post_smtp_dashboard_after_config', array( $this, 'promote_bundles_dashboard' ) );
        
        }
        
        add_filter( 'gettext', array( $this, 'change_fs_submenu_text' ), 10, 3 );
        add_action( 'admin_action_ps_skip_pro_banner', array( $this, 'skip_pro_banner' ) );
        
    }

    /**
     * Gather pro extenstions
     * 
     * @since 2.2
     * @version 1.0
     */
    public function pro_extenstions() {

        if( !class_exists( 'Post_Smtp_Office365' ) ) {
            $this->data[] = array(
                'extenstion'    =>  'Office365 API (Pro)',
                'logo'          =>  POST_SMTP_ASSETS . 'images/logos/office365.png',
                'pro'           =>  POST_SMTP_ASSETS . 'images/icons/pro.png',
                'url'           =>  'https://postmansmtp.com/extensions/office-365-extension-for-post-smtp/'
            );
        }

        if( !class_exists( 'Post_Smtp_Amazon_Ses' ) ) {
            $this->data[] = array(
                'extenstion'    =>  'Amazon SES (Pro)',
                'logo'          =>  POST_SMTP_ASSETS . 'images/logos/amazonses.png',
                'pro'           =>  POST_SMTP_ASSETS . 'images/icons/pro.png',
                'url'           =>  'https://postmansmtp.com/extensions/post-smtp-extension-for-amazon-ses/'
            );
        }

        if( !class_exists( 'PostSMTP_ZohoMail' ) ) {
            $this->data[] = array(
                'extenstion'    =>  'Zoho (Pro)',
                'logo'          =>  POST_SMTP_ASSETS . 'images/logos/zoho.jpg',
                'pro'           =>  POST_SMTP_ASSETS . 'images/icons/pro.png',
                'url'           =>  'https://postmansmtp.com/extensions/zoho-mail-pro-extension/'
            );
        }


    }

    /**
     * Enqueue Script | Action call-back
     * 
     * @since 2.2
     * @version 1.0
     */
    public function admin_enqueue_scripts( $hook ) {

        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );

        wp_register_script( 'postman-suggest-pro-sockets', POST_SMTP_ASSETS . 'js/postman-admin.js', array( 'jquery' ), $pluginData['version'], true );

        wp_enqueue_script( 'postman-suggest-pro-sockets' );

        wp_localize_script( 
            'postman-suggest-pro-sockets', 
            'postmanPro', 
            $this->data
        );
        
        wp_register_style( 'extension-ui-fonts', 'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap', array(), $pluginData['version'], 'all' );

        if ( 'post-smtp_page_extensions' === $hook ) {
            wp_enqueue_style( 'extensions-ui', plugin_dir_url( __FILE__ ) . 'assets/css/extensions-ui.css', array( 'extension-ui-fonts' ), $pluginData['version'], 'all' );
            wp_enqueue_script( 'extensions-ui', plugin_dir_url( __FILE__ ) . 'assets/js/extensions-ui.js', array( 'jquery' ), $pluginData['version'], true );
        }

    }

    /**
     * Promote bundles HTML
     * 
     * @since 2.5.9.3
     * @version 1.0.1
     */
    public function promote_bundles_html() {

        ?>
        <div style="color:#000;background: #fed90f;display: inline-block;padding: 23px;border-radius: 14px;font-size: 16px;font-weight: 400;box-shadow: 5px 5px 8px #c7c7c7; padding-bottom:10px; display: flex; width: 84%;" >
            <div style="width: 75%;">
                <div>
                    <a style="text-decoration:none; color:#231F20;" href="<?php echo esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=banner&utm_campaign=plugin' ); ?>">🎉 UNLOCK THE FULL POTENTIAL OF POST SMTP WITH PRO FEATURES</a>
                </div>                
                <div style="margin-top:8px">
                    <a style="font-size:10px; color:#0019ff;" href="<?php echo admin_url( 'admin.php?action=ps_skip_pro_banner' ); ?>">Not interested, Hide for now.</a>
                </div>
            </div>
            <div style="margin: 11px 0;">
                <a style="text-decoration:none; color:#231F20; font-size: 12px; display: block;" href="<?php echo esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=banner&utm_campaign=plugin' ); ?>"><span style="background: #000;color: #fff;text-decoration: none;padding: 10px;border-radius: 10px;">👉 <?php printf( '%s', esc_html__( 'LEARN MORE', 'post-smtp' ) ); ?></span> </a>
            </div>
        </div>
        <?php

    }

    /**
     * Promote bundles Freemius
     * 
     * @since 2.5.9.3
     * @version 1.0
     */
    public function promote_bundles_fs() {

        ?>
        <div style="clear: both;"></div>
        <div style="margin-left: 29px;" >
            <?php $this->promote_bundles_html(); ?> 
        </div>
        <?php

    }

    /**
     * Promote bundles Dashboard
     * 
     * @since 2.5.9.3
     * @version 1.0.1
     */
    public function promote_bundles_dashboard() {

    ?>
        <div style="margin-top: 10px; float: left;">
            <?php $this->promote_bundles_html(); ?>
        </div>
        <div style="clear: both;"></div>
        
    <?php

    }

    /**
     * Change Freemius Submenu Text
     * 
     * @since 2.5.9.3
     * @version 1.0
     */
    public function change_fs_submenu_text( $translated_text, $text, $domain ) {

        if( $text == 'Upgrade' && $domain == 'freemius' ) {

            return sprintf( 
                '👉 %s <b>%s</b>', 
                esc_html__( 'Get', 'post-smtp' ), 
                esc_html__( 'Pro Bundle', 'post-smtp' ) 
            );

        }

        return $translated_text;

    }

    /**
     * Skip Pro banner
     * 
     * @since 2.6.0
     * @version 1.0.0
     */
    public function skip_pro_banner() {

        if( isset( $_GET['action'] ) && $_GET['action'] == 'ps_skip_pro_banner' ) {

            set_transient( 'post_smtp_skip_banner', 23668200 );

            wp_redirect( admin_url( 'admin.php?page=postman' ) );

        }

    }

    /**
     * Add menu
     * 
     * @since 2.8.6
     * @version 1.0.0
     */
    public function add_menu() {

        if( postman_is_bfcm() ) {

            $menu_text = sprintf( 
                '<span class="dashicons dashicons-superhero ps-pro-icon"></span>%1$s<span class="menu-counter"><b>%2$s</b></span>', 
                __( 'Extensions', 'post-smtp' ),
                '24%OFF'
            );

        }
        else {

            $menu_text = sprintf( '<span class="dashicons dashicons-superhero ps-pro-icon"></span> %1$s', __( 'Extensions', 'post-smtp' ) );

        }
        
        add_submenu_page(
            PostmanViewController::POSTMAN_MENU_SLUG,
            __( 'Extensions', 'post-smtp' ),
            $menu_text,
            'manage_options',
            'extensions',
            array( $this, 'extensions' ),
            99
        );
        
    }

    public function extensions() {
	    $images_url       = plugin_dir_url( __FILE__ ) . 'assets/images/';
	    $sockets          = array(
		    array(
			    'logo'        => $images_url . 'logos/office365.png',
			    'title'       => esc_html__( 'Office / Microsoft 365', 'post-smtp' ),
			    'description' => esc_html__( 'Integrate your WordPress site with your Office 365 / Microsoft 365 account to improve email deliverability.', 'post-smtp' ),
		    ),
		    array(
			    'logo'        => $images_url . 'logos/amazonses.png',
			    'title'       => esc_html__( 'Amazon SES', 'post-smtp' ),
			    'description' => esc_html__( 'Integrate your WordPress site with your Amazon SES account to improve email deliverability.', 'post-smtp' ),
		    ),
		    array(
			    'logo'        => $images_url . 'logos/zoho.png',
			    'title'       => esc_html__( 'Zoho Mail', 'post-smtp' ),
			    'description' => esc_html__( 'Integrate your WordPress site with your Zoho Mail account to improve email deliverability.', 'post-smtp' ),
		    ),
	    );
        $bonus            = array(
            array(
                'logo' => $images_url . 'logos/email-log-attachments.png',
                'title' => esc_html__( 'Email Log Attachments', 'post-smtp' ),
                'description' => esc_html__( 'View and resend any email attachment right from you email log screen to streamline email communication.', 'post-smtp' ),
            ),
            array(
                'logo' => $images_url . 'logos/email-delivery-log.png',
                'title' => esc_html__( 'Email Delivery and Logs', 'post-smtp' ),
                'description' => esc_html__( 'Send emails from the back-end, manage your email quota, retry failed emails, and delete log history to optimize email delivery.', 'post-smtp' ),
            ),
            array(
                'logo' => $images_url . 'logos/report-tracking.png',
                'title' => esc_html__( 'Reporting and Tracking', 'post-smtp' ),
                'description' => esc_html__( 'Monitor email delivery status with daily, weekly, and monthly reports and track opened emails to analyze email performance.', 'post-smtp' ),
            ),
            array(
                'logo' => $images_url . 'logos/twilio-sms-notification.png',
                'title' => esc_html__( 'Twilio SMS Notification', 'post-smtp' ),
                'description' => esc_html__( 'Configure and receive all your WordPress email failure alerts through SMS by connecting your Twilio account.', 'post-smtp' ),
            ),
        );
        $features         = array(
            esc_attr__( 'Office365, Amazon SES, and Zoho SMTP.', 'post-smtp' ),
	        esc_attr__( 'Resend failed emails in bulk.', 'post-smtp' ),
	        esc_attr__( 'Auto-resend failed emails.', 'post-smtp' ),
	        esc_attr__( 'Open email tracking.', 'post-smtp' ),
	        esc_attr__( 'Advance email report and tracking.', 'post-smtp' ),
	        esc_attr__( 'Post SMTP Mobile App PRO.', 'post-smtp' ),
        );

        ob_start();
        ?>
        
        <div class="wrap ">
            <div class="post-smtp-container bb-1">
                <img src="<?php echo esc_attr( POST_SMTP_ASSETS ) . 'images/reporting/post_logo.png'; ?>" alt="Post SMTP Logo" />
            </div>
            
            <div class="post-smtp-container post-smtp-clearfix">
                
                <div>
                    <div class="post-smtp-heading">
                        <h2 class="post-smtp-h2">
                            <?php esc_html_e( 'Socket Extensions', 'post-smtp' ); ?>
                            <span>PRO</span>
                        </h2>
                        
                        <p class="post-smtp-p"><?php esc_html_e( 'Activate and configure the advance mailers you would like to use to send emails from this site.', 'post-smtp' ); ?></p>
                    </div>
                    
                    <button class="post-smtp-open-popup post-smtp-disabled button button-secondary button-disabled" style="color: #375CAF !important;cursor: not-allowed !important;font-size: 14px;">
                        <img src="<?php echo esc_attr( $images_url ); ?>magic-wand.png" alt="magic want" style="margin-bottom: -6px;margin-right: 5px;" />
                        <?php esc_html_e( 'Launch Setup Wizard', 'post-smtp' ); ?>
                    </button>
                </div>
                
                <div class="post-smtp-clearfix" style="margin-top: 25px;">

                    <?php foreach ( $sockets as $socket ) : ?>
                        <div class="post-smtp-socket-wrapper post-smtp-fl post-smtp-disabled">

                            <div class="post-smtp-p-20">
                                <img src="<?php echo esc_attr( $socket['logo'] ); ?>" alt="<?php echo esc_attr( $socket['title'] ); ?>" />

                                <h2 class="post-smtp-h2">
	                                <?php echo esc_attr( $socket['title'] ); ?>
                                </h2>

                                <p class="post-smtp-p">
                                    <?php echo esc_attr( $socket['description'] ); ?>
                                </p>

                            </div>
                            
                            <div class="post-smtp-socket-footer post-smtp-bt-1">
                                <div class="post-smtp-p-20 post-smtp-clearfix">

                                    <div class="post-smtp-fl post-smtp-deactivated">
                                        <?php esc_html_e( 'Deactivated' ); ?>
                                    </div>
                                    <div class="post-smtp-fr">
                                    
                                        <div class="post-smtp-toggle"></div>
                                    
                                    </div>
                                    
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>
                
                <div style="margin-top: 50px;">

                    <div class="post-smtp-heading">
                        <h2 class="post-smtp-h2">
			                <?php esc_html_e( 'Bonus Extensions', 'post-smtp' ); ?>
                            <span>PRO</span>
                        </h2>

                        <p class="post-smtp-p"><?php esc_html_e( 'These bonus extensions gives you an edge that enhances your WordPress email management and performance reporting.', 'post-smtp' ); ?></p>
                    </div>

                </div>
                
                <div class="post-smtp-clearfix" style="margin-top: 25px;">

                    <?php foreach ( $bonus as $socket ) : ?>
                        <div class="post-smtp-socket-wrapper post-smtp-fl post-smtp-disabled">

                            <div class="post-smtp-p-20">
                                <img src="<?php echo esc_attr( $socket['logo'] ); ?>" alt="<?php echo esc_attr( $socket['title'] ); ?>" />

                                <h2 class="post-smtp-h2">
                                    <?php echo esc_attr( $socket['title'] ); ?>
                                </h2>

                                <p class="post-smtp-p">
                                    <?php echo esc_attr( $socket['description'] ); ?>
                                </p>

                            </div>
                            
                            <div class="post-smtp-socket-footer post-smtp-bt-1">
                                <div class="post-smtp-p-20 post-smtp-clearfix">

                                    <div class="post-smtp-fl post-smtp-deactivated">
                                        <?php esc_html_e( 'Deactivated' ); ?>
                                    </div>
                                    <div class="post-smtp-fr">
                                    
                                        <div class="post-smtp-toggle"></div>
                                    
                                    </div>
                                    
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="post-smtp-fr" style="margin-top: 25px;">
                    <a href="<?php echo esc_attr( add_query_arg( array( 'page' => 'postman' ), admin_url( 'admin.php' ) ) ); ?>" style="background: #375CAF;font-family: Inter;font-size: 14px;font-weight: 400;line-height: 16px;text-align: left;padding: 10px;" class="button button-primary">
                        <img style="margin-bottom: -7px;" src="<?php echo esc_attr( $images_url ); ?>go-back.png" alt="Go back">
                        <?php esc_html_e( 'Back to dashboard', 'post-smtp' ); ?>
                    </a>
                </div>
            </div>
            
        </div>
        
        
        <div class="post-smtp-popup-wrapper">
            <div class="post-smtp-popup">
                
                <span class="post-smtp-close-button">&times;</span>
                
                <div class="post-smtp-logo post-smtp-container">
                    <img src="<?php echo esc_attr( POST_SMTP_ASSETS ) . 'images/reporting/post_logo.png'; ?>" alt="Post SMTP Logo" />
                </div>
                
                <div class="post-smtp-container" style="padding-top:0;padding-bottom: 0;">
                    
                    <h2 class="post-smtp-h2">
                        <?php esc_html_e( 'Unlock Pro SMTP Mailers & More Advanced Features!', 'post-smtp' ); ?>
                    </h2>
                    
                    <ul class="post-smtp-unorderlist">
                        <?php foreach ( $features as $feature ) : ?>
                            <li>
                                <img style="margin-bottom: -4px;" src="<?php echo esc_attr( $images_url ); ?>check.png" alt="Check" />
                                <?php echo esc_html( $feature ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                </div>
                
                <div class="post-smtp-text-center" style="margin-top: 25px;">
                    <a href="https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=extension_screen_pop_up&utm_campaign=plugin" class="post-smtp-cta">
                        <img style="margin: 0 4px -6px 0;" alt="diamond" src="<?php echo esc_attr( $images_url ); ?>diamond.png" />
                        <?php esc_html_e( 'Get Post SMTP Pro', 'post-smtp' ); ?>
                        <img style="margin: 0 0 -4px 4px;" alt="arrow" src="<?php echo esc_attr( $images_url ); ?>arrow.png" />
                    </a>
                </div>
            </div>
        </div>
        
        <?php
        echo ob_get_clean();
    }

}

new PostmanSuggestProSocket();

endif;