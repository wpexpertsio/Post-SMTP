<?php
/**
 * Postman SMTP i18n
 *
 * @package Postman
 */

defined( 'ABSPATH' ) || exit;


return array(
	'ads'           => array(
		'mobileApp'  => array(
			'title'    => __( 'Checkout NEW Post SMTP', 'post-smtp' ),
			'subTitle' => __( 'Mobile App', 'post-smtp' ),
			'list'     => array(
				__( 'Instant notifications for failed emails.', 'post-smtp' ),
				__( 'Resend emails directly from the app.', 'post-smtp' ),
				__( 'Connect with multiple sites.', 'post-smtp' ),
			),
		),
		'mainWP'     => array(
			'title'    => __( 'Easily Manage SMTP Configurations Across All Sites From', 'post-smtp' ),
			'subTitle' => __( 'MainWP Dashboard' ),
			'link'     => __( 'Get The Extension', 'post-smtp' ),
			'list'     => array(
				__( 'One-stop SMTP Configuration', 'post-smtp' ),
				__( 'Backup SMTP Connection', 'post-smtp' ),
				__( 'Instant Email Failure Alerts', 'post-smtp' ),
				__( 'Dedicated Mobile App (Free)', 'post-smtp' ),
				__( 'Comprehensive Email Logging', 'post-smtp' ),
			),
		),
		'spamScore'  => array(
			'title'    => __( 'Check Your Domain', 'post-smtp' ),
			'subTitle' => __( 'Span Score', 'post-smtp' ),
			'link'     => __( 'Give it a try', 'post-smtp' ),
			'list'     => array(
				__( 'Setup SPF, DKIM, and DMARC records for your domain', 'post-smtp' ),
				__( 'Integrate your email service provider account with the website', 'post-smtp' ),
				__( 'Test, analyze, and improve your website’s email deliverability', 'post-smtp' ),
				__( 'Fix spam email for WordPress, WooCommerce, Shopify', 'post-smtp' ),
				__( 'Improve sender score / IP reputation for your domain', 'post-smtp' ),
			),
		),
	),
	'banners'       => array(
		'configured'    => array(
			'text'   => __( 'Post SMTP is configured!', 'post-smtp' ),
			'button' => __( 'Send a test email', 'post-smtp' ),
		),
		'notConfigured' => array(
			'text'   => __( 'Post SMTP is not configured and is mimicking out-of-the-box WordPress email delivery.', 'post-smtp' ),
			'button' => __( 'Setup the wizard', 'post-smtp' ),
		),
		'isLogOnly'     => array(
			'text'   => __( 'Postman is in non-Production mode and is dumping all emails.', 'post-smtp' ),
			'button' => __( 'Setup the wizard', 'post-smtp' ),
		),
	),
	'cards'         => array(
		'total'     => __( 'Total Emails', 'post-smtp' ),
		'success'   => __( 'Successful emails', 'post-smtp' ),
		'failed'    => __( 'Failed emails', 'post-smtp' ),
		'openedPro' => array(
			'title'    => __( 'Opened Emails', 'post-smtp' ),
			'subTitle' => __( 'Unlock with pro', 'post-smtp' ),
		),
		'opened'    => __( 'Opened Emails', 'post-smtp' ),
		'days'      => array(
			__( 'Today', 'post-smtp' ),
			__( 'This week', 'post-smtp' ),
			__( 'This month', 'post-smtp' ),
		),
	),
	'dashboard'     => array(
		'relaunch' => __( 'Relaunch the wizard', 'post-smtp' ),
	),
	'days'          => array(
		__( 'Month', 'post-smtp' ),
		__( 'Week', 'post-smtp' ),
		__( 'Day', 'post-smtp' ),
	),
	'documentation' => array(
		'more'          => __( 'More', 'post-smtp' ),
		'title'         => __( 'Guides and Documentation', 'post-smtp' ),
		'viewAll'       => __( 'View All', 'post-smtp' ),
		'documentation' => array(
			array(
				'title'     => __( 'Getting Started', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'Installation and Activation Guide', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/postman-smtp-documentation/activation-and-installation/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Email Logs', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/postman-smtp-documentation/email-logs/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/postman-smtp-documentation/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
			array(
				'title'     => __( 'Socket', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'Complete Mailer Guide', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/sockets-addons/post-smtp-complete-mailer-guide/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Gmail /Google Workspace With Post SMTP', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/sockets-addons/gmail/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Office/Microsoft 365(PRO) with Post SMTP', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-office-365/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/sockets-addons/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
			array(
				'title'     => __( 'Advance Functionality', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'Email Report and Tracking', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/advance-functionality/report-and-tracking-extension/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Advance Delivery and Logs', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/advance-functionality/advance-delivery-logs/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Email Log Attachment', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/advance-functionality/email-log-attachment/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/advance-functionality/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
			array(
				'title'     => __( 'Post SMTP Mobile App', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'Download the app and connect with plugin', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/post-smtp-mobile-app/download-the-app-and-connect-with-plugin/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Connect and Monitor Multiple WordPress Sites', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/post-smtp-mobile-app/connect-and-monitor-wordpress-multiple-sites-88137/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Resend Failed Email from Mobile App', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/post-smtp-mobile-app/resend-failed-emails-88130/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/post-smtp-mobile-app/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
			array(
				'title'     => __( 'MainWP Extension', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'Configure MainWP Post SMTP Extension', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/mainwp-extension/configure-mainwp-post-smtp-extension/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'Configure MainWP Dashboard for MainWP Post SMTP Extension', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/mainwp-extension/configure-mainwp-dashboard-for-mainwp-post-smtp-extension/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/mainwp-extension/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
			array(
				'title'     => __( 'Tools and Troubleshooting', 'post-smtp' ),
				'links'     => array(
					array(
						'title' => __( 'How to get Diagnostic test report?', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/tools-and-troubleshooting/how-to-get-diagnostic-test-report/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
					array(
						'title' => __( 'How to get a Connectivity test report?', 'post-smtp' ),
						'href'  => 'https://postmansmtp.com/documentation/tools-and-troubleshooting/how-to-get-a-connectivity-test-report/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
					),
				),
				'viewMore' => 'https://postmansmtp.com/documentation/tools-and-troubleshooting/?utm_source=dashboard&utm_medium=documentation&utm_campaign=plugin',
			),
		),
	),
	'logActionMenu' => array(
		'action'      => array(
			'view'       => __( 'View', 'post-smtp' ),
			'resend'     => __( 'Resend', 'post-smtp' ),
			'transcript' => __( 'Transcript', 'post-smtp' ),
			'details'    => __( 'View Details', 'post-smtp' ),
		),
		'headers'     => array(
			'from'        => __( 'From', 'post-smtp' ),
			'to'          => __( 'To', 'post-smtp' ),
			'date'        => __( 'Date', 'post-smtp' ),
			'subject'     => __( 'Subject', 'post-smtp' ),
			'deliveryURI' => __( 'Delivery URI', 'post-smtp' ),
		),
		'resendEmail' => array(
			'emailAddress' => __( 'Email Address', 'post-smtp' ),
			'description'  => __( 'For multiple recipients, separate them with a comma.', 'post-smtp' ),
			'button'       => __( 'Resend', 'post-smtp' ),
		),
	),
	'logs'          => array(
		'title'         => __( 'Recent Logs', 'post-smtp' ),
		'button'        => __( 'View All', 'post-smtp' ),
		'headers'       => array(
			__( 'Subjects', 'post-smtp' ),
			__( 'Sent to', 'post-smtp' ),
			__( 'Delivery time', 'post-smtp' ),
			__( 'Status', 'post-smtp' ),
		),
		'notConfigured' => __( 'Configure Now', 'post-smtp' ),
		'noLogs'        => array(
			'title'       => __( 'No Data Available', 'post-smtp' ),
			'description' => __( 'Once you configured the Post SMTP, the logs will be available here.', 'post-smtp' ),
		),
		'success'       => __( 'Success', 'post-smtp' ),
		'failed'        => __( 'Failed', 'post-smtp' ),
	),
	'notification'  => array(
		'title'       => __( 'Failed email report', 'post-smtp' ),
		'description' => __( 'No Failed notification found', 'post-smtp' ),
	),
	'proFeatures'   => array(
		'title'       => __( 'Pro Features', 'post-smtp' ),
		'description' => __( 'Supercharge your Email', 'post-smtp' ),
		'button'      => __( 'Get Post SMTP Pro', 'post-smtp' ),
		'list'        => array(
			__( "Email Scheduling \r\n Quota Management", 'post-smtp' ),
			__( "Email Report \r\n and Tracking", 'post-smtp' ),
			__( "Email Log \r\n Attachment", 'post-smtp' ),
			__( "SMS \r\n Notification", 'post-smtp' ),
			__( "Auto Resend \r\n Failed Emails", 'post-smtp' ),
			__( "Microsoft 365 / \r\n Office 365", 'post-smtp' ),
			__( "Amazon SES \r\n Support", 'post-smtp' ),
			__( "Zoho Mail \r\n Support", 'post-smtp' ),
		),
	),
	'settings'      => array(
		__( 'Connections', 'post-smtp' ),
		__( 'Fallback', 'post-smtp' ),
		__( 'Message', 'post-smtp' ),
		__( 'Logging', 'post-smtp' ),
		__( 'Advanced', 'post-smtp' ),
		__( 'Notification', 'post-smtp' ),
	),
	'sidebar'       => array(
		'titleTroubleshooting' => __( 'Troubleshooting', 'post-smtp' ),
		'appointment'          => array(
			'description' => __( 'Let Our Experts Handle Your Post SMTP Plugin Setup', 'post-smtp' ),
			'button'      => __( 'Book Now', 'post-smtp' ),
		),
		'troubleshooting'      => array(
			__( 'Send test email', 'post-smtp' ),
			__( 'Spam Score Checker', 'post-smtp' ),
			__( 'Import/Export', 'post-smtp' ),
			__( 'Connectivity test', 'post-smtp' ),
			__( 'Diagnostic test', 'post-smtp' ),
			__( 'Reset plugin', 'post-smtp' ),
		),
	),
);