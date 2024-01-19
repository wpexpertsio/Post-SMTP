<?php

if( !class_exists( 'Post_SMTP_New_Wizard' ) ):

class Post_SMTP_New_Wizard {

    private $sockets = array();
    private $options;
    private $options_array;
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
        'select'        =>  array(
            'name'          =>  array()
        ),
        'option'        =>  array(
            'value'         =>  array(),
            'selected'      => array()
        )
    );

    private $socket_sequence = array(
        'gmail_api',
        'sendinblue_api',
        'sendgrid_api',
        'mailgun_api',
        'elasticemail_api',
        'mandrill_api',
        'postmark_api',
        'sparkpost_api',
        'mailjet_api',
        'office365_api',
        'aws_ses_api',
        'zohomail_api',
        'smtp',
        'default'
    );

    /**
     * Constructor for the class
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {

        add_filter( 'post_smtp_legacy_wizard', '__return_false' );
        add_action( 'post_smtp_new_wizard', array( $this, 'load_wizard' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_ps-save-wizard', array( $this, 'save_wizard' ) );
        add_action( 'admin_action_zoho_auth_request', array( $this, 'auth_zoho' ) );

        if( isset( $_GET['wizard'] ) && $_GET['wizard'] == 'legacy' ) {

            add_filter( 'post_smtp_legacy_wizard', '__return_true' );

        }

        $this->options_array = get_option( PostmanOptions::POSTMAN_OPTIONS );
        
    }

    /**
     * Load the wizard | Action Callback
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function load_wizard() {

        $transports = PostmanTransportRegistry::getInstance()->getTransports();
        //Not for wizard
        $settings_registry = new PostmanSettingsRegistry();
        $this->options = PostmanOptions::getInstance();
        $is_active = ( isset( $_GET['step'] ) && $_GET['step'] == 2 ) ? 'ps-active-nav' : 'ps-in-active-nav';
        $in_active = ( isset( $_GET['step'] ) && $_GET['step'] != 1 ) ? '' : 'ps-active-nav';
        $selected_tansport = $this->options->getTransportType();
        $socket = isset( $_GET['socket'] ) ? "{$_GET['socket']}-outer" : '';
        ?>

        <div class="ps-pro-popup-overlay">
            <div class="ps-pro-popup-container">
                <div class="ps-pro-popup-outer">
                    <div class="ps-pro-popup-body">
                        <span class="dashicons dashicons-no-alt ps-pro-close-popup"></span>
                        <div class="ps-pro-popup-content">
                            <img src="" class="ps-pro-for-img" />
                            <h1><span class="ps-pro-for"></span> is a Pro feature</h1>
                            <p>
                                We're sorry, the <span class="ps-pro-for"></span> mailer is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome fetures.
                            </p>
                            <div>
                                <a href="https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard" target="_blank" class="button button-primary ps-yellow-btn" style="color: #ffffff!important">UPGRADE TO PRO</a>
                            </div>
                            <div>
                                <a href="" class="ps-pro-close-popup" style="color: #c2c2c2; font-size: 10px;">Already purchased?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                                            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/post-smtp-complete-mailer-guide' ),
                                            __( 'complete mailer guide', 'post-smtp' ),
                                            __( ' for details on each option.', 'post-smtp' )
                                        ); 
                                        ?></p>
                                        <div class="ps-wizard-sockets">      
                                        <?php

                                        $row  = 0;

                                        $transports = array_merge( array_flip( $this->socket_sequence ), $transports );

                                        foreach( $transports as $key => $transport ) {

                                            $urls = array(
                                                'default'           =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp.png',
                                                'smtp'              =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp.png',
                                                'gmail_api'         =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/gmail.png',
                                                'mandrill_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mandrill.png',
                                                'sendgrid_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendgrid.png',
                                                'mailgun_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailgun.png',
                                                'sendinblue_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/brevo.png',
                                                'postmark_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/postmark.png',
                                                'sparkpost_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sparkpost.png',
                                                'mailjet_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailjet.png',
                                                'office365_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/logo.png',
                                                'elasticemail_api'  =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/elasticemail.png',
                                                'aws_ses_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png',
                                                'zohomail_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png'
                                            );

                                            $url = '';
                                            $checked = '';
                                            $slug = '';
                                            $transport_name = '';
                                            $is_pro = '';

                                            if( is_object( $transport ) ) {
                                                
                                                $url = isset( $urls[$transport->getSlug()] ) ? $urls[$transport->getSlug()] : $transport->getLogoURL();
                                                $this->sockets[$transport->getSlug()] = $transport->getName();
                                                $checked = $transport->getSlug() == $this->options->getTransportType() ? 'checked' : '';
                                                $slug = $transport->getSlug();
                                                $transport_name = $transport->getName();

                                            }
                                            else {
                                                
                                                $transport_slug = $key;

                                                if( $transport_slug == 'office365_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Microsoft 365';
                                                    $is_pro = 'ps-pro-extension';

                                                }
                                                if( $transport_slug == 'zohomail_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Zoho';
                                                    $is_pro = 'ps-pro-extension';

                                                }
                                                if( !class_exists( 'Post_Smtp_Amazon_Ses' ) && $transport_slug == 'aws_ses_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Amazon SES';
                                                    $is_pro = 'ps-pro-extension';

                                                }

                                            }

                                            if( $row >= 4 ) {

                                                $row = 0;

                                                ?>
                                                </div>
                                                <div class="ps-wizard-sockets">
                                                <?php


                                            }

                                            ?>
                                            <div class="ps-wizard-socket-radio-outer">
                                                <div class="ps-wizard-socket-radio <?php echo !empty( $is_pro ) ? esc_attr( $is_pro ) . '-outer' : ''; ?>">
                                                    <?php if( !empty( $is_pro ) ): ?>
                                                        <span class="<?php echo $is_pro . '-tag' ?>">PRO</span>
                                                    <?php endif; ?>
                                                    <?php if( empty( $is_pro ) ) : ?> <label for="ps-wizard-socket-<?php echo esc_attr( $slug ); ?>"><?php endif; ?>                                                    
                                                        <?php if( empty( $is_pro ) ) : ?> 
                                                            <input type="radio" <?php echo esc_attr( $checked ) ;?> class="ps-wizard-socket-check <?php echo esc_attr( $is_pro ); ?>" id="ps-wizard-socket-<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $slug ); ?>" name="<?php echo 'postman_options[' . esc_attr( PostmanOptions::TRANSPORT_TYPE ) . ']'; ?>">
                                                        <?php endif; ?>
                                                        <img src="<?php echo esc_url( $url ); ?>">
                                                        <?php if( empty( $is_pro ) ) : ?>
                                                            <div class="ps-wizard-socket-tick-container">
                                                                <div class="ps-wizard-socket-tick"><span class="dashicons dashicons-yes"></span></div>
                                                            </div> 
                                                        <?php endif; ?>
                                                        <h4><?php echo esc_attr( $transport_name ); ?></h4>
                                                    <?php if( empty( $is_pro ) ) : ?> </label><?php endif; ?>
                                                </div>
                                            </div>
                                            <?php

                                            $row++;

                                        }
                                        ?>
                                        </div>
                                        <p style="width: 70%; margin-bottom: 30px;">
                                        <?php echo sprintf(
                                            '%1$s <i><a href="%2$s">%3$s</a></i>',
                                            __( 'Did we miss out What you are looking for?', 'post-smtp' ),
                                            esc_url( admin_url( 'admin.php?page=postman-contact' ) ),
                                            __( 'Suggest your Mailer', 'post-smtp' )
                                        ); ?>
                                        </p>
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
                                                    <h3><?php echo $title == 'Default' ? '' : esc_attr( $title ); ?></h3>
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
                                                <span class="ps-form-control-info">Enter the email address where you want to send a test email message.</span>
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
                                        <div class="ps-home-middle-right" style="background-image: url(<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/mobile-banner.png' ) ?>); float: unset; width: 100%; height: 230px;">
                                            <div class="ps-mobile-notice-content">
                                                <p><?php _e( 'Introducing NEW Post SMTP Mobile App' ); ?></p>
                                                <div class="ps-mobile-notice-features">
                                                    <div class="ps-mobile-feature-left">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Easy Email Tracking
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Quickly View Error Details
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Easy Email Tracking			
                                                    </div>
                                                    <div class="ps-mobile-feature-right">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Get Email Preview
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Resend Failed Emails
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        Support multiple sites		
                                                    </div>
                                                </div>
                                                <div style="display: flex;">
                                                    <div class="ps-app-download-button">
                                                        <a href="https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Ddashboard%26anid%3Dadmob" target="_blank">Download on Android</a>
                                                    </div>
                                                    <div class="ps-app-download-button">
                                                        <a href="https://apps.apple.com/us/app/post-smtp/id6473368559" target="_blank">Download on iOS</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="clear: both"></div>
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
                                <p class="ps-wizard-success"><?php echo ( isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <p class="ps-wizard-error"><?php echo ( !isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="2"></span>Save and Continue <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-3">
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn ps-finish-wizard" data-step="3"><?php _e( 'I\'ll send a test email later.', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function enqueue_scripts() {

        $localized = array(
            'Step1E1'           => __( 'Select a socket type to continue.', 'post-smtp' ),
            'Step2E2'           => __( 'Please enter From Email.', 'post-smtp' ),
            'Step2E3'           => __( 'Please try again, something went wrong.', 'post-smtp' ),
            'Step3E4'           => __( 'Please enter recipient email address.', 'post-smtp' ),
            'finish'           => __( 'Finish', 'post-smtp' ),
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

        wp_enqueue_style( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/css/wizard.css', array(), POST_SMTP_VER );
        wp_enqueue_script( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/js/wizard.js', array( 'jquery' ), POST_SMTP_VER );
        wp_localize_script( 'post-smtp-wizard', 'PostSMTPWizard', $localized );

    }


    /**
     * Render Name and Email Settings
     * 
     * @since 2.7.0
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
                    esc_html__( 'This address, like the letterhead printed on a letter, identifies the sender to the recipient. Change this when you are sending on behalf of someone else.', 'post-smtp' )
                ) .'</p>
                <div><label>From Email</label></div>
                <input type="text" class="ps-from-email" required data-error="'.__( 'Please enter From Email.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_EMAIL ).']" value="'.$from_email.'" placeholder="From Email">
                <span class="ps-form-control-info">'.__( 'The email address that emails are sent from.', 'post-smtp' ).'</span>
                <div class="ps-form-control-info">'.__( 'Please note that other plugins may override this field, to prevent this use the setting below.', 'post-smtp' ).'</div>
                <div>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_email_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ).']" id="">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b> %3$s <b>%4$s</b> %5$s <b>%6$s</b>',
                        __( 'Check this to prevent changes on the', 'post-smtp' ),
                        __( 'From Email', 'post-smtp' ),
                        __( 'field by other', 'post-smtp' ),
                        __( 'Plugins', 'post-smtp' ),
                        __( 'and', 'post-smtp' ),
                        __( 'Themes', 'post-smtp' )
                    ).
                    '</span>
                </div> 
            </div>
            <div class="ps-form-control">
                <div><label>From Name</label></div>
                <input type="text" class="ps-from-name" required data-error="'.__( 'Please enter From Name.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_NAME ).']" value="'.$from_name.'" placeholder="From Name">
                <span class="ps-form-control-info">The name that emails are sent from.</span>
                <div>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_name_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ).']" id="">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b> %3$s <b>%4$s</b> %5$s <b>%6$s</b>',
                        __( 'Check this to prevent changes on the', 'post-smtp' ),
                        __( 'From Name', 'post-smtp' ),
                        __( 'field by other', 'post-smtp' ),
                        __( 'Plugins', 'post-smtp' ),
                        __( 'and', 'post-smtp' ),
                        __( 'Themes', 'post-smtp' )
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_socket_settings( $socket ) {

        switch ( $socket ) {
            
            case 'default':
                echo wp_kses( $this->render_default_settings(), $this->allowed_tags );
            break;
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
            case 'mailjet_api':
                echo wp_kses( $this->render_mailjet_settings(), $this->allowed_tags );
            break;
            case 'elasticemail_api':
                echo wp_kses( $this->render_elasticemail_settings(), $this->allowed_tags );
            break;
            case 'aws_ses_api';
                echo wp_kses( $this->render_amazonses_settings(), $this->allowed_tags );
            break;
            case 'office365_api';
                echo wp_kses( $this->render_office365_settings(), $this->allowed_tags );
            break;
            case 'zohomail_api';
                echo wp_kses( $this->render_zoho_settings(), $this->allowed_tags );
            break;

        }

    }

    /**
     * Render default Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_default_settings() {

        $html = '<input type="hidden" name="postman_options=[transport_type]" value="dafault" />';

        return $html;

    }


    /**
     * Render SMTP Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_smtp_settings() {

        $hostname = null !== $this->options->getHostname() ? esc_attr ( $this->options->getHostname() ) : '';
        $port = null !== $this->options->getPort() ? esc_attr ( $this->options->getPort() ) : '';
        $username = null !== $this->options->getUsername() ? esc_attr ( $this->options->getUsername() ) : '';
        $password = null !== $this->options->getPassword() ? esc_attr ( $this->options->getPassword() ) : '';

        $html = '
        <p>'.__( 'The SMTP option lets you send emails directly through an SMTP server instead of using a SMTP Server provider\'s API. This is easy and convenient, but it\'s less secure than the other mailers.', 'post-smtp' ).'</p>
        <p>'.sprintf(
            '%1$s <a href="%2$s" target="_blank">%3$s</a>',
            __( 'Let\'s get started with our ', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-other-smtp' ),
            __( 'SMTP Documentation', 'post-smtp' )
        ).'</p>
        ';

        $html .= '
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_gmail_settings() {

        $client_id = null !== $this->options->getClientId() ? esc_attr ( $this->options->getClientId() ) : '';
        $client_secret = null !== $this->options->getClientSecret() ? esc_attr ( $this->options->getClientSecret() ) : '';
        $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';

        $html = '
        <p>'.sprintf(
            '%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
            __( 'Our', 'post-smtp' ),
            esc_url( 'https://www.google.com/gmail/about/' ),
            __( 'Gmail mailer', 'post-smtp' ),
            __( 'works with any Gmail or Google Workspace account via the Google API. You can send WordPress emails from your main email address and it\'s more secure than directly connecting to Gmail using SMTP credentials.', 'post-smtp' )
        ).'
        </p>';

        $html .= __( 'The configuration steps are more technical than other options, so our detailed guide will walk you through the whole process.', 'post-smtp' );

        $html .= '
        <p>'.sprintf(
            '%1$s <a href="%2$s" target="_blank">%3$s</a>',
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/gmail/' ),
            __( 'Gmail Documentation', 'post-smtp' )
        ).'
        </p>';

        $html .= '
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
            <span class="ps-form-control-info">
            '.__( 'Please copy this URL into the "Authorized redirect URL" field of your Gmail account settings.', 'post-smtp' ).'
            </span>
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
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_mandrill_settings() {

        $api_key = null !== $this->options->getMandrillApiKey() ? esc_attr ( $this->options->getMandrillApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://mandrillapp.com/login/?referrer=%2F' ),
            __( 'Mandrill', 'post-smtp' ),
            __( 'is an email infrastructure service offered as an add-on for MailChimp that you can use to send personalized, one-to-one e-commerce emails, or automated transactional emails.You can easily send WordPress emails from your Mandrill account.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mandrill-with-post-smtp/' ),
            __( 'Mandrill Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mandrill-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MANDRILL_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://mandrillapp.com/login/?referrer=%2F' ),
                esc_attr( 'Mandrill' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://mandrillapp.com/settings/index' ),
                esc_attr( 'API Key.' )
            )
            .'
        </div>
        ';

        return $html;

    }


    /**
     * Render SendGrid Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_sendgrid_settings() {

        $api_key = null !== $this->options->getSendGridApiKey() ? esc_attr ( $this->options->getSendGridApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://sendgrid.com/' ),
            __( 'SendGrid', 'post-smtp' ),
            __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendgrid-with-post-smtp/' ),
            __( 'SendGrid Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-sendgrid-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDGRID_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://sendgrid.com/' ),
                esc_attr( 'SendGrid' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.sendgrid.com/settings/api_keys' ),
                esc_attr( 'API Key.' )
            ).'
        </div>
        ';

        return $html;

    }


    /**
     * Render Mailgun Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_mailgun_settings() {

        $api_key = null !== $this->options->getMailgunApiKey() ? esc_attr ( $this->options->getMailgunApiKey() ) : '';
        $domain_name = null !== $this->options->getMailgunDomainName() ? esc_attr ( $this->options->getMailgunDomainName() ) : '';
        $region = null !== $this->options->getMailgunRegion() ? ' checked' : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://www.mailgun.com/' ),
            __( 'Mailgun', 'post-smtp' ),
            __( 'is a transactional email provider that offers a generous 3-month free trial. After that, it offers a \'Pay As You Grow\' plan that allows you to pay for what you use without committing to a fixed monthly rate.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-mail-gun/' ),
            __( 'Mailgun Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailgun-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILGUN_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://www.mailgun.com/' ),
                esc_attr( 'Mailgun' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.mailgun.com/settings/api_security' ),
                esc_attr( 'API Key.' )
            )
            .'
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
                '%1$s <a href="%2$s" target="_blank">%3$s</a>',
                __( ' Follow this link to get the Mailgun', 'post-smtp' ),
                esc_url( 'https://app.mailgun.com/app/sending/domains' ),
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
            '.
            sprintf(
                '<div class="ps-form-control-info">%1$s</div><div class="ps-form-control-info">%2$s <a href="%3$s" target="_blank">%4$s</a> %5$s</div>',
                __( 'Define your endpoint to send messages.', 'post-smtp' ),
                __( 'If you are operating under EU laws then check the above button.', 'post-smtp' ),
                esc_url( 'https://www.mailgun.com/about/regions/' ),
                __( 'More information', 'post-smtp' ),
                __( 'about Mailgun.', 'post-smtp' )
            )
            .'
        </div> 
        ';

        return $html;

    }


    /**
     * Render Brevo Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_brevo_settings() {

        $api_key = null !== $this->options->getSendinblueApiKey() ? esc_attr ( $this->options->getSendinblueApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">Brevo</a> %2$s</p><p>%3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a>',
            esc_url( 'https://www.brevo.com/products/transactional-email/?tap_a=30591-fb13f0&tap_s=1114139-605ce2' ),
            __( 'is one of our recommended mailers. It\'s a transactional email provider with scalable price plans, so it\'s suitable for any size of business.', 'post-smtp' ),
            __( 'If you\'re just starting out, you can use Brevo\'s free plan to send up to 300 emails a day. You don\'t need to use a credit card to try it out. When you\'re ready, you can upgrade to a higher plan to increase your sending limits.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendinblue-aka-brevo-with-post-smtp/' ),
            __( 'Brevo Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-brevo-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDINBLUE_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://www.brevo.com/products/transactional-email/?tap_a=30591-fb13f0&tap_s=1114139-605ce2' ),
                esc_attr( 'Brevo' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.brevo.com/settings/keys/api' ),
                esc_attr( 'API Key.' )
            )
            .
        '</div>
        ';

        return $html;

    }


    /**
     * Render Postmark Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_postmark_settings() {

        $api_key = null !== $this->options->getPostmarkApiKey() ? esc_attr ( $this->options->getPostmarkApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://postmarkapp.com/' ),
            __( 'Postmark', 'post-smtp' ),
            __( 'is a transactional email provider that offers great deliverability and accessible pricing for any business. You can start out with the free trial that allows you to send 100 test emails each month via its secure API.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/postmark/' ),
            __( 'PostMark Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-postmark-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::POSTMARK_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://postmarkapp.com/' ),
                esc_attr( 'Postmark' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://account.postmarkapp.com/api_tokens' ),
                esc_attr( 'API Key or Server API Token.' )
            )
            .'
        </div>
        ';

        return $html;

    }


    /**
     * Render Sparkpost Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_sparkpost_settings() {

        $api_key = null !== $this->options->getSparkPostApiKey() ? esc_attr ( $this->options->getSparkPostApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://www.sparkpost.com/' ),
            __( 'SparkPost', 'post-smtp' ),
            __( 'is a transactional email provider that\'s trusted by big brands and small businesses. It sends more than 4 trillion emails each year and reports 99.9% uptime. You can get started with the free test account that lets you send up to 500 emails per month.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/sparkpost/' ),
            __( 'SparkPost Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-sparkpost-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SPARKPOST_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://app.sparkpost.com/join' ),
                esc_attr( 'SparkPost' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.sparkpost.com/account/api-keys' ),
                esc_attr( 'API Key.' )
            )
            .'
        </div>
        ';

        return $html;

    }

    /**
     * Render ElasticEmail Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_elasticemail_settings() {

        $api_key = null !== $this->options->getElasticEmailApiKey() ? esc_attr ( $this->options->getElasticEmailApiKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">Elastic Email</a> %2$s</p><p>%3$s <a href="%4$s" target="_blank">%5$s</a>',
            esc_url( 'https://elasticemail.com/' ),
            __( 'is a powerful transactional email platform designed to deliver exceptional performance and affordability for businesses of all sizes. which grants you the ability to send 100 test emails every month through our secure API.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-elastic-email' ),
            __( 'Elastic Email Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-elasticemail-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::ELASTICEMAIL_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://elasticemail.com/' ),
                esc_attr( 'Elastic Email' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://elasticemail.com/account#/settings/new/manage-api' ),
                esc_attr( 'API Key.' )
            )
            .
        '</div>
        ';

        return $html;

    }

    /**
     * Render Mailjet Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_mailjet_settings() {

        $api_key = null !== $this->options->getMailjetApiKey() ? esc_attr ( $this->options->getMailjetApiKey() ) : '';
        $secret_key = null !== $this->options->getMailjetApiKey() ? esc_attr ( $this->options->getMailjetSecretKey() ) : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">Mailjet</a> %2$s</p><p>%6$s<p>%3$s <a href="%4$s" target="_blank">%5$s</a>',
            esc_url( 'https://app.mailjet.com/signin?redirect=aHR0cHM6Ly9hcHAubWFpbGpldC5jb20vfDI0fDgyMzU3ZDFmMWE4Y2NjMjc4ZWRhMzI0MDUzZTNlMjY0' ),
            __( 'is a leading email service provider that delivers a complete set of email marketing and transactional email solutions.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-mailjet' ),
            __( 'Mailjet Documentation', 'post-smtp' ),
            __( 'Mailjet’s platform enables you to create, send, and track email marketing campaigns, transactional email messages, and email performance metrics.', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-elasticemail-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILJET_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key"></div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Secret Key</label></div>
            <input type="text" class="ps-elasticemail-secret-key" required data-error="'.__( 'Please enter Secret Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILJET_SECRET_KEY ) .']" value="'.$secret_key.'" placeholder="Secret Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://app.mailjet.com/signup' ),
                esc_attr( 'Mailjet' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.mailjet.com/account/apikeys' ),
                esc_attr( 'Mailjet API and Access Key' )
            )
            .
        '</div>
        ';

        return $html;

    }

    /**
     * Render Amazon SES Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_amazonses_settings() {

        $access_key_id = isset( $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_ACCESS_KEY_ID ] ) ? base64_decode( $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_ACCESS_KEY_ID ] ) : '';
        $access_key_secret = isset( $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_SECRET_ACCESS_KEY ] ) ? base64_decode( $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_SECRET_ACCESS_KEY ] ) : '';
        $region = isset( $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_REGION ] ) ? $this->options_array[ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_REGION ] : '';

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">Amazon Simple Email Service (Amazon SES)</a> %2$s</p><p>%3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a>',
            esc_url( 'https://aws.amazon.com/ses/' ),
            __( 'a cloud-based email platform was developed by AWS to make sending and receiving emails at scale simple and effective. They also offer tools to create and send out marketing emails.', 'post-smtp' ),
            __( 'To use Amazon SES for your wordpress site, you must have an SSL certificate installed on your WordPress site.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/amazon-ses-pro/' ),
            __( 'Amazon SES Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>Access Key ID</label></div>
            <input type="text" class="ps-amazon-key-id" required data-error="'.__( 'Please enter Access Key ID', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_ACCESS_KEY_ID ) .']" value="'.$access_key_id.'" placeholder="Access Key ID"></div>';

        $html .= '
        <div class="ps-form-control">
            <div><label>Access Key Secret</label></div>
            <input type="text" class="ps-amazon-key-secret" required data-error="'.__( 'Please enter Access Key Secret', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_SECRET_ACCESS_KEY ) .']" value="'.$access_key_secret.'" placeholder="Access Key Secret">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://portal.aws.amazon.com/billing/signup?nc2=h_ct&src=header_signup&redirect_url=https%3A%2F%2Faws.amazon.com%2Fregistration-confirmation#/start/email' ),
                esc_attr( 'Amazon SES' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://us-east-1.console.aws.amazon.com/iamv2/home#/users' ),
                esc_attr( 'Access Key ID and Sceret Access Key' )
            )
            .
        '</div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>SES Region</label></div>
            <input type="text" class="ps-amazon-region" required data-error="'.__( 'Please enter SES Region', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_REGION ) .']" value="'.$region.'" placeholder="SES Region"></div>
        ';

        return $html;

    }


    /**
     * Render Office365 Settings
     * 
     * @since 2.7.0
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
            __( 'Read how to setup Office 365', 'post-smtp' )
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
     * Render Gmail API Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_zoho_settings() {

        $regions = array(
            'com'       => __( 'United States (US)', 'postsmtp-zoho' ),
            'eu'        => __( 'Europe (EU)', 'postsmtp-zoho' ),
            'in'        => __( 'India (IN)', 'postsmtp-zoho' ),
            'com.cn'    => __( 'China (CN)', 'postsmtp-zoho' ),
            'com.au'    => __( 'Australia (AU)', 'postsmtp-zoho' ),
            'jp'        => __( 'Japan (JP)', 'postsmtp-zoho' ),
        );
        $selected_region = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_REGION ] ) ? $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_REGION ]: '';
        
        $client_id = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ] ) ? $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ] : '';
        $client_secret = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] ) ? $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] : '';
        $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';

        $html = '
        <p>'.sprintf(
            '<a href="%1$s" target="_blank">%2$s</a> %3$s',
            esc_url( 'https://www.zoho.com/mail/' ),
            __( 'Zoho', 'post-smtp' ),
            __( 'is a well-known provider of cloud-based business software and services. Zoho Corporation offers Zoho Mail, a leading email hosting and collaboration solution.', 'post-smtp' )
        ).'
        </p>';

        $html .= '<p>' . __( 'Zoho Mail offers free email accounts as well as domain-specific email accounts. You can use Zoho Mail\'s API to help emails from your WordPress site deliver reliably.', 'post-smtp' ) . '</p>';

        $html .= '
        <p>'.sprintf(
            '%1$s <a href="%2$s" target="_blank">%3$s</a>',
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/zoho-mail-pro/' ),
            __( 'Zoho Mail Documentation', 'post-smtp' )
        ).'
        </p>';

        $html .= '
        <div class="ps-form-control">
            <div><label>Region</label></div>
            <select>';
        foreach( $regions as $key => $value ) {

            $selected = $key == $selected_region ? 'selected="selected"' : '';

            $html .= "<option value='{$key}' {$selected}>{$value}</option>";

        }
        $html .= '</select></div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Client ID</label></div>
            <input type="text" class="ps-zoho-client-id" required data-error="'.__( 'Please enter Client ID.', 'post-smtp' ).'" name="postman_options['. esc_attr( ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ) .']" value="'.$client_id.'" placeholder="Client ID">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Client Secret</label></div>
            <input type="text" class="ps-zoho-client-secret" required data-error="'.__( 'Please enter Client Secret.', 'post-smtp' ).'" name="postman_options['. esc_attr( ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ) .']" value="'.$client_secret.'" placeholder="Client Secret">
            <div class="ps-form-control-info">
            '.sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://www.zoho.com/mail/' ),
                __( 'Zoho Mail', 'post-smtp' )
            ).'
            </div>
            <div class="ps-form-control-info">
            '.sprintf(
                '%1$s <a href="%2$s" target="_blank">%3$s</a>',
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://api-console.zoho.com/' ),
                __( 'Zoho API Credentials', 'post-smtp' )
            ).'
            </div>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Redirect URI</label></div>
            <input type="text" class="ps-zoho-redirect-uri" value="'.admin_url( 'admin.php?page=postman/' ).'" readonly>
            <span class="ps-form-control-info">
            '.__( 'Please copy this URL into the "Redirect URL" field of your Zoho account settings.', 'post-smtp' ).'
            </span>
        </div>
        ';
        
        $html .= '
        <h3>'.__( 'Authorization (Required)', 'post-smtp' ).'</h3>
        <p>'.__( 'Before continuing, you\'ll need to allow this plugin to send emails using Zoho.', 'post-smtp' ).'</p>
        <input type="hidden" '.$required.' data-error="Please authenticate by clicking Connect to Zoho" />
        <a href="'.admin_url( 'admin.php?postman/configuration_wizard&action=zoho_auth_request' ).'" class="button button-primary ps-blue-btn" id="ps-wizard-connect-zoho">Connect to Zoho</a>';

        return $html;

    }


    /**
     * Save Wizard | AJAX Callback
     * 
     * @since 2.7.0
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
                
                //Envelop Email Address
                $sanitized['envelope_sender'] = isset( $sanitized['sender_email'] ) ? $sanitized['sender_email'] : '';

                //Encode API Keys
                $sanitized['office365_app_id'] = isset( $sanitized['office365_app_id'] ) ? $sanitized['office365_app_id'] : '';
                $sanitized['office365_app_password'] = isset( $sanitized['office365_app_password'] ) ? $sanitized['office365_app_password'] : '';
                $sanitized[PostmanOptions::SENDINBLUE_API_KEY] = isset( $sanitized[PostmanOptions::SENDINBLUE_API_KEY] ) ? $sanitized[PostmanOptions::SENDINBLUE_API_KEY] : '';
                $sanitized['sparkpost_api_key'] = isset( $sanitized['sparkpost_api_key'] ) ? $sanitized['sparkpost_api_key'] : '';
                $sanitized['postmark_api_key'] = isset( $sanitized['postmark_api_key'] ) ? $sanitized['postmark_api_key'] : '';
                $sanitized['mailgun_api_key'] = isset( $sanitized['mailgun_api_key'] ) ? $sanitized['mailgun_api_key'] : '';
                $sanitized[PostmanOptions::SENDGRID_API_KEY] = isset( $sanitized[PostmanOptions::SENDGRID_API_KEY] ) ? $sanitized[PostmanOptions::SENDGRID_API_KEY] : '';
                $sanitized['mandrill_api_key'] = isset( $sanitized['mandrill_api_key'] ) ? $sanitized['mandrill_api_key'] : '';
                $sanitized['elasticemail_api_key'] = isset( $sanitized['elasticemail_api_key'] ) ? $sanitized['elasticemail_api_key'] : '';
                $sanitized[PostmanOptions::MAILJET_API_KEY] = isset( $sanitized[PostmanOptions::MAILJET_API_KEY] ) ? $sanitized[PostmanOptions::MAILJET_API_KEY] : '';
                $sanitized[PostmanOptions::MAILJET_SECRET_KEY] = isset( $sanitized[PostmanOptions::MAILJET_SECRET_KEY] ) ? $sanitized[PostmanOptions::MAILJET_SECRET_KEY] : '';
                $sanitized['basic_auth_password'] = isset( $sanitized['basic_auth_password'] ) ? $sanitized['basic_auth_password'] : '';
                $sanitized['ses_access_key_id'] = isset( $sanitized['ses_access_key_id'] ) ? $sanitized['ses_access_key_id'] : '';
                $sanitized['ses_secret_access_key'] = isset( $sanitized['ses_secret_access_key'] ) ? $sanitized['ses_secret_access_key'] : '';
                $sanitized['ses_region'] = isset( $sanitized['ses_region'] ) ? $sanitized['ses_region'] : '';
                $sanitized['enc_type'] = 'tls';
                $sanitized['auth_type'] = 'login';
                
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

        //Prevent redirection
        delete_transient( PostmanSession::ACTION );

        wp_send_json( array(), 200 );

    }

    /**
     * Redirect to Zoho Authentication
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function auth_zoho() {

        $zoho_mailer = new PostSMTP_ZohoMail();
        $oauthClient = $zoho_mailer->zohomail_configuration();
        $PostmanOauthClient = new ZohoMailPostSMTP\ZohoMailOauth( $oauthClient );
        $state = get_transient( PostSMTP_ZohoMail::STATE );
        // Save client state so we can validate in response
        
        if ( $state === false ) {
            $state = bin2hex( random_bytes( 32 / 2 ) );
            set_transient( PostSMTP_ZohoMail::STATE, $state, 5 * MINUTE_IN_SECONDS );
        }
        
        // // Generate the auth URL
        $redirect_url = $PostmanOauthClient->getZohoMailAuthURL( array(
            'state' => $state,
        ) );

        wp_redirect( $redirect_url );

    }

}

new Post_SMTP_New_Wizard();

endif;