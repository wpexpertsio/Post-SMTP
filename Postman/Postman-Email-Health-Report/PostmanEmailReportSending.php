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

			add_action( 'init', array( $this, 'send_report' ) );
		}

		/**
		 * Send the report
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function send_report() {

			$options = get_option( 'postman_rat' );

			$enabled = ( $options && isset( $options['enable_email_reporting'] ) ) ? $options['enable_email_reporting'] : false;

			$interval = ( $options && isset( $options['reporting_interval'] ) ) ? $options['reporting_interval'] : false;

			$has_sent = get_transient( 'ps_rat_has_sent' );

			// If transient expired, let's send :).
			if ( $enabled && $interval && ! $has_sent ) {

				$expiry_time = '';
				$report_sent = $this->send_mail( $interval );

				if ( $report_sent ) {

					if ( $interval === 'd' ) {

						$expiry_time = DAY_IN_SECONDS;
					}
					if ( $interval === 'w' ) {

						$expiry_time = WEEK_IN_SECONDS;
					}
					if ( $interval === 'm' ) {

						$expiry_time = MONTH_IN_SECONDS;
					}

					// Set Future Transient :D.
					set_transient( 'ps_rat_has_sent', '1', $expiry_time );
				}
			}
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


			$query = "SELECT pl.original_subject AS subject, COUNT( pl.original_subject ) AS total, SUM( pl.success = 1 ) As sent, SUM( pl.success != 1 ) As failed FROM {$ps_query->table} AS pl";

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

			$duration = '';

			if ( $interval === 'd' ) {

				$duration = 'day';
				$date = new DateTime( date( 'Y-m-d', $to ) );
				$date->setTime( 23, 59, 0 );
				$from = $date->sub( new DateInterval( 'P1D' ) );
				$from = strtotime( $from->format( 'Y-m-d H:i:s' ) );
			}
			if ( $interval === 'w' ) {

				$duration = 'week';
				$date = new DateTime( date( 'Y-m-d', $to ) );
				$date->setTime( 23, 59, 0 );
				$from = $date->sub( new DateInterval( 'P1W' ) );
				$from = strtotime( $from->format( 'Y-m-d H:i:s' ) );
			}
			if ( $interval === 'm' ) {

				$duration = 'month';
				$date = new DateTime( date( 'Y-m-d', $to ) );
				$date->setTime( 23, 59, 0 );
				$from = $date->sub( new DateInterval( 'P1M' ) );
				$from = strtotime( $from->format( 'Y-m-d H:i:s' ) );
			}

			$logs = $this->get_total_logs( $from, $to, 4 );

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
