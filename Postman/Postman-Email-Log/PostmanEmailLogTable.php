<div class="wrap">
    <h1><?php esc_html_e( 'Post SMTP Email Logs', 'post-smtp' ); ?></h1>
    <?php 
    /**
     * Fires before the logs table.
     * 
     * @since 2.6.1
     * @version 1.0.0
     */
    do_action( 'post_smtp_before_logs_table' );
    ?>
    <input type="hidden" id="ps-email-log-nonce" value="<?php echo wp_create_nonce( 'security' ) ?>" />
    <table width="100%" id="ps-email-log">
        <thead>
            <tr>
                <th><input type="checkbox" class="ps-email-log-select-all" /></th>
                <th><?php esc_html_e( 'Subject', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Sent To', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Delivery Time', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Status', 'post-smtp' ); ?></th>
                <th class="ps-email-log-actions"><?php esc_html_e( 'Actions', 'post-smtp' ); ?></th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><input type="checkbox" class="ps-email-log-select-all" /></th>
                <th><?php esc_html_e( 'Subject', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Sent To', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Delivery Time', 'post-smtp' ); ?></th>
                <th><?php esc_html_e( 'Status', 'post-smtp' ); ?></th>
                <th class="ps-email-log-actions"><?php esc_html_e( 'Actions', 'post-smtp' ); ?></th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </tfoot>
    </table>

    <div class="ps-popup-wrap">
        <div class="ps-popup-box">
            <a class="ps-popup-close-btn ps-popup-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
            <div class="ps-popup-container"></div>
        </div>
    </div>
</div>