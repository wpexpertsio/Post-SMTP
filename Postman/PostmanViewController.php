<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( ! class_exists( 'PostmanViewController' ) ) {
	class PostmanViewController {
		private $logger;
		private $rootPluginFilenameAndPath;
		private $options;
		private $authorizationToken;
		private $oauthScribe;
		private $importableConfiguration;
		private $adminController;
		const POSTMAN_MENU_SLUG = 'postman';

		// style sheets and scripts
		const POSTMAN_STYLE = 'postman_style';
		const JQUERY_SCRIPT = 'jquery';
		const POSTMAN_SCRIPT = 'postman_script';

				const BACK_ARROW_SYMBOL = '&#11013;';

		/**
		 * Constructor
		 *
		 * @param PostmanOptions          $options
		 * @param PostmanOAuthToken       $authorizationToken
		 * @param PostmanConfigTextHelper $oauthScribe
		 */
		function __construct( $rootPluginFilenameAndPath, PostmanOptions $options, PostmanOAuthToken $authorizationToken, PostmanConfigTextHelper $oauthScribe, PostmanAdminController $adminController ) {
			$this->options = $options;
			$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
			$this->authorizationToken = $authorizationToken;
			$this->oauthScribe = $oauthScribe;
			$this->adminController = $adminController;
			$this->logger = new PostmanLogger( get_class( $this ) );
			PostmanUtils::registerAdminMenu( $this, 'generateDefaultContent' );
			PostmanUtils::registerAdminMenu( $this, 'addPurgeDataSubmenu' );

			// initialize the scripts, stylesheets and form fields
			add_action( 'admin_init', array( $this, 'registerStylesAndScripts' ), 0 );
			add_action( 'wp_ajax_delete_lock_file', array( $this, 'delete_lock_file' ) );
			add_action( 'wp_ajax_dismiss_version_notify', array( $this, 'dismiss_version_notify' ) );
			add_action( 'wp_ajax_dismiss_donation_notify', array( $this, 'dismiss_donation_notify' ) );

			//add_action( 'admin_init', array( $this, 'do_activation_redirect' ) );

		}


		function dismiss_version_notify() {
            check_admin_referer( 'postsmtp', 'security' );

			$result = update_option('postman_release_version', true );
		}

        function dismiss_donation_notify() {
            check_admin_referer( 'postsmtp', 'security' );

            $result = update_option('postman_dismiss_donation', true );
        }

		function delete_lock_file() {
            check_admin_referer( 'postman', 'security' );

			if ( ! PostmanUtils::lockFileExists() ) {
				echo __('No lock file found.', 'post-smtp' );
				die();
			}

			echo PostmanUtils::deleteLockFile() == true ? __('Success, try to send test email.', 'post-smtp' ) : __('Failed, try again.', 'post-smtp' );
			die();
		}

		function do_activation_redirect() {

			// Bail if no activation redirect
		    if ( ! get_transient( '_post_activation_redirect' ) ) {
				return;
			}

			// Delete the redirect transient
			delete_transient( '_post_activation_redirect' );

			// Bail if activating from network, or bulk
			if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
				return;
			}

			// Bail if the current user cannot see the about page
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Redirect to bbPress about page
			wp_safe_redirect( add_query_arg( array( 'page' => 'post-about' ), admin_url( 'index.php' ) ) );
		}

		public static function getPageUrl( $slug ) {
			return PostmanUtils::getPageUrl( $slug );
		}

		/**
		 * Add options page
		 */
		public function generateDefaultContent() {
			// This page will be under "Settings"
			$pageTitle = sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) );
			$pluginName = __( 'Post SMTP', 'post-smtp' );
			$uniqueId = self::POSTMAN_MENU_SLUG;
			$pageOptions = array(
					$this,
					'outputDefaultContent',
			);
			$mainPostmanSettingsPage = add_menu_page( $pageTitle, $pluginName, Postman::MANAGE_POSTMAN_CAPABILITY_NAME, $uniqueId, $pageOptions );
			// When the plugin options page is loaded, also load the stylesheet
			add_action( 'admin_print_styles-' . $mainPostmanSettingsPage, array(
					$this,
					'enqueueHomeScreenStylesheet',
			) );
		}
		function enqueueHomeScreenStylesheet() {
			wp_enqueue_style( PostmanViewController::POSTMAN_STYLE );
			wp_enqueue_script( PostmanViewController::POSTMAN_SCRIPT );
		}

		/**
		 * Register the Email Test screen
		 */
		public function addPurgeDataSubmenu() {
			$page = add_submenu_page( null, sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) ), __( 'Post SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG, array(
					$this,
					'outputPurgeDataContent',
			) );
			// When the plugin options page is loaded, also load the stylesheet
			add_action( 'admin_print_styles-' . $page, array(
					$this,
					'enqueueHomeScreenStylesheet',
			) );
		}

		/**
		 * Register and add settings
		 */
		public function registerStylesAndScripts() {
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'registerStylesAndScripts()' );
			}
			// register the stylesheet and javascript external resources
			$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
			wp_register_style( PostmanViewController::POSTMAN_STYLE, plugins_url( 'style/postman.css', $this->rootPluginFilenameAndPath ), null, $pluginData ['version'] );
			wp_register_style( 'jquery_ui_style', plugins_url( 'style/jquery-steps/jquery-ui.css', $this->rootPluginFilenameAndPath ), PostmanViewController::POSTMAN_STYLE, '1.1.0' );
			wp_register_style( 'jquery_steps_style', plugins_url( 'style/jquery-steps/jquery.steps.css', $this->rootPluginFilenameAndPath ), PostmanViewController::POSTMAN_STYLE, '1.1.0' );

			wp_register_script( PostmanViewController::POSTMAN_SCRIPT, plugins_url( 'script/postman.js', $this->rootPluginFilenameAndPath ), array(
					PostmanViewController::JQUERY_SCRIPT,
				'jquery-ui-core',
				'jquery-ui-datepicker',
			), $pluginData ['version'] );
			wp_register_script( 'sprintf', plugins_url( 'script/sprintf/sprintf.min.js', $this->rootPluginFilenameAndPath ), null, '1.0.2' );
			wp_register_script( 'jquery_steps_script', plugins_url( 'script/jquery-steps/jquery.steps.min.js', $this->rootPluginFilenameAndPath ), array(
					PostmanViewController::JQUERY_SCRIPT
			), '1.1.0' );
			wp_register_script( 'jquery_validation', plugins_url( 'script/jquery-validate/jquery.validate.min.js', $this->rootPluginFilenameAndPath ), array(
					PostmanViewController::JQUERY_SCRIPT
			), '1.13.1' );

			wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_ajax_msg', array(
					'bad_response' => __( 'An unexpected error occurred', 'post-smtp' ),
					'corrupt_response' => __( 'Unexpected PHP messages corrupted the Ajax response', 'post-smtp' ),
			) );

			wp_localize_script( 'jquery_steps_script', 'steps_current_step', 'steps_current_step' );
			wp_localize_script( 'jquery_steps_script', 'steps_pagination', 'steps_pagination' );
			wp_localize_script( 'jquery_steps_script', 'steps_finish', _x( 'Finish', 'Press this button to Finish this task', 'post-smtp' ) );
			wp_localize_script( 'jquery_steps_script', 'steps_next', _x( 'Next', 'Press this button to go to the next step', 'post-smtp' ) );
			wp_localize_script( 'jquery_steps_script', 'steps_previous', _x( 'Previous', 'Press this button to go to the previous step', 'post-smtp' ) );
			wp_localize_script( 'jquery_steps_script', 'steps_loading', 'steps_loading' );
		}

		/**
		 * Options page callback
		 */
		public function outputDefaultContent() {
			// Set class property
			print '<div class="wrap">';
			$this->displayTopNavigation();
			if ( ! PostmanPreRequisitesCheck::isReady() ) {
				printf( '<p><span style="color:red; padding:2px 0; font-size:1.1em">%s</span></p>', __( 'Postman is unable to run. Email delivery is being handled by WordPress (or another plugin).', 'post-smtp' ) );
			} else {
				$ready_messsage = PostmanTransportRegistry::getInstance()->getReadyMessage();
				$statusMessage = $ready_messsage['message'];
				if ( PostmanTransportRegistry::getInstance()->getActiveTransport()->isConfiguredAndReady() ) {
					if ( $this->options->getRunMode() != PostmanOptions::RUN_MODE_PRODUCTION ) {
						printf( '<p><span style="background-color:yellow">%s</span></p>', $statusMessage );
					} else {
						printf( '<p><span style="color:green;padding:2px 0; font-size:1.1em">%s</span></p>', $statusMessage );
					}
				} else {
					printf( '<p><span style="color:red; padding:2px 0; font-size:1.1em">%s</span></p>', $statusMessage );
				}
				$this->printDeliveryDetails();
				/* translators: where %d is the number of emails delivered */
				print '<p style="margin:10px 10px"><span>';
				printf( _n( 'Postman has delivered <span style="color:green">%d</span> email.', 'Postman has delivered <span style="color:green">%d</span> emails.', PostmanState::getInstance()->getSuccessfulDeliveries(), 'post-smtp' ), PostmanState::getInstance()->getSuccessfulDeliveries() );
				if ( $this->options->isMailLoggingEnabled() ) {
					print ' ';
					printf( __( 'The last %d email attempts are recorded <a href="%s">in the log</a>.', 'post-smtp' ), PostmanOptions::getInstance()->getMailLoggingMaxEntries(), PostmanUtils::getEmailLogPageUrl() );
				}
				print '</span></p>';
			}
			if ( $this->options->isNew() ) {
				printf( '<h3 style="padding-top:10px">%s</h3>', __( 'Thank-you for choosing Postman!', 'post-smtp' ) );
				/* translators: where %s is the URL of the Setup Wizard */
				printf( '<p><span>%s</span></p>', sprintf( __( 'Let\'s get started! All users are strongly encouraged to <a href="%s">run the Setup Wizard</a>.', 'post-smtp' ), $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG ) ) );
				printf( '<p><span>%s</span></p>', sprintf( __( 'Alternately, <a href="%s">manually configure</a> your own settings and/or modify advanced options.', 'post-smtp' ), $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_SLUG ) ) );
			} else {
				if ( PostmanState::getInstance()->isTimeToReviewPostman() && ! PostmanOptions::getInstance()->isNew() ) {
					print '</br><hr width="70%"></br>';
					/* translators: where %s is the URL to the WordPress.org review and ratings page */
					printf( '%s</span></p>', sprintf( __( 'Please consider <a href="%s">leaving a review</a> to help spread the word! :D', 'post-smtp' ), 'https://wordpress.org/support/view/plugin-reviews/post-smtp?filter=5' ) );
				}
				printf( '<p><span>%s :-)</span></p>', sprintf( __( 'Postman needs translators! Please take a moment to <a href="%s">translate a few sentences on-line</a>', 'post-smtp' ), 'https://translate.wordpress.org/projects/wp-plugins/post-smtp/stable' ) );
			}
			printf(
			        '<p><span>%s</span>&nbsp;<a target="_blank" href="%s">%s</a></p>',
                    __( '<b style="background-color:yellow">New for v1.9.8!</b> Fallback - setup a second delivery method when the first one is failing', 'post-smtp' ),
                    'https://postmansmtp.com/post-smtp-1-9-7-the-smtp-fallback/',
                    __( 'Check the detailes here', 'post-smtp')
            );
		}

		/**
		 */
		private function printDeliveryDetails() {
			$currentTransport = PostmanTransportRegistry::getInstance()->getActiveTransport();
			$deliveryDetails = $currentTransport->getDeliveryDetails( $this->options );
			printf( '<p style="margin:0 10px"><span>%s</span></p>', $deliveryDetails );
		}

		/**
		 *
		 * @param mixed $title
		 * @param string  $slug
		 */
		public static function outputChildPageHeader( $title, $slug = '' ) {
			printf( '<h2>%s</h2>', sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) ) );
			printf( '<div id="postman-main-menu" class="welcome-panel %s">', $slug );
			print '<div class="welcome-panel-content">';
			print '<div class="welcome-panel-column-container">';
			print '<div class="welcome-panel-column welcome-panel-last">';
			printf( '<h4>%s</h4>', $title );
			print '</div>';
			printf( '<p id="back_to_main_menu">%s <a id="back_to_menu_link" href="%s">%s</a></p>', self::BACK_ARROW_SYMBOL, PostmanUtils::getSettingsPageUrl(), _x( 'Back To Main Menu', 'Return to main menu link', 'post-smtp' ) );
			print '</div></div></div>';
		}

		/**
		 */
		public function outputPurgeDataContent() {
			$importTitle = __( 'Import', 'post-smtp' );
			$exportTile = __( 'Export', 'post-smtp' );
			$resetTitle = __( 'Reset Plugin', 'post-smtp' );
			$options = $this->options;
			print '<div class="wrap">';
			PostmanViewController::outputChildPageHeader( sprintf( '%s/%s/%s', $importTitle, $exportTile, $resetTitle ) );
			print '<section id="export_settings">';
			printf( '<h3><span>%s<span></h3>', $exportTile );
			printf( '<p><span>%s</span></p>', __( 'Copy this data into another instance of Postman to duplicate the configuration.', 'post-smtp' ) );
			$data = '';
			if ( ! PostmanPreRequisitesCheck::checkZlibEncode() ) {
				$extraDeleteButtonAttributes = sprintf( 'disabled="true"' );
				$data = '';
			} else {
				$extraDeleteButtonAttributes = '';
				if ( ! $options->isNew() ) {
					$data = $options->export();
				}
			}
			printf( '<textarea cols="80" rows="5" readonly="true" name="settings" %s>%s</textarea>', $extraDeleteButtonAttributes, $data );
			print '</section>';
			print '<section id="import_settings">';
			printf( '<h3><span>%s<span></h3>', $importTitle );
			print '<form method="POST" action="' . get_admin_url() . 'admin-post.php">';
			wp_nonce_field( PostmanAdminController::IMPORT_SETTINGS_SLUG );
			printf( '<input type="hidden" name="action" value="%s" />', PostmanAdminController::IMPORT_SETTINGS_SLUG );
			print '<p>';
			printf( '<span>%s</span>', __( 'Paste data from another instance of Postman here to duplicate the configuration.', 'post-smtp' ) );
			if ( PostmanTransportRegistry::getInstance()->getSelectedTransport()->isOAuthUsed( PostmanOptions::getInstance()->getAuthenticationType() ) ) {
				$warning = __( 'Warning', 'post-smtp' );
				$errorMessage = __( 'Using the same OAuth 2.0 Client ID and Client Secret from this site at the same time as another site will cause failures.', 'post-smtp' );
				printf( ' <span><b>%s</b>: %s</span>', $warning, $errorMessage );
			}
			print '</p>';
			printf( '<textarea cols="80" rows="5" name="settings" %s></textarea>', $extraDeleteButtonAttributes );
			submit_button( __( 'Import', 'post-smtp' ), 'primary', 'import', true, $extraDeleteButtonAttributes );
			print '</form>';
			print '</section>';
			print '<section id="delete_settings">';
			printf( '<h3><span>%s<span></h3>', $resetTitle );
			print '<form class="post-smtp-reset-options" method="POST" action="' . get_admin_url() . 'admin-post.php">';
			wp_nonce_field( PostmanAdminController::PURGE_DATA_SLUG );
			printf( '<input type="hidden" name="action" value="%s" />', PostmanAdminController::PURGE_DATA_SLUG );
			printf( '<p><span>%s</span></p><p><span>%s</span></p>', __( 'This will purge all of Postman\'s settings, including account credentials and the email log.', 'post-smtp' ), __( 'Are you sure?', 'post-smtp' ) );
			$extraDeleteButtonAttributes = 'style="background-color:red;color:white"';
			if ( $this->options->isNew() ) {
				$extraDeleteButtonAttributes .= ' disabled="true"';
			}
			submit_button( $resetTitle, 'delete', 'submit', true, $extraDeleteButtonAttributes );
			print '</form>';
			print '</section>';
			print '</div>';
		}

		/**
		 */
		private function displayTopNavigation() {
			$version = PostmanState::getInstance()->getVersion();
			$show = get_option('postman_release_version' );
			printf( '<h2>%s</h2>', sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) ) );

			if ( ! $show && POST_SMTP_SHOW_RELEASE_MESSAGE ) {
				echo '
				<div class="updated settings-error notice is-dismissible"> 
					<p>
					<strong>Version ' . $version . ' ' . POST_SMTP_RELEASE_MESSAGE . ':</strong> <a target="_blank" href="' . POST_SMTP_RELEASE_URL . '">Read Here</a>
					</p>
					<button style="z-index: 100;" data-version="'. $version . '" data-security="' . wp_create_nonce('postsmtp') .'" type="button" class="notice-dismiss postman-release-message">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>';
			}

            include_once POST_SMTP_PATH . '/Postman/extra/donation.php';

            echo '<div class="twitter-wrap">';
			    print '<div id="postman-main-menu" class="welcome-panel">';
                print '<div class="welcome-panel-content">';
                print '<div class="welcome-panel-column-container">';
                print '<div class="welcome-panel-column">';
                printf( '<h4>%s</h4>', __( 'Configuration', 'post-smtp' ) );
                printf( '<a class="button button-primary button-hero" href="%s">%s</a>', $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG ), __( 'Start the Wizard', 'post-smtp' ) );
                printf( '<p class="">%s <a href="%s" class="configure_manually">%s</a></p>', __( 'or', 'post-smtp' ), $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_SLUG ), __( 'Show All Settings', 'post-smtp' ) );
                print '</div>';
                print '<div class="welcome-panel-column">';
                printf( '<h4>%s</h4>', _x( 'Actions', 'Main Menu', 'post-smtp' ) );
                print '<ul>';

                // Grant permission with Google
                ob_start();
                PostmanTransportRegistry::getInstance()->getSelectedTransport()->printActionMenuItem();
                $oauth_link = ob_get_clean();

                echo apply_filters( 'post_smtp_oauth_actions', $oauth_link );

                if ( PostmanWpMailBinder::getInstance()->isBound() ) {
                    printf( '<li><a href="%s" class="welcome-icon send_test_email">%s</a></li>', $this->getPageUrl( PostmanSendTestEmailController::EMAIL_TEST_SLUG ), __( 'Send a Test Email', 'post-smtp' ) );
                } else {
                    printf( '<li><div class="welcome-icon send_test_email">%s</div></li>', __( 'Send a Test Email', 'post-smtp' ) );
                }

                // import-export-reset menu item
                if ( ! $this->options->isNew() || true ) {
                    $purgeLinkPattern = '<li><a href="%1$s" class="welcome-icon oauth-authorize">%2$s</a></li>';
                } else {
                    $purgeLinkPattern = '<li>%2$s</li>';
                }
                $importTitle = __( 'Import', 'post-smtp' );
                $exportTile = __( 'Export', 'post-smtp' );
                $resetTitle = __( 'Reset Plugin', 'post-smtp' );
                $importExportReset = sprintf( '%s/%s/%s', $importTitle, $exportTile, $resetTitle );
                printf( $purgeLinkPattern, $this->getPageUrl( PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG ), sprintf( '%s', $importExportReset ) );
                print '</ul>';
                print '</div>';
                print '<div class="welcome-panel-column welcome-panel-last">';
                printf( '<h4>%s</h4>', _x( 'Troubleshooting', 'Main Menu', 'post-smtp' ) );
                print '<ul>';
                printf( '<li><a href="%s" class="welcome-icon run-port-test">%s</a></li>', $this->getPageUrl( PostmanConnectivityTestController::PORT_TEST_SLUG ), __( 'Connectivity Test', 'post-smtp' ) );
                printf( '<li><a href="%s" class="welcome-icon run-port-test">%s</a></li>', $this->getPageUrl( PostmanDiagnosticTestController::DIAGNOSTICS_SLUG ), __( 'Diagnostic Test', 'post-smtp' ) );
                printf( '<li><a href="%s" data-security="%s" class="welcome-icon release-lock-file">%s</a></li>', '#', wp_create_nonce( "postman" ), __( 'Release Lock File Error', 'post-smtp' ) );
                printf( '<li><a href="https://wordpress.org/support/plugin/post-smtp/" class="welcome-icon postman_support">%s</a></li>', __( 'Online Support', 'post-smtp' ) );
                printf( '<li><img class="align-middle" src="' . plugins_url( 'style/images/new.gif', dirname( __DIR__ ) . '/postman-smtp.php' ) . '"><a target="blank" class="align-middle" href="https://postmansmtp.com/category/guides/" class="welcome-icon postman_guides">%s</a></li>', __( 'Guides', 'post-smtp' ) );
                print '</ul></div></div></div></div>';
                ?>
            </div>
            <?php
		}
	}
}

