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
            $current_time = 1732752000;

            $start_time = $this->promotions[$promotion]['start_time'];
            $end_time = $this->promotions[$promotion]['end_time'];

            if ( $current_time >= $start_time && $current_time <= $end_time ) {

                return true;

            }

        }
        
        return false;

    }

}

endif;