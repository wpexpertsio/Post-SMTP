<?php 

class Post_SMTP_Dashboard {

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

        wp_enqueue_style( 'post-smtp-dashboard', POST_SMTP_URL . '/Postman/Dashboard/assets/css/dashboard.css', array(), POST_SMTP_VER );
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
            <div class="ps-dashboard" style="height: 100vh;">
                <div class="ps-dash-left">
                    <div class="ps-dash-header">
                        <div class="ps-dash-header-left">
                            <h1><?php _e( 'Dashboard', 'post-smtp' ) ?></h1>
                            <h6><?php _e( 'Email Summary', 'post-smtp' ) ?></h6>
                        </div>
                        <div class="ps-dash-header-right">
                            <div class="ps-dash-sort">
                                <button class="active">Month</button>
                                <button><span class="ps-sort-border"></span>Day</button>
                                <button>Week</button>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>
                <div class="ps-dash-right">

                </div>
                <div class="clear"></div>
            </div>
        </div>
        <?php

    }

}