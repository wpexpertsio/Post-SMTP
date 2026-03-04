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
			if ( class_exists( 'Post_SMTP_Report_And_Tracking' ) ) {
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
                       if ( $is_addonactivated && property_exists( $log, 'opened' )  ) {
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

            $body = '<div style=" width: 500px; margin: 0 auto; color: rgba(125, 152, 178, 1); font-size: 12px; font-family: Poppins, sans-serif;">
        <table>
            <tr>
                <td style="padding: 20px 0;text-align: center;">
                    <a href="https://postmansmtp.com"><img src="'.POST_SMTP_ASSETS.'images/reporting/post_logo.png"/></a>
                </td>
            </tr>
            <tr>
                <td style="padding: 20px;background: #F0F6FF;border-radius: 10px;">
                    <h4 style=" margin: 0 0 5px 0;">Hi '.$admin_name.' </h4>
                    <p style=" margin: 0 0 5px 0;">Here is a quick overview of how your emails were performing in the past '.$duration.'</p>
                    <table style=" width: 100%; ">
                        <tr>
                            <td style=" width: 80px;">
                                <div style="text-align: center; padding: 10px 10px; border-radius: 10px; background: #fff; color:#151D48; margin: 0 2px;">
                                    <img src="'.POST_SMTP_ASSETS.'images/reporting/total.png" style="margin: 0 0 5px;width: 40px;height: 40px; "/>
                                    <h5 style="margin:0;font-weight: 400;font-size: 10px;">Total Emails<br> <strong style="font-size: 20px; font-weight: 600;">'.$total.'</strong></h5>
                                </div>
                            </td>
                            <td style=" width: 80px;">
                                <div style=" text-align: center; padding: 10px 10px; border-radius: 10px; background: #fff; color:#151D48; margin: 0 2px;">
                                    <img src="'.POST_SMTP_ASSETS.'images/reporting/sent.png" style="margin: 0 0 5px;width: 40px;height: 40px;"/>
                                    <h5 style="margin:0;font-weight: 400;font-size: 10px;">Sent<br> <strong style="font-size: 20px; font-weight: 600;">'.$sent.'</strong></h5>
                                </div>
                            </td>
                            <td style=" width: 80px;">
                                <div style=" text-align: center; padding: 10px 10px; border-radius: 10px; background: #fff; color:#151D48; margin: 0 2px;">
                                    <img src="'.POST_SMTP_ASSETS.'images/reporting/failed.png" style="margin: 0 0 5px;width: 40px;height: 40px;"/>
                                    <h5 style="margin:0;font-weight: 400;font-size: 10px;">Failed <br> <strong style="font-size: 20px; font-weight: 600;">'.$failed.'</strong></h5>
                                </div>
                            </td>
                            <td style=" width: 80px;">
                                '.($is_addonactivated ? '
                                <div style=" text-align: center; padding: 10px 10px; border-radius: 10px; background: #fff; color:#151D48; margin: 0 2px;">
                                    <img src="'.POST_SMTP_ASSETS.'images/reporting/opened.png" style="margin: 0 0 5px;width: 40px;height: 40px;"/>
                                    <h5 style="margin:0;font-weight: 400;font-size: 10px;">Opened<br><strong style="font-size: 20px; font-weight: 600;">'.$opened.'</strong></h5>
                                </div>
                                ' : '
                                <a href="'.$extension_url.'" style="text-decoration: none;">
                                    <div style="text-align: center; padding: 10px 10px; border-radius: 10px; background: #fbbc1f; color:#fff; margin: 0 2px;">
                                        <img src="'.POST_SMTP_ASSETS.'images/reporting/opend.png" style="margin: 0 0 5px;width: 40px;height: 40px;"/>
                                        <h5 style="margin:0;font-weight: 400;font-size: 10px;">Opened<br>
                                        <img src="'.POST_SMTP_ASSETS.'images/reporting/lock.png" style="margin: 5px 0 0 0;"/>
                                    </div>
                                </a>
                                
                                ').' 
                                
                            </td>
                        </tr>
                        '.($is_addonactivated ? '
                        
                         <tr>
                            <td colspan="4" style=" text-align: center;">
                                <a href="'.$url.'" style=" display: inline-block; background: #375CAF; margin: 20px 0 0; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 100px;">View More Stats</a>
                            </td>
                        </tr>
                        
                        ' : '' ).'
                       
                    </table>
                </td>
            </tr>
            <!---->';

            if(! post_smtp_has_pro()) {
                $body .= '<tr>
                <td>
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 60%;">
                                <div>
                                    <h3 style="color:rgba(33,74,114,1);font-size: 14px;">Unlock the Post SMTP Pro and enhance your email deliverability</h3>
                                    <ul style="margin: 0; padding: 0; list-style: none;">
                                        <li style="margin-bottom: 10px;">
                                            <img src="'.POST_SMTP_ASSETS.'images/reporting/list-icon.png" alt="check" style="width: 14px;margin-bottom: -2px;">
                                                More Pro Mailers 
                                            <img style="margin-bottom: -5px;" src="'.POST_SMTP_ASSETS.'images/reporting/mailers.png" alt="mailers.png">
                                        </li>
                                        <li style="margin-bottom: 10px;">
                                            <img src="'.POST_SMTP_ASSETS.'images/reporting/list-icon.png" alt="check" style="width: 14px;margin-bottom: -2px;">
                                            All mobile app premium features. 
                                        </li>
                                        <li style="margin-bottom: 10px;">
                                           <img src="'.POST_SMTP_ASSETS.'images/reporting/list-icon.png" alt="check" style="width: 14px;margin-bottom: -2px;">
                                            Auto-resend failed emails. 
                                        </li>
                                        <li style="margin-bottom: 10px;">
                                           <img src="'.POST_SMTP_ASSETS.'images/reporting/list-icon.png" alt="check" style="width: 14px;margin-bottom: -2px;">
                                            SMS Failure Notification.
                                        </li>
                                    </ul>
                                    <a href="'.$extension_url.'" style="border: 1px solid rgba(58, 94, 175, 1); color: rgba(58, 94, 175, 1); background: rgba(240, 246, 255, 1); text-decoration: none; padding: 12px 30px; margin: 15px 0; display:inline-block; border-radius: 100px;">Learn more about PRO <img src="'.POST_SMTP_ASSETS.'images/reporting/btn-arrow.png"  style="margin: 0 0 0 5px;"/>
                                    </a>
                                </div>
                            </td>
                            <td style="width: 40%; text-align: center;">
                                <div>
                                    <img src="'.POST_SMTP_ASSETS.'images/reporting/email-fav.png" alt="email-fav.png">
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
           <!---->';
            }
            
            if(!empty($logs)) {
                $logs_html = '';
                $logs_html .= '<!-- loop-->';
                $row = 1;
                foreach ( $logs as $log ) {
                     // Let break if greater than 3.
                    if ( $row > 3 ) {
                        break;
                    } else { 
                        $logs_html .= '
                         <tr style="background: #F0F6FF;">
                            <td style="padding:10px;color:#444a6d;font-size: 12px;font-weight:400;text-align: left;">'.$log->subject.'</td>
                            <td style="padding: 10px;"></td>
                            <td style="padding: 10px;"></td>
                            <td style="padding: 10px;"></td>
                            <td style="padding: 10px;"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="padding: 10px; text-align: center;">'.( isset( $log->total ) ? $log->total : '' ).'</td>
                            <td style="padding: 10px; text-align: center;">'.( isset( $log->sent ) ? $log->sent : '' ).'</td>
                            <td style="padding: 10px; text-align: center;">'.( isset( $log->failed ) ? $log->failed : '' ).'</td>
                            <td style="padding: 10px; text-align: center;">'.( isset( $log->opened ) ? $log->opened : '' ).'</td>
                        </tr>
                        ';
                    }
                    $row ++;
                }
                $logs_html .= '<!-- end loop-->';
            }

            if(empty($log)) {
                  $logs_html = '';
                  $logs_html .= '<tr><td colspan="5">No emails were sent last '.$duration.'</td></tr>';
            }
            
            if($is_addonactivated && ! empty($logs)) {
                $body .= '<tr>
                <td style=" text-align: center;">
                    <h4 style="text-align:center;color:#214A72;font-size:16px;display: inline-block;">
                        <img src="'.POST_SMTP_ASSETS.'images/reporting/dashicons-clock.png" alt="dashicons-clock" style="vertical-align:middle;width: 20px;margin: -4px 0 0 0;"> Last '.$duration.' Top Emails
                    </h4>
                    <table style="width: 100%; border-spacing: 0;">
                        <tr>
                            <td style="width: 50%; color: #151D48; font-size: 14px; font-weight: 600; padding: 10px 0 15px;">
                                Subject
                            </td>
                            <td style="color: #83F5AF; width: 12%;text-align: center; padding: 10px 0 15px;">
                                Total
                            </td>
                            <td style="color: #98B9F9; width: 12%;text-align: center; padding: 10px 0 15px;">
                                Sent
                            </td>
                            <td style="color: #FF955F; width: 12%;text-align: center; padding: 10px 0 15px;">
                                Failed
                            </td>
                             <td style="color: #FFAE3A; width: 12%;text-align: center; padding: 10px 0 15px;">
                                Opened
                            </td>
                        </tr>
                        '.$logs_html.'
                    </table>
                </td>
            </tr>';
            }
            
       $body .='
        <!---->
        <tr>
            <td style="text-align: center;padding: 20px 0;">This email was auto-generated and learn how to <a href="'.$disable_url.'"><strong>disable it</strong></a>
            </td>
        </tr>
       </table>
    </div>
    </body>
</html>';

			return $body;

		}

	}

endif;