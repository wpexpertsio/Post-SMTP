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
			$extension_url = 'https://postmansmtp.com/extensions/reporting-and-tracking-extension/';
			$disable_url = 'https://postmansmtp.com/documentation/advance-functionality/email-health-report/';

			$body = "
        <!DOCTYPE html>
<html>

<head>
    <style>
        .container {
            width: 100%;
                    height: 600px;
                    background-color: #f1f1f1;
                    display: flex;
                    align-items: start;
                    justify-content: center;
                    padding: 15px 0;

        }

        .main {
            width: 490px;
            height: 100%;
            font-family: 'Inter';
            background-color: white;
            margin: 0 auto;

        }

        .logo {
            margin-top: 36px;
            text-align: center;

        }

        .text {

            font-size: 12px;
            font-weight: 400;
            line-height: 15px;
            text-align: left;
            margin-top: 32px;
            height: 60px;
            width: 424px;
            margin-left: 38px;

        }

        .cards {

            margin-top: 20px;
            Width: 424px;
            Height: 120px;
            margin-left: 36px;
        }

        .inner-cards {
            display: inline-block;
            box-sizing: border-box;
            text-align: center;
            width: 100px;
            height: 120px;
            padding: 10px;
            border-radius: 5px;
        }

        .total {

            background: #EAFFF2;
            border: 1px solid #26E271;

        }

        .sent {

            background: #E8EFF9;
            border: 1px solid #B8C7E4;

        }

        .failed {

            background: #FFEFE7;
            border: 1px solid #E8AF92;

        }

        .opened-pro {

            background: #FFF5E9;
            border: 1px solid #FA8900;
        }

        .opened{
            background: #FA8900;
        }

        .txt {

            font-size: 12px;
            color: #151D48;
            padding: 5px;
        }

        .count {

            font-weight: 700;
            font-size: 27px;
            color: #151D48;
        }

        .ellipse {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-block;
        }

        .ellipse-total {

            background: #26E271;

        }

        .ellipse-sent {

            background: #B8C7E4;

        }

        .ellipse-failed {

            background: #E8AF92;

        }

        .ellipse-opened {

            background: #FFF5E9;

        }
        .ellipse-opened-pro {

            background: #FA8900;

        }

        .icon {

            width: 24px;
            height: 24px;
            display: inline-block;
            line-height: 44px;

        }

        .btn {

            width: 110px;
            background-color: #375CAF;
            color: white;
            font-size: 11px;
            border-radius: 10px;
            margin-top: 17px;
            margin-left: 40%;
            text-align: center;
            padding: 2px;
        }

        .btn a {
            text-decoration: none;
            color: white;
        }

        .table-display {
            margin-top: 15px;
            height: 153px;
            width: 424px;
            border: 1px solid #CDCDCD;
            border-top: none;
            margin-left: 32px;
            border-radius: 10px;
        }

        .table-display-free{
            margin-top: 25px;
            height: 187px;
            width: 424px;
            border: 1px solid #CDCDCD;
            border-top: none;
            margin-left: 32px;
            border-radius: 10px;

        }

        .table-header {
            width: 100%;
            height: 30px;
            background-color: #3A5EAF;
            color: white;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-size: 12px;
            display: flex;
            line-height: 32px;
        }

        .table-header span {
            line-height: 20px;
            margin: 8px;
        }

        .table-header-free{
            width: 100%;
            height: 30px;
            background-color: #E8EFF9;
            color: black;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-size: 12px;
            line-height: 32px;
            text-align:center;
        }

        table,
        td,
        th {
            border-bottom: 1px solid #CDCDCD;
            border-collapse: collapse;
            text-align: center;
        }
        table td{
            line-height: 18px;
            font-size: 10px;
            color: #444A6D;
            font-weight: 600;
        }
        .heading{
                font-size: 11px;
                color: #151D48;
                line-height: 22px;
                font-weight: 800;
        }
        .bottom-text{
            width: 312px;
            height: 17px;
            color: #375CAF;
            font-size: 12px;
            font-weight: 400;
           margin-left: 16%;
           margin-top: 20px;
        }

        .wrap-text{
            white-space: nowrap; 
            width: 100px; 
            overflow: hidden;
            text-overflow: ellipsis;
        }

    </style>
</head>

<body>
    <div class='container'>
        <div class='main'>
            <div class='logo'>
            <img src='" . POST_SMTP_ASSETS . "images/reporting/post_logo.png' />
            </div>
            <div class='text'>
            Hi {$admin_name}
                <br>
                <br>
                Here is a quick overview of how your emails were performing in the past {$duration}

            </div>
            <div class='cards'>
                <div class='total inner-cards'>

                    <div class='ellipse ellipse-total'>

                        <div class='icon' style='line-height: 53px;'>
                        <img src='" . POST_SMTP_ASSETS . "images/reporting/total.png' />
                        </div>

                    </div>

                    <div class='txt'>
                        Total Emails
                    </div>
                    <div class='count'>
                    {$total}
                    </div>
                </div>
                <div class='sent inner-cards'>
                    <div class='ellipse ellipse-sent'>

                        <div class='icon'>
                        <img src='" . POST_SMTP_ASSETS . "images/reporting/sent.png' />
                        </div>

                    </div>

                    <div class='txt'>
                        Sent
                    </div>
                    <div class='count'>
                    {$sent}
                    </div>
                </div>
                <div class='failed inner-cards'>

                    <div class='ellipse ellipse-failed'>

                        <div class='icon' >
                        <img src='" . POST_SMTP_ASSETS . "images/reporting/failed.png' />
                        </div>

                    </div>

                    <div class='txt'>
                        Failed
                    </div>
                    <div class='count'>
                    {$failed}
                    </div>

                </div>";

			if ( $is_addonactivated ) {

				$body .= "

                    <div class='opened-pro inner-cards'>
        
                    <div class='ellipse ellipse-opened-pro'>

                        <div class='icon' style='line-height: 53px;'>
                            <img src='" . POST_SMTP_ASSETS . "images/reporting/opened-pro.png' />
                        </div>

                    </div>
        
                            <div class='txt'>
                                Opened
                            </div>
                            <div class='count'>
                                {$opened}
                            </div>
        
                        </div>
                    
                    ";
			} else {

				$body .= "
                    <a href='{$extension_url}' target='_blank' style='text-decoration:none;'>
                    <div class='opened inner-cards'>

                    <div class='ellipse ellipse-opened'>

                        <div class='icon' style='line-height: 53px;'>
                        <img src='" . POST_SMTP_ASSETS . "images/reporting/opened.png' />
                        </div>

                    </div>

                    <div class='txt' style='color: white;'>
                        Opened
                    </div>
                    <div class='count'>
                    <img src='" . POST_SMTP_ASSETS . "images/reporting/lock.png' />
                    </div>

                </div>
                </a>";
			}

			if ( $is_addonactivated ) {

				$body .= "  
                </div>
                            <div class='btn'>
                                <a href='{$url}' target='_blank'>View More Stats</a>
                            </div> 
                            
                            <div class='table-display'>
                            <div class='table-header'>
                            <span><img src='" . POST_SMTP_ASSETS . "images/reporting/clock.png'></span>
                            Last {$duration} top emails
                        </div>";
				if ( ! empty( $logs ) ) {

					$body .= "<div>
                            <table style='width:100%'>
                                <tr>
                                    <td class='heading' style='text-align: left; padding-left: 10px;'>Subject</td>
                                    <td class='heading'>Total</td>
                                    <td class='heading'>Sent</td>
                                    <td class='heading'>Failed</td>
                                    <td class='heading'>Opened</td>
                                </tr>";

					if ( ! empty( $logs ) ) {

						$row = 1;
						foreach ( $logs as $log ) {

							// Let break if greater than 3.
							if ( $row > 3 ) {
								break;
							} else {

								$body .= "

                                        <tr>
                                            <td style='text-align: left; padding-left: 10px;'><div class='wrap-text'>{$log->subject}</div></td>
                                            <td>{$log->total}</td>
                                            <td>{$log->sent}</td>
                                            <td>{$log->failed}</td>
                                            <td>{$log->opened}</td>
                                        </tr>

                                            ";
							}
						}
					}

					$body .= "        
                            </table>
                            <div class='btn' style='position: relative; margin-left: 10px; margin-top: 8px;'>
                                <a href='{$url}' target='_blank'>View More Emails ></a>
                            </div>
                        </div>";
				}

				if ( empty( $logs ) ) {
					$body .= "<div style='text-align: center; margin-top: 45px;'>No emails were sent last {$duration}</div>";
				}

				$body .= "</div>
                    <div class='bottom-text'>
                        This email was autogenerated and sent from <a href='{$url}' style='text-decoration:none;'>{$site_title}</a>
                    </div>";
			} else {

				$body .= "   </div>

            <div class='table-display-free'>
                <div class='table-header-free'>
                    Unlock the Post SMTP Pro and enhance your email deliverability
                </div>
                <div>
                    <table style='width:100%; margin-top: 20px; border-bottom: none;'>
                        
                        <tr>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>Open rate email tracking.</td>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>Connect any mailer of your choice.</td>                            
                        </tr>
                        <tr>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>Email quota scheduling.</td>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>Multiple email failure alert options.</td>
                        </tr>
                        <tr>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>Auto-resend failed emails.</td>
                            <td style='font-size: 11px; font-weight: 400; text-align: left; padding-left: 10px; color: #444A6D; padding-bottom: 4px; padding-top: 4px;'><span style='margin-right: 9px;'><img src='" . POST_SMTP_ASSETS . "images/reporting/okay.png'></span>One-click email attachment resending.</td>
                        </tr>
                    </table>
                    <div class='btn' style='margin-left: 35% !important; background-color: #FA8900 !important; padding:3px !important;'>
                        <a href='{$extension_url}' target='_blank'>Upgrade to PRO ></a>
                    </div>
                </div>

            </div>
            <div class='bottom-text' style='width: 332px; margin-left: 15%;'>
                This email was auto-generated and learn how to <a href='{$disable_url}' target='_blank'>disable it.</a>
            </div>";
			}

			$body .= '
         
        </div>
    </div>
</body>

</html>
    
        ';

			return $body;
		}
	}

endif;
