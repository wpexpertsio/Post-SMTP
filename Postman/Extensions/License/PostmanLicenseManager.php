<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PostmanLicenseManager {

    const ENDPOINT = 'https://postmansmtp.com';

    const CORE_EXTENSIONS = [ 'gmail_api', 'sendgrid_api', 'mandrill_api', 'mailgun_api' ];

    private $extensions;

    private $rand_cache_interval = 12;

    private static $instance;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * PostmanLicenseManager constructor.
     */
    private function __construct()
    {
        $this->includes();
        $this->rand_cache_interval = rand( 1, 24 );
    }

    public function includes() {
        include_once 'PostmanLicenseHandler.php';

        include_once ABSPATH . '/wp-admin/includes/plugin.php';

    }

    /**
     * Init
     */
    public function init() {

        $plugins = get_plugins();
        foreach ( $plugins as $plugin_dir_and_filename => $plugin_data ) {

            if ( ! is_plugin_active( $plugin_dir_and_filename ) ) {
                continue;
            }

            if ( false !== strpos( $plugin_dir_and_filename, 'post-smtp-extension' ) ) {
                $slug = $plugin_dir_and_filename;
                $class = $plugin_data['Class'];
                $plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin_dir_and_filename;

                $this->extensions[$slug]['plugin_data'] = $plugin_data;
                $this->extensions[$slug]['plugin_dir_and_filename'] = $plugin_dir_and_filename;
                $this->extensions[$slug]['license_manager'] = new PostmanLicenseHandler(
                    $plugin_path, $plugin_data['Name'],
                    $plugin_data['Version'], $plugin_data['Author'], null, self::ENDPOINT
                );
                if ( $this->extensions[$slug]['license_manager']->is_licensed() ) {
                    $this->extensions[$slug]['instance'] = new $class;
                }
            }
        }

        if ( ! empty( $this->extensions ) ) {
            new PostmanAdmin();
        }
    }

    public function add_extension($slug) {
        $plugin_path = WP_CONTENT_DIR . '/plugins/' . $this->extensions[$slug]['plugin_dir_and_filename'];
        $class = $this->extensions[$slug]['plugin_data']['Class'];

        include_once $plugin_path;
        $this->extensions[$slug]['instance'] = new $class;
    }

    public function remove_extension($slug) {
        $this->extensions[$slug]['instance'] = null;
        unset($this->extensions[$slug]['instance']);
    }

    public function get_extensions() {
        return $this->extensions;
    }
}
