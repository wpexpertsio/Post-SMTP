<?php

if( !class_exists( 'Post_SMTP_New_Wizard' ) ):

class Post_SMTP_New_Wizard {

    private $sockets = array();
    private $options;
    private $allowed_tags = array(
        'input'			=>	array(
            'type'			=>	array(),
            'id'			=>	array(),
            'name'			=>	array(),
            'value'			=>	array(),
            'class'			=>	array(),
            'placeholder'	=>	array(),
            'size'			=>	array(),
            'checked'		=>	array(),
            'required'		=>	array(),
            'data-error'    =>  array(),
            'readonly'      =>  array()
        ),
        'div'           =>  array(
            'class'         =>  array()
        ),
        'label'         =>  array(
            'class'         =>  array()
        ),
        'span'          => array(
            'class'         =>  array()
        ),
        'b'             =>  array(),
        'a'            =>  array(
            'href'          =>  array(),
            'target'        =>  array(),
            'class'         =>  array(),
            'id'            =>  array()
        ),
        'p'             =>  array(),
        'h3'            =>  array(),
    );

    /**
     * Constructor for the class
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function __construct() {

        add_filter( 'post_smtp_legacy_wizard', '__return_false' );
        add_action( 'post_smtp_new_wizard', array( $this, 'load_wizard' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_ps-save-wizard', array( $this, 'save_wizard' ) );

        if( isset( $_GET['wizard'] ) && $_GET['wizard'] == 'legacy' ) {

            add_filter( 'post_smtp_legacy_wizard', '__return_true' );

        }
        
    }

    /**
     * Load the wizard | Action Callback
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function load_wizard() {

        $transports = PostmanTransportRegistry::getInstance()->getTransports();
        //Not for wizard
        unset( $transports['default'] );
        $settings_registry = new PostmanSettingsRegistry();
        $this->options = PostmanOptions::getInstance();
        $is_active = ( isset( $_GET['step'] ) && $_GET['step'] == 2 ) ? 'ps-active-nav' : 'ps-in-active-nav';
        $in_active = ( isset( $_GET['step'] ) && $_GET['step'] != 1 ) ? '' : 'ps-active-nav';
        $selected_tansport = $this->options->getTransportType();
        $socket = isset( $_GET['socket'] ) ? "{$_GET['socket']}-outer" : '';
        ?>

        <div class="wrap">
            <div class="ps-wizard">
                <div class="ps-logo">
                    <img src="https://postmansmtp.com/wp-content/uploads/2022/06/postman-smtp-mailer-1024x163.png" width="250px" />
                </div>
                <div class="ps-wizard-outer <?php echo esc_attr( $socket ); ?>">
                    <div class="ps-wizard-section">
                        <div class="ps-wizard-nav">
                            <table>
                                <tr class="<?php echo esc_attr( $in_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"></span></td>
                                    <td class="ps-wizard-text">Choose your SMTP Mailer</td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="1"></span></td>
                                </tr>
                                <tr class="<?php echo esc_attr( $is_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text">Configure Mailer Settings</td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="2"></span></td>
                                </tr>
                                <tr class="ps-in-active-nav">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text">Send Test Email</td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="3"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="ps-wizard-pages">
                            <form id="ps-wizard-form">
                                <?php wp_nonce_field( 'post-smtp', 'security' );  ?>
                                <div class="ps-wizard-screens-container">
                                    <div class="ps-wizard-step ps-wizard-step-1">
                                        <p style="width: 70%; margin-bottom: 30px;"><?php 
                                        /**
                                         * Translators: %1$s Description of the step, %2$s Link to the complete mailer guide, %3$s Link text, %4$s Description of the step
                                         */
                                        printf( 
                                            '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
                                            __( 'Which mailer would you like to use to send emails? Not sure which mailer to choose? Check out our ', 'post-smtp' ),
                                            esc_url( '#' ),
                                            __( 'complete mailer guide', 'post-smtp' ),
                                            __( ' for details on each option.', 'post-smtp' )
                                        ); 
                                        ?></p>
                                        <div class="ps-wizard-sockets">      
                                        <?php

                                        $row  = 0;

                                        foreach( $transports as $transport ) {

                                            $this->sockets[$transport->getSlug()] = $transport->getName();
                                            $checked = $transport->getSlug() == $this->options->getTransportType() ? 'checked' : '';

                                            $urls = array(
                                                'smtp'              =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp.png',
                                                'gmail_api'         =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/gmail.png',
                                                'mandrill_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mandrill.png',
                                                'sendgrid_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendgrid.png',
                                                'mailgun_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailgun.png',
                                                'sendinblue_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendinblue.png',
                                                'postmark_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/postmark.png',
                                                'sparkpost_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sparkpost.png',
                                                'office365_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/logo.png'
                                            );

                                            $url = isset( $urls[$transport->getSlug()] ) ? $urls[$transport->getSlug()] : $transport->getLogoURL();

                                            if( $row >= 4 ) {

                                                $row = 0;

                                                ?>
                                                </div>
                                                <div class="ps-wizard-sockets">
                                                <?php


                                            }

                                            ?>
                                            <div class="ps-wizard-socket-radio-outer">
                                                <div class="ps-wizard-socket-radio">
                                                    <label for="ps-wizard-socket-<?php echo esc_attr( $transport->getSlug() ); ?>">                                                    
                                                        <input type="radio" <?php echo esc_attr( $checked ) ;?> class="ps-wizard-socket-check" id="ps-wizard-socket-<?php echo esc_attr( $transport->getSlug() ); ?>" value="<?php echo esc_attr( $transport->getSlug() ); ?>" name="<?php echo 'postman_options[' . esc_attr( PostmanOptions::TRANSPORT_TYPE ) . ']'; ?>">
                                                        <img src="<?php echo esc_url( $url ); ?>">
                                                        <div class="ps-wizard-socket-tick-container">
                                                            <div class="ps-wizard-socket-tick"><span class="dashicons dashicons-yes"></span></div>
                                                        </div>
                                                        <h4><?php echo esc_attr( $transport->getName() ); ?></h4>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php

                                            $row++;

                                        }
                                        ?>
                                        </div>
                                    </div>
                                    <div class="ps-wizard-step ps-wizard-step-2">
                                        <a href="" data-step="1" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span>Back</a>
                                        <?php
                                        if( !empty( $this->sockets ) ) {

                                            $this->render_name_email_settings();

                                            foreach( $this->sockets as $key => $title ) {

                                                $active_socket = ( isset( $_GET['socket'] ) && $_GET['socket'] == $key ) ? 'style="display: block;"' : '';

                                                ?>
                                                <div class="ps-form-ui ps-wizard-socket <?php echo esc_attr( $key ); ?>" <?php echo $active_socket; ?>>
                                                    <h3><?php echo esc_attr( $title ); ?></h3>
                                                    <?php $this->render_socket_settings( $key ); ?>
                                                </div>
                                                <?php

                                            }

                                        }
                                        ?>
                                    </div>
                                    <div class="ps-wizard-step ps-wizard-step-3">
                                        <a href="" data-step="2" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span>Back</a>
                                        <p><?php _e( 'This step allows you to send an email message for testing. If there is a problem, Post SMTP will give up after 60 seconds.', 'post-smtp' ); ?></p>
                                        <div class="ps-form-ui">
                                            <div class="ps-form-control">
                                                <div><label>Recipient Email Address</label></div>
                                                <input type="text" class="ps-test-to" required data-error="Enter Recipient Email Address" name="postman_test_options[test_email]" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" placeholder="Recipient Email Address">
                                            </div>
                                            <button class="button button-primary ps-blue-btn ps-wizard-send-test-email" data-step="3">Send Test Email <span class="dashicons dashicons-email"></span></button>
                                            <div>
                                                <p class="ps-wizard-error"></p>
                                                <p class="ps-wizard-success"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ps-wizard-step ps-wizard-step-4">
                                        <h4>❤ <?php _e( 'Share Your Feedback', 'post-smtp' ) ?></h4>
                                        <p><?php 
                                        /**
                                         * Translators: %1$s Text, %2$s URL, %3$s URL Text
                                         */
                                        printf(
                                            '%1$s <a href="%2$s" target="_blank">%3$s</a>',
                                            __( 'We value your opinion on your experience with Post SMTP and would appreciate your feedback. ' ),
                                            esc_url( 'https://wordpress.org/support/plugin/post-smtp/reviews/#new-post' ),
                                            __( 'Leave a review here.', 'post-smtp' )
                                        ) ?></p>
                                        <p><?php
                                        /**
                                         * Translators: %1$s Text, %2$s URL, %3$s URL Text with
                                         */
                                        printf(
                                            '<b>%1$s</b> <a href="%2$s" target="_blank">%3$s </a>',
                                            __( 'Get More Insights & Optimize Your Email Delivery - ' ),
                                            esc_url( 'https://postmansmtp.com/extension/' ),
                                            __( 'Learn more about Post SMTP Addons 💪', 'post-smtp' )
                                        );
                                        ?>
                                        </p>
                                        <div class="ps-wizard-card">
                                            <p><?php printf(
                                                '<b>%1$s</b>%2$s',
                                                __( '📉 Report and Tracking Addon', 'post-smtp' ),
                                                __( 'Receive in-depth reports and statics of your website\'s email performance. and, get access to email\'s open rate and get more visibility.' )
                                            ) ?></p>
                                            <p><?php printf(
                                                '<b>%1$s</b>%2$s',
                                                __( '⚡ Advanced Email delivery and logs', 'post-smtp' ),
                                                __( 'You can automate the process of retrying failed email attempts and optamize your website for users by sending emails asychronously from the backend.' )
                                            ) ?></p>
                                            <a href="<?php echo esc_url( 'https://postmansmtp.com/extension/' ); ?>" class="button button-primary ps-yellow-btn" target="_blank"><?php esc_html_e( 'CHECK THERE ADDONS', 'post-smtp' ); ?><span class="dashicons dashicons-arrow-right-alt"></span></a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div style="clear: both"></div>
                    </div>
                    <div class="ps-wizard-footer">
                        <div class="ps-wizard-footer-left">
                            <div class="ps-wizard-nav">
                                <table>
                                    <tr class="ps-in-active-nav">
                                        <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                        <td class="ps-wizard-text"></td>
                                        <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="4"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="ps-wizard-footer-right">
                            <div class="ps-wizard-step ps-wizard-step-1">
                                <p class="ps-wizard-error"></p>
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="1">Continue <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-2">
                                <p class="ps-wizard-success"><?php echo ( isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? $_GET['msg'] : ''; ?></p>
                                <p class="ps-wizard-error"><?php echo ( !isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? $_GET['msg'] : ''; ?></p>
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="2"></span>Save and Continue <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-3">
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="3">Finish <span class="dashicons dashicons-arrow-right-alt"></span></button>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-4">
                                <div class="ps-wizard-congrates">
                                    <h2>👏 <?php _e( 'Great you are all done!', 'post-smtp' ); ?></h1>
                                    <?php 
                                    printf( 
                                        '<a href="%1$s" style="font-size: 12px;">%2$s <b>%3$s</b> %4$s <b>%5$s</b> %6$s</a>', 
                                        esc_url( admin_url( 'admin.php?page=postman/configuration' ) ),
                                        __( 'Visit settings to setup', 'post-smtp' ),
                                        __( 'Notifications', 'post-smtp' ),
                                        __( 'and', 'post-smtp' ),
                                        __( 'Fallback SMTP'),
                                        __( 'options.', 'post-smtp' )
                                    );
                                    ?>
                                </div>
                                <div class="ps-wizard-view-logs">
                                    <div><a href="<?php echo esc_url( admin_url( 'admin.php?page=postman_email_log' ) ); ?>" class="button button-primary ps-blue-btn"><?php esc_html_e( 'View logs section', 'post-smtp' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a></div>
                                    <div><a href="<?php echo esc_url( admin_url( 'admin.php?page=postman' ) ); ?>" style="font-size: 12px; color: #999999;"><?php esc_html_e( 'Skip to dashboard', 'post-smtp' ); ?></a></div>
                                </div>
                                <div style="clear: both"></div>
                            </div>
                        </div>
                        <div style="clear: both"></div>
                    </div>
                </div>
                <div class="ps-wizard-page-footer">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=postman/configuration_wizard&wizard=legacy' ) ); ?>"><?php _e( 'Continue with legacy wizard', 'post-smtp' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=postman' ) );?>"><?php _e( 'Go back to dashboard', 'post-smtp' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=postman/configuration' ) );?>"><?php _e( 'Switch to settings section', 'post-smtp' ); ?></a>
                </div>
            </div>
        </div>

        <?php

    }


    /**
     * Enqueue Scripts | Action Callback
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function enqueue_scripts() {

        $localized = array(
            'Step1E1'           => __( 'Select a socket type to continue.', 'post-smtp' ),
            'Step2E2'           => __( 'Please enter From Email.', 'post-smtp' ),
            'Step2E3'           => __( 'Please try again, something went wrong.', 'post-smtp' ),
            'adminURL'          => admin_url(),
        );

        if( class_exists( 'Post_Smtp_Office365' ) ) {

            //Office 365 URL Params | State
            $state = get_transient( Post_Smtp_Office365::STATE );

            if ( $state === false ) {

                $state = bin2hex( random_bytes( 32 / 2 ) );
                set_transient( Post_Smtp_Office365::STATE, $state, 5 * MINUTE_IN_SECONDS );

            }

            $localized['office365State'] = $state;

        }

        wp_enqueue_style( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/css/wizard.css' );
        wp_enqueue_script( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/js/wizard.js', array( 'jquery' ) );
        wp_localize_script( 'post-smtp-wizard', 'PostSMTPWizard', $localized );

    }


    /**
     * Render Name and Email Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_name_email_settings() {

        $from_name = null !== $this->options->getMessageSenderName() ? esc_attr( $this->options->getMessageSenderName() ) : '';
        $from_email = null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '';
        $from_name_enforced = $this->options->isPluginSenderNameEnforced() ? 'checked' : '';
        $from_email_enforced = $this->options->isPluginSenderEmailEnforced() ? 'checked' : '';

        $html = '
        <div class="ps-form-ui ps-name-email-settings">
            <div class="ps-form-control">
                <h3>From Address</h3>
                <p>'. sprintf(
                    '%1$s',
                    esc_html__( 'This address, like the letterhead printed on a letter, identifies the sender to the recipient. Change this when you are sending on behalf of someone else. Other plugins, especially Contact Forms, may override the field to be your visitor\'s address.', 'post-smtp' )
                ) .'</p>
                <div><label>From Email</label></div>
                <input type="text" class="ps-from-email" required data-error="'.__( 'Please enter From Email.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_EMAIL ).']" value="'.$from_email.'" placeholder="From Email">
                <span class="ps-form-control-info">The name that emails are sent from.</span>
                <div>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_email_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ).']" id="">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b> %3$s <b>%4$s</b> %5$s', 
                        __( 'Prevent', 'post-smtp' ),
                        __( 'plugins', 'post-smtp' ),
                        __( 'and', 'post-smtp' ),
                        __( 'themes', 'post-smtp' ),
                        __( 'from changing this.', 'post-smtp' )
                    ).
                    '</span>
                </div> 
            </div>
            <div class="ps-form-control">
                <div><label>From Name</label></div>
                <input type="text" class="ps-from-name" required data-error="'.__( 'Please enter From Name.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_NAME ).']" value="'.$from_name.'" placeholder="From Name">
                <span class="ps-form-control-info">The email that emails are sent from.</span>
                <div>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_name_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ).']" id="">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b> %3$s <b>%4$s</b> %5$s', 
                        __( 'Prevent', 'post-smtp' ),
                        __( 'plugins', 'post-smtp' ),
                        __( 'and', 'post-smtp' ),
                        __( 'themes', 'post-smtp' ),
                        __( 'from changing this.', 'post-smtp' )
                    ).
                    '</span>
                </div>
            </div>
        </div>
        ';

        echo wp_kses( $html, $this->allowed_tags );

    }


    /**
     * Render Socket Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_socket_settings( $socket ) {

        switch ( $socket ) {
            
            case 'smtp':
                echo wp_kses( $this->render_smtp_settings(), $this->allowed_tags );
            break;
            case 'gmail_api':
                echo wp_kses( $this->render_gmail_settings(), $this->allowed_tags );
            break;
            case 'mandrill_api':
                echo wp_kses( $this->render_mandrill_settings(), $this->allowed_tags );
            break;
            case 'sendgrid_api';
                echo wp_kses( $this->render_sendgrid_settings(), $this->allowed_tags );
            break;
            case 'mailgun_api':
                echo wp_kses( $this->render_mailgun_settings(), $this->allowed_tags );
            break;
            case 'sendinblue_api':
                echo wp_kses( $this->render_brevo_settings(), $this->allowed_tags );
            break;
            case 'postmark_api':
                echo wp_kses( $this->render_postmark_settings(), $this->allowed_tags );
            break;
            case 'sparkpost_api':
                echo wp_kses( $this->render_sparkpost_settings(), $this->allowed_tags );
            break;
            case 'office365_api';
                echo wp_kses( $this->render_office365_settings(), $this->allowed_tags );
            break;

        }

    }


    /**
     * Render SMTP Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_smtp_settings() {

        $hostname = null !== $this->options->getHostname() ? esc_attr ( $this->options->getHostname() ) : '';
        $port = null !== $this->options->getPort() ? esc_attr ( $this->options->getPort() ) : '';
        $username = null !== $this->options->getUsername() ? esc_attr ( $this->options->getUsername() ) : '';
        $password = null !== $this->options->getPassword() ? esc_attr ( $this->options->getPassword() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>Host Name</label></div>
            <input type="text" class="ps-smtp-host-name" required data-error="'.__( 'Please enter Host Name.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::HOSTNAME ) .']" value="'.$hostname.'" placeholder="Host Name">
            <span class="ps-form-control-info">
            '.__( 'Outgoing Mail Server Hostname', 'post-smtp' ).'
            </span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Port</label></div>
            <input type="text" class="ps-smtp-port" required data-error="'.__( 'Please enter Port.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::PORT ) .']" value="'.$port.'" placeholder="Port">
            <span class="ps-form-control-info">
            '.__( 'Outgoing Mail Server Port', 'post-smtp' ).'
            </span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Username</label></div>
            <input type="text" class="ps-smtp-username" required data-error="'.__( 'Please enter Username.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::BASIC_AUTH_USERNAME ) .']" value="'.$username.'" placeholder="Username">
            <span class="ps-form-control-info">
            '.__( 'The Username is usually the same as the Envelope-From Email Address.', 'post-smtp' ).'
            </span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Password</label></div>
            <input type="text" class="ps-smtp-password" required data-error="'.__( 'Please enter Password.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::BASIC_AUTH_PASSWORD ) .']" value="'.$password.'" placeholder="Password">
            <span class="ps-form-control-info">
            '.__( 'Password or App Password.', 'post-smtp' ).'
            </span>
        </div>
        ';

        return $html;

    }


    /**
     * Render Gmail API Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_gmail_settings() {

        $client_id = null !== $this->options->getClientId() ? esc_attr ( $this->options->getClientId() ) : '';
        $client_secret = null !== $this->options->getClientSecret() ? esc_attr ( $this->options->getClientSecret() ) : '';
        $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';

        $html = '
        <div class="ps-form-control">
            <div><label>Client ID</label></div>
            <input type="text" class="ps-gmail-api-client-id" required data-error="'.__( 'Please enter Client ID.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::CLIENT_ID ) .']" value="'.$client_id.'" placeholder="Client ID">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Client Secret</label></div>
            <input type="text" class="ps-gmail-client-secret" required data-error="'.__( 'Please enter Client Secret.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::CLIENT_SECRET ) .']" value="'.$client_secret.'" placeholder="Client Secret">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Authorized JavaScript origins</label></div>
            <input type="text" class="ps-gmail-js-origin" value="'.site_url().'" readonly>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Authorized redirect URI</label></div>
            <input type="text" class="ps-gmail-redirect-uri" value="'.admin_url( 'options-general.php?page=postman' ).'" readonly>
        </div>
        ';

        $html .= '
        <h3>'.__( 'Authorization (Required)', 'post-smtp' ).'</h3>
        <p>'.__( 'Before continuing, you\'ll need to allow this plugin to send emails using Gmail API.', 'post-smtp' ).'</p>
        <input type="hidden" '.$required.' data-error="Please authenticate by clicking Connect to Gmail API" />
        <a href="'.admin_url( 'admin-post.php?action=postman/requestOauthGrant' ).'" class="button button-primary ps-blue-btn" id="ps-wizard-connect-gmail">Connect to Gmail API</a>';

        return $html;

    }


    /**
     * Render Mandrill Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_mandrill_settings() {

        $api_key = null !== $this->options->getMandrillApiKey() ? esc_attr ( $this->options->getMandrillApiKey() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mandrill-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MANDRILL_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://mandrillapp.com/' ),
                esc_attr( 'Mandrill' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://mandrillapp.com/settings' ),
                esc_attr( 'API Key.' )
            )
            .'</span>
        </div>
        ';

        return $html;

    }


    /**
     * Render SendGrid Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_sendgrid_settings() {

        $api_key = null !== $this->options->getSendGridApiKey() ? esc_attr ( $this->options->getSendGridApiKey() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-sendgrid-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDGRID_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://sendgrid.com/' ),
                esc_attr( 'SendGrid' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://app.sendgrid.com/settings/api_keys' ),
                esc_attr( 'API Key.' )
            )
            .'</span>
        </div>
        ';

        return $html;

    }


    /**
     * Render Mailgun Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_mailgun_settings() {

        $api_key = null !== $this->options->getMailgunApiKey() ? esc_attr ( $this->options->getMailgunApiKey() ) : '';
        $domain_name = null !== $this->options->getMailgunDomainName() ? esc_attr ( $this->options->getMailgunDomainName() ) : '';
        $region = null !== $this->options->getMailgunRegion() ? ' checked' : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailgun-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILGUN_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://mailgun.com/' ),
                esc_attr( 'Mailgun' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://app.mailgun.com/app/account/security/api_keys' ),
                esc_attr( 'API Key.' )
            )
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Domain Name</label></div>
            <input type="text" class="ps-mailgun-domain-name" required data-error="'.__( 'Please Domain Name.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILGUN_DOMAIN_NAME ) .']" value="'.$domain_name.'" placeholder="Domain Name">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://mailgun.com/' ),
                esc_attr( 'Mailgun' ),
                __( 'and enter a', 'post-smtp' ),
                esc_url( 'https://app.mailgun.com/app/domains' ),
                esc_attr( 'Domain Name.' )
            )
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Mailgun Europe Region?</label></div>
            <div class="ps-form-switch-control">
                <label class="ps-switch-1">
                    <input type="checkbox" '.$region.' name="postman_options['.esc_attr( PostmanOptions::MAILGUN_REGION ).']" id="">
                    <span class="slider round"></span>
                </label> 
            </div>
        </div> 
        ';

        return $html;

    }


    /**
     * Render Sendinblue Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_brevo_settings() {

        $api_key = null !== $this->options->getSendinblueApiKey() ? esc_attr ( $this->options->getSendinblueApiKey() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-brevo-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDINBLUE_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://www.brevo.com/' ),
                esc_attr( 'Brevo (Formerly SendInBlue)' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://app.brevo.com/settings/keys/smtp' ),
                esc_attr( 'API Key' )
            )
            .'</span>
        </div>
        ';

        return $html;

    }


    /**
     * Render Sendinblue Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_postmark_settings() {

        $api_key = null !== $this->options->getPostmarkApiKey() ? esc_attr ( $this->options->getPostmarkApiKey() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-postmark-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::POSTMARK_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://postmarkapp.com/' ),
                esc_attr( 'Postmark' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://account.postmarkapp.com/sign_up' ),
                esc_attr( 'API Token' )
            )
            .'</span>
        </div>
        ';

        return $html;

    }


    /**
     * Render Sparkpost Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_sparkpost_settings() {

        $api_key = null !== $this->options->getSparkPostApiKey() ? esc_attr ( $this->options->getSparkPostApiKey() ) : '';

        $html = '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-sparkpost-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SPARKPOST_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <a href="%5$s" target="_blank">%6$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://app.sparkpost.com/join' ),
                esc_attr( 'SparkPost' ),
                __( 'and enter an', 'post-smtp' ),
                esc_url( 'https://app.sparkpost.com/account/api-keys' ),
                esc_attr( 'API Key' )
            )
            .'</span>
        </div>
        ';

        return $html;

    }


    /**
     * Render Office365 Settings
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function render_office365_settings() {

        $options = get_option( PostmanOptions::POSTMAN_OPTIONS );
        $app_client_id = isset( $options['office365_app_id'] ) ? base64_decode( $options['office365_app_id'] ) : '';
        $app_client_secret = isset( $options['office365_app_password'] ) ? base64_decode( $options['office365_app_password'] ) : '';
        $redirect_uri = admin_url();
        $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s </p><a href="%4$s" target="_blank">%5$s</a>',
            esc_url( 'https://postmansmtp.com/extensions/office-365-extension-for-post-smtp/' ),
            __( 'Office 365', 'post-smtp' ),
            __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/pro-extensions/configure-office-365-integration/' ),
            __( 'Read how to setup Office 365', 'post-smtp' ),
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>'.__( 'Application (Client) ID', 'post-smtp' ).'</label></div>
            <input type="text" class="ps-office365-client-id" required data-error="'.__( 'Please enter Application (Client) ID.', 'post-smtp' ).'" name="postman_options[office365_app_id]" value="'.$app_client_id.'" placeholder="Application (Client) ID">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s URL, %2$s URL Text, %3$s Text
             */
            sprintf(
                '<a href="%1$s" target="_blank">%2$s</a> %3$s',
                esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/pro-extensions/configure-office-365-integration/' ),
                __( 'Follow this link', 'post-smtp' ),
                __( 'to get Application (Client) ID for Office 365', 'post-smtp' )
            )
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>'.__( 'Client Secret (Value)', 'post-smtp' ).'</label></div>
            <input type="text" class="ps-office365-client-secret" required data-error="'.__( 'Please enter Client Secret (Value).', 'post-smtp' ).'" name="postman_options[office365_app_password]" value="'.$app_client_secret.'" placeholder="Client Secret (Value)">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s URL, %2$s URL Text, %3$s Text
             */
            sprintf(
                '<a href="%1$s" target="_blank">%2$s</a> %3$s',
                esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/pro-extensions/configure-office-365-integration/' ),
                __( 'Follow this link', 'post-smtp' ),
                __( 'to get Client Secret (Value) for Office 365', 'post-smtp' )
            )
            .'</span>
        </div>
        ';


        $html .= '
        <div class="ps-form-control">
            <div><label>'.__( 'Redirect URI', 'post-smtp' ).'</label></div>
            <input type="text" readonly class="ps-office365-redirect-uri" value="'.$redirect_uri.'">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s URL, %2$s URL Text, %3$s Text
             */
            sprintf(
                '<a href="%1$s" target="_blank">%2$s</a> %3$s',
                esc_url( 'https://postmansmtp.com/documentation/postman-smtp-documentation/pro-extensions/configure-office-365-integration/' ),
                __( 'Follow this link', 'post-smtp' ),
                __( 'to get Redirect URI for Office 365', 'post-smtp' )
            )
            .'</span>
        </div>
        ';

        $html .= '
        <h3>'.__( 'Authorization (Required)', 'post-smtp' ).'</h3>
        <p>'.__( 'Before continuing, you\'ll need to allow this plugin to send emails using your Office 365 account.', 'post-smtp' ).'</p>
        <input type="hidden" '.$required.' data-error="Please authenticate by clicking Connect to Office 365" />
        <a class="button button-primary ps-blue-btn" id="ps-wizard-connect-office365">Connect to Office 365</a>';

        return $html;

    }


    /**
     * Save Wizard | AJAX Callback
     * 
     * @since 2.0.0
     * @version 1.0.0
     */
    public function save_wizard() {

        $form_data = array();
        parse_str( $_POST['FormData'], $form_data );
        $response = false;

        if( 
            isset( $_POST['action'] )
            &&
            'ps-save-wizard' == $_POST['action'] 
            &&
            wp_verify_nonce( $form_data['security'], 'post-smtp' )
        ) {

            if( isset( $form_data['postman_options'] ) && !empty( $form_data['postman_options'] ) ) {

                $sanitized = post_smtp_sanitize_array( $form_data['postman_options'] );
                $options = get_option( PostmanOptions::POSTMAN_OPTIONS );
                $_options = $options;
                $options = $options ? $options : array();

                //for the checkboxes
                $sanitized['prevent_sender_email_override'] = isset( $sanitized['prevent_sender_email_override'] ) ? 1 : '';
                $sanitized['prevent_sender_name_override'] = isset( $sanitized['prevent_sender_name_override'] ) ? 1 : '';

                //Encode API Keys
                $sanitized['office365_app_id'] = isset( $sanitized['office365_app_id'] ) ? base64_encode( $sanitized['office365_app_id'] ) : '';
                $sanitized['office365_app_password'] = isset( $sanitized['office365_app_password'] ) ? base64_encode( $sanitized['office365_app_password'] ) : '';
                $sanitized['sendinblue_api_key'] = isset( $sanitized['sendinblue_api_key'] ) ? base64_encode( $sanitized['sendinblue_api_key'] ) : '';
                $sanitized['sparkpost_api_key'] = isset( $sanitized['sparkpost_api_key'] ) ? base64_encode( $sanitized['sparkpost_api_key'] ) : '';
                $sanitized['postmark_api_key'] = isset( $sanitized['postmark_api_key'] ) ? base64_encode( $sanitized['postmark_api_key'] ) : '';
                $sanitized['mailgun_api_key'] = isset( $sanitized['mailgun_api_key'] ) ? base64_encode( $sanitized['mailgun_api_key'] ) : '';
                $sanitized['sendgrid_api_key'] = isset( $sanitized['sendgrid_api_key'] ) ? base64_encode( $sanitized['sendgrid_api_key'] ) : '';
                $sanitized['mandrill_api_key'] = isset( $sanitized['mandrill_api_key'] ) ? base64_encode( $sanitized['mandrill_api_key'] ) : '';
                $sanitized['basic_auth_password'] = isset( $sanitized['basic_auth_password'] ) ? base64_encode( $sanitized['basic_auth_password'] ) : '';

                foreach( $sanitized as $key => $value ) {

                    $options[$key] = $value;

                }

                if( $options == $_options ) {

                    $response = true;

                } else {

                    $response = update_option( PostmanOptions::POSTMAN_OPTIONS, $options );

                }

            }
            
        }

        wp_send_json( array(), $response ? 200 : 400 );

    }

    

}

new Post_SMTP_New_Wizard();

endif;