<?php

if( !class_exists( 'PostmanEmailLogsMigration' ) ):
class PostmanEmailLogsMigration {
    
    private $new_logging = false;
    private $migrating = false;

    /**
     *  Constructor PostmanEmailLogsMigration
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function __construct() {

        $this->new_logging = get_option( 'postman_db_version' );
        $this->migrating = get_option( 'ps_migrate_logs' );

        //Show DB Update Notice
        if( $this->have_old_logs() && !$this->migrating ) {

            add_action( 'admin_notices', array( $this, 'notice' ) );
            add_action( 'wp_ajax_ps-migrate-logs', array( $this, 'update_database' ) );

        }

        add_filter( 'cron_schedules', array( $this, 'ps_fifteen_minutes' ) );
        
        //Add Hook of Migration if Migrating
        if( $this->migrating ) {

            add_action( 'ps_migrate_logs', array( $this, 'migrate_logs' ) );

        }
        
    }


    /**
     * Checks if have logs in old system
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function have_old_logs() {

        $recent_posts = wp_get_recent_posts( array(
			'numberposts' 	=> 	1,
			'post_type'		=>	PostmanEmailLogPostType::POSTMAN_CUSTOM_POST_TYPE_SLUG
		) );

        if( !empty( $recent_posts ) ) {

            return true;
            
        }

        return false;

    }
    

    /**
     *  Shows DB Update Notice | Action call-back
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function notice() {

        ?>
        <div class="notice ps-db-update-notice is-dismissible">
            <p><b><?php _e( 'Post SMTP database update required', 'post-smtp' ); ?></b></p>
            <p><?php 
                _e( 'Post SMTP has been updated! To keep things running smoothly, we have to update your database to the newest version, migrate email logs to new system. The database update process runs in the background and may take a little while, so please be patient.', 'post-smtp' ); 
            ?></p>
            <p>
                <button class="button button-primary" data-security="<?php echo wp_create_nonce( 'ps-migrate-logs' ); ?>" id="ps-migrate-logs">Update and Migrate Logs</button>
                <a href="" target="__blank" class="button button-secondary">Learn about migration</a>
            </p>
        </div>
        <?php

    }


    /**
     * Updates Database | AJAX call-back
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function update_database() {

        wp_verify_nonce( $_POST['security'], 'ps-migrate-logs' );

        //Let's start migration 
        if( $_POST['action'] == 'ps-migrate-logs' ) {

            $email_logs = new PostmanEmailLogs;
            $email_logs->install_table();

            if( $this->have_old_logs() ) {

                update_option( 'ps_migrate_logs', 1 );

            }

        }

    }


    /**
     * Every thirty minutes Cron Interval
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function ps_fifteen_minutes( $schedules ) {

        $schedules['fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => esc_html__( 'Every Fifteen Minutes' ), 'post-smtp' 
        );

        return $schedules;

    }

}

new PostmanEmailLogsMigration;

endif;