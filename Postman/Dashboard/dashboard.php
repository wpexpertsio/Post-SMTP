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
            <div class="ps-dashboard" style="height: 200vh;">
                <div class="ps-dash-left">
                    <div class="ps-dash-header">
                        <div class="ps-dash-header-left">
                            <h1><?php _e( 'Dashboard', 'post-smtp' ) ?></h1>
                            <h6><?php _e( 'Email Summary', 'post-smtp' ) ?></h6>
                        </div>
                        <div class="ps-dash-header-right">
                            <div class="ps-dash-sort">
                                <button class="active">Month</button>
                                <button><span class="ps-sort-border"></span>Week</button>
                                <button>Day</button>
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
                                <h1>Opened emails</h1>
                                <p>Unlock with PRO</p>
                            </div>
                            <span class="dashicons dashicons-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="ps-dash-right">
                    <div class="ps-dash-header">
                        <div class="ps-dash-top-icons-bar">
                            <button><span class="dashicons dashicons-bell ps-unread-notifications"></span></span></button>
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
                    <div class="ps-slidebox ps-tools-trouble">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-warning"></span>
                            <h5>Tools & Troubleshooting</h5>
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
                                <span class="dashicons dashicons-admin-links"></span></span><a href="<?php echo $this->getPageUrl( PostmanConnectivityTestController::PORT_TEST_SLUG ); ?>"><?php _e( 'Connectivity test', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-search"></span><a href="<?php echo $this->getPageUrl( PostmanDiagnosticTestController::DIAGNOSTICS_SLUG ); ?>"><?php _e( 'Diagnostic test', 'post-smtp' ); ?></a>
                            </div>
                            <div>
                                <span class="dashicons dashicons-plugins-checked"></span><a href="<?php echo esc_url( $this->getPageUrl( PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG ) ); ?>"><?php _e( 'Reset plugin', 'post-smtp' ); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="ps-slidebox ps-pro-features">
                        <div class="ps-slide-header">
                            <span class="dashicons dashicons-awards"></span>
                            <h5>Pro Features</h5>
                            <span class="dashicons dashicons-arrow-down-alt2 ps-slide-toggle"></span>
                        </div>
                        <div class="ps-slide-body">
                            <h3>Supercharge your email</h5>
                            <div class="ps-pro-urls">
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/reporting-and-tracking-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Report & Opened email Tracking', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/office-365-extension-for-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Office 365/Microsoft 365 Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/post-smtp-extension-for-amazon-ses/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Amazon SES Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/advanced-email-delivery/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Auto resend failed emails', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/email-log-attachment/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Attachment support in email logs', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/twilio-extension-pro/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'SMS email Failure Notification', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/zoho-mail-pro-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Zoho Mail Support', 'post-smtp' ); ?></a>
                                </div>
                                <div>
                                    <span class="dashicons dashicons-yes-alt"></span></span><a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/advanced-email-delivery/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><?php _e( 'Email Scheduling & Quota Managements', 'post-smtp' ); ?></a>
                                </div>
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