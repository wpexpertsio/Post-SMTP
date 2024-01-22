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
        
        
        if( post_smtp_check_extensions() ) {

            add_action( 'admin_menu', array( $this, 'add_menu' ), 22 );
        
        }
        if( post_smtp_check_extensions() && !$hide_notice ){

            add_action( 'post_smtp_dashboard_after_config', array( $this, 'promote_bundles_dashboard' ) );
        
        }
        
        add_filter( 'gettext', array( $this, 'change_fs_submenu_text' ), 10, 3 );
        add_action( 'admin_action_ps_skip_pro_banner', array( $this, 'skip_pro_banner' ) );
        add_action( 'init', array( $this, 'init' ) );
        
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
    public function admin_enqueue_scripts() {

        $pluginData = apply_filters( 'postman_get_plugin_metadata', null );

        wp_register_script( 'postman-suggest-pro-sockets', POST_SMTP_ASSETS . 'js/postman-admin.js', array( 'jquery' ), $pluginData['version'], true );

        wp_enqueue_script( 'postman-suggest-pro-sockets' );

        wp_localize_script( 
            'postman-suggest-pro-sockets', 
            'postmanPro', 
            $this->data
        );

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
                    <a style="text-decoration:none; color:#231F20;" href="<?php echo esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=banner' ); ?>">🎉 UNLOCK THE FULL POTENTIAL OF POST SMTP WITH PRO FEATURES</a>
                </div>                
                <div style="margin-top:8px">
                    <a style="font-size:10px; color:#0019ff;" href="<?php echo admin_url( 'admin.php?action=ps_skip_pro_banner' ); ?>">Not interested, Hide for now.</a>
                </div>
            </div>
            <div style="margin: 11px 0;">
                <a style="text-decoration:none; color:#231F20; font-size: 12px; display: block;" href="<?php echo esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=banner' ); ?>"><span style="background: #000;color: #fff;text-decoration: none;padding: 10px;border-radius: 10px;">👉 <?php printf( '%s', esc_html( 'LEARN MORE', 'post-smtp' ) ); ?></span> </a>
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
                esc_html( 'Get', 'post-smtp' ), 
                esc_html( 'Pro Bundle', 'post-smtp' ) 
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
     * Redirect
     * 
     * @since 2.6.3
     * @version 1.0.0
     */
    public function init() {
        
        if ( isset( $_GET['page'] ) && 'postman-pricing' === $_GET['page'] ) {

            wp_redirect( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=submenu' );
            exit;

        }
        
    }

    /**
     * Add menu
     * 
     * @since 2.8.6
     * @version 1.0.0
     */
    public function add_menu() {
        
        add_submenu_page( 
            PostmanViewController::POSTMAN_MENU_SLUG, 
            __( '👉 Get Pro Bundle', 'post-smtp' ), 
            sprintf( '👉 %1$s <b>%2$s</b>&nbsp;&nbsp;➤', __( 'Get', 'post-smtp' ), __( 'Pro Bundle', 'post-smtp' ) ),
            'manage_options', 
            esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=submenu' ),
            '',
            99
        );
        
    }

}

new PostmanSuggestProSocket();

endif;