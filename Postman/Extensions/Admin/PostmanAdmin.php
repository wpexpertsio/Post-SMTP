<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PostmanAdmin {

    public function __construct()
    {
        $PostmanLicenseManager = PostmanLicenseManager::get_instance();
        $extensions = $PostmanLicenseManager->get_extensions();

        if ( count( $extensions ) > 0 ) {
            add_action('admin_menu', [ $this, 'add_menu' ], 20 );
        }

    }

    public function add_menu() {
        add_submenu_page(
            PostmanViewController::POSTMAN_MENU_SLUG,
            __('Extensions', 'post-smtp'),
            __('Extensions', 'post-smtp'),
            'manage_options',
            'post-smtp-extensions',
            [ $this, 'render_menu' ]
        );
    }

    public function render_menu() {
        include_once 'PostmanAdminView.php';
    }
}
