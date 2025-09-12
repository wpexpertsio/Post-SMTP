<div class="wrap">
    <h1><?php esc_html_e( 'Post SMTP Mailer Logs', 'post-smtp' ); ?></h1>

    <?php
    /**
     * Fires before the logs table.
     *
     * @since 2.6.1
     * @version 1.0.0
     */
    do_action( 'post_smtp_before_logs_table' );
    ?>


    <div id="ps-email-provider-log_wrapper" class="dataTables_wrapper">
        <div class="ps-flex-controls" style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px; margin-bottom: 10px;">
            <?php
            // Define provider list. You can update this array as needed.
            $providers = array(
                'sendinblue'   => __( 'Sendinblue', 'post-smtp' ),
                'sendgrid'     => __( 'Sendgrid', 'post-smtp' ),
                'elasticemail' => __( 'Elastic Email', 'post-smtp' ),
                'postmark'     => __( 'Postmark', 'post-smtp' ),
                'resend'       => __( 'Resend', 'post-smtp' ),
                'smtp2go'      => __( 'SMTP2GO', 'post-smtp' ),
                'sparkpost'    => __( 'SparkPost', 'post-smtp' ),
                'sendpulse'    => __( 'SendPulse', 'post-smtp' ),
                'gmail'        => __( 'Gmail', 'post-smtp' ),
                'aws_ses_api'  => __( 'AWS SES API', 'post-smtp' ),
                'zohomail_api' => __( 'Zoho Mail API', 'post-smtp' ),
                'office365_api' => __( 'Office 365 API', 'post-smtp' ),
            );
            ?>
            <label style="margin-bottom: 0;">
                <?php esc_html_e( 'Provider:', 'post-smtp' ); ?>
                <select name="ps-provider-log-select" id="ps-provider-log-select">
                    <option value="none"><?php esc_html_e( 'None', 'post-smtp' ); ?></option>
                    <?php foreach ( $providers as $slug => $name ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="margin-bottom: 0;">
                <?php esc_html_e( 'Show', 'post-smtp' ); ?>
                <select name="ps-email-log_length" aria-controls="ps-email-log">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
                <?php esc_html_e( 'entries', 'post-smtp' ); ?>
            </label>
            <label style="margin-bottom: 0;">
                <input type="search" id="ps-provider-log-search" placeholder="<?php esc_attr_e( 'Search', 'post-smtp' ); ?>" aria-controls="ps-email-log" />
            </label>
            <div class="ps-email-log-date-filter" style="display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
                <label style="margin-bottom: 0;">
                    <?php esc_html_e( 'From', 'post-smtp' ); ?>
                    <input type="date" class="ps-email-log-from" />
                </label>
                <label style="margin-bottom: 0;">
                    <?php esc_html_e( 'To', 'post-smtp' ); ?>
                    <input type="date" class="ps-email-log-to" />
                </label>
                <span class="ps-refresh-logs" title="<?php esc_attr_e( 'Refresh logs', 'post-smtp' ); ?>">
                    <span class="dashicons dashicons-image-rotate"></span>
                </span>
            </div>
        </div>
    </div>

    <input type="hidden" id="ps-email-log-nonce" value="<?php echo esc_attr( wp_create_nonce( 'security' ) ); ?>" />

    <div id="ps-provider-log-loader" style="display:none;text-align:center;padding:20px 0;">
        <span class="spinner is-active" style="float:none;display:inline-block;"></span>
    </div>
    <table width="100%" id="ps-email-log-provider">
        <thead>
            <tr>
               <th><?php esc_html_e( 'Id', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Subject', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Sent From', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Sent To', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Delivery Time', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'post-smtp' ); ?></th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </thead>
        <tbody>
            <tr class="ps-no-logs-row">
                <td colspan="100" style="text-align:center;padding:10px 0;">
                    <?php esc_html_e( 'No data available in table.', 'post-smtp' ); ?>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                    <th><?php esc_html_e( 'Id', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Subject', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Sent From', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Sent To', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Delivery Time', 'post-smtp' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'post-smtp' ); ?></th>
                <?php do_action( 'post_smtp_email_logs_table_header' ); ?>
            </tr>
        </tfoot>
    </table>

    <div class="ps-popup-wrap">
        <div class="ps-popup-box" <?php echo postman_is_bfcm() ? 'style="height:512px;"' : ''; ?>>
            <a class="ps-popup-close-btn ps-popup-close" href="#">
                <span class="dashicons dashicons-no-alt"></span>
            </a>
            <div class="ps-popup-container"></div>

            <?php if ( postman_is_bfcm() ) : ?>
                <a href="https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024"
                   target="_blank">
                    <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/bfcm-2024/dashboard.png' ); ?>" style="width:100%;" />
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?action=ps-skip-bfcm' ) ); ?>" style="font-size:10px;">
                    <?php esc_html_e( 'Not interested, Hide for now.', 'post-smtp' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
