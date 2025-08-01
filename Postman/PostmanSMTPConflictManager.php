<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class PostmanSMTPConflictManager
 * 
 * Manages detection and notification of conflicting SMTP plugins
 */
class PostmanSMTPConflictManager {

    // Option name to store dismissed notices
    const DISMISSED_NOTICES_OPTION = 'postman_dismissed_smtp_conflicts';
    
    private $logger;
    private $conflicting_plugins = array();

    /**
     * Constructor
     */
    public function __construct() {
        // Include the plugin functions if not already available
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        
        $this->logger = new PostmanLogger( get_class( $this ) );
        
        // Hook into admin_notices to display warnings
        add_action( 'admin_notices', array( $this, 'display_smtp_conflict_notices' ) );
        
        // Hook into AJAX for dismissing notices
        add_action( 'wp_ajax_dismiss_smtp_conflict_notice', array( $this, 'dismiss_notice_ajax' ) );
        
    }

    /**
     * Get list of known SMTP plugins that could conflict with Post SMTP
     * 
     * @return array
     */
    private function get_smtp_plugins_list() {
        return array(
            /**
             * Url: https://wordpress.org/plugins/easy-wp-smtp/
             */
            array(
                'name'  => 'Easy WP SMTP',
                'slug'  => 'easy-wp-smtp/easy-wp-smtp.php',
                'class' => 'EasyWPSMTP',
            ),

            /**
             * Closed.
             *
             * Url: https://wordpress.org/plugins/postman-smtp/
             */
            array(
                'name'     => 'Postman SMTP',
                'slug'     => 'postman-smtp/postman-smtp.php',
                'function' => 'postman_start',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-mail-bank/
             */
            array(
                'name'     => 'Mail Bank',
                'slug'     => 'wp-mail-bank/wp-mail-bank.php',
                'function' => 'mail_bank',
            ),

            /**
             * Url: https://wordpress.org/plugins/smtp-mailer/
             */
            array(
                'name'  => 'SMTP Mailer',
                'slug'  => 'smtp-mailer/main.php',
                'class' => 'SMTP_MAILER',
            ),

            /**
             * Url: https://wordpress.org/plugins/gmail-smtp/
             */
            array(
                'name'  => 'Gmail SMTP',
                'slug'  => 'gmail-smtp/main.php',
                'class' => 'GMAIL_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-email-smtp/
             */
            array(
                'name'  => 'WP Email SMTP',
                'class' => 'WP_Email_Smtp',
            ),

            /**
             * Url: https://wordpress.org/plugins/smtp-mail/
             */
            array(
                'name'     => 'SMTP Mail',
                'slug'     => 'smtp-mail/index.php',
                'function' => 'smtpmail_include',
            ),

            /**
             * Url: https://wordpress.org/plugins/bws-smtp/
             */
            array(
                'name'     => 'SMTP by BestWebSoft',
                'slug'     => 'bws-smtp/bws-smtp.php',
                'function' => 'bwssmtp_init',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-sendgrid-smtp/
             */
            array(
                'name'  => 'WP SendGrid SMTP',
                'slug'  => 'wp-sendgrid-smtp/wp-sendgrid-smtp.php',
                'class' => 'WPSendGrid_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/sar-friendly-smtp/
             */
            array(
                'name'     => 'SAR Friendly SMTP',
                'slug'     => 'sar-friendly-smtp/sar-friendly-smtp.php',
                'function' => 'sar_friendly_smtp',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-gmail-smtp/
             */
            array(
                'name'  => 'WP Gmail SMTP',
                'slug'  => 'wp-gmail-smtp/wp-gmail-smtp.php',
                'class' => 'WPGmail_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/cimy-swift-smtp/
             */
            array(
                'name'     => 'Cimy Swift SMTP',
                'slug'     => 'cimy-swift-smtp/cimy_swift_smtp.php',
                'function' => 'st_smtp_check_config',
            ),

            /**
             * Closed.
             *
             * Url: https://wordpress.org/plugins/wp-easy-smtp/
             */
            array(
                'name'  => 'WP Easy SMTP',
                'slug'  => 'wp-easy-smtp/wp-easy-smtp.php',
                'class' => 'WP_Easy_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-mailgun-smtp/
             */
            array(
                'name'  => 'WP Mailgun SMTP',
                'slug'  => 'wp-mailgun-smtp/wp-mailgun-smtp.php',
                'class' => 'WPMailgun_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/my-smtp-wp/
             */
            array(
                'name'     => 'MY SMTP WP',
                'slug'     => 'my-smtp-wp/my-smtp-wp.php',
                'function' => 'my_smtp_wp',
            ),

            /**
             * Closed.
             *
             * Url: https://wordpress.org/plugins/wp-mail-booster/
             */
            array(
                'name'     => 'WP Mail Booster',
                'slug'     => 'wp-mail-booster/wp-mail-booster.php',
                'function' => 'mail_booster',
            ),

            /**
             * Url: https://wordpress.org/plugins/sendgrid-email-delivery-simplified/
             */
            array(
                'name'  => 'SendGrid',
                'slug'  => 'sendgrid-email-delivery-simplified/wpsendgrid.php',
                'class' => 'Sendgrid_Settings',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-mail-smtp/
             */
            array(
                'name'     => 'WP Mail Smtp',
                'slug'     => 'wp-mail-smtp/wp_mail_smtp.php',
                'function' => 'WPMS_php_mailer',
            ),

            /**
             * Closed.
             *
             * Url: https://wordpress.org/plugins/wp-amazon-ses-smtp/
             */
            array(
                'name'  => 'WP Amazon SES SMTP',
                'slug'  => 'wp-amazon-ses-smtp/wp-amazon-ses.php',
                'class' => 'WPAmazonSES_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/postmark-approved-wordpress-plugin/
             */
            array(
                'name'  => 'Postmark (Official)',
                'slug'  => 'postmark-approved-wordpress-plugin/postmark.php',
                'class' => 'Postmark_Mail',
            ),

            /**
             * Url: https://wordpress.org/plugins/mailgun/
             */
            array(
                'name'  => 'Mailgun',
                'slug'  => 'mailgun/mailgun.php',
                'class' => 'Mailgun',
            ),

            /**
             * Url: https://wordpress.org/plugins/sparkpost/
             */
            array(
                'name'  => 'SparkPost',
                'slug'  => 'sparkpost/wordpress-sparkpost.php',
                'class' => 'WPSparkPost\SparkPost',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-yahoo-smtp/
             */
            array(
                'name'  => 'WP Yahoo SMTP',
                'slug'  => 'wp-yahoo-smtp/wp-yahoo-smtp.php',
                'class' => 'WPYahoo_SMTP',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-ses/
             */
            array(
                'name'     => 'WP Offload SES Lite',
                'slug'     => 'wp-ses/wp-ses.php',
                'function' => 'wp_offload_ses_lite_init',
            ),

            /**
             * Url: https://deliciousbrains.com/wp-offload-ses/
             */
            array(
                'name' => 'WP Offload SES',
                'slug' => 'wp-offload-ses/wp-offload-ses.php',
            ),

            /**
             * Url: https://wordpress.org/plugins/turbosmtp/
             */
            array(
                'name'     => 'turboSMTP',
                'slug'     => 'turbosmtp/turbosmtp.php',
                'function' => 'TSPHPMailer',
            ),

            /**
             * Url: https://wordpress.org/plugins/wp-smtp/
             */
            array(
                'name'  => 'Solid Mail',
                'slug'  => 'wp-smtp/wp-smtp.php',
                'class' => 'WP_SMTP',
            ),

            /**
             * This plugin can be used along with our plugin if disable next option
             * WooCommerce -> Settings -> Sendinblue -> Email Options -> Enable Sendinblue to send WooCommerce emails.
             *
             * Url: https://wordpress.org/plugins/woocommerce-sendinblue-newsletter-subscription
             */
            array(
                'name'    => 'Sendinblue - WooCommerce Email Marketing',
                'slug'    => 'woocommerce-sendinblue-newsletter-subscription/woocommerce-sendinblue.php',
                'class'   => 'WC_Sendinblue_Integration',
                'test'    => 'test_wc_sendinblue_integration',
                'message' => esc_html__( 'Or disable the Sendinblue email sending setting in WooCommerce > Settings > Sendinblue (tab) > Email Options (tab) > Enable Sendinblue to send WooCommerce emails.', 'post-smtp' ),
            ),

            /**
             * Url: https://wordpress.org/plugins/disable-emails/
             */
            array(
                'name'  => 'Disable Emails',
                'slug'  => 'disable-emails/disable-emails.php',
                'class' => '\webaware\disable_emails\Plugin',
            ),

            /**
             * Url: https://wordpress.org/plugins/fluent-smtp/
             */
            array(
                'name'     => 'FluentSMTP',
                'slug'     => 'fluent-smtp/fluent-smtp.php',
                'function' => 'fluentSmtpInit',
            ),

            /**
             * This plugin can be used along with our plugin if enable next option
             * Settings > Email template > Sender (tab) -> Do not change email sender by default.
             *
             * Url: https://wordpress.org/plugins/wp-html-mail/
             */
            array(
                'name'     => 'WP HTML Mail - Email Template Designer',
                'function' => 'Haet_Mail',
                'test'     => 'test_wp_html_mail_integration',
                'message'  => esc_html__( 'Or enable "Do not change email sender by default" setting in Settings > Email template > Sender (tab).', 'post-smtp' ),
            ),

            /**
             * This plugin can be used along with our plugin if "SMTP" module is deactivated.
             *
             * Url: https://wordpress.org/plugins/branda-white-labeling/
             */
            array(
                'name'     => 'Branda',
                'slug'     => 'branda-white-labeling/ultimate-branding.php',
                'function' => 'set_ultimate_branding',
                'test'     => 'test_branda_integration',
                'message'  => esc_html__( 'Or deactivate "SMTP" module in Branda > Emails > SMTP.', 'post-smtp' ),
            ),

            /**
             * Url: https://wordpress.org/plugins/zoho-mail/
             */
            array(
                'name'     => 'Zoho Mail for WordPress',
                'slug'     => 'zoho-mail/zohoMail.php',
                'function' => 'zmail_send_mail_callback',
            ),

            /**
             * Url: https://elementor.com/products/site-mailer/
             */
            array(
                'name'  => 'Site Mailer - SMTP Replacement, Email API Deliverability & Email Log',
                'slug'  => 'site-mailer/site-mailer.php',
                'class' => 'SiteMailer',
            ),

            /**
             * Url: https://wordpress.org/plugins/suremails/
             */
            array(
                'name'  => 'SureMail',
                'slug'  => 'suremails/suremails.php',
                'class' => 'MailHandler',
            ),

            /**
             * Url: https://www.gravityforms.com/gravity-smtp/
             */
            array(
                'name'  => 'Gravity SMTP',
                'slug'  => 'gravitysmtp/gravitysmtp.php',
                'class' => 'Gravity_SMTP',
            ),
			
			 /**
             * Url: https://wordpress.org/plugins/pro-mail-smtp/
             */
            array(
                'name'  => 'Pro Mail SMTP',
                'slug'  => 'pro-mail-smtp/pro-mail-smtp.php',
                'function' => 'pro-mail-smtp',
            ),
        );
    }

    /**
     * Check if a plugin is active and conflicts with Post SMTP
     * 
     * @param array $plugin Plugin configuration array
     * @return bool
     */
    private function is_plugin_conflicting( $plugin ) {
        // Skip our own plugin
        if ( isset( $plugin['slug'] ) && $plugin['slug'] === 'post-smtp/postman-smtp.php' ) {
            return false;
        }

        // Check if plugin is active by slug
        if ( isset( $plugin['slug'] ) && is_plugin_active( $plugin['slug'] ) ) {
            return true;
        }

        // Check if plugin class exists
        if ( isset( $plugin['class'] ) && class_exists( $plugin['class'] ) ) {
            return true;
        }

        // Check if plugin function exists
        if ( isset( $plugin['function'] ) && function_exists( $plugin['function'] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Get all currently conflicting plugins
     * 
     * @return array
     */
    private function get_conflicting_plugins() {
        if ( ! empty( $this->conflicting_plugins ) ) {
            return $this->conflicting_plugins;
        }

        $smtp_plugins = $this->get_smtp_plugins_list();
        $conflicting = array();

        foreach ( $smtp_plugins as $plugin ) {
            if ( $this->is_plugin_conflicting( $plugin ) ) {
                $conflicting[] = $plugin;
                
                // Log the conflict detection
                if ( $this->logger->isDebug() ) {
                    $this->logger->debug( sprintf( 
                        'SMTP plugin conflict detected: %s (%s)', 
                        $plugin['name'], 
                        isset( $plugin['slug'] ) ? $plugin['slug'] : 'no slug'
                    ) );
                }
            }
        }

        $this->conflicting_plugins = $conflicting;
        
        // Log summary if conflicts found
        if ( ! empty( $conflicting ) && $this->logger->isInfo() ) {
            $this->logger->info( sprintf( 
                'Found %d conflicting SMTP plugin(s): %s', 
                count( $conflicting ),
                implode( ', ', array_column( $conflicting, 'name' ) )
            ) );
        }
        
        return $conflicting;
    }

    /**
     * Display SMTP conflict admin notices
     */
    public function display_smtp_conflict_notices() {
        // Allow disabling conflict detection via filter
        if ( ! apply_filters( 'post_smtp_enable_conflict_detection', true ) ) {
            return;
        }

        // Only show in admin area and to users who can manage options
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $conflicting_plugins = $this->get_conflicting_plugins();
        $dismissed_notices = get_option( self::DISMISSED_NOTICES_OPTION, array() );

        foreach ( $conflicting_plugins as $plugin ) {
            $notice_id = sanitize_key( $plugin['name'] );
            $this->display_conflict_notice( $plugin, $notice_id );
        }
    }

    /**
     * Display individual conflict notice
     * 
     * @param array $plugin Plugin configuration
     * @param string $notice_id Unique notice ID
     */
    private function display_conflict_notice( $plugin, $notice_id ) {
		 
	//echo "<pre>"; ar_dump($plugin);

        $plugin_name = esc_html( $plugin['name'] );
        $custom_message = isset( $plugin['message'] ) ? $plugin['message'] : '';
             
        echo '<div class="notice notice-warning is-dismissible postman-smtp-conflict-notice" data-notice-id="' . esc_attr( $notice_id ) . '">';
        echo '<h3>' . esc_html__( 'Post SMTP - Plugin Conflict Detected', 'post-smtp' ) . '</h3>';
        echo '<p>';
        printf( 
            /* translators: %s: conflicting plugin name */
            esc_html__( 'You have %s activated alongside Post SMTP. This may cause email delivery issues or conflicts. We recommend deactivating one of these plugins to ensure reliable email delivery.', 'post-smtp' ),
            '<strong>' . $plugin_name . '</strong>'
        );
        echo '</p>';
        
        if ( ! empty( $custom_message ) ) {
            echo '<p>' . wp_kses_post( $custom_message ) . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Handle AJAX request to dismiss notice
     */
    public function dismiss_notice_ajax() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'postman_smtp_conflict_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check user capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }

        // Validate notice ID
        if ( ! isset( $_POST['notice_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Notice ID is required' ) );
        }

        $notice_id = sanitize_key( $_POST['notice_id'] );
        if ( empty( $notice_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid notice ID' ) );
        }

        $dismissed_notices = get_option( self::DISMISSED_NOTICES_OPTION, array() );
        
        if ( ! in_array( $notice_id, $dismissed_notices ) ) {
            $dismissed_notices[] = $notice_id;
            if ( update_option( self::DISMISSED_NOTICES_OPTION, $dismissed_notices ) ) {
                wp_send_json_success( array( 'message' => 'Notice dismissed successfully' ) );
            } else {
                wp_send_json_error( array( 'message' => 'Failed to save dismissal' ) );
            }
        } else {
            wp_send_json_success( array( 'message' => 'Notice already dismissed' ) );
        }
    }


    /**
     * Get count of conflicting plugins
     * 
     * @return int
     */
    public function get_conflicts_count() {
        return count( $this->get_conflicting_plugins() );
    }

    /**
     * Check if there are any conflicts
     * 
     * @return bool
     */
    public function has_conflicts() {
        return $this->get_conflicts_count() > 0;
    }

    /**
     * Reset dismissed notices (for testing or admin purposes)
     */
    public function reset_dismissed_notices() {
        delete_option( self::DISMISSED_NOTICES_OPTION );
    }

    /**
     * Get detailed information about all SMTP plugins (for debugging/admin purposes)
     * 
     * @return array
     */
    public function get_smtp_plugins_status() {
        $smtp_plugins = $this->get_smtp_plugins_list();
        $status = array();

        foreach ( $smtp_plugins as $plugin ) {
            $plugin_status = array(
                'name'        => $plugin['name'],
                'slug'        => isset( $plugin['slug'] ) ? $plugin['slug'] : '',
                'is_active'   => false,
                'conflict'    => false,
                'detection'   => array()
            );

            // Check plugin activation status
            if ( isset( $plugin['slug'] ) && is_plugin_active( $plugin['slug'] ) ) {
                $plugin_status['is_active'] = true;
                $plugin_status['detection'][] = 'Active plugin';
            }

            // Check class existence
            if ( isset( $plugin['class'] ) && class_exists( $plugin['class'] ) ) {
                $plugin_status['detection'][] = 'Class exists: ' . $plugin['class'];
            }

            // Check function existence
            if ( isset( $plugin['function'] ) && function_exists( $plugin['function'] ) ) {
                $plugin_status['detection'][] = 'Function exists: ' . $plugin['function'];
            }

            // Determine if it's conflicting
            $plugin_status['conflict'] = $this->is_plugin_conflicting( $plugin );

            $status[] = $plugin_status;
        }

        return $status;
    }
}
