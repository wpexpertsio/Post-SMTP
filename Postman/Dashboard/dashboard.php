<?php 

class Post_SMTP_Dashboard extends PostmanViewController {

    /**
     * Constructor Post_SMTP_Dashboard
     * 
     * @since 3.0.0
     */
    public function __construct() {

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

    }

    /**
     * Admin Enqueue | Action Call-back
     * 
     * @since 3.0.0
     */
    public function admin_enqueue_scripts() {

        wp_enqueue_style( 'post-smtp-dashboard-light', POST_SMTP_URL . '/Postman/Dashboard/assets/css/dashboard-light.css', array(), POST_SMTP_VER );
        wp_enqueue_style( 'post-smtp-dashboard-dark', POST_SMTP_URL . '/Postman/Dashboard/assets/css/dashboard-dark.css', array(), POST_SMTP_VER );
        wp_enqueue_script( 'post-smtp-dashboard', POST_SMTP_URL . '/Postman/Dashboard/assets/js/dashboard.js', array( 'jquery' ), POST_SMTP_VER );

    }

    /**
     * Renders new dashboard
     * 
     * @since 3.0.0
     */
    public function render() {

        ?>
        <div class="wrap">
            <div class="ps-dashboard" style="height: 300vh;">
                <div class="ps-dash-left">
                    <div class="ps-dash-header">
                        <div class="ps-dash-header-left">
                            <h1><?php _e( 'Dashboard', 'post-smtp' ) ?></h1>
                            <h6><?php _e( 'Email Summary', 'post-smtp' ) ?></h6>
                        </div>
                        <div class="ps-dash-header-right">
                            <div class="ps-dash-sort">
                                <button class="active"><?php _e( 'Month', 'post-smtp' ) ?></button>
                                <button><span class="ps-sort-border"></span><?php _e( 'Week', 'post-smtp' ) ?></button>
                                <button><?php _e( 'Day', 'post-smtp' ) ?></button>
                                <div class="clear"></div>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="ps-dash-email-summery">
                        <div class="ps-total">
                            <div class="ps-summery-img-container">
                                <span class="dashicons dashicons-email"></span>
                            </div>
                            <div class="ps-summery-container">
                                <h1>150K</h1>
                                <p><b>Total</b> Email This Week</p>
                            </div>
                        </div>
                        <div class="ps-success">
                            <div class="ps-summery-img-container">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </div>
                            <div class="ps-summery-container">
                                <h1>150K</h1>
                                <p><b>Total</b> Email This Week</p>
                            </div>
                        </div>
                        <div class="ps-failed">
                            <div class="ps-summery-img-container">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <div class="ps-summery-container">
                                <h1>150K</h1>
                                <p><b>Total</b> Email This Week</p>
                            </div>
                        </div>
                        <div class="ps-opened-pro">
                            <div class="ps-summery-img-container">
                                <span class="dashicons dashicons-buddicons-pm"></span>
                            </div>
                            <div class="ps-summery-container">
                                <h1><?php _e( 'Opened emails', 'post-smtp' ) ?></h1>
                                <p><?php _e( 'Unlock with PRO', 'post-smtp' ) ?></p>
                            </div>
                            <span class="dashicons dashicons-lock"></span>
                        </div>
                    </div>
                    <!-- Recent Logs -->
                    <div class="ps-slidebox ps-recent-logs">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-media-document"></span>
                            <h5><?php _e( 'Recent Logs', 'post-smtp' ) ?></h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            
                        </div>
                    </div>
                    <!-- Guides and Documentation -->
                    <div class="ps-slidebox ps-guide-and-doc">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-book-alt"></span>
                            <h5><?php _e( 'Guides and Documentation', 'post-smtp' ) ?></h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            <div class="ps-dash-docs">
                                <h4><?php _e( 'Getting Started' ); ?></h4>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/email-logs/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Installation and Activation Guide', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/email-logs/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Email Logs', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( '' ); ?>" target="_blank"><?php _e( 'Verifying Post SMTP Pro License', 'post-smtp' ); ?></a>
                                </div>
                            </div>
                            <div class="ps-dash-docs">
                                <h4><?php _e( 'SMTP Mailer Configuration Guide' ); ?></h4>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/post-smtp-complete-mailer-guide/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Complete Mailer Guide', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendinblue-aka-brevo-with-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Brevo with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/gmail/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Gmail /Google Workspace With Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-office-365-new-ui/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Office/Microsoft 365(PRO) with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mandrill-with-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Mandrill with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-mailjet/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Mailjet with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/postmark/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Postmark with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/sparkpost/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Spark Post with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendgrid-with-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'SendGrid with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/amazon-ses-pro/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Amazon SES (Pro)', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/zoho-with-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Zoho with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-elastic-email/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Elastic Email with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-mail-gun/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Mailgun with Post SMTP', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-other-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Post SMTP with other SMTP', 'post-smtp' ); ?></a>
                                </div>
                            </div>
                            <div class="ps-dash-docs">
                                <!-- Advance Functionality -->
                                <h4><?php _e( 'Advance Functionality' ); ?></h4>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/advance-functionality/report-and-tracking-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Email Report and Tracking', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/advance-functionality/advance-delivery-logs/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Advance Delivery and Logs', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/advance-functionality/fallback-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Fallback Email Setup', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/advance-functionality/email-log-attachment/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Email Log Attachment', 'post-smtp' ); ?></a>
                                </div>
                                <!-- Integrations -->
                                <h4><?php _e( 'Integrations' ); ?></h4>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/notification/slack/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Slack', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/notification/twilio/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Twilio', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/notification/google-chrome-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Chrome Extension', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-text-page"></span>
                                    <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/post-smtp-mobile-app/download-the-app-and-connect-with-plugin/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Post SMTP Mobile App', 'post-smtp' ); ?></a>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                            <div>
                                <a href="<?php echo esc_url( 'https://postmansmtp.com/documentation/?utm_source=plugin&utm_medium=dashboard' ); ?>" class="ps-simple button button-primary"><?php _e( 'View More', 'post-smtp' ); ?><span class="dashicons dashicons-arrow-right-alt2"></span></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ps-dash-right">
                    <div class="ps-dash-header">
                        <div class="ps-dash-top-icons-bar">
                            <button><span class="dashicons dashicons-bell ps-unread-notifications"></span></button>
                            <button><span class="dashicons dashicons-admin-generic active"></span></button>
                            <input type="checkbox" class="ps-theme-mode" id="ps-theme-mode" checked>
                            <label for="ps-theme-mode" class="ps-theme-mode-label">
                                <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/dark.png' ); ?>" />
                                <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/light.png' ); ?>" />
                                <span class="ps-theme-mode-ball"></span>
                            </label>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div>
                        <img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/dashboard/sidebar/1.png' ); ?>" class="ps-dash-sidebar" />
                    </div>
                    <!-- Tools & Troubleshooting	 -->
                    <div class="ps-slidebox ps-tools-trouble">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-warning"></span>
                            <h5><?php _e( 'Tools & Troubleshooting', 'post-smtp' ) ?></h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            <div>
                                <span class="dashicons dashicons-email"></span><a href="<?php echo esc_url( $this->getPageUrl( PostmanSendTestEmailController::EMAIL_TEST_SLUG ) ); ?>"><?php _e( 'Send test email', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-redo"></span><a href="<?php echo esc_url( $this->getPageUrl( PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG ) ); ?>"><?php _e( 'Import/ Export', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-admin-links"></span><a href="<?php echo $this->getPageUrl( PostmanConnectivityTestController::PORT_TEST_SLUG ); ?>"><?php _e( 'Connectivity test', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-search"></span><a href="<?php echo $this->getPageUrl( PostmanDiagnosticTestController::DIAGNOSTICS_SLUG ); ?>"><?php _e( 'Diagnostic test', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-plugins-checked"></span><a href="<?php echo esc_url( $this->getPageUrl( PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG ) ); ?>"><?php _e( 'Reset plugin', 'post-smtp' ); ?></a>
                            </div>
                        </div>
                    </div>
                    <!-- Pro Features -->
                    <div class="ps-slidebox ps-pro-features">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-awards"></span>
                            <h5><?php _e( 'Pro Features', 'post-smtp' ) ?></h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            <h3><?php _e( 'Supercharge your email', 'post-smtp' ) ?></h5>
                            <div class="ps-pro-urls">
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/reporting-and-tracking-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Report & Opened email Tracking', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/office-365-extension-for-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Office 365/Microsoft 365 Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/post-smtp-extension-for-amazon-ses/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Amazon SES Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/advanced-email-delivery/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Auto resend failed emails', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/email-log-attachment/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Attachment support in email logs', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/twilio-extension-pro/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'SMS email Failure Notification', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/zoho-mail-pro-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Zoho Mail Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/advanced-email-delivery/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Email Scheduling & Quota Managements', 'post-smtp' ); ?></a>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo esc_url( 'https://postmansmtp.com/extension/' ); ?>" target="_blank" class="ps-pro button button-primary"><?php _e( 'Learn More', 'post-smtp' ); ?></a>
                            </div>
                        </div>
                    </div>
                    <!-- Assistance from Experts -->
                    <div class="ps-slidebox ps-pro-features ps-expert-assistance">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-book"></span>
                            <h5><?php _e( 'Assistance from Experts', 'post-smtp' ) ?></h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            <p>
                                <?php _e( 'Let out experts handle your post SMTP Plugin Setup', 'post-smtp' ) ?>
                            </p>
                            <div>
                                <a href="<?php echo esc_url( 'https://postmansmtp.com/configuration-request/' ); ?>" target="_blank" class="ps-pro button button-secondary"><?php _e( 'Learn More', 'post-smtp' ); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <?php

    }

}