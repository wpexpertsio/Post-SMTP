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
			$hostname = PostmanOptions::getInstance()->getHostname();
			$transportType = PostmanOptions::getInstance()->getTransportType();
			$auth_type = PostmanOptions::getInstance()->getAuthenticationType();

			PostmanUtils::registerAdminMenu( $this, 'generateDefaultContent' );
			PostmanUtils::registerAdminMenu( $this, 'addPurgeDataSubmenu' );

			// initialize the scripts, stylesheets and form fields
			add_action( 'admin_init', array( $this, 'registerStylesAndScripts' ), 0 );
			add_action( 'wp_ajax_delete_lock_file', array( $this, 'delete_lock_file' ) );
			add_action( 'wp_ajax_dismiss_version_notify', array( $this, 'dismiss_version_notify' ) );
			add_action( 'wp_ajax_dismiss_donation_notify', array( $this, 'dismiss_donation_notify' ) );
			add_action( 'wp_ajax_ps-discard-less-secure-notification', array( $this, 'discard_less_secure_notification' ) );

			$show_less_secure_notification = get_option( 'ps_hide_less_secure' );

			if( !$show_less_secure_notification && $transportType == 'smtp' && $hostname == 'smtp.gmail.com' && ( $auth_type == 'plain' || $auth_type == 'login' ) ) {
				add_action( 'admin_notices', array( $this, 'google_less_secure_notice' ) );
			}

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
				echo esc_html__('No lock file found.', 'post-smtp' );
				die();
			}

			echo PostmanUtils::deleteLockFile() == true ? esc_html__('Success, try to send test email.', 'post-smtp' ) : esc_html__('Failed, try again.', 'post-smtp' );
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
		 * 
		 * @since 2.1 Added `add_submenu_page`
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
			$mainPostmanSettingsPage = add_menu_page( $pageTitle, $pluginName, Postman::MANAGE_POSTMAN_CAPABILITY_NAME, $uniqueId, $pageOptions, 'dashicons-email' );
			
			//To change the text of top level menu
			add_submenu_page( $uniqueId, $pageTitle, 'Dashboard', Postman::MANAGE_POSTMAN_CAPABILITY_NAME, $uniqueId, $pageOptions );

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
			$page = add_submenu_page( '', sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) ), __( 'Post SMTP', 'post-smtp' ), Postman::MANAGE_POSTMAN_CAPABILITY_NAME, PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG, array(
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
					'bad_response' 			=>	__( 'An unexpected error occurred', 'post-smtp' ),
					'corrupt_response' 		=>	__( 'Unexpected PHP messages corrupted the Ajax response', 'post-smtp' )
			) );

			wp_localize_script( PostmanViewController::POSTMAN_SCRIPT, 'postman_ajax', array(
				'lessSecureNotice'	=>	wp_create_nonce( 'less-secure-security' )
		) );
		}

		/**
		 * Options page callback
		 */
		public function outputDefaultContent() {

			// Set class property
			print '<div class="wrap">';
			print '<div class="ps-main-container-wrap">';

			$version = PostmanState::getInstance()->getVersion();

			printf(
				'<div class="ps-main-header post-smtp-welcome-panel"><h2>%s</h2></div>', 
				esc_html__( 'Post SMTP Setup', 'post-smtp' )
			);

			//Top Notification message
			if( !PostmanPreRequisitesCheck::isReady() ) {

				printf( 
					'<div class="ps-config-bar"><span>%s</span><span style="color: red" class="dashicons dashicons-dismiss"></span></div>', 
					esc_html__( 'Postman is unable to run. Email delivery is being handled by WordPress (or another plugin).', 'post-smtp' ) 
				);

			}
			else {

				$ready_messsage = PostmanTransportRegistry::getInstance()->getReadyMessage();
				$statusMessage = $ready_messsage['message'];

				$transport = PostmanTransportRegistry::getInstance()->getSelectedTransport();

				if ( PostmanTransportRegistry::getInstance()->getActiveTransport()->isConfiguredAndReady() ) {

					if ( $this->options->getRunMode() != PostmanOptions::RUN_MODE_PRODUCTION ) {
						printf( 
							'<div class="ps-config-bar">
								<span>%s</span><span style="color: orange;" class="dashicons dashicons-yes-alt"></span>
							</div>', 
							wp_kses_post( $statusMessage ) 
						);
					} 
					else {
						printf( 
							'<div class="ps-config-bar">
								<span>%s</span><span style="color: green" class="dashicons dashicons-yes-alt"></span>
								<div class="ps-right">
									What\'s Next? Get Started by Sending a Test Email! <a href="%s" class="button button-primary"> Send a Test Email</a>
								</div>
								<div class="clear"></div>
							</div>', 
							wp_kses_post( $statusMessage ),
							esc_url( $this->getPageUrl( PostmanSendTestEmailController::EMAIL_TEST_SLUG ) )
						);
					}
				}
				elseif ( !$transport->has_granted() ) {

					$notice = $transport->get_not_granted_notice();

					printf( 
						'<div class="ps-config-bar">
							<span >%s</span>
							<div class="ps-right">
								<img src="%s" style="vertical-align: middle;width: 30px;" />
								<a href="%s" class="button button-primary">%s</a>
							</div>
						</div>',
						esc_html( $notice['message'] ),
						esc_url( POST_SMTP_ASSETS . 'images/icons/hand.png' ),
						esc_attr( $notice['url'] ),
						esc_html(  $notice['url_text'] )
					);

				}
				else {
					printf( 
						'<div class="ps-config-bar">
							<span >%s</span>
							<span style="color: red" class="dashicons dashicons-dismiss"></span>
							<div class="ps-right">
								%s <a href="%s" class="button button-primary">%s</a>
							</div>
						</div>',
						wp_kses_post( $statusMessage ),
						esc_html__( 'Get Started by Setup Wizard!', 'post-smtp' ),
						esc_attr( $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG ) ),
						esc_html__( 'Start the Wizard', 'post-smtp' )
					);
				}

			}

			//Main Content
			?>
			<div class="ps-flex ps-home-main">
				<div class="ps-setting-box">
					<div>
						<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/configuration.png' ) ?>" />
						<h3 class="ps-ib ps-vm"><?php esc_html_e( 'Configuration', 'post-smtp' ); ?></h3>
					</div> 
					<div class="ps-wizard">
						<a href="<?php esc_attr_e( $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG ) ) ?>" class="button button-primary"><?php esc_html_e( 'Start the Wizard', 'post-smtp' ); ?></a>
						<h4><?php esc_html_e( 'OR', 'post-smtp' ); ?></h4>
						<div>
							<a href="<?php echo esc_url( $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_SLUG ) ) ?>">
								<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />
								<?php esc_html_e( 'Show All Settings', 'post-smtp' ) ?>
							</a>
						</div>
					</div>
				</div>
				<div class="ps-setting-box">
					<img src="<?php echo esc_attr( POST_SMTP_ASSETS . 'images/icons/action.png' ) ?>" />
					<h3 class="ps-ib ps-vm"><?php esc_html_e( 'Actions', 'post-smtp' ); ?></h3>
						<?php
							// Grant permission with Google
							ob_start();
							PostmanTransportRegistry::getInstance()->getSelectedTransport()->printActionMenuItem();
							$oauth_link = ob_get_clean();
							$oauth_link = apply_filters( 'post_smtp_oauth_actions', $oauth_link ); 
							$has_link =  preg_match('/<\s?[^\>]*\/?\s?>/i', $oauth_link );

							if( $has_link ): ?>
								<div>
									<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />
									<?php echo wp_kses_post( $oauth_link ); ?>
								</div>
							<?php endif; ?>
					<div>
						<?php
							if ( PostmanWpMailBinder::getInstance()->isBound() ) {

								echo '
								<div>
									<a href="'.esc_url( $this->getPageUrl( PostmanSendTestEmailController::EMAIL_TEST_SLUG ) ).'">
										<img src="'.esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ).'" width="15" />
										'.esc_html__( 'Send a Test Email', 'post-smtp' ).
									'</a>
								</div>';

							} else {

								echo '
								<div>
									<img src="'.esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ).'" width="15" />
									'.esc_html__( 'Send a Test Email', 'post-smtp' ) .'
								</div>
								';

							}
						?>
					</div>
					<div>
						<?php
							
							$purgeLinkPattern = '
							<a href="%1$s">
								<img src="'.esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ).'" width="15" />
								%2$s
							</a>';

							$importTitle = __( 'Import', 'post-smtp' );
							$exportTile = __( 'Export', 'post-smtp' );
							$resetTitle = __( 'Reset Plugin', 'post-smtp' );
							$importExportReset = sprintf( '%s/%s/%s', $importTitle, $exportTile, $resetTitle );
							
							printf(
								wp_kses_post( $purgeLinkPattern ), 
								esc_url( $this->getPageUrl( PostmanAdminController::MANAGE_OPTIONS_PAGE_SLUG ) ), 
								sprintf( '%s', esc_html( $importExportReset ) ) 
							);
			
							do_action( 'post_smtp_extension_reset_link' );

							if( post_smtp_check_extensions() ) {
					
								$badgesDisplay = "ps-dashboard-pro";
					
							}
							else{
								
								$badgesDisplay = "ps-dashboard-pro-hide";
							}
						?>
					</div>
				</div>
				<div class="ps-setting-box">
					<div>
						<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/extentions.png' ) ?>" />
						<h3 class="ps-ib ps-vm"><?php esc_html_e( 'Extensions', 'post-smtp' ); ?></h3>
					</div> 
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/office-365-extension-for-post-smtp/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" /><?php echo esc_html( 'Microsoft 365/ Office 365' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/post-smtp-extension-for-amazon-ses/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Amazon SES' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/email-log-attachment/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Email Log attachment support' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/reporting-and-tracking-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Report & Tracking' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/advanced-email-delivery/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Advanced Email Delivery & Logs' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/twilio-extension-pro/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Twilio' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/extensions/zoho-mail-pro-extension/?utm_source=plugin&utm_medium=dashboard' ); ?>" target="_blank"><img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	<?php echo esc_html( 'Zoho Mail' ); ?></a>
						<span class="<?php echo $badgesDisplay; ?>">Pro</span>
					</div>
				</div>
				<div class="ps-setting-box">
					<div>
						<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/troubleshooting.png' ) ?>" />
						<h3 class="ps-ib ps-vm"><?php esc_html_e( 'Troubleshooting', 'post-smtp' ); ?></h3>
					</div> 
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/help-configure-post-smtp/' ); ?>" target="_blank" >
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />
							<?php echo esc_html( 'Need help setup everything? (paid)' ); ?>
						</a>
					</div>
					<div>
						<a href="<?php echo $this->getPageUrl( PostmanConnectivityTestController::PORT_TEST_SLUG ); ?>">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Connectivity Test' ); ?>
						</a>
					</div>
					<div>
						<a href="<?php echo $this->getPageUrl( PostmanDiagnosticTestController::DIAGNOSTICS_SLUG ); ?>">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Diagnostic Test' ); ?>
						</a>
					</div>
					<div>
						<a href="#" class="release-lock-file" data-security="<?php esc_attr_e( wp_create_nonce( "postman" ) ); ?>" >
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Release Lock File Error' ); ?>
						</a>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/forums/' ); ?>" target="_blank">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Online Support' ); ?>
						</a>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://www.facebook.com/groups/post.smtp' ); ?>" target="_blank">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Facebook Group' ); ?>
						</a>
					</div>
					<div>
						<a href="<?php echo esc_url( 'https://postmansmtp.com/category/guides/' ); ?>" target="_blank">
							<img src="<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/finger.png' ) ?>" width="15" />	
							<?php echo esc_html( 'Guides' ); ?>
						</a>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="ps-home-middle">
				<div class="ps-home-middle-left">
				<?php
				/**
				 * Fires after the Postman SMTP dashboard configuration.
				 * 
				 * @since 2.5.9.3
				 */
				do_action( 'post_smtp_dashboard_after_config' ); 

				if ( PostmanPreRequisitesCheck::isReady() ) {

					$this->printDeliveryDetails();
					/* translators: where %d is the number of emails delivered */
					print '<p><span>';
					printf( 
						wp_kses_post( _n( 
							'Postman has delivered <span style="color:green">%d</span> email.', 
							'Postman has delivered <span style="color:green">%d</span> emails.', 
							esc_attr( PostmanState::getInstance()->getSuccessfulDeliveries() ) , 'post-smtp' 
						) ), 
						esc_attr( PostmanState::getInstance()->getSuccessfulDeliveries() ) 
					);
					if ( $this->options->isMailLoggingEnabled() ) {
						print ' ';
						printf( 
							wp_kses_post( __( 
								'The last %1$d email attempts are recorded <a href="%2$s">in the log</a>.', 'post-smtp' 
							) ), 
							esc_attr( PostmanOptions::getInstance()->getMailLoggingMaxEntries() ), 
							esc_attr( PostmanUtils::getEmailLogPageUrl() ) 
						);
					}
					print '</span></p>';

				}

				if ( $this->options->isNew() ) {
					printf( 
						'<h3 style="padding-top:10px">%s</h3>', 
						esc_html( 'Thank-you for choosing Postman!', 'post-smtp' ) 
					);
					/* translators: where %s is the URL of the Setup Wizard */
					printf( 
						'<p><span>%s</span></p>', 
						sprintf( 
							wp_kses_post( 'Let\'s get started! All users are strongly encouraged to <a href="%s">run the Setup Wizard</a>.', 'post-smtp' ), 
							esc_url( $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_WIZARD_SLUG ) ) 
						) 
					);
					printf( 
						'<p><span>%s</span></p>', 
						sprintf( 
							wp_kses_post( 'Alternately, <a href="%s">manually configure</a> your own settings and/or modify advanced options.', 'post-smtp' ), 
							esc_attr( $this->getPageUrl( PostmanConfigurationController::CONFIGURATION_SLUG ) ) 
						) 
					);
				} else {
					if ( PostmanState::getInstance()->isTimeToReviewPostman() && ! PostmanOptions::getInstance()->isNew() ) {
						print '</br><hr width="70%"></br>';
						/* translators: where %s is the URL to the WordPress.org review and ratings page */
						printf( 
							'<p>%s <a href="%s">%s</a>%s</p>',
							esc_html__( 'Please consider', 'post-smtp' ),
							esc_url( 'https://wordpress.org/support/plugin/post-smtp/reviews/?filter=5' ),
							esc_html__( 'leaving a review', 'post-smtp' ),
							esc_html( 'to help spread the word! :D', 'post-smtp' )
						);
					}

					printf( 
						esc_html__( '%1$s Postman needs translators! Please take a moment to %2$s translate a few sentences on-line %3$s', 'post-smtp' ),
						'<p><span>',
						'<a href="https://translate.wordpress.org/projects/wp-plugins/post-smtp/stable">',
						'</a> :-)</span></p>'
					);
				}
				printf(
					'<p><span><b style="
					background-color:#2172b3; color: #fff;">%1$s</b>%2$s</span>&nbsp;<a target="_blank" href="%3$s">%4$s</a></p>',
					esc_html__( 'New for v1.9.8!', 'post-smtp' ),
					esc_html__( ' Fallback - setup a second delivery method when the first one is failing', 'post-smtp' ),
					esc_url( 'https://postmansmtp.com/post-smtp-1-9-7-the-smtp-fallback/' ),
					esc_html__( 'Check the detailes here', 'post-smtp')
				);

				?>
				</div>

				<div class="ps-home-middle-right" style="background-image: url(<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/mobile-banner.png' ) ?>)">
					<div class="ps-mobile-notice-content">
						<p><?php _e( 'Introducing NEW Post SMTP Mobile App' ); ?></p>
						<div class="ps-mobile-notice-features">
							<div class="ps-mobile-feature-left">
								<span class="dashicons dashicons-yes-alt"></span>
								Easy Email Tracking
								<br>
								<span class="dashicons dashicons-yes-alt"></span>
								Quickly View Error Details
								<br>
								<span class="dashicons dashicons-yes-alt"></span>
								Easy Email Tracking			
							</div>
							<div class="ps-mobile-feature-right">
								<span class="dashicons dashicons-yes-alt"></span>
								Get Email Preview
								<br>
								<span class="dashicons dashicons-yes-alt"></span>
								Resend Failed Emails
								<br>
								<span class="dashicons dashicons-yes-alt"></span>
								Support multiple sites		
							</div>
						</div>
						<div style="display: flex;">
							<div class="ps-app-download-button">
								<a href="https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Ddashboard%26anid%3Dadmob" target="_blank">Download on Android</a>
							</div>
							<div class="ps-app-download-button">
								<a href="https://apps.apple.com/us/app/post-smtp/id6473368559" target="_blank">Download on iOS</a>
							</div>
						</div>
					</div>
				</div>
				<div style="clear: both"></div>
			</div>
		</div>
	</div>

			<?php

		}

		/**
		 */
		private function printDeliveryDetails() {
			$currentTransport = PostmanTransportRegistry::getInstance()->getActiveTransport();
			$deliveryDetails = $currentTransport->getDeliveryDetails( $this->options );
			printf( 
				'<p><span>%s</span></p>',
				wp_kses_post( $deliveryDetails )  
			);
		}

		/**
		 *
		 * @param mixed $title
		 * @param string  $slug
		 */
		public static function outputChildPageHeader( $title, $slug = '' ) {

			$content = '';
			$content .= sprintf( '<h2>%s</h2>', sprintf( __( '%s Setup', 'post-smtp' ), __( 'Post SMTP', 'post-smtp' ) ) );
			$content .= "
			<div id='postman-main-menu' class='post-smtp-welcome-panel {$slug}'>
				<div class='post-smtp-welcome-panel-content'>
					<div class='welcome-panel-column-container'>
						<div class='welcome-panel-last'>
							<div class='ps-left'>
								<h1>{$title}<h1/>
							</div>";
			$content .= sprintf( '<div class="ps-right"><div class="back-to-menu-link"><a href="%s" class="button button-primary" >%s</a></div></div>', PostmanUtils::getSettingsPageUrl(), _x( 'Back To Main Menu', 'Return to main menu link', 'post-smtp' ) );
			$content .= '
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>';

			echo wp_kses_post( $content );
			
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
			print '<section id="export_settings" class="ps-left">';
			printf( '<h3><span>%s<span></h3>', esc_html( $exportTile ) );
			printf( '<p><span>%s</span></p>', esc_html__( 'Copy this data into another instance of Postman to duplicate the configuration.', 'post-smtp' ) );
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
			printf( 
				'<textarea cols="80" rows="10" class="ps-textarea" readonly="true" name="settings" %s>%s</textarea>', 
				esc_attr( $extraDeleteButtonAttributes ), esc_textarea( $data ) 
			);
			print '</section>';
			print '<section id="import_settings" class="ps-right">';
			printf( 
				'<h3><span>%s<span></h3>', 
				esc_html( $importTitle ) 
			);
			print '<form method="POST" action="' . esc_attr( get_admin_url() ) . 'admin-post.php">';
			wp_nonce_field( PostmanAdminController::IMPORT_SETTINGS_SLUG );
			printf( 
				'<input type="hidden" name="action" value="%s" />', 
				esc_attr( PostmanAdminController::IMPORT_SETTINGS_SLUG  )
			);
			print '<p>';
			printf( 
				'<span>%s</span>',
				esc_html__( 'Paste data from another instance of Postman here to duplicate the configuration.', 'post-smtp' ) 
			);
			if ( PostmanTransportRegistry::getInstance()->getSelectedTransport()->isOAuthUsed( PostmanOptions::getInstance()->getAuthenticationType() ) ) {
				$warning = __( 'Warning', 'post-smtp' );
				$errorMessage = __( 'Using the same OAuth 2.0 Client ID and Client Secret from this site at the same time as another site will cause failures.', 'post-smtp' );
				printf( ' <span><b>%s</b>: %s</span>', esc_html( $warning ), esc_html( $errorMessage ) );
			}
			print '</p>';
			printf( 
				'<textarea cols="80" rows="10" class="ps-textarea" name="settings" %s></textarea>', 
				esc_textarea( $extraDeleteButtonAttributes ) 
			);
			submit_button( __( 'Import', 'post-smtp' ), 'button button-primary', 'import', true, $extraDeleteButtonAttributes );
			print '</form>';
			print '</section>';
			print '<div class="clear"></div>';
			print '<section id="delete_settings">';
			printf( '<h3><span>%s<span></h3>', esc_html( $resetTitle ) );
			print '<form class="post-smtp-reset-options" method="POST" action="' . esc_attr( get_admin_url() ) . 'admin-post.php">';
			wp_nonce_field( PostmanAdminController::PURGE_DATA_SLUG );
			printf( 
				'<input type="hidden" name="action" value="%s" />', 
				esc_attr( PostmanAdminController::PURGE_DATA_SLUG ) 
			);
			printf( 
				'<p><span>%s</span></p><p><span>%s</span></p>',
				esc_html__( 'This will purge all of Postman\'s settings, including account credentials and the email log.', 'post-smtp' ), 
				esc_html__( 'Are you sure?', 'post-smtp' ) 
			);

			printf(
				'<input type="checkbox" name="ps_preserve_email_logs" value="1" checked /> %s',
				esc_html__( 'Preserve my email logs', 'post-smtp' )
			);

			submit_button( $resetTitle, 'delete button button-secondary', 'submit', true );
			print '</form>';
			print '</section>';
			print '</div>';
		}

		public function google_less_secure_notice() {

			?>
			<div class="notice notice-error is-dismissible ps-less-secure-notice">
			<?php 
				printf(
					'<p>
						%1$s
						<a href="%2$s" target="blank">%3$s</a>
						%4$s
						<a href="%5$s" target="blank">%6$s</a>
						%7$s
						<br />
						<a href="%8$s" target="_blank">%9$s</a>
						<br />
						<a href="" id="discard-less-secure-notification">%10$s</a>
					</p>',
					esc_html__( 'To help keep your account secure, Google will no longer support using third-party apps to sign in to your Google Account using only your username and primary password. You can ', 'post-smtp' ),
					esc_url( 'https://postmansmtp.com/gmail-is-disabling-less-secure-apps-feature-soon/' ),
					esc_html__( 'switch to the Auth 2.0', 'post-smtp' ),
					esc_html__( 'alternative or use your ', 'post-smtp' ),
					esc_url( 'https://postmansmtp.com/documentation/#setting-up-an-app-password-in-your-google-account' ),
					esc_html__( 'App Password', 'post-smtp' ),
					esc_html__( 'option to continue.	', 'post-smtp' ),
					esc_url( 'https://postmansmtp.com/gmail-is-disabling-less-secure-apps' ),
					esc_html__( 'Click here for more info', 'post-smtp' ),
					esc_html__( 'I understand and would like to discard this notice', 'post-smtp' )
				);
			?>
			</div>
			<?php

		}

		/**
		 * Discards less secure notification
		 * 
		 * @since 2.1.2
		 * @version 1.0
		 */
		public function discard_less_secure_notification() {

			if( !wp_verify_nonce( $_POST['_wp_nonce'], 'less-secure-security' ) ) {
				die( 'Not Secure.' );
			}

			$result = update_option( 'ps_hide_less_secure', 1 );
			
			if( $result ) {
				wp_send_json_success( 
					array( 'message' => 'Success' ),
					200 
				);
			}

			wp_send_json_error( 
				array( 'message' => 'Something went wrong' ),
				500 
			);

		}
	}
}