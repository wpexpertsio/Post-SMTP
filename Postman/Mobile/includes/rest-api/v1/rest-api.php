<?php

class Post_SMTP_Mobile_Rest_API {


    /**
     * Register routes
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function __construct() {

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

    }

    /**
     * Register routes
     * 
     * @since 2.8.0
     * @version 1.0.0
     */
    public function register_routes() {

        register_rest_route( 'post-smtp/v1', '/connect-app', array(
            'methods'               => WP_REST_Server::READABLE,
            'callback'              => array( $this, 'settings' ),
            'permission_callback'   => '__return_true',
        ) );


    }

}

new Post_SMTP_Mobile_Rest_API();