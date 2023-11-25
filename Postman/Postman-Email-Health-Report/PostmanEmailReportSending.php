<?php

if( !class_exists( 'PostmanEmailReportSending' ) ):

class PostmanEmailReportSending {

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

        $post_option = get_option( 'postman_options' );



        //If transient expired, let's send :)
        if( $enabled && $interval && !$has_sent && isset ( $post_option['transport_type'] ) && $post_option['transport_type'] != 'default' ) {

            $expiry_time = '';
            $report_sent = $this->send_mail( $interval );

            if( $report_sent ) {

                if( $interval == 'd' ) {

                    $expiry_time = DAY_IN_SECONDS;
    
                } 
                if( $interval == 'w' ) {
    
                    $expiry_time = WEEK_IN_SECONDS;
    
                } 
                if( $interval == 'm' ) {
    
                    $expiry_time = MONTH_IN_SECONDS;
    
                }
    
                //Set Future Transient :D
                set_transient( 'ps_rat_has_sent', '1', $expiry_time );

            }

        }
        
    }


     /**
     * Get total email count
     * 
     * @param string $email
     * @since 2.9.0
     * @version 1.0.0
     */
    public function get_total_logs( $from = '', $to = '', $limit = '' ) {

        if( !class_exists( 'PostmanEmailQueryLog' ) ) {

            include_once POST_SMTP_PATH . '/Postman/Postman-Email-Log/PostmanEmailQueryLog.php';

        }
                $ps_query = new PostmanEmailQueryLog();

        $where = ( !empty( $from ) && !empty( $to ) ) ? " WHERE pl.time >= {$from} && pl.time <= {$to}" : '';


        $query = "SELECT pl.original_subject AS subject, COUNT( pl.original_subject ) AS total, SUM( pl.success = 1 ) As sent, SUM( pl.success != 1 ) As failed FROM {$ps_query->table} AS pl";
        
    /**
     * Filter to get query from extension
     * 
     * @since 2.9.0
     * @version 1.0.0
     */
        $query = apply_filters( 'postman_health_count', $query );
        
        $query .= "{$where} GROUP BY pl.original_subject";
        $query .= !empty( $limit ) ? " LIMIT {$limit}" : '';
        
        global $wpdb;
        $response = $wpdb->get_results( $query );

        return $response ? $response : false;

    }


    /**
     * Get the email body
     * 
     * @since 2.9.0
     * @version 1.0.0
     */
    public function get_body( $interval ) {

        $total = 0;
        $sent = 0;
        $failed = 0;
        $opened = 0;
    
        $yesterday = new DateTime('yesterday');
        $yesterday->setTime(23, 59, 0);
        $to = strtotime( $yesterday->format('Y-m-d H:i:s') );
        $from = '';

        $duration = '';

        if( $interval == 'd' ) {

            $duration = 'day';
            $date = new DateTime( date( 'Y-m-d', $to ) );
            $date->setTime(23, 59, 0);
            $from = $date->sub( new DateInterval( 'P1D' ) );
            $from = strtotime( $from->format('Y-m-d H:i:s') );

        }
        if( $interval == 'w' ) {

            $duration = 'week';
            $date = new DateTime( date( 'Y-m-d', $to ) );
            $date->setTime(23, 59, 0);
            $from = $date->sub( new DateInterval( 'P1W' ) );
            $from = strtotime( $from->format('Y-m-d H:i:s') );

        }
        if( $interval == 'm' ) {

            $duration = 'month';
            $date = new DateTime( date( 'Y-m-d', $to ) );
            $date->setTime(23, 59, 0);
            $from = $date->sub( new DateInterval( 'P1M' ) );
            $from = strtotime( $from->format('Y-m-d H:i:s') );

        }

        $logs = $this->get_total_logs( $from, $to, 4 );
        
        //lets calculate the total
        if( $logs ) {

            foreach( $logs as $log ) {

                if( $log->total ) {

                    $total += $log->total;

                }
                if( $log->sent ) {

                    $sent += $log->sent;

                }
                if( $log->failed ) {

                    $failed += $log->failed;

                }
                if( $log->opened ) {

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

            $admin_name = !empty( $user->first_name ) ? $user->first_name : $user->user_login;

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

        $body = "
        <html>
            <head>
                <style>
                    .main {
                        background-color: #f1f1f1;
                        padding: 20px;
                    }
                    .logo {
                        text-align: center;
                    }
                    .logo img {
                        width: 250px;
                    }
                    .content {
                        width: 380px;
                        margin: 0 auto;
                        background: #fff;
                        padding: 30px 50px;
                        margin-top: 20px;
                    }
                    .inner-box img {
                        filter: invert(31%) sepia(74%) saturate(916%) hue-rotate(193deg) brightness(89%) contrast(88%);
                        width: 50px;
                    }
                    .outer-box {
                        display: flex;
                        width: max-content;
                        margin: 0 auto;
                    }
                    .inner-box {
                        border: 1px solid #375caf;
						padding-top: 15px;
						text-align: center;
						margin: 10px 10px;
						min-width: 113px;
						color: #375caf;
                    }
                    .img-box {
                        min-height: 58px;
                    }
                    .button-outer {
                        text-align: center;
                        margin: 25px 0;
                    }
                    .button-outer a {
                        background: #375caf;
                        color: #fff;
                        text-decoration: none;
                        padding: 7px 22px;
                    }
                    .content h4{
                        text-align: center;
                        color: #375caf;
                        margin: 35px 0;
                    }
                    table {
                        color: #375caf;
                        margin: 30px 0;
                        border-collapse: collapse;
                    }
                    table td {
                        padding: 10px 15px;
                    }
                    .table-header {
                        text-decoration: underline;
                        background-color: #fafafa;
                    }
                    table .odd {
                        background-color: #e7ebf5;
                    }
                    table .even {
                        background-color: fafafa;
                    }
                    .footer{
                        text-align: center;
                        color: #375caf;
                    }
                </style>
            </head>
            <body>
                <div class='main'>
                    <div class='logo'>
                        <img src='https://postmansmtp.com/wp-content/uploads/2022/06/postman-smtp-mailer-1024x163.png' />
                    </div>
                    <div class='content'>
                        <p>Hi {$admin_name},</p>
                        <p>Here is a quick overview of how your emails were performing in the past {$duration}</p>
                        <div class='outer-box'>
                            <div class='inner-box'>
                                <div class='img-box'>
                                    <img src='".POST_SMTP_ASSETS."images/reporting/total.png' />
                                </div>
                                <div>
                                    Total Emails
                                </div>
                                <h1>
                                    {$total}
                                </h1>
                            </div>
                            <div class='inner-box'>
                                <div class='img-box'>
                                    <img src='".POST_SMTP_ASSETS."images/reporting/sent.png' />
                                </div>
                                <div>
                                    Sent
                                </div>
                                <h1>
                                    {$sent}
                                </h1>
                            </div>
                        </div>
                        <div class='outer-box'>
                            <div class='inner-box'>
                                <div class='img-box'>
                                    <img src='".POST_SMTP_ASSETS."images/reporting/failed.png' />
                                </div>
                                <div>
                                    Failed
                                </div>
                                <h1>
                                    {$failed}
                                </h1>
                            </div>
                            <div class='inner-box'>
                                <div class='img-box'>
                                    <img src='".POST_SMTP_ASSETS."images/reporting/opened.png' />
                                </div>
                                <div>
                                    Opened
                                </div>
                                <h1>";
                                if( !class_exists( "Post_SMTP_Report_And_Tracking" ) ) 
                                {
                                    $body .= "<a href={$extension_url} style='text-decoration: none' target='_blank'>🔒</a>";
                                }
                                else{
                                    $body .= $opened;
                                }
                            $body .= "
                                </h1>
                            </div>
                        </div>
                        <div class='button-outer'>";
                        ( !class_exists( "Post_SMTP_Report_And_Tracking" ) ) ? $body .= "<a href='{$extension_url}' target='_blank'>View more stats</a>": $body .= "<a href='{$url}' target='_blank'>View more stats</a>";
                            $body .= "</div>";
                        

                        if( !empty( $logs ) ) {

                            $body .= "
                            <h4 style='text-transform: uppercase;'>LAST {$duration} TOP EMAILS</h4>
                            <table width='100%'>
                                <tr class='table-header'>
                                    <td>Subject</td>
                                    <td>Total</td>
                                    <td>Sent</td>
                                    <td>Failed</td>
                                    <td>Opened</td>
                                </tr>";

                            $row = 1;

                            foreach( $logs as $log ) {

                                //Let break if greater than 3
                                if( $row > 3 ) {
                                    break;
                                }

                                //Let's class odd and even :P
                                $row_class = ( $row % 2 == 1 ) ? 'odd' : 'even';

                                $body .= "
                                <tr class='{$row_class}'>
                                    <td>{$log->subject}</td>
                                    <td>{$log->total}</td>
                                    <td>{$log->sent}</td>
                                    <td>{$log->failed}</td>";
                                ( !class_exists( "Post_SMTP_Report_And_Tracking" ) ) ? $body .= "<td>🔒</td>" : $body .= "<td>{$log->opened}</td>";
                                    
                               $body .= "</tr>";

                                $row++;

                            }

                            $body .= "</table>";

                            if( count( $logs ) > 3 ) {

                                $body .= "
                                <div class='button-outer'>";

                                ( !class_exists( "Post_SMTP_Report_And_Tracking" ) ) ? $body .= "<a href='{$extension_url}' target='_blank'>View more emails</a>" : $body .= "<a href='{$url}' target='_blank'>View more emails</a>";
                                    
                                $body .= "</div>
                                ";
                            }

                        }
                        
                    $body .= "</div>
                    <div class='footer'>
                        <p>This was autogenerated and sent from {$site_title}</p>
                    </div>
                </div>
            </body>
        </html>
        ";

        return $body;

    }


    /**
     * Function to send the report
     * 
     * @since 2.9.0
     * @version 1.0.0
     */
    public function send_mail( $interval ) {

        $duration = '';

        if( $interval == 'd' ) {

            $duration = 'Daily';

        }
        if( $interval == 'm' ) {

            $duration = 'Monthly';

        }
        if( $interval == 'w' ) {

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