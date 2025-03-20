<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/*
 * Plugin Name: Post SMTP
 * Plugin URI: https://wordpress.org/plugins/post-smtp/
 * Description: Email not reliable? Post SMTP is the first and only WordPress SMTP plugin to implement OAuth 2.0 for Gmail, Hotmail and Yahoo Mail. Setup is a breeze with the Configuration Wizard and integrated Port Tester. Enjoy worry-free delivery even if your password changes!
 * Version: 3.2.0
 * Author: Post SMTP
 * Text Domain: post-smtp
 * Author URI: https://postmansmtp.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * Post SMTP (aka Postman SMTP) was originally developed by Jason Hendriks
 */
// The Postman Mail API
//
// filter postman_test_email: before calling wp_mail, implement this filter and return true to disable the success/fail counters
// filter postman_wp_mail_result: apply this filter after calling wp_mail for an array containing the SMTP error, transcript and time
// filter postman_get_plugin_metadata: apply this filter to get plugin metadata
// filter postman_wp_mail_bind_status: apply this filter to get wp_mail bind status
// filter print_postman_status: apply this filter to print the human-readable plugin state
// filter postman_module: implement this filter and return the instance of the module
// filter postman_register_modules: apply this filter to register the module

/** 
 * Freemius initialization
 * 
 * @since 2.1.1
 * @version 1.0
 */
if ( ! function_exists( 'ps_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ps_fs() {
        global $ps_fs;

        if ( ! isset( $ps_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $ps_fs = fs_dynamic_init( array(
                'id'                  => '10461',
                'slug'                => 'post-smtp',
                'type'                => 'plugin',
                'public_key'          => 'pk_28fcefa3d0ae86f8cdf6b7f71c0cc',
                'is_premium'          => false,
                'has_addons'          => false,
				'bundle_id' 		  => '10910',
				'bundle_public_key'   => 'pk_c5110ef04ba30cd57dd970a269a1a',
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'postman',
                    'first-path'     => 'admin.php?page=postman/configuration_wizard',
                    'account'        => false,
                ),
            ) );
        }

        return $ps_fs;
    }

    // Init Freemius.
    ps_fs();
    // Signal that SDK was initiated.
    do_action( 'ps_fs_loaded' );
}

function ps_fs_custom_connect_message_on_update(
    $message,
    $user_first_name,
    $product_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
		'<div class="ps-optin-popup">' .
        '<h1>' . __( 'Stay on the safe side', 'post-smtp' ) . '</h1>' .
		'<p>'.__( 'Receive our plugin\'s alert in case of critical security and feature updates and allow non-sensitive diagnostic tracking.', 'post-smtp' ).'</p>' .
		'</div>' . 
		'<div style="clear: both;"></div>'
    );
}
 
ps_fs()->add_filter('connect_message', 'ps_fs_custom_connect_message_on_update', 10, 6);

function ps_fs_custom_icon() {
    return dirname( __FILE__ ) . '/assets/images/icons/optin.png';
}
 
ps_fs()->add_filter( 'plugin_icon' , 'ps_fs_custom_icon' );


/**
 * DO some check and Start Postman
 */

define( 'POST_SMTP_BASE', __FILE__ );
define( 'POST_SMTP_PATH', __DIR__ );
define( 'POST_SMTP_URL', plugins_url('', POST_SMTP_BASE ) );
define( 'POST_SMTP_VER', '3.2.0' );
define( 'POST_SMTP_DB_VERSION', '1.0.1' );
define( 'POST_SMTP_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );

$postman_smtp_exist = in_array( 'postman-smtp/postman-smtp.php', (array) get_option( 'active_plugins', array() ) );
$required_php_version = version_compare( PHP_VERSION, '5.6.0', '<' );

if( ! function_exists( 'post_smtp_load_textdomain' ) ):
function post_smtp_load_textdomain() {
	// had to hardcode the third parameter, Relative path to WP_PLUGIN_DIR,
	// because __FILE__ returns the wrong path if the plugin is installed as a symlink
	$shortLocale = substr( get_locale(), 0, 2 );
	if ( $shortLocale != 'en' ) {
		$langDir = 'post-smtp/Postman/languages';
		$success = load_plugin_textdomain( 'post-smtp', false, $langDir );
	}
}
endif;

add_action( 'init', 'post_smtp_load_textdomain' );

if ( $postman_smtp_exist || $required_php_version ) {
	add_action( 'admin_init', 'post_smtp_plugin_deactivate' );

	if ( $postman_smtp_exist ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice' );
	}

	if ( $required_php_version ) {
		add_action( 'admin_notices', 'post_smtp_plugin_admin_notice_version' );
	}
} else {
	post_smtp_start( memory_get_usage() );
}


function post_smtp_plugin_deactivate() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	
	$timestamp = wp_next_scheduled( 'postman_rat_email_report' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'postman_rat_email_report' );
	}

}

function post_smtp_plugin_admin_notice_version() {
	echo '<div class="error">
				<p>
				<strong>Post SMTP</strong> plugin require at least PHP version 5.6, contact to your web hostig support to upgrade.
				</p>
				<p>
				<a href="https://secure.php.net/supported-versions.php">See supported versions on PHP.net</a>
				</p>
				</div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] ); }
}

function post_smtp_plugin_admin_notice() {
		echo '<div class="error"><p><strong>Post SMTP</strong> plugin is a fork (twin brother) of the original Postman SMTP, you must disable Postman SMTP to use this plugin.</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] ); }
}

/**
 * @todo
 */
function post_dismiss_not_configured() {
	?>
	<script>
		(function($) {
			$(document).on('click','.postman-not-configured-notice .notice-dismiss', function(e) {
				e.preventDefault();

				var $this = $(this);
				var args = {
					action: 'dismiss_version_notify',
					security: $('.postman-not-configured-notice').find('.security').val(),
					version: 'not_configured',
				};

				$.post(ajaxurl, args, function() {
					$this.parent().slideUp();
				});
			});
		})(jQuery);
	</script>
<?php
}
add_action( 'admin_footer', 'post_dismiss_not_configured' );

function post_smtp_general_scripts() {
    $localize = include( POST_SMTP_PATH . '/Postman/Localize.php' );
    wp_register_script( 'post-smtp-localize', POST_SMTP_URL . '/script/localize.js', [], false );
    wp_localize_script( 'post-smtp-localize', 'post_smtp_localize', $localize );
    wp_enqueue_script( 'post-smtp-localize' );
    wp_enqueue_script( 'post-smtp-hooks', POST_SMTP_URL . '/script/post-smtp-hooks.js', [], false );
}
add_action( 'admin_enqueue_scripts', 'post_smtp_general_scripts', 8 );

/**
 * Create the main Postman class to start Postman
 *
 * @param mixed $startingMemory
 */
function post_smtp_start( $startingMemory ) {
	post_setupPostman();
	PostmanUtils::logMemoryUse( $startingMemory, 'Postman' );
}

/**
 * Instantiate the mail Postman class
 */
function post_setupPostman() {
	require_once 'Postman/Postman.php';
	$kevinCostner = new Postman( __FILE__, POST_SMTP_VER );
	do_action( 'post_smtp_init');
}

//wizard popup
function wizard_popup(){
	?>
	<div class="ps-pro-popup-overlay">
            <div class="ps-pro-popup-container">
                <div class="ps-pro-popup-outer">
                    <div class="ps-pro-popup-body">
                        <span class="dashicons dashicons-no-alt ps-pro-close-popup"></span>
                        <div class="ps-pro-popup-content">
                            <img src="<?php echo  POST_SMTP_URL . '/Postman/Wizard/assets/images/wizard-gogole.png' ?>" class="ps-pro-for-img" />
                            <h1>Ready to Supercharge Your Emails via <strong>1-Click</strong> Google Mailer Setup?</h1>
                            <h4>Unlock this <strong>Pro Feature NOW</strong> and get a </h4> 
                            <span class="smily">🤩 <strong>HUGE 25% discount! </strong>🤩</span>
                            <div <?php echo postman_is_bfcm() ? 'style="background: url( '.esc_url( POST_SMTP_ASSETS . 'images/bfcm-2024/popup.png' ).' ); background-size: cover; margin: 20px 0 5px 0; padding: 16px 0px; position: relative;"' : 'class="ps-pro-promo-area"'; ?>>   
                                <?php /*
                                if( postman_is_bfcm() ) {
                                    ?>
                                    <p style="color: #fff; font-size: 14px; margin: 0 auto;">
                                        <b style="color: #fbb81f;">24% OFF!</b> BFCM is here - Grab your deal before it's gone!🛍️
                                    </p>
                                    <?php
                                }
                                else {
                                    ?>
                                    <p>
                                        <b>Bonus:</b> Upgrade now and get <span class="ps-pro-discount">25% off</span> on Post SMTP lifetime plans!
                                    </p>
                                    <?php
                                } */
                                ?>
                                <div <?php echo postman_is_bfcm() ? 'style="background: #fbb81f";' : '';  ?> class="ps-pro-coupon">
                                    <b <?php echo postman_is_bfcm() ? 'style="color: #1a3b63";' : '';  ?>>
                                        Use Coupon: <span class="ps-pro-coupon-code"><?php echo postman_is_bfcm() ? 'BFCM2024' : 'GETSMTPPRO'; ?></span> 
                                        <span class="copy-icon ps-click-to-copy">
                                            <svg width="7" height="7" viewBox="0 0 7 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6.1261 6.7273H3.47361C3.25716 6.7273 3.04957 6.64131 2.89651 6.48825C2.74345 6.3352 2.65746 6.12761 2.65746 5.91115V3.25867C2.65746 3.04221 2.74345 2.83462 2.89651 2.68156C3.04957 2.5285 3.25716 2.44252 3.47361 2.44252H6.1261C6.34255 2.44252 6.55015 2.5285 6.7032 2.68156C6.85626 2.83462 6.94225 3.04221 6.94225 3.25867V5.91115C6.94165 6.12742 6.85547 6.33466 6.70254 6.48759C6.54961 6.64052 6.34237 6.7267 6.1261 6.7273ZM3.47361 2.89593C3.42598 2.89593 3.37881 2.90531 3.3348 2.92354C3.29079 2.94177 3.25081 2.96849 3.21712 3.00217C3.18344 3.03586 3.15672 3.07584 3.13849 3.11985C3.12026 3.16386 3.11088 3.21103 3.11088 3.25867V5.91115C3.11088 6.00735 3.1491 6.09961 3.21712 6.16764C3.28515 6.23567 3.37741 6.27388 3.47361 6.27388H6.1261C6.2223 6.27388 6.31456 6.23567 6.38259 6.16764C6.45061 6.09961 6.48883 6.00735 6.48883 5.91115V3.25867C6.48883 3.16246 6.45061 3.0702 6.38259 3.00217C6.31456 2.93415 6.2223 2.89593 6.1261 2.89593H3.47361ZM1.932 4.43755C1.932 4.37742 1.90811 4.31976 1.8656 4.27724C1.82308 4.23472 1.76542 4.21084 1.70529 4.21084H1.41057C1.31437 4.21084 1.22211 4.17262 1.15408 4.1046C1.08605 4.03657 1.04784 3.94431 1.04784 3.84811V1.19562C1.04784 1.09942 1.08605 1.00716 1.15408 0.939131C1.22211 0.871105 1.31437 0.832889 1.41057 0.832889H4.06305C4.11069 0.832889 4.15786 0.842271 4.20187 0.8605C4.24588 0.878729 4.28586 0.905448 4.31955 0.939131C4.35323 0.972814 4.37995 1.0128 4.39818 1.05681C4.41641 1.10082 4.42579 1.14799 4.42579 1.19562V1.49034C4.42579 1.55047 4.44967 1.60813 4.49219 1.65065C4.53471 1.69317 4.59237 1.71705 4.6525 1.71705C4.71262 1.71705 4.77029 1.69317 4.8128 1.65065C4.85532 1.60813 4.8792 1.55047 4.8792 1.49034V1.19562C4.8792 0.979166 4.79322 0.771575 4.64016 0.618517C4.4871 0.46546 4.27951 0.379473 4.06305 0.379473H1.41057C1.1943 0.380071 0.987055 0.46625 0.834127 0.619178C0.681199 0.772106 0.59502 0.97935 0.594421 1.19562V3.84811C0.594421 4.06456 0.680408 4.27215 0.833466 4.42521C0.986524 4.57827 1.19411 4.66426 1.41057 4.66426H1.70529C1.76542 4.66426 1.82308 4.64037 1.8656 4.59785C1.90811 4.55534 1.932 4.49767 1.932 4.43755Z" fill="#5E7CBF"/>
                                            </svg>
                                            </span>
                                    </b>
                                </div>
                                <div id="ps-pro-code-copy-notification">
                                    Code Copied<span class="dashicons dashicons-yes"></span>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo postman_is_bfcm() ? 'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin'; ?>" target="_blank" class="button button-primary ps-yellow-btn ps-pro-product-url">CLAIM 25% OFF NOW <span class="dashicons dashicons-arrow-right-alt2"></span></a>
                            </div>
                           
                            <div>
                                <a href="" class="ps-pro-close-popup" style="color: #6A788B; font-size: 10px; font-size: 12px;">Already purchased?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	<?php
}