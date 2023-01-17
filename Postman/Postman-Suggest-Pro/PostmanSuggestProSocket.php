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
    
    /**
     * class constructor PostmanSuggestProSocket
     * 
     * @since 2.2
     * @version 1.0
     */
    public function __construct() {

        $this->pro_extenstions();

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
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

        wp_register_script( 'postman-suggest-pro-sockets', POST_SMTP_ASSETS . 'js/postman-admin.js', array( 'jquery' ), $pluginData, true );

        wp_enqueue_script( 'postman-suggest-pro-sockets' );

        wp_localize_script( 
            'postman-suggest-pro-sockets', 
            'postmanPro', 
            $this->data
        );

    }

}

new PostmanSuggestProSocket();

endif;