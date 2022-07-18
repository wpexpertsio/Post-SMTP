<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PostmanLicenseHandler' ) ) :


class PostmanLicenseHandler {

	const DAYS_TO_ALERT = array( 30, 14, 7, 1);

    private $file;
    private $license;
    private $license_data;
    private $item_name;
    private $item_id;
    private $item_shortname;
    private $version;
    private $author;
	private $api_url = 'https://postmansmtp.com';


	function __construct( $_file, $_item_name, $_version, $_author, $_optname = null, $_api_url = null, $_item_id = null ) {
		$this->file = $_file;
		$this->item_name = $_item_name;

		if ( is_numeric( $_item_id ) ) {
			$this->item_id = absint( $_item_id );
		}

		$this->item_shortname = $this->get_slug();
		$this->version        = $_version;
		$this->license        = trim( get_option( $this->item_shortname . '_license_key', '' ) );
		$this->license_data        = get_option( $this->item_shortname . '_license_active', '' );
		$this->author         = $_author;
		$this->api_url        = is_null( $_api_url ) ? $this->api_url : $_api_url;

		/**
		 * Allows for backwards compatibility with old license options,
		 * i.e. if the plugins had license key fields previously, the license
		 * handler will automatically pick these up and use those in lieu of the
		 * user having to reactive their license.
		 */
		if ( ! empty( $_optname ) ) {
			$opt = get_option( $_optname, false );

			if( isset( $opt ) && empty( $this->license ) ) {
				$this->license = trim( $opt );
			}
		}

		// Setup hooks
		$this->includes();
		$this->hooks();

	}

	/**
	 * Include the updater class
	 *
	 * @access  private
	 * @return  void
	 */
	private function includes() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) )  {
			require_once 'EDD_SL_Plugin_Updater.php';
		}
	}

	/**
	 * Setup hooks
	 *
	 * @access  private
	 * @return  void
	 */
	public function hooks() {

		// Activate license key on settings save
		add_action( 'admin_init', array( $this, 'activate_license' ) );

		// Deactivate license key
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );

		add_action( 'init', array( $this, 'cron' ), 20 );

        add_action( 'admin_init', array( $this, 'validate_license' ) );

		// Updater
		add_action( 'admin_init', array( $this, 'auto_updater' ), 0 );

		// Display notices to admins
		add_action( 'admin_notices', array( $this, 'notices' ) );

		//add_action( 'in_plugin_update_message-' . plugin_basename( $this->file ), array( $this, 'plugin_row_license_missing' ), 10, 2 );
	}

    /**
     * Auto updater
     *
     * @access  private
     * @return  void
     */
    public function auto_updater() {

        $args = array(
            'version'   => $this->version,
            'license'   => $this->license,
            'author'    => $this->author,
            'beta'      => function_exists( 'edd_extension_has_beta_support' ) && edd_extension_has_beta_support( $this->item_shortname ),
        );

        if( ! empty( $this->item_id ) ) {
            $args['item_id']   = $this->item_id;
        } else {
            $args['item_name'] = $this->item_name;
        }

        // Setup the updater
        $edd_updater = new EDD_SL_Plugin_Updater(
            $this->api_url,
            $this->file,
            $args
        );
    }

    public function cron() {
        if ( ! wp_next_scheduled( $this->item_shortname . '_scheduled_events' ) ) {
            wp_schedule_event( current_time( 'timestamp', true ), 'daily', $this->item_shortname . '_scheduled_events' );
        }
    }

    public function get_slug() {
        return preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
    }

	/**
	 * Display help text at the top of the Licenses tag
	 *
	 * @since   2.5
	 * @param   string   $active_tab
	 * @return  void
	 */
	public function license_help_text( $active_tab = '' ) {

		static $has_ran;

		if( 'licenses' !== $active_tab ) {
			return;
		}

		if( ! empty( $has_ran ) ) {
			return;
		}

		echo '<p>' . sprintf(
			__( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, please <a href="%s" target="_blank">renew your license</a>.', 'easy-digital-downloads' ),
			'http://docs.easydigitaldownloads.com/article/1000-license-renewal'
		) . '</p>';

		$has_ran = true;

	}


	/**
	 * Activate the license key
	 *
	 * @return  void
	 */
	public function activate_license() {

		if ( ! isset( $_POST['post_smtp_extension'][ $this->item_shortname . '_activate'] ) ) {
			return;
		}

		if ( ! isset( $_REQUEST[ $this->item_shortname . '_license_key-nonce'] ) || ! wp_verify_nonce( $_REQUEST[ $this->item_shortname . '_license_key-nonce'], $this->item_shortname . '_license_key-nonce' ) ) {

			return;

		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_POST['post_smtp_extension'][ $this->item_shortname . '_license_key'] ) ) {

			delete_option( $this->item_shortname . '_license_active' );
            delete_option( $this->item_shortname . '_license_key' );

			return;

		}

		foreach ( $_POST as $key => $value ) {
			if( false !== strpos( $key, 'license_key_deactivate' ) ) {
				// Don't activate a key when deactivating a different key
				return;
			}
		}

		$details = get_option( $this->item_shortname . '_license_active' );

		if ( is_object( $details ) && 'valid' === $details->license ) {
			return;
		}

		$license = sanitize_text_field( $_POST['post_smtp_extension'][ $this->item_shortname . '_license_key'] );

		if( empty( $license ) ) {
			return;
		}

		// Data to send to the API
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url()
		);

		if ( ! empty( $this->item_id ) ) {
			$api_params['item_id'] = $this->item_id;
		}

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);

		// Make sure there are no errors
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Tell WordPress to look for updates
		set_site_transient( 'update_plugins', null );

		// Decode license data
		$this->license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( $this->item_shortname . '_license_active', $this->license_data );
		update_option( $this->item_shortname . '_license_key', $license );

		if ( $this->license_data->success && $this->license_data->license == 'valid' ) {
            $slug = plugin_basename($this->file);
            PostmanLicenseManager::get_instance()->add_extension($slug);
        }
	}


	/**
	 * Deactivate the license key
	 *
	 * @return  void
	 */
	public function deactivate_license() {

        if ( ! isset( $_POST['post_smtp_extension'][ $this->item_shortname . '_deactivate'] ) ) {
            return;
        }

		if ( ! isset( $_POST['post_smtp_extension'][ $this->item_shortname . '_license_key'] ) )
			return;

		if( ! wp_verify_nonce( $_REQUEST[ $this->item_shortname . '_license_key-nonce'], $this->item_shortname . '_license_key-nonce' ) ) {

			wp_die( __( 'Nonce verification failed', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'response' => 403 ) );

		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$license_key = sanitize_text_field( base64_decode( $_POST['post_smtp_extension'][ $this->item_shortname . '_license_key'] ) );

		// Run on deactivate button press
        // Data to send to the API
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license_key,
            'item_name'  => urlencode( $this->item_name ),
            'url'        => home_url()
        );

        if ( ! empty( $this->item_id ) ) {
            $api_params['item_id'] = $this->item_id;
        }

        // Call the API
        $response = wp_remote_post(
            $this->api_url,
            array(
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => $api_params
            )
        );

        // Make sure there are no errors
        if ( is_wp_error( $response ) ) {
            return;
        }

        // Decode the license data
		$this->license_data = json_decode( wp_remote_retrieve_body( $response ) );

        delete_option( $this->item_shortname . '_license_active' );
        delete_option( $this->item_shortname . '_license_key' );

        $slug = plugin_basename($this->file);
        PostmanLicenseManager::get_instance()->remove_extension($slug);

	}

	public function validate_license() {
        if ( false === ( $cron_data = get_transient( $this->item_shortname . '_cron' ) ) ) {
            $this->license_check();

            set_transient( $this->item_shortname . '_cron', true, rand( 12, 48 ) *  HOUR_IN_SECONDS );
        }

        $license_data = $this->license_data;

        if ( $license_data && isset( $license_data->expires ) ) {
            if ( $license_data->expires == 'lifetime' ) {
                $expires = '2500/12/12';
            } else {
                $expires = $license_data->expires;
            }
        } else {
            return;
        }

		$datetime1 = new DateTime();

		if ( is_numeric( $expires ) ) {
            $datetime2 = new DateTime();
            $datetime2->setTimestamp( $expires );
        } else {
            $datetime2 = new DateTime( $expires );
        }

		foreach ( self::DAYS_TO_ALERT as $day_to_alert ) {

	        $interval = $datetime1->diff($datetime2);
	        if( $interval->days == $day_to_alert ){
		        add_action( 'admin_notices', function () use ( $day_to_alert, $license_data ) {
			        //echo $this->item_name . ' is about to expire in ' . $day_to_alert . ' days: ' . $license_data->expires;
		        });

		        return;
	        }

	        if ( $interval->days == 0 ) {
		        add_action( 'admin_notices', function () use ( $license_data ) {
			        //echo $this->item_name . ' license expire today at: ' . $license_data->expires;
		        });

		        return;
	        }
        }

        if ( $license_data->activations_left == 0 ) {
	        add_action( 'admin_notices', function () use ( $license_data ) {
		        //echo $this->item_name . ' has no activations';
	        });

	        return;
        }
    }


	/**
	 * Check if license key is valid once per week
	 *
	 * @since   2.5
	 * @return  void
	 */
	public function license_check() {

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'check_license',
			'license' 	=> $this->license,
			'item_name' => urlencode( $this->item_name ),
			'url'       => home_url()
		);

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$this->license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( $this->item_shortname . '_license_active', $this->license_data );

	}

	private function get_license_data() {
		return get_option( $this->item_shortname . '_license_active' );
	}


	/**
	 * Admin notices for errors
	 *
	 * @return  void
	 */
	public function notices() {

		$showed_invalid_message = null;

		if( empty( $this->license ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$messages = array();

		$license = get_option( $this->item_shortname . '_license_active' );

		if( is_object( $license ) && 'valid' !== $license->license && empty( $showed_invalid_message ) ) {

			if( isset( $_GET['page'] ) && 'post-smtp-extensions' === $_GET['page'] ) {

				$messages[] = sprintf(
					__( '%s has invalid or expired license key for Post SMTP.'),
					'<strong>' . $this->item_name . '</strong>'
				);

				$showed_invalid_message = true;

			}

		}

		if( ! empty( $messages ) ) {

			foreach( $messages as $message ) {

				echo '<div class="error">';
					echo '<p>' . $message . '</p>';
				echo '</div>';

			}

		}

	}

	public function is_licensed() {
	    return is_object($this->license_data) && 'valid' === $this->license_data->license;
    }
}

endif; // end class_exists check
