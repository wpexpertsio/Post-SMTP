<?php

if ( ! class_exists( 'PostmanReportTemplate' ) ) :

	class PostmanReportTemplate {

		/**
		 * Template of the email
		 *
		 * @since 2.9.0
		 * @version 1.0.0
		 */
		public function reporting_template( $duration, $from, $to, $logs ) {

			$is_addonactivated = false;
			if ( is_plugin_active( 'report-and-tracking-addon-premium/post-smtp-report-and-tracking.php' ) ) {

				$is_addonactivated = true;
			}

			$total = 0;
			$sent = 0;
			$failed = 0;
			$opened = 0;

			// lets calculate the total.
			if ( $logs ) {

				foreach ( $logs as $log ) {

					if ( $log->total ) {

						$total += $log->total;
					}
					if ( $log->sent ) {

						$sent += $log->sent;
					}
					if ( $log->failed ) {

						$failed += $log->failed;
					}
					if ( $is_addonactivated && $log->opened ) {

						$opened += $log->opened;
					}
				}
			}

			/**
			 * Filters the email address to which the report is sent.
			 *
			 * @param string $to The email address to which the report is sent.
			 * @since 2.9.0
			 * @version 1.0.0
			 */
			$admin_email = apply_filters( 'postman_rat_reporting_email_to', get_option( 'admin_email' ) );
			$admin_name = '';
			$user = get_user_by( 'email', $admin_email );

			if ( $user ) {

				$admin_name = ! empty( $user->first_name ) ? $user->first_name : $user->user_login;
			}

			/**
			 * Filters the site title to be used in the email subject.
			 *
			 * @param string $site_title The site title.
			 * @since 2.9.0
			 * @version 1.0.0
			 */
			$site_title = apply_filters( 'postman_rat_reporting_email_site_title', get_bloginfo( 'name' ) );
			$url = admin_url( "admin.php?page=post-smtp-email-reporting&from={$from}&to={$to}" );
			$extension_url = 'https://postmansmtp.com/pricing/?utm_source=wordpress&utm_medium=email&utm_campaign=email_report&utm_content=report_and_tracking';
			$disable_url = 'https://postmansmtp.com/pricing/?utm_source=wordpress&utm_medium=email&utm_campaign=email_report&utm_content=email_health_report/';

			$body = '<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <div style="background-color: #f1f1f1; padding: 15px 0; font-family: Arial, Helvetica, sans-serif;">
            <div style="width: 490px; background-color: #ffffff; margin: 0 auto; padding: 1px 0; font-family: Arial, Helvetica, sans-serif;">
                <div style="margin-top: 36px; text-align: center;">
                    <img src="' . POST_SMTP_ASSETS . 'images/reporting/post_logo.png" />
                </div>
                <div style="width: 80%; margin: 0 auto; font-size: 12px; font-weight: 400; line-height: 15px; padding-top: 25px;">
                    Hi ' . $admin_name . '
                    <br>
                    <br>
                    Here is a quick overview of how your emails were performing in the past ' . $duration . '
                </div>
                <div style="width: 80%; margin: 0 auto; margin-top: 20px; display: flex !important;">
                    <div style="display: inline-block; box-sizing: border-box; text-align: center; width: 100px; height: 120px; padding: 10px; border-radius: 5px; margin: 0 2px; background: #eafff2;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; display: inline-block;">
                            <div style="display: inline-block; line-height: 44px;">
                                <img src="' . POST_SMTP_ASSETS . 'images/reporting/total.png" style="width: 40px; height: 40px; border-radius: 50%;" />
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #151D48; padding: 5px;">
                            Total
                        </div>
                        <div style="font-weight: 700; font-size: 27px; color: #151D48;">
                            ' . $total . '
                        </div>
                    </div>
                    <div style="display: inline-block; box-sizing: border-box; text-align: center; width: 100px; height: 120px; padding: 10px; border-radius: 5px; margin: 0 2px; background: #e8eff9;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; display: inline-block;">
                            <div style="display: inline-block; line-height: 44px;">
                                <img src="' . POST_SMTP_ASSETS . 'images/reporting/sent.png" style="width: 40px; height: 40px; border-radius: 50%;" />
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #151D48; padding: 5px;">
                            Sent
                        </div>
                        <div style="font-weight: 700; font-size: 27px; color: #151D48;">
                            ' . $sent . '
                        </div>
                    </div>
                    <div style="display: inline-block; box-sizing: border-box; text-align: center; width: 100px; height: 120px; padding: 10px; border-radius: 5px; margin: 0 2px; background: #ffefe7;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; display: inline-block;">
                            <div style="display: inline-block; line-height: 44px;">
                                <img src="' . POST_SMTP_ASSETS . 'images/reporting/failed.png" style="width: 40px; height: 40px; border-radius: 50%;" />
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #151D48; padding: 5px;">
                            Failed
                        </div>
                        <div style="font-weight: 700; font-size: 27px; color: #151D48;">
                            ' . $failed . '
                        </div>
                    </div>';
			if ( $is_addonactivated ) {
				$body .= '<div style="display: inline-block; box-sizing: border-box; text-align: center; width: 100px; height: 120px; padding: 10px; border-radius: 5px; margin: 0 2px; background: #FFF5E9;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; display: inline-block; background: #FA8900;">
                                <div style="display: inline-block; line-height: 44px;">
                                    <img src="' . POST_SMTP_ASSETS . 'images/reporting/opened-pro.png" style="width: 40px; height: 40px; border-radius: 50%;" />
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #151D48; padding: 5px;">
                                Opened
                            </div>
                            <div style="font-weight: 700; font-size: 27px; color: #151D48;">
                                '. $opened . '
                            </div>
                        </div>';
			} else {
				$body .= '<a href="' . $extension_url . '" target="_blank" style="text-decoration:none;">
                            <div style="display: inline-block; box-sizing: border-box; text-align: center; width: 100px; height: 120px; padding: 10px; border-radius: 5px; margin: 0 2px; background: #ffa41c;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; display: inline-block;">
                                    <div style="display: inline-block; line-height: 44px;">
                                        <img src="' . POST_SMTP_ASSETS . 'images/reporting/opened.png" style="width: 40px; height: 40px; border-radius: 50%;" />
                                    </div>
                                </div>
                                <div style="font-size: 12px; color: white; padding: 5px;">
                                    Opened
                                </div>
                                <div style="font-weight: 700; font-size: 27px; color: #151D48;">
                                    <img src="' . POST_SMTP_ASSETS . 'images/reporting/lock.png" />
                                </div>
                            </div>
                        </a>';
			}
			if ( $is_addonactivated ) {
				$body .= '</div>
                            <div style="width: 110px; background-color: #375CAF; color: white; font-size: 11px; border-radius: 10px; margin-top: 17px; margin-left: 40%; text-align: center; padding: 2px;">
                                <a href="' . $url . '" target="_blank" style="text-decoration: none; color: white;">View More Stats</a>
                            </div>
                            <div style="margin-top: 15px; height: 153px; width: 424px; border: 1px solid #CDCDCD; border-top: none; margin-left: 32px; border-radius: 10px;">
                                <div style="width: 100%; height: 30px; background-color: #3A5EAF; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px; font-size: 12px; display: flex; line-height: 32px;">
                                    <span style="line-height: 20px; margin: 8px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/clock.png"></span> Last ' . $duration . ' top emails
                                </div>';
				if ( ! empty( $logs ) ) {
					$body .= '<div>
                                <table style="width:100%; border-bottom: 1px solid #CDCDCD; border-collapse: collapse;">
                                    <tr>
                                        <td style="font-size: 11px; color: #151D48; line-height: 22px; font-weight: 800; text-align: left; padding-left: 10px; border-bottom: 1px solid #CDCDCD; border-collapse: collapse;">Subject</td>
                                        <td style="font-size: 11px; color: #151D48; line-height: 22px; font-weight: 800; border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center;">Total</td>
                                        <td style="font-size: 11px; color: #151D48; line-height: 22px; font-weight: 800; border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center;">Sent</td>
                                        <td style="font-size: 11px; color: #151D48; line-height: 22px; font-weight: 800; border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center;">Failed</td>
                                        <td style="font-size: 11px; color: #151D48; line-height: 22px; font-weight: 800; border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center;">Opened</td>
                                    </tr>';
					$row = 1;
					foreach ( $logs as $log ) {
						if ( $row > 3 ) break;
						$body .= '<tr>
                                    <td style="text-align: left; padding-left: 10px; border-bottom: 1px solid #CDCDCD; border-collapse: collapse; line-height: 18px; font-size: 10px; color: #444A6D; font-weight: 600;"><div style="white-space: nowrap; width: 100px; overflow: hidden;text-overflow: ellipsis;">' . $log->subject . '</div></td>
                                    <td style="border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center; line-height: 18px; font-size: 10px; color: #444A6D; font-weight: 600;">' . $log->total . '</td>
                                    <td style="border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center; line-height: 18px; font-size: 10px; color: #444A6D; font-weight: 600;">' . $log->sent . '</td>
                                    <td style="border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center; line-height: 18px; font-size: 10px; color: #444A6D; font-weight: 600;">' . $log->failed . '</td>
                                    <td style="border-bottom: 1px solid #CDCDCD; border-collapse: collapse; text-align: center; line-height: 18px; font-size: 10px; color: #444A6D; font-weight: 600;">' . $log->opened . '</td>
                                </tr>';
						$row++;
					}
					$body .= '</table>
                                <div style="width: 110px; background-color: #375CAF; color: white; font-size: 11px; border-radius: 10px; position: relative; margin-left: 10px; margin-top: 8px; padding: 2px; text-align: center;">
                                    <a href="' . $url . '" target="_blank" style="text-decoration: none; color: white;">View More Emails ></a>
                                </div>
                            </div>';
				}
				if ( empty( $logs ) ) {
					$body .= '<div style="text-align: center; margin-top: 45px;">No emails were sent last ' . $duration . '</div>';
				}
				$body .= '</div>
                            <div style="color: #375CAF; font-size: 12px; font-weight: 400; padding: 20px 0; width: 80%; margin: 0 auto;">
                                This email was autogenerated and sent from <a href="' . $url . '" style="text-decoration:none; color: #375CAF;">' . $site_title . '</a>
                            </div>';
			} else {
				$body .= '</div>
                            <div style="width: 80%; margin: 0 auto; margin-top: 25px; height: 187px; border: 1px solid #CDCDCD; border-radius: 10px;">
                                <div style="width: 100%; height: 30px; background-color: #E8EFF9; color: black; border-top-left-radius: 8px; border-top-right-radius: 8px; font-size: 12px; line-height: 32px; text-align:center;">
                                    Unlock the Post SMTP Pro and enhance your email deliverability
                                </div>
                                <div>
                                    <table style="width:100%; margin-top: 20px; border-bottom: none; border-collapse: collapse;">
                                        <tr>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>Open rate email tracking.</td>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>Connect any mailer of your choice.</td>                            
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>Email quota scheduling.</td>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>Multiple email failure alert options.</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>Auto-resend failed emails.</td>
                                            <td style="font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px; border-color: transparent;"><span style="margin-right: 9px;"><img src="' . POST_SMTP_ASSETS . 'images/reporting/okay.png"></span>One-click email attachment resending.</td>
                                        </tr>
                                    </table>
                                    <div style="width: 110px; background-color: #FA8900 !important; color: white; font-size: 11px; border-radius: 10px; margin-left: 35% !important; padding:3px !important; text-align: center;">
                                        <a href="' . $extension_url . '" target="_blank" style="text-decoration: none; color: white;">Upgrade to PRO ></a>
                                    </div>
                                </div>
                            </div>
                            <div style="color: #375CAF; font-size: 12px; font-weight: 400; padding: 20px 0; width: 80%; margin: 0 auto;">
                                This email was auto-generated. You can disable it anytime you want. <a href="' . $disable_url . '" target="_blank" style="color: #375CAF;">Learn how?</a>
                            </div>';
			}
			$body .= '</div>
        </div>
    </body>
</html>';

			return $body;

		}

	}

endif;