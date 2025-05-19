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

			$body = "<!DOCTYPE html>
                    <html >
                        <head>
                       <style>
                                .outer-box {
                                    background-color: #f1f1f1;
                                    padding: 15px 0;
                                }
                                .main {
                                    width: 520px;
                                    background-color: #ffffff;
                                    margin: 0 auto;
                                    padding: 1px 0;
                                }
                                .container {
                                   
                                }
                                .logo {
                                       margin: 36px 0 15px;
                                    text-align: center;
                                }
                                .text {
                                    font-size: 12px;
                                    font-weight: 400;
                                    line-height: 15px;
                                    
                                }
                                .cards {
                                    margin-top: 20px;
                                    display: flex;
                                }
                                .inner-cards {
                                    display: inline-block;
                                    box-sizing: border-box;
                                    text-align: center;
                                    width: 100px;
                                    padding: 10px;
                                    border-radius: 5px;
                                    margin: 0 4px;
                                    background: #fff;
                                }
                                .total {
                                   
                                }
                                .sent {
                                    
                                }
                                .failed {
                                    
                                }
                                .opened-pro {
                                    background: #FFF5E9;
                                }
                                .opened{
                                    background:linear-gradient(190deg, #FB9E1F, #FBBC1F)
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
                                .ellipse-opened-pro {
                                    background: #FA8900;
                                }
                                .icon {
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
                                }
                                .table-display-free {
                                   
                                }
                                .table-display tr {
                                }
                                .table-display tr th {

                                }
                                .table-display tr td, 
                                .table-display tr th{
                                    padding: 5px;
                                    font-size: 12px;
                                    
                                }
                                .table-display tr th {
                                    font-weight: 600;
                                }
                                .table-display tr td {
                                    font-weight: 400;
                                }
                                .table-header {
                                    color: #214a72;
                                    text-align: center;
                                    display: flex;
                                    align-self: center;
                                    justify-content: center;
                                }
                                .table-header span {
                                    display: flex;
                                    margin: 0 auto;
                                    gap: 5px;
                                }
                                .table-header-free{
                                   
                                }
                                table,
                                td,
                                th {
                                   
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
                                }
                                .bottom-text {
                                    color: #375CAF;
                                    font-size: 12px;
                                    font-weight: 400;
                                    padding: 20px 0;
                                }
                                .bottom-text a {
                                    color: #375CAF;
                                }
                                .wrap-text{
                                    white-space: nowrap; 
                                    width: 200px; 
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                                .ps-features-table td {
                                    font-size: 11px; 
                                    font-weight: 400; 
                                    text-align: left; 
                                    padding-left: 10px; 
                                    color: #444A6D; 
                                    padding-bottom: 4px; 
                                    padding-top: 4px;
                                    border-color: transparent;
                                }
                                .div_wrap.bg {
                                    background: #f0f6ff;
                                    border-radius: 6px;
                                }
                                .div_wrap {
                                    width: 90%;
                                    margin: 0 auto;
                                    padding: 20px 25px;
                                    box-sizing: border-box;
                                }
                                .inline-box {
                                    display: flex;
                                    align-items: center;
                                }
                                .inline-box .box.bleft {
                                    width: 65%
                                }
                                .inline-box .box.bright {
                                    width: 35%;
                                    margin-top: 40px;
                                }
                                .inline-box .box.bleft h3{
                                    color: #214a72;
                                    text-align: left;
                                    font-size: 12px;
                                    margin: 0 0 10px 0;
                                }
                                .inline-box .box.bleft ul.icon-list {
                                    padding: 0;
                                    margin: 0;
                                    list-style: none;
                                    text-align: left;
                                }
                                .inline-box .box.bleft ul.icon-list li {
                                    color:#7D98B2;
                                    display: flex;
                                    align-content: center;
                                    gap: 9px;
                                    font-size: 12px;
                                    margin: 0;
                                    padding: 5px 0; 
                                }
                                .inline-box .box.bleft ul.icon-list li span {
                                    width: 15px;
                                    height: 15px;
                                    background: url('".POST_SMTP_ASSETS."images/reporting/list-icon.png');
                                    margin-right: 6px;
                                    border-radius: 100%;
                                    background-size: 100%;
                                }
                                .inline-box .box.bleft ul.icon-list li img {
                                    margin: 0 10px;
                                }
                                .inline-box .box.bright img{
                                    width: 100%
                                }
                                .button.center{
                                    text-align: center;
                                }
                                .button {
                                    text-align: left;
                                    display: block;
                                    margin: 20px 0 0;

                                }
                                .button.bg a {
                                    background: #375CAF;
                                    color: #fff;
                                }
                               .button a {
                                        background: #F0F6FF;
                                        padding: 10px 30px;
                                        text-decoration: none;
                                        color: #3A5EAF;
                                        font-size: 12px;
                                        border-radius: 100px;
                                        border: 1px solid;
                                        display: inline-block;
                                }
                                
                            </style>
                        </head>
                        <body>
                            <div class='outer-box'>
                                <div class='main'>
                                    <div class='logo'>
                                    <img src='" . POST_SMTP_ASSETS . "images/reporting/post_logo.png' />
                                    </div>
                                    <div class='div_wrap bg' style=''>
                                        <div class='text container'>
                                            <strong>Hi {$admin_name}</strong>
                                            <br>
                                            <br>
                                            Here is a quick overview of how your emails were performing in the past {$duration}
                                        </div>
                                        <div class='cards container'>
                                            <div class='total inner-cards'>
                                                <div class='ellipse ellipse-total'>
                                                    <div class='icon'>
                                                    <img src='" . POST_SMTP_ASSETS . "images/reporting/total.png' />
                                                    </div>
                                                </div>
                                                <div class='txt'>
                                                    Total
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

                                                $body .= "<div class='opened-pro inner-cards'>
                                                            <div class='ellipse ellipse-opened-pro'>
                                                                <div class='icon'>
                                                                    <img src='" . POST_SMTP_ASSETS . "images/reporting/opened-pro.png' />
                                                                </div>
                                                            </div>
                                                            <div class='txt'>
                                                                Opened
                                                            </div>
                                                            <div class='count'>
                                                                {$opened}
                                                            </div>
                                                        </div>";

                                            }
                                            else {

                                                $body .= "<a href='{$extension_url}' target='_blank' style='text-decoration:none;'>
                                                            <div class='opened inner-cards'>
                                                                <div class='ellipse ellipse-opened'>
                                                                    <div class='icon'>
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

                                            $body .= "</div>
                                                    <div class='button bg center'>
                                                        <a href='{$url}' target='_blank'>View More Stats</a>
                                                    </div>
                                                    </div>
                                                    <div class='table-display'>
                                                        <div class='table-header'>
                                                            <span><img style='margin-right: 5px;' src='" . POST_SMTP_ASSETS . "images/reporting/dashicons-clock.png' width='20px'> Last {$duration} top emails</span>
                                                        </div>";

                                            if ( ! empty( $logs ) ) {

                                                $body .= "<div class='div_wrap' style='padding: 10px 0;'>
                                                            <table style='width:100%'>
                                                                <thead>
                                                                    <tr>
                                                                        <th style='text-align: left; text-align: left; color: #151D48; font-size: 14px;' class=''>Subject</th>
                                                                        <th style='color: #83F5AF;'>Total</th>
                                                                        <th style='color: #98B9F9;'>Sent</th>
                                                                        <th style='color: #FF955F'>Failed</th>
                                                                        <th style='color: #FFAE3A;'>Opened</th>
                                                                    </tr>
                                                                </thead>
                                                                ";

                                                if ( ! empty( $logs ) ) {

                                                    $row = 1;
                                                    $body .= "<tbody>";
                                                    foreach ( $logs as $log ) {

                                                        // Let break if greater than 3.
                                                        if ( $row > 3 ) {

                                                            break;

                                                        } 
                                                        else {
                                                            $body .= "
                                                            <tr class='thead' style=' background: #F0F6FF; '>
                                                            <td style='text-align: left;'><div class='wrap-text'>{$log->subject}</div></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>";
                                                            $body .= "<tr>
                                                                        <td style='text-align: left;'><div class='wrap-text'></div></td>
                                                                        <td>{$log->total}</td>
                                                                        <td>{$log->sent}</td>
                                                                        <td>{$log->failed}</td>
                                                                        <td>{$log->opened}</td>
                                                                    </tr>";

                                                        }

                                                    }

                                                }

                                                $body .= " </tbody></table>
                                                            <div class='button bg center'>
                                                                <a href='{$url}' target='_blank'>View More Email</a>
                                                            </div>
                                                        </div>";

                                            }

                                            if ( empty( $logs ) ) {

                                                $body .= "<div style='text-align: center; margin-top: 45px;'>No emails were sent last {$duration}</div>";

                                            }

                                            $body .= "</div>
                                                       <div class='div_wrap' style='text-align: center;'>
                                                            This email was autogenerated and sent from <a href='{$url}' style='text-decoration:none;'>{$site_title}</a>
                                                        </div>";

                                        } 
                                        else {

                                            $body .= "</div> 
                                            </div> <!-- end div wrap -->
                                                <div class='div_wrap'>
                                                  <div class='table-display-free container'>
                                                        <div class='table-header-free'>
                                                            <table>
                                                            <tr>
                                                                <td style='width: 100%;'>
                                                                    <div class='inline-box'>
                                                                        <div class='box bleft'>
                                                                        <h3>Unlock the Post SMTP Pro and enhance your email deliverability</h3>
                                                                            <ul class='icon-list'>
                                                                                <li><span></span>More Pro Mailers <img src='".POST_SMTP_ASSETS."images/reporting/mailers.png'/></li>
                                                                                <li><span></span>All mobile app premium features. </li>
                                                                                <li><span></span>Auto-resend failed emails. </li>
                                                                                <li><span></span>SMS Failure Notification.</li>
                                                                            </ul>
                                                                             <div class='button'>
                                                                                <a href='#'>Learn more about PRO </a>
                                                                            </div>
                                                                        </div>
                                                                        <div class='box bright'>
                                                                            <img src='".POST_SMTP_ASSETS."images/reporting/email-fav.png'/>
                                                                        </div>
                                                                    </div>
                                                                   
                                                                    </td>
                                                               
                                                            </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                  
                                                    <div class='div_wrap' style='text-align: center;'>
                                                        This email was auto-generated and learn how to <a href='{$disable_url}' target='_blank'><strong>disable it</strong></a>.
                                                    </div>";

                                    }

                        $body .= '</div>
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