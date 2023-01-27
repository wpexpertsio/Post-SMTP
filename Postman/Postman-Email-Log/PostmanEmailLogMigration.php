<?php

if( !class_exists( 'PostmanEmailLogsMigration' ) ):
class PostmanEmailLogsMigration {
    
    private $new_logging = false;
    private $migrating = false;
    private $have_old_logs = false;

    /**
     *  Constructor PostmanEmailLogsMigration
     * 
     * @since 2.4.0
     * @version 1.0.0
     */
    public function __construct() {

        $this->new_logging = get_option( 'postman_db_version' );
        $this->migrating = get_option( 'ps_migrate_logs' );
        $this->have_old_logs = $this->have_old_logs();

        //Show DB Update Notice
        if( $this->have_old_logs && !$this->migrating ) {

            add_action( 'admin_notices', array( $this, 'notice' ) );
            add_action( 'wp_ajax_ps-migrate-logs', array( $this, 'update_database' ) );

        }

        add_filter( 'cron_schedules', array( $this, 'ps_fifteen_minutes' ) );
        
        //Add Hook of Migration, Schedule Migration
        if( $this->migrating ) {

            add_action( 'ps_migrate_logs', array( $this, 'migrate_logs' ) );

            if ( ! wp_next_scheduled( 'ps_migrate_logs' ) ) {
                wp_schedule_event( time(), 'fifteen_minutes', 'ps_migrate_logs' );
            }

        }
        //Unschedule Migration, because no old logs left :)
        if( !$this->have_old_logs ) {

            $timestamp = wp_next_scheduled( 'ps_migrate_logs' );
            wp_unschedule_event( $timestamp, 'ps_migrate_logs' );

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

            //Have old logs, setup cronjob for migration
            if( $this->have_old_logs ) {

                update_option( 'ps_migrate_logs', 1 );

            }

            wp_send_json_success( array(), 200 );

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
            //'interval' => 900,
            'interval' => 5,
            'display'  => esc_html__( 'Every Fifteen Minutes' ) 
        );

        return $schedules;

    }

    
    /**
     * Gets last old log, to be migrated
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_last_old_log() {

        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->postmeta} WHERE post_id = ( SELECT ID FROM {$wpdb->posts} ORDER BY ID DESC LIMIT 1 )"
        );

    }


    /**
     * Migrate Logs
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function migrate_logs() {
        
        if( $this->have_old_logs ) {

            $last_log = $this->get_last_old_log();


            var_dump( $last_log );die;

        }

    }

}

$woo = new PostmanEmailLogsMigration;

$woo->migrate_logs();

endif;