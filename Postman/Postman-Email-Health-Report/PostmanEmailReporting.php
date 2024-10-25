<?php

if ( ! class_exists( 'PostmanEmailHealthReporting' ) ) :
	class PostmanEmailHealthReporting {

		/**
		 * Instance of the class
		 *
		 * @var mixed
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		private static $instance = null;

		/**
		 * Get the instance of the class
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public static function get_instance() {

			if ( null == self::$instance ) {

				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Constructor of the class
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function __construct() {

			add_filter( 'post_smtp_admin_tabs', array( $this, 'add_tab' ), 11 );
			add_action( 'post_smtp_settings_menu', array( $this, 'section' ) );
			add_filter( 'post_smtp_sanitize', array( $this, 'sanitize' ), 10, 3 );
		}

		/**
		 * Add tab to Post SMTP Admin | Filter Callback
		 *
		 * @param array $tabs Tabs name.
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function add_tab( $tabs ) {

			$tabs['email_reporting'] = sprintf( '<span class="dashicons dashicons-media-document"></span> %s', __( 'Email Reporting', 'post-smtp' ) );

			return $tabs;
		}

		/**
		 * Sanitize the Settings | Filter Callback
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function sanitize( $input, $option, $section ) {

			$data = array();

			if ( isset( $_POST['_EmailReportingNounce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_EmailReportingNounce'] ) ), '_Reporting' ) ) {

				// Do not save the settings if it's not saving from settings page.
				if ( isset( $_POST['action'] ) && $_POST['action'] !== 'ps-save-wizard' ) {

					$data['enable_email_reporting'] = isset( $_POST['enable_email_reporting'] ) ? 1 : 0;
					$data['reporting_interval'] = isset( $_POST['reporting_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['reporting_interval'] ) ) : 'w';

					update_option( 'postman_rat', $data );
				}
			}
			return $input;
		}


		/**
		 * Section to Display Fields | Actoin Callback
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function section() {

			$data = get_option( 'postman_rat' );
			
			$checked = ( isset( $data['enable_email_reporting'] ) && $data['enable_email_reporting'] === 1 ) ? 'checked' : '';
			$selected_interval = ( isset( $data['reporting_interval'] ) ) ? $data['reporting_interval'] : 'w';
			$selection = array(
				'd' => __( 'Daily', 'post-smtp' ),
				'w' => __( 'Weekly', 'post-smtp' ),
				'm' => __( 'Monthly', 'post-smtp' ),
			);
			?>
			<section id="email_reporting">
				<h2><?php ( class_exists( 'Post_SMTP_Report_And_Tracking' ) ) ? esc_attr_e( 'Email Health Reporting', 'psrat' ) : esc_attr_e( 'Email Health Reporting Lite', 'psrat' ); ?></h2>
				<br>
				<table>
					<tr>
						<td><?php ( class_exists( 'Post_SMTP_Report_And_Tracking' ) ) ? esc_attr_e( 'Enable Email Reporting', 'psrat' ) : esc_attr_e( 'Enable Lite Email Reporting', 'psrat' ); ?></td>
						<td>
							<label class="ps-switch-1">
								<input type="checkbox" name="enable_email_reporting" <?php echo esc_attr( $checked ); ?> />
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<br>
						</td>
					</tr>
					<tr>
						<td><?php esc_attr_e( 'Reporting Interval', 'psrat' ); ?></td>
						<td>
							<select name="reporting_interval">
								<?php
								foreach ( $selection as $key => $value ) {

									$selected = ( $selected_interval == $key ) ? 'selected' : $selected_interval;

									echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr__( $value , 'psrat' ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
				<p>
					<?php esc_attr_e( 'Get a detailed report of your emails, including the number of emails sent, the number of emails opened, the number of emails failed, on interval bases.', 'psrat' ); ?>
				</p>
			</section>
			<?php
			wp_nonce_field( '_Reporting', '_EmailReportingNounce' );
		}
	}
	PostmanEmailHealthReporting::get_instance();

endif;
