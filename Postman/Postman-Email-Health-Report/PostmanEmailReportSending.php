<?php

if ( ! class_exists( 'PostmanEmailReportSending' ) ) :

	class PostmanEmailReportSending {

		/**
		 * Variable for the instance
		 *
		 * @var mixed
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		private static $_instance;

		/**
		 * Get the instance of the class
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public static function get_instance() {

			if ( self::$_instance == null ) {

				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * PostmanEmailReportTemplate constructor.
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'schedule_email_reporting' ) );
			add_action( 'postman_rat_email_report', array( $this,  'handle_email_reporting' ) );
			add_filter( 'cron_schedules', array( $this, 'add_monthly_schedule' ) );
		}


		/**
		 * Schedules the email reporting cron event based on user-defined settings.
		 *
		 * This function retrieves the reporting interval from plugin options and schedules
		 * a WordPress cron job accordingly. If a schedule already exists and its interval
		 * is different from the new one, the existing schedule is unscheduled and a new
		 * schedule is created.
		 * @since 3.0.1
		 * @version 3.0.1
		 */
		public function schedule_email_reporting() {
			$options = get_option( 'postman_rat', array() );
			if ( isset( $options['enable_email_reporting'] ) && $options['enable_email_reporting'] ) {
				$schedules = array(
					'd' => 'daily',
					'w' => 'weekly',
					'm' => 'monthly',
				);

				if ( isset( $options['reporting_interval'] ) && isset( $schedules[ $options['reporting_interval'] ] ) ) {
					$schedule = $schedules[ $options['reporting_interval'] ];

					if ( ! wp_next_scheduled( 'postman_rat_email_report' ) ) {
						wp_schedule_event( current_time( 'timestamp' ), $schedule, 'postman_rat_email_report' );
					}
				}
			}
		}

		/**
		 * Handles the email reporting functionality triggered by the cron job.
		 *
		 * This function checks if email reporting is enabled and retrieves the configured 
		 * reporting interval. If both conditions are met, it triggers the email-sending 
		 * functionality.
		 *  @since 3.0.1
		 *  @version 3.0.1
		 */
		public function handle_email_reporting() {
			$options = get_option( 'postman_rat' );
			$enabled = isset( $options['enable_email_reporting'] ) ? $options['enable_email_reporting'] : false;
			$interval = isset( $options['reporting_interval'] ) ? $options['reporting_interval'] : false;

			if ( $enabled && $interval ) {
				$report_sent = $this->send_mail( $interval );
			}
		}

		/**
		 * Add a custom monthly schedule to WordPress's cron system.
		 *
		 * @param array $schedules The existing cron schedules.
		 * @return array Modified array of cron schedules with 'monthly' added.
		 * @since 3.0.1
		 * @version 3.0.1
		 */
		public function add_monthly_schedule( $schedules ) {
			// Check if textdomain is loaded to avoid early translation loading
			$display_text = 'Once Monthly';

			if ( did_action( 'init' ) && function_exists( 'is_textdomain_loaded' ) && is_textdomain_loaded( 'post-smtp' ) ) {
				$display_text = __( 'Once Monthly', 'post-smtp' );
			}
			
			$schedules['monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => $display_text,
			);
			
			return $schedules;
    	}

		/**
		 * Get total email count
		 *
		 * @param int $from From Time Start.
		 * @param int $to To time end.
		 * @param int $limit Number of rows.
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function get_total_logs( $from = '', $to = '', $limit = '' ) {

			if ( ! class_exists( 'PostmanEmailQueryLog' ) ) {

				include_once POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';
			}
			$ps_query = new PostmanEmailQueryLog();

			$where = ( ! empty( $from ) && ! empty( $to ) ) ? " WHERE pl.time >= {$from} && pl.time <= {$to}" : '';


			$query = "SELECT pl.original_subject AS subject, COUNT( pl.original_subject ) AS total, 
				SUM( pl.success = 1 OR pl.success = 'Sent ( ** Fallback ** )' OR pl.success LIKE '( ** Fallback ** )%' ) As sent, 
				SUM( pl.success != 1 AND pl.success != 'Sent ( ** Fallback ** )' AND pl.success NOT LIKE '( ** Fallback ** )%' ) As failed 
				FROM {$ps_query->table} AS pl";

			/**
			 * Filter to get query from extension
			 *
			 * @since 2.9.0
			 * @version 1.0.0
			 */
			$query = apply_filters( 'postman_health_count', $query );

			$query .= "{$where} GROUP BY pl.original_subject";
			$query .= ! empty( $limit ) ? " LIMIT {$limit}" : '';

			global $wpdb;
			$response = $wpdb->get_results( $query );

			return $response ? $response : false;
		}


		/**
		 * Get the email body
		 *
		 * @param string $interval Time interval.
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function get_body( $interval ) {

			$yesterday = new DateTime( 'yesterday' );
			$yesterday->setTime( 23, 59, 0 );
			$to = strtotime( $yesterday->format( 'Y-m-d H:i:s' ) );
			$from = '';
			$current_time  = current_time( 'timestamp' );
			$duration = '';

			if ( $interval === 'd' ) {
				$from = strtotime( 'today', $current_time );
			}
			if ( $interval === 'w' ) {
				$today  = strtotime( 'today', $current_time );
				$from = strtotime( '-7 days', $today );
			}
			if ( $interval === 'm' ) {
				$today  = strtotime( 'today', $current_time );
				$from = strtotime( '-1 month', $today );
			}

			$logs = $this->get_total_logs( $from, $current_time );

			include_once POST_SMTP_PATH . '/Postman/Postman-Email-Health-Report/PostmanReportTemplate.php';
			$get_body = new PostmanReportTemplate();
			$body = $get_body->reporting_template( $duration, $from, $to, $logs );

			return $body;
		}


		/**
		 * Function to send the report
		 *
		 * @param string $interval Time Interval.
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function send_mail( $interval ) {

			$duration = '';

			if ( $interval === 'd' ) {

				$duration = 'Daily';
			}
			if ( $interval === 'm' ) {

				$duration = 'Monthly';
			}
			if ( $interval === 'w' ) {

				$duration = 'Weekly';
			}

			/**
			 * Filters the site title to be used in the email subject.
			 *
			 * @param string $site_title The site title.
			 * @since 2.9.0
			 * @version 1.0.0
			 */
			$site_title = apply_filters( 'postman_rat_reporting_email_site_title', get_bloginfo( 'name' ) );

			/**
			 * Filters the email address to which the report is sent.
			 *
			 * @param string $to The email address to which the report is sent.
			 * @since 2.9.0
			 * @version 1.0.0
			 */
			$to = apply_filters( 'postman_rat_reporting_email_to', get_option( 'admin_email' ) );

			$subject = "Your {$duration} Post SMTP Report for {$site_title}";
			$body = $this->get_body( $interval );
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			return wp_mail( $to, $subject, $body, $headers );
		}
	}

	PostmanEmailReportSending::get_instance();

endif;
