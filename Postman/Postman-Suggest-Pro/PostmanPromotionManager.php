<?php

if( ! class_exists( 'Postman_Promotion_Manager' ) ):
class Postman_Promotion_Manager {

    /**
     * The promotion manager instance.
     *
     * @var Postman_Promotion_Manager
     */
    private static $instance;
    private $promotion;private $promotions = array(
        'bfcm-2024' => array(
            'title'         => 'Black Friday 2024',
            'start_time'    => 1732752000,
            'end_time'      => 1733270340,
        )
    );

    /**
     * The promotion manager instance.
     *
     * @since 2.9.10
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {

            self::$instance = new Postman_Promotion_Manager();

        }

        return self::$instance;

    }

    /**
     * The promotion manager constructor.
     *
     * @since 2.9.10
     */
    public function __construct() {

        add_action( 'admin_action_ps-skip-bfcm', array( $this, 'skip_bfcm' ) );

    }

    /**
     * Get the promotion.
     * 
     * @since 2.9.10
     */
    public function get_promotion( $promotion ) {

        if ( ! isset( $this->promotions[$promotion] ) ) {

            return false;

        }

        return $this->promotions[$promotion];

    }

    /**
     * Check if the promotion is active.
     * 
     * @since 2.9.10
     */
    public function is_promotion_active( $promotion ) {

        if ( isset( $this->promotions[$promotion] ) ) {

            $current_time = time();

            $start_time = $this->promotions[$promotion]['start_time'];
            $end_time = $this->promotions[$promotion]['end_time'];

            if ( $current_time >= $start_time && $current_time <= $end_time ) {

                return true;

            }

        }
        
        return false;

    }

    /**
     * Skip the promotion | Action Callback
     * 
     * @since 2.9.10
     */
    public function skip_bfcm() {

        if( isset( $_GET['action'] ) && $_GET['action'] == 'ps-skip-bfcm' ) {

            set_transient( 'ps-skip-bfcm', true, 604800 );

            wp_redirect( wp_get_referer() );

        }

    }

}

Postman_Promotion_Manager::get_instance();

endif;