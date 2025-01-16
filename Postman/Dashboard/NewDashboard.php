<?php
/**
 * New Dashboard
 */

if ( ! class_exists( 'Post_SMTP_New_Dashboard' ) ) {
    class Post_SMTP_New_Dashboard {
        public function __construct() {
			$this->include();

            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_filter( 'post_smtp__new_dashboard', '__return_true' );
            add_action( 'post_smtp__new_dashboard_content', array( $this, 'dashboard_content' ) );

            if ( 
                is_plugin_active( 'report-and-tracking-addon-premium/post-smtp-report-and-tracking.php' ) 
                || 
                is_plugin_active( 'post-smtp-pro/post-smtp-pro.php' ) 
            ) {
                add_filter( 'post_smtp_dashboard_opened_emails_count', array( $this, 'opened_email_count' ), 10, 2 );
            }

        }

		private function include() {
			require_once POST_SMTP_PATH . '/Postman/Dashboard/includes/rest-api/v1/class-psd-rest-api.php';
		}
        
        public function admin_enqueue_scripts( $hook ) {
			if ( 'toplevel_page_postman' === $hook ) {
				wp_enqueue_script( 'post-smtp-dashboard', POST_SMTP_URL . '/Postman/Dashboard/assets/js/app.js', array( 'wp-i18n' ), POST_SMTP_VER, true );
				wp_localize_script(
					'post-smtp-dashboard',
					'postSmtpNewDashboard',
					array(
						'plugin_dir_url' => plugin_dir_url( __FILE__ ),
						'json_url'       => rest_url( 'psd/v1' ),
						'nonce'          => wp_create_nonce( 'wp_rest' ),
						'admin_url'      => admin_url( 'admin.php' ),
						'page_hook'      => $hook,
						'is_bfcm'        => postman_is_bfcm()
					)
				);

				wp_enqueue_style( 'post-smtp-dashboard', POST_SMTP_URL . '/Postman/Dashboard/assets/css/app.css', array(), POST_SMTP_VER, 'all' );
				wp_enqueue_style( 'post-smtp-dashboard-responsive', POST_SMTP_URL . '/Postman/Dashboard/assets/css/responsive-style.css', array(), POST_SMTP_VER, 'all' );
			}
        }
        
        public function dashboard_content() {
            $transport          = PostmanTransportRegistry::getInstance()->getActiveTransport();
            $app_connected      = get_option( 'post_smtp_mobile_app_connection' );
	        $main_wp_configured = get_option( 'post_smtp_use_from_main_site' );
            $configured         = $transport->isConfiguredAndReady() ? 'true' : 'false';
            $app_connected      = empty( $app_connected ) ? 'false' : 'true';
	        $main_wp_configured = empty( $main_wp_configured ) ? 'false' : 'true';
			$has_post_smtp_pro  = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
			$has_post_smtp_pro  = in_array( 'post-smtp-pro/post-smtp-pro.php', $has_post_smtp_pro, true )
				? 'true' : 'false';

	        $ad_position = get_option('postman_dashboard_ad', 'maximize' );
            echo '<div id="post-smtp-app">
                <post-smtp-app-wrapper
                    :post-smtp-configured="' . esc_attr( $configured ) . '"
                    :post-smtp-pro="' . esc_attr( $has_post_smtp_pro ) . '"
                    :is-mobile-app-configured="' . esc_attr( $app_connected ) . '"
                    :is-main-wp-configured="' . esc_attr( $main_wp_configured ) . '"
                    :is-domain-spam-score-configured="false"
                    
                    ad-position="' . esc_attr( $ad_position ) . '"
                    
                    @click="closePopup"
                ></post-smtp-app-wrapper>
            </div>';
        }

        public function opened_email_count( $count, $args ) {
            $current_time = $args['current_time'];
            $filter       = $args['filter'];

            global $wpdb;
            $sql = 'SELECT COUNT( * ) FROM %i WHERE event_type = "open-email" AND time <= %d AND time >= %d';
            $sql = $wpdb->prepare( $sql, $wpdb->prefix . 'post_smtp_tracking', $current_time, $filter );

            return $wpdb->get_var( $sql );
        }
    }
    
    new Post_SMTP_New_Dashboard();
}