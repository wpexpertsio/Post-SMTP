<div class="wrap">
    <h1>Post SMTP Email Logs</h1>
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
                <th>Subject</th>
                <th>Sent To</th>
                <th>Delivery Time</th>
                <th>Status</th>
                <th class="ps-email-log-actions">Actions</th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><input type="checkbox" class="ps-email-log-select-all" /></th>
                <th>Subject</th>
                <th>Sent To</th>
                <th>Delivery Time</th>
                <th>Status</th>
                <th class="ps-email-log-actions">Actions</th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </tfoot>
    </table>

    <div class="ps-popup-wrap">
        <div class="ps-popup-box" <?php echo postman_is_bfcm() ? 'style="height: 512px";' : '';  ?>>
            <a class="ps-popup-close-btn ps-popup-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
            <div class="ps-popup-container"></div>
            <?php
            if( postman_is_bfcm() ) {

            ?>
            <a href="https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024" target="_blank">
                <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/bfcm-2024/dashboard.png' ) ?>" style="width: 100%;" />
            </a>
            <a href="<?php echo admin_url( 'admin.php?action=ps-skip-bfcm' ); ?>" style="font-size: 10px;">Not interested, Hide for now.</a>
            <?php

            }
            ?>
        </div>
    </div>
</div>