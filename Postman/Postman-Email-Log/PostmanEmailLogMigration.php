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
        if( $this->have_old_logs  ) {

            add_action( 'admin_notices', array( $this, 'notice' ) );

        }

        if( isset( $_GET['action'] ) && $_GET['action'] == 'ps-migrate-logs' ) {

            $this->update_database();

        }
        
        //Add Hook of Migration, Schedule Migration
        if( $this->migrating && ( isset( $_GET['page'] ) && $_GET['page'] == 'postman_email_log' ) ) {

            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );

        }

        add_action( 'wp_ajax_ps-migrate-logs', array( $this, 'migrate_logs' ) );

    }


    /**
     * Checks if have logs in old system
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function have_old_logs() {

        global $wpdb;

        $data = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'postman_sent_mail' LIMIT 1;"
        );
        
        if( !empty( $data ) ) {

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

        $security = wp_create_nonce( 'ps-migrate-logs' );
        $migration_url = admin_url( 'admin.php?page=postman_email_log' ) . '&security=' . $security . '&action=ps-migrate-logs';
        $status_url = admin_url( 'admin.php?page=postman_email_log' );
        $total_old_logs = wp_count_posts( 'postman_sent_mail' );

        ?>
        <div class="notice ps-db-update-notice is-dismissible" style="border: 1px solid #2271b1; border-left-width: 4px;">
            <input type="hidden" value="<?php echo esc_attr( $security ); ?>" class="ps-security">
            <p><b><?php _e( 'Post SMTP database update required', 'post-smtp' ); ?></b></p>
            <p><?php 
                _e( 'Post SMTP has been updated! To keep things running smoothly, we have to update your database to the newest version, migrate email logs to new system. The database update process runs in the background and may take a little while, so please be patient.', 'post-smtp' ); 
            ?></p>
            <p>
                <?php if( $this->have_old_logs && !$this->migrating ): ?>
                    <a href="<?php echo esc_url( $migration_url ) ?>" class="button button-primary">Update and Migrate Logs</a>
                <?php endif; ?>
                <?php if(  
                    $this->migrating
                    &&
                    ( isset( $_GET['page'] ) && $_GET['page'] !== 'postman_email_log' )
                ): ?>
                    <a href="<?php echo esc_url( $status_url ); ?>" class="button button-secondary">View Progress →</a>
                <?php endif; ?>
                <a href="" target="__blank" class="button button-secondary">Learn about migration</a>
            </p>
        </div>
        <?php

        if(
            $this->have_old_logs 
            && 
            $this->migrating 
            && 
            ( isset( $_GET['page'] ) && $_GET['page'] == 'postman_email_log' )
        ) {
            ?>
            <div>
                <progress id="ps-migration-progress" value="32" max="<?php echo esc_attr( $total_old_logs->private ); ?>"></progress>
            </div>
            <?php
        }

    }


    /**
     * Updates Database 
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function update_database() {

        wp_verify_nonce( $_GET['security'], 'ps-migrate-logs' );

        //Let's start migration 

        $email_logs = new PostmanEmailLogs;
        $email_logs->install_table();

        //Have old logs, setup cronjob for migration
        if( $this->have_old_logs && !$this->migrating ) {

            update_option( 'ps_migrate_logs', 1 );

        }
        
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
     * Migrate Logs | AJAX call-back
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function migrate_logs() {

        wp_verify_nonce( $_POST['security'], 'ps-migrate-logs' );

        if( isset( $_POST['action'] ) && $_POST['action'] == 'ps-migrate-logs' ) {

            if( $this->have_old_logs ) {

                $last_log = $this->get_old_logs();
    
    
                var_dump( $last_log );die;
    
            }

        }

        wp_send_json_success( array( 'left' => 10 ), 200 );

    }


    /**
     * Enqueue Scripts | Action call-back
     * 
     * @since 2.5.0
     * @version 1.0.0
     */
    public function enqueue_script() {

        wp_enqueue_script( 'ps-migrate', POST_SMTP_URL . '/script/logs-migration.js', array( 'jquery' ), array(), true );

    }

    
    /**
     * Gets old logs
     * 
     * @param $limit String
     * @since 2.5.0
     * @version 1.0.0
     */
    public function get_old_logs( $limit = 100 ) {

        global $wpdb;

        $log_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} AS p WHERE p.post_type = 'postman_sent_mail' && p.pinged != 1 LIMIT %d;",
                $limit
            ),
            OBJECT_K
        );
        $log_ids = array_keys( $log_ids );

        $logs = $wpdb->get_results(
                "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN {$log_ids};"
        );
var_dump( $logs );die;
        if( $log_ids ) {



        }

        //Wind up, all migrated

    }

}

new PostmanEmailLogsMigration;

endif;