<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (! class_exists ( "PostmanDashboardWidgetController" )) {
	
	//
	class PostmanDashboardWidgetController {
		private $rootPluginFilenameAndPath;
		private $options;
		private $authorizationToken;
		private $wpMailBinder;
		
		/**
		 * Start up
		 */
		public function __construct($rootPluginFilenameAndPath, PostmanOptions $options, PostmanOAuthToken $authorizationToken, PostmanWpMailBinder $binder) {
			assert ( ! empty ( $rootPluginFilenameAndPath ) );
			assert ( ! empty ( $options ) );
			assert ( ! empty ( $authorizationToken ) );
			assert ( ! empty ( $binder ) );
			$this->rootPluginFilenameAndPath = $rootPluginFilenameAndPath;
			$this->options = $options;
			$this->authorizationToken = $authorizationToken;
			$this->wpMailBinder = $binder;

			add_action( 'wp_ajax_post_smtp_dash_widget_lite_save_widget_meta', array( $this, 'post_smtp_save_widget_meta_ajax' ) );
			
			add_action ( 'admin_enqueue_scripts', array (
					$this,
					'dasboardWidgetsScripts' 
			) );
			
			add_action ( 'wp_dashboard_setup', array (
					$this,
					'addDashboardWidget' 
			) );
			
			add_action ( 'wp_network_dashboard_setup', array (
					$this,
					'addNetworkDashboardWidget' 
			) );
			
			// dashboard glance mod
			if ($this->options->isMailLoggingEnabled ()) {
				add_filter ( 'dashboard_glance_items', array (
						$this,
						'customizeAtAGlanceDashboardWidget' 
				), 10, 1 );
			}
			
			// Postman API: register the human-readable plugin state
			add_filter ( 'print_postman_status', array (
					$this,
					'print_postman_status' 
			) );
		}

		/**
		 * Save a widget meta for a current user using AJAX.
		 *
		 * @since 1.4.0
		 */
		public function post_smtp_save_widget_meta_ajax() {

			check_admin_referer( 'post_smtp_dash_widget_lite_nonce' );

			// if ( ! current_user_can( post_smtp()->get_capability_manage_options() ) ) {
				// wp_send_json_error();
			// }

			$meta  = ! empty( $_POST['meta'] ) ? sanitize_key( $_POST['meta'] ) : '';
			$value = ! empty( $_POST['value'] ) ? sanitize_key( $_POST['value'] ) : 0;

			$this->post_smtp_widget_meta( 'set', $meta, $value );

			wp_send_json_success();
		}

		/**
		 * Get/set a widget meta.
		 *
		 * @since 1.4.0
		 *
		 * @param string $action Possible value: 'get' or 'set'.
		 * @param string $meta   Meta name.
		 * @param int    $value  Value to set.
		 *
		 * @return mixed
		 */
		protected function post_smtp_widget_meta( $action, $meta, $value = 0 ) {

			$allowed_actions = [ 'get', 'set' ];

			if ( ! in_array( $action, $allowed_actions, true ) ) {
				return false;
			}

			if ( $action === 'get' ) {
				return $this->post_smtp_get_widget_meta( $meta );
			}

			$meta_key = $this->post_smtp_get_widget_meta_key( $meta );
			$value    = sanitize_key( $value );

			if ( 'set' === $action && ! empty( $value ) ) {
				return update_user_meta( get_current_user_id(), $meta_key, $value );
			}

			if ( 'set' === $action && empty( $value ) ) {
				return delete_user_meta( get_current_user_id(), $meta_key );
			}

			return false;
		}

		/**
		 * Get the widget meta value.
		 *
		 * @since 1.4.0
		 *
		 * @param string $meta Meta name.
		 *
		 * @return mixed
		 */
		private function post_smtp_get_widget_meta( $meta ) {

			$defaults = [
				'hide_graph'                      => 0,
				'hide_summary_report_email_block' => 0,
				'hide_email_alerts_banner'        => 0,
			];

			$meta_value = get_user_meta( get_current_user_id(), $this->post_smtp_get_widget_meta_key( $meta ), true );

			if ( ! empty( $meta_value ) ) {
				return $meta_value;
			}

			if ( isset( $defaults[ $meta ] ) ) {
				return $defaults[ $meta ];
			}

			return null;
		}

		/**
		 * Retrieve the meta key.
		 *
		 * @since 1.4.0
		 *
		 * @param string $meta Meta name.
		 *
		 * @return string
		 */
		private function post_smtp_get_widget_meta_key( $meta ) {

			return 'post_smtp_dash_widget_' . $meta;
		}
		
		/**
		 * Add a widget to the dashboard.
		 *
		 * This function is hooked into the 'wp_dashboard_setup' action below.
		 * 
		 * @since 1.4.0
		 */
		public function dasboardWidgetsScripts( $hook ) {
			if ( 'index.php' === $hook ) {
				// Enqueue Chart.js (with the built-in Luxon adapter).
				wp_enqueue_script( 'chart-js', POST_SMTP_URL . '/Postman/Postman-Controller/assets/js/chart.min.js', array(), '', true );

				// Enqueue Moment.js Adapter (chartjs-adapter-moment).
				wp_enqueue_script( 'moment-js', POST_SMTP_URL . '/Postman/Postman-Controller/assets/js/moment.min.js', array( 'chart-js' ), '1.0.0', true );
				// Enqueue your custom script that depends on both Chart.js and Luxon.
				wp_enqueue_script( 'post-smtp-wp-dashboard-widget', POST_SMTP_URL . '/Postman/Postman-Controller/assets/js/post-smtp-wp-dashboard.js', array( 'jquery', 'chart-js', 'moment-js' ), POST_SMTP_VER, true );
				wp_localize_script(
					'post-smtp-wp-dashboard-widget',
					'post_smtp_dashboard_widget',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'slug'     => 'dash_widget_lite',
						'nonce'    => wp_create_nonce( 'post_smtp_dash_widget_lite_nonce' ),
					)
				);
				wp_enqueue_script( 'post-smtp-wp-dashboard-widget' );
				wp_enqueue_style( 'post-smtp-wp-dashboard-widget-style', POST_SMTP_URL . '/Postman/Postman-Controller/assets/css/post-smtp-wp-dashboard-style.css', array(), POST_SMTP_VER, 'all' );
			}
		}
		
		/**
		 * Add a widget to the dashboard.
		 *
		 * This function is hooked into the 'wp_dashboard_setup' action below.
		 */
		public function addDashboardWidget() {
			// only display to the widget to administrator
			if (PostmanUtils::isAdmin ()) {
				wp_add_dashboard_widget ( 'example_dashboard_widget', __ ( 'Postman SMTP', 'post-smtp' ), array (
						$this,
						'printDashboardWidget' 
				) ); // Display function.

				if ( ! post_smtp_has_pro() ) {
					$widget_key = 'post_smtp_reports_widget_lite';
				} else {
					$widget_key = 'post_smtp_reports_widget';
				}
				wp_add_dashboard_widget ( $widget_key, __ ( 'Postman SMTP Stats', 'post-smtp' ), array (
						$this,
						'printStatsDashboardWidget' 
				),
				'high'
				); // Display function.
			}
		}
		
		/**
		 * Add a widget to the network dashboard
		 */
		public function addNetworkDashboardWidget() {
			// only display to the widget to administrator
			if (PostmanUtils::isAdmin ()) {
				wp_add_dashboard_widget ( 'example_dashboard_widget', __ ( 'Postman SMTP', 'post-smtp' ), array (
						$this,
						'printNetworkDashboardWidget' 
				) ); // Display function.
			}
		}
		
		/**
		 * Create the function to output the contents of our Dashboard Widget.
		 */
		public function printDashboardWidget() {
			$goToSettings = sprintf ( '<a href="%s">%s</a>', PostmanUtils::getSettingsPageUrl (), __ ( 'Settings', 'post-smtp' ) );
			$goToEmailLog = sprintf ( '%s', _x ( 'Email Log', 'The log of Emails that have been delivered', 'post-smtp' ) );
			if ($this->options->isMailLoggingEnabled ()) {
				$goToEmailLog = sprintf ( '<a href="%s">%s</a>', PostmanUtils::getEmailLogPageUrl (), $goToEmailLog );
			}
			apply_filters ( 'print_postman_status', null );
			printf ( '<p>%s | %s</p>', $goToEmailLog, $goToSettings );
		}
		
		/**
		 * Create the function to output the contents of our Dashboard Widget.
		 * 
		 * @since 1.4.0
		 */
		public function printStatsDashboardWidget() {
			?>
			<div class="post-smtp-dash-widget post-smtp-dash-widget--lite">
				<div class="post-smtp-dash-widget-chart-block-container">
					<div class="post-smtp-dash-widget-block post-smtp-dash-widget-chart-block">
						<canvas id="post-smtp-dash-widget-chart" width="554" height="291"></canvas>
						<?php if ( ! post_smtp_has_pro() ) { ?>
						<div class="post-smtp-dash-widget-chart-upgrade">
							<div class="post-smtp-dash-widget-modal">
								<a href="#" class="post-smtp-dash-widget-dismiss-chart-upgrade">
									<span class="dashicons dashicons-no-alt"></span>
								</a>
								<h2><?php esc_html_e( 'To get Graphs Insights', 'post-smtp' ); ?></h2>
								<p>
									<a href="<?php echo esc_url( 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wp_dashboard_widget' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound ?>" target="_blank" rel="noopener noreferrer" class="button button-primary post_smtp_pro_btn button-hero">
										<?php esc_html_e( 'Upgrade to Post SMTP Pro ', 'post-smtp' ); ?><span class="dashicons dashicons-arrow-right-alt"></span>
									</a>
								</p>
							</div>
						</div>
						<?php } else if ( ! class_exists( 'Post_SMTP_Report_And_Tracking' ) ) { ?>
						<div class="post-smtp-dash-widget-chart-upgrade">
							<div class="post-smtp-dash-widget-modal">
								<?php $extension_page_url = '<a href="'.PostmanUtils::getPageUrl ( 'post-smtp-pro' ).'" target="_blank" rel="noopener noreferrer">Reporting and Tracking</a>' ?>
								<p>
									<?php printf(
										/* translators: %s is the link text */
										esc_html__( 'Activate %s Extension to get Graphs Insights', 'post-smtp' ),
										$extension_page_url
									); ?>
								</p>
							</div>
						</div>
						<?php } ?>
						<div class="post-smtp-dash-widget-overlay"></div>
					</div>
				</div>
				<div class="post-smtp-dash-widget-block post-smtp-dash-widget-block-settings">
					<div>
						<?php $this->emailTypesSelectHtml(); ?>
						<?php $this->viewFullEmailLogs(); ?>
					</div>
					<div>
						<?php
							$this->TimespanSelectHtml();
							$this->widgetSettingsHtml();
						?>
					</div>
				</div>

				<div id="post-smtp-dash-widget-email-stats-block" class="post-smtp-dash-widget-block post-smtp-dash-widget-email-stats-block">
					<?php $this->emailStatsBlock(); ?>
				</div>
				<div id="post-smtp-dash-widget-upgrade-footer" class="post-smtp-dash-widget-block post-smtp-dash-widget-upgrade-footer post-smtp-dash-widget-upgrade-footer--">
					<p>
						<?php
						printf(
							wp_kses( /* translators: %s - URL to WPMailSMTP.com. */
								__( 'Now Post SMTP is available on your mobile device. <a href="%s" target="_blank" rel="noopener noreferrer">Download now</a>', 'post-smtp' ),
								[
									'a' => [
										'href'   => [],
										'rel'    => [],
										'target' => [],
									],
								]
							),
							// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
							esc_url( "https://postmansmtp.com/post-smtp-mobile-app/" )
						);
						?>
					</p>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Email types select HTML.
		 *
		 * @since 1.4.0
		 */
		private function emailTypesSelectHtml() {

			$options = array(
				'sent_emails'   => esc_html__( 'Sent Emails', 'post-smtp' ),
				'failed_emails' => esc_html__( 'Failed Emails', 'post-smtp' ),
			);
			if ( ! post_smtp_has_pro() ) {
				$disabled = 'disabled';
			} else {
				$disabled = '';
			}
			?>
			<select id="post-smtp-dash-widget-email-type" class="post-smtp-dash-widget-select-email-type" title="<?php esc_attr_e( 'Select email type', 'post-smtp' ); ?>">
				<option value="all_emails">
					<?php esc_html_e( 'All Emails', 'post-smtp' ); ?>
				</option>
				<?php foreach ( $options as $key => $title ) : ?>
					<option value="<?php echo sanitize_key( $key ); ?>" <?php echo $disabled; ?>>
						<?php echo esc_html( $title ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php
		}
		
		/**
		 * Email types select HTML.
		 *
		 * @since 1.4.0
		 */
		private function viewFullEmailLogs() {
			$goToEmailLog = sprintf ( '%s', _x ( 'View Full Log', 'The log of Emails that have been delivered', 'post-smtp' ) );
			if ($this->options->isMailLoggingEnabled ()) {
				$goToEmailLog = sprintf ( '<a href="%s">%s</a>', PostmanUtils::getEmailLogPageUrl (), $goToEmailLog );
			}
			printf ( '<p>%s</p>', $goToEmailLog );
		}
		
		/**
		 * Timespan select HTML.
		 *
		 * @since 1.4.0
		 */
		private function TimespanSelectHtml() {
			// Check if Post SMTP Pro is available, disable options if not
			$disabled = post_smtp_has_pro() ? '' : 'disabled';
			?>
			<select id="post-smtp-dash-widget-timespan" class="post-smtp-dash-widget-select-timespan" title="<?php esc_attr_e( 'Select timespan', 'post-smtp' ); ?>">
				<?php 
					if ( ! post_smtp_has_pro() ) { ?>
						<option value=""><?php esc_html_e( 'Select a timespan', 'post-smtp' ); ?></option>
					<?php } 
					foreach ( [ 7, 14, 30 ] as $option ) : ?>
					<option value="<?php echo absint( $option ); ?>" <?php echo $disabled; ?>>
						<?php /* translators: %d - Number of days. */ ?>
						<?php echo esc_html( sprintf( _n( 'Last %d day', 'Last %d days', absint( $option ), 'post-smtp' ), absint( $option ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Widget settings HTML.
		 *
		 * @since 1.4.0
		 */
		private function widgetSettingsHtml() {
			
			$chart_style = $this->post_smtp_get_widget_meta( 'chart_style' );

			if ( ! post_smtp_has_pro() ) {
				$disabled = 'disabled';
			} else {
				$disabled = '';
			}
			?>
			<div class="post-smtp-dash-widget-settings-container">
				<button id="post-smtp-dash-widget-settings-button" class="post-smtp-dash-widget-settings-button button" type="button">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19 19">
						<path d="M18,11l-2.18,0c-0.17,0.7 -0.44,1.35 -0.81,1.93l1.54,1.54l-2.1,2.1l-1.54,-1.54c-0.58,0.36 -1.23,0.63 -1.91,0.79l0,2.18l-3,0l0,-2.18c-0.68,-0.16 -1.33,-0.43 -1.91,-0.79l-1.54,1.54l-2.12,-2.12l1.54,-1.54c-0.36,-0.58 -0.63,-1.23 -0.79,-1.91l-2.18,0l0,-2.97l2.17,0c0.16,-0.7 0.44,-1.35 0.8,-1.94l-1.54,-1.54l2.1,-2.1l1.54,1.54c0.58,-0.37 1.24,-0.64 1.93,-0.81l0,-2.18l3,0l0,2.18c0.68,0.16 1.33,0.43 1.91,0.79l1.54,-1.54l2.12,2.12l-1.54,1.54c0.36,0.59 0.64,1.24 0.8,1.94l2.17,0l0,2.97Zm-8.5,1.5c1.66,0 3,-1.34 3,-3c0,-1.66 -1.34,-3 -3,-3c-1.66,0 -3,1.34 -3,3c0,1.66 1.34,3 3,3Z"></path>
					</svg>
				</button>
				<div class="post-smtp-dash-widget-settings-menu">
					<div class="post-smtp-dash-widget-settings-menu--style">
						<h4><?php esc_html_e( 'Graph Style', 'post-smtp' ); ?></h4>
						<div>
							<div class="post-smtp-dash-widget-settings-menu-item">
								<input type="radio" id="post-smtp-dash-widget-settings-style-bar" class="post-smtp-dash-widget-settings-style" name="post-smtp-chart-style" value="bar" <?php echo $chart_style == 'bar' ? 'checked' : ''; ?> <?php echo $disabled; ?>>
								<label for="post-smtp-dash-widget-settings-style-bar"><?php esc_html_e( 'Bar', 'post-smtp' ); ?></label>
							</div>
							<div class="post-smtp-dash-widget-settings-menu-item">
								<input type="radio" id="post-smtp-dash-widget-settings-style-line" class="post-smtp-dash-widget-settings-style" name="post-smtp-chart-style" value="line" <?php echo $chart_style == 'line' ? 'checked' : ''; ?> <?php echo $disabled; ?>>
								<label for="post-smtp-dash-widget-settings-style-line"><?php esc_html_e( 'Line', 'post-smtp' ); ?></label>
							</div>
						</div>
					</div>
					<button type="button" class="button post-smtp-dash-widget-settings-menu-save" <?php echo $disabled; ?>><?php esc_html_e( 'Save Changes', 'post-smtp' ); ?></button>
				</div>
			</div>
			<?php
		}

		/**
		 * Email statistics block.
		 *
		 * @since 1.4.0
		 */
		private function emailStatsBlock() {
			
			$logs_query = new PostmanEmailQueryLog;
			$logs = $logs_query->get_logs();

			$total = count( $logs );

			$output_data = array();

			// Initialize the categories
			$output_data['all_emails'] = array(
				'type'  => 'all',
				'icon'  => esc_url( POST_SMTP_URL . "/Postman/Postman-Controller/assets/images/email.svg" ),
				'title' => $total . ' total', // Set the actual total count
			);

			// Initialize counters for sent and failed emails
			$sent_count = 0;
			$failed_count = 0;
			
			if ( post_smtp_has_pro() ){
				$opened_count = 0;
				// $opened_email = new Post_SMTP_New_Dashboard;
				$current_time  = current_time( 'timestamp' );
				$period = 'day';
				$filter = strtotime( 'today', $current_time );
				$opened_count = apply_filters(
					'post_smtp_dashboard_opened_emails_count',
					0,
					array(
						'period'       => $period,
						'current_time' => $current_time,
						'filter'       => $filter,
					)
				);
			}
			// Loop through logs to count sent and failed emails, including fallback sent.
			foreach ( $logs as $log ) {
				if ( isset( $log->success ) ) {
					// Treat normal success and successful fallback ("Sent ( ** Fallback ** )") as sent.
					$success_value = $log->success;

					$is_normal_success = ( 1 === $success_value || '1' === $success_value );
					$is_fallback_sent  = is_string( $success_value ) && 0 === strpos( $success_value, 'Sent ( ** Fallback ** )' );

					if ( $is_normal_success || $is_fallback_sent ) {
						$sent_count++;
					} else {
						// Any other value (including fallback failures) is considered failed.
						$failed_count++;
					}
				}
			}

			// Set sent and failed emails data
			$output_data['sent_emails'] = array(
				'type'  => 'sent',
				'icon'  => esc_url( POST_SMTP_URL . "/Postman/Postman-Controller/assets/images/sent.svg" ),
				'title' => 'Sent '. $sent_count, // Show the actual count of sent emails.
			);

			$output_data['failed_emails'] = array(
				'type'  => 'failed',
				'icon'  => esc_url( POST_SMTP_URL . "/Postman/Postman-Controller/assets/images/failed.svg" ),
				'title' => 'Failed '. $failed_count, // Show the actual count of failed emails.
			);
			if ( post_smtp_has_pro() ){
				$output_data['opened_emails'] = array(
					'type'  => 'opened',
					'icon'  => esc_url( POST_SMTP_URL . "/Postman/Postman-Controller/assets/images/opend.svg" ),
					'title' => 'Opened '. $opened_count, // Show the actual count of failed emails.
				);
			}
			?>

			<table id="post-smtp-dash-widget-email-stats-table" cellspacing="0">
				<tr>
					<?php
					$count   = 0;
					$per_row = 3;
					if ( post_smtp_has_pro() ) {
						$per_row = 4;
					}
					foreach ( array_values( $output_data ) as $stats ) :
						if ( ! is_array( $stats ) ) {
							continue;
						}

						if ( ! isset( $stats['icon'], $stats['title'] ) ) {
							continue;
						}

						// Create new row after every $per_row cells.
						if ( $count !== 0 && $count % $per_row === 0 ) {
							echo '</tr><tr>';
						}

						$count++;
						?>
						<td class="post-smtp-dash-widget-email-stats-table-cell post-smtp-dash-widget-email-stats-table-cell--<?php echo esc_attr( $stats['type'] ); ?> post-smtp-dash-widget-email-stats-table-cell--<?php echo esc_attr( count($output_data) ); ?>">
							<div class="post-smtp-dash-widget-email-stats-table-cell-container">
								<img src="<?php echo esc_url( $stats['icon'] ); ?>" alt="<?php esc_attr_e( 'Table cell icon', 'post-smtp' ); ?>">
								<span>
									<?php echo esc_html( $stats['title'] ); ?>
								</span>
							</div>
						</td>
					<?php endforeach; ?>
				</tr>
			</table>

			<?php
		}

		
		/**
		 * Print the human-readable plugin state
		 */
		public function print_postman_status() {
			if (! PostmanPreRequisitesCheck::isReady ()) {
				printf ( '<p><span style="color:red">%s</span></p>', __ ( 'Error: Postman is missing a required PHP library.', 'post-smtp' ) );
			} else if ($this->wpMailBinder->isUnboundDueToException ()) {
				printf ( '<p><span style="color:red">%s</span></p>', __ ( 'Postman: wp_mail has been declared by another plugin or theme, so you won\'t be able to use Postman until the conflict is resolved.', 'post-smtp' ) );
			} else {
				if ($this->options->getRunMode () != PostmanOptions::RUN_MODE_PRODUCTION) {
					printf ( '<p><span style="background-color:yellow">%s</span></p>', __ ( 'Postman is in <em>non-Production</em> mode and is dumping all emails.', 'post-smtp' ) );
				} else if (PostmanTransportRegistry::getInstance ()->getSelectedTransport ()->isConfiguredAndReady ()) {
					printf ( '<p class="wp-menu-image dashicons-before dashicons-email"> %s </p>', sprintf ( _n ( '<span style="color:green">Postman is configured</span> and has delivered <span style="color:green">%d</span> email.', '<span style="color:green">Postman is configured</span> and has delivered <span style="color:green">%d</span> emails.', PostmanState::getInstance ()->getSuccessfulDeliveries (), 'post-smtp' ), PostmanState::getInstance ()->getSuccessfulDeliveries () ) );
				} else {
					printf ( '<p><span style="color:red">%s</span></p>', __ ( 'Postman is <em>not</em> configured and is mimicking out-of-the-box WordPress email delivery.', 'post-smtp' ) );
				}
				$currentTransport = PostmanTransportRegistry::getInstance ()->getActiveTransport ();
				$deliveryDetails = $currentTransport->getDeliveryDetails ( $this->options );
				printf ( '<p>%s</p>', $deliveryDetails );
			}
		}
		
		/**
		 * Create the function to output the contents of our Dashboard Widget.
		 */
		public function printNetworkDashboardWidget() {
			printf ( '<p class="wp-menu-image dashicons-before dashicons-email"> %s</p>', __ ( 'Postman is operating in per-site mode.', 'post-smtp' ) );
		}
		
		/**
		 * From http://www.hughlashbrooke.com/2014/02/wordpress-add-items-glance-widget/
		 * http://coffeecupweb.com/how-to-add-custom-post-types-to-at-a-glance-dashboard-widget-in-wordpress/
		 *
		 * @param mixed $items        	
		 * @return string
		 */
		function customizeAtAGlanceDashboardWidget($items = array()) {
			// only modify the At-a-Glance for administrators
			if (PostmanUtils::isAdmin ()) {
				$post_types = array (
						PostmanEmailLogPostType::POSTMAN_CUSTOM_POST_TYPE_SLUG 
				);
				
				foreach ( $post_types as $type ) {
					
					if (! post_type_exists ( $type ))
						continue;
					
					$num_posts = wp_count_posts ( $type );
					
					if ($num_posts) {
						
						$published = intval ( $num_posts->publish );
						$privated = intval ( $num_posts->private );
						$post_type = get_post_type_object ( $type );
						
						$text = _n ( '%s ' . $post_type->labels->singular_name, '%s ' . $post_type->labels->name, $privated, 'post-smtp' );
						$text = sprintf ( $text, number_format_i18n ( $privated ) );
						
						$items [] = sprintf ( '<a class="%1$s-count" href="%3$s">%2$s</a>', $type, $text, PostmanUtils::getEmailLogPageUrl () ) . "\n";
					}
				}
				
				return $items;
			}
		}
	}
}