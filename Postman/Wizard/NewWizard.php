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
            'readonly'      =>  array(),
            'disabled'      =>  array()
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
        ),
        'hr'            =>  array()
    );

    private $socket_sequence = array();

    /**
     * Constructor for the class
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {

        $this->socket_sequence = array(
            'gmail_api',
            'sendinblue_api',
            'sendgrid_api',
            'mailgun_api',
            'elasticemail_api',
            'mandrill_api',
            'postmark_api',
            'sparkpost_api',
            'mailjet_api',
            'smtp2go_api',
            'sendpulse_api',
            
        );
        
        if( !is_plugin_active( 'post-smtp-pro/post-smtp-pro.php' ) ) {

            $this->socket_sequence[] = 'office365_api';
            $this->socket_sequence[] = 'aws_ses_api';
            $this->socket_sequence[] = 'zohomail_api';

        }

        $this->socket_sequence[] = 'mailersend_api';
        $this->socket_sequence[] = 'smtp';
        $this->socket_sequence[] = 'default';
        
        add_filter( 'post_smtp_legacy_wizard', '__return_false' );
        add_action( 'post_smtp_new_wizard', array( $this, 'load_wizard' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_ps-save-wizard', array( $this, 'save_wizard' ) );
        add_action( 'wp_ajax_update_post_smtp_pro_option', array( $this, 'update_post_smtp_pro_option_callback' ) );
        add_action( 'admin_action_zoho_auth_request', array( $this, 'auth_zoho' ) );
        add_action( 'admin_post_remove_oauth_action', array( $this, 'post_smtp_remove_oauth_action' ) );
        add_action( 'admin_init', array( $this, 'handle_gmail_oauth_redirect' ) );

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
        // Add popup trigger file
        require_once POST_SMTP_PATH. '/Postman/Popup/popup.php';
        ?>
 
        
        <div class="wrap">
            <div class="ps-wizard">
                <div class="ps-logo">
                    <img src="<?php echo esc_attr( POST_SMTP_ASSETS ) . '/images/logos/post-smtp-logo-large.svg'; ?>" width="250px" />
                </div>
                <div class="ps-wizard-outer <?php echo esc_attr( $socket ); ?>">
                    <div class="ps-wizard-section">
                        <div class="ps-wizard-nav">
                            <table>
                                <tr class="<?php echo esc_attr( $in_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Choose your SMTP Mailer', 'post-smtp' ) ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="1"></span></td>
                                </tr>
                                <tr class="<?php echo esc_attr( $is_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Configure Mailer Settings', 'post-smtp' ) ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="2"></span></td>
                                </tr>
                                <tr class="ps-in-active-nav">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Send Test Email', 'post-smtp' ) ?></td>
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
                                                'mailersend_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailersend.png',
                                                'mailgun_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailgun.png',
                                                'sendinblue_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/brevo.png',
                                                'postmark_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/postmark.png',
                                                'sparkpost_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sparkpost.png',
                                                'mailjet_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailjet.png',
                                                'sendpulse_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendpulse.png',
                                                'smtp2go_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp2go.png',
                                                'office365_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png',
                                                'elasticemail_api'  =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/elasticemail.png',
                                                'aws_ses_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png',
                                                'zohomail_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png'
                                            );

                                            $url = '';
                                            $checked = '';
                                            $slug = '';
                                            $transport_name = '';
                                            $is_pro = '';
                                            $product_url = '';

                                            if( is_object( $transport ) ) {
                                                
                                                $url = isset( $urls[$transport->getSlug()] ) ? $urls[$transport->getSlug()] : $transport->getLogoURL();
                                                $this->sockets[$transport->getSlug()] = $transport->getName();

                                                if( isset( $_GET['socket'] ) && !empty( sanitize_text_field( $_GET['socket'] ) ) && $transport->getSlug() == sanitize_text_field( $_GET['socket'] ) ) {

                                                    $checked = 'checked';

                                                }
                                                elseif( $transport->getSlug() == $this->options->getTransportType() && !is_plugin_active( 'post-smtp-pro/post-smtp-pro.php' ) ) {

                                                    $checked = 'checked';

                                                }
                                                
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
                                                    $product_url = postman_is_bfcm() ? 'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_microsoft&utm_campaign=plugin';

                                                }
                                                if( $transport_slug == 'zohomail_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Zoho';
                                                    $is_pro = 'ps-pro-extension';
                                                    $product_url = postman_is_bfcm() ? 'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_zoho&utm_campaign=plugin';

                                                }
                                                if( !class_exists( 'Post_Smtp_Amazon_Ses' ) && $transport_slug == 'aws_ses_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Amazon SES';
                                                    $is_pro = 'ps-pro-extension';
                                                    $product_url = postman_is_bfcm() ? 'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_amazonses&utm_campaign=plugin';

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
                                                <div class="ps-wizard-socket-radio <?php echo !empty( $is_pro ) ? esc_attr( $is_pro ) . '-outer' : ''; ?>" <?php echo !empty( $is_pro ) ? 'data-url="' . esc_url( $product_url ) . '"' : ''; ?>>
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
                                        <a href="" data-step="2" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span><?php _e( 'Back', 'post-smtp' ) ?></a>
                                        <p><?php _e( 'This step allows you to send an email message for testing. If there is a problem, Post SMTP will give up after 60 seconds.', 'post-smtp' ); ?></p>
                                        <div class="ps-form-ui">
                                            <div class="ps-form-control">
                                                <div><label><?php _e( 'Recipient Email Address', 'post-smtp' ) ?></label></div>
                                                <input type="text" class="ps-test-to" required data-error="Enter Recipient Email Address" name="postman_test_options[test_email]" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" placeholder="Recipient Email Address">
                                                <span class="ps-form-control-info"><?php _e( 'Enter the email address where you want to send a test email message.', 'post-smtp' ) ?></span>
                                                <p class="ps-form-control-info"><?php _e( 'Are your WordPress emails getting broken? Check out our guide on', 'post-smtp' ) ?> <a href="https://postmansmtp.com/fix-for-broken-emails/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin" target="_blank"><?php _e( 'how to Fix Broken Emails', 'post-smtp' ) ?></a>.</p>
                                            </div>
                                            <button class="button button-primary ps-blue-btn ps-wizard-send-test-email" data-step="3"><?php _e( 'Send Test Email', 'post-smtp' ) ?> <span class="dashicons dashicons-email"></span></button>
                                            <div>
                                                <p class="ps-wizard-error"></p>
                                                <p class="ps-wizard-success"></p>
                                                <p class="ps-wizard-health-report"></p>
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
                                            __( 'We value your opinion on your experience with Post SMTP and would appreciate your feedback. ', 'post-smtp' ),
                                            esc_url( 'https://wordpress.org/support/plugin/post-smtp/reviews/#new-post' ),
                                            __( 'Leave a review here.', 'post-smtp' )
                                        ) ?></p>
                                        <div class="ps-home-middle-right" style="background-image: url(<?php echo esc_url( POST_SMTP_ASSETS . 'images/icons/mobile-banner.png' ) ?>); float: unset; width: 100%; height: 230px;">
                                            <div class="ps-mobile-notice-content">
                                                <p><?php _e( 'Introducing NEW Post SMTP Mobile App' ); ?></p>
                                                <div class="ps-mobile-notice-features">
                                                    <div class="ps-mobile-feature-left">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Easy Email Tracking', 'post-smtp' ) ?>
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Quickly View Error Details', 'post-smtp' ) ?>
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Easy Email Tracking', 'post-smtp' ) ?>                                                   			
                                                    </div>
                                                    <div class="ps-mobile-feature-right">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Get Email Preview', 'post-smtp' ) ?>                                               
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Resend Failed Emails', 'post-smtp' ) ?>                                                    
                                                        <br>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                        <?php _e( 'Support multiple sites', 'post-smtp' ) ?>                                                      
                                                    </div>
                                                </div>
                                                <div style="display: flex; margin-top: 15px;">
                                                    <div class="ps-app-download-button">
                                                        <a href="https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Ddashboard%26utm_campaign%3Dplugin%26anid%3Dadmob" target="_blank"><img src=<?php echo esc_url( POST_SMTP_URL . '/Postman/Wizard/assets/images/android-icon.png' ) ?>><div><p style="font-size: 12px;">Get it On</p><p style="font-size: 14px; font-weight: 750">Google Play</p></div></a>
                                                    </div>
                                                    <div class="ps-app-download-button">
                                                        <a href="https://apps.apple.com/us/app/post-smtp/id6473368559?utm_source=plugin&utm_medium=dashboard&utm_campaign=plugin" target="_blank"><img src=<?php echo esc_url( POST_SMTP_URL . '/Postman/Wizard/assets/images/apple-icon.png' ) ?>><div><p style="font-size: 12px;">Download on the</p><p style="font-size: 14px; font-weight: 750;">App Store</p></div></a>
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
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="1"><?php _e( 'Continue', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-2">
                                <p class="ps-wizard-success"><?php echo ( isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <p class="ps-wizard-error"><?php echo ( !isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="2"></span><?php _e( 'Save and Continue', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-3">
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn ps-finish-wizard" data-step="3"><?php _e( 'I\'ll send a test email later.', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-4">
                                <div class="ps-wizard-congrates">
                                    <h2>👏 <?php _e( 'Great you are all done!', 'post-smtp' ); ?></h2>
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
            'finish'            => __( 'Finish', 'post-smtp' ),
            'adminURL'          => admin_url(),
            'connectivityTestMsg'  => sprintf( 
                '%1$s %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s',
                '<span class="dashicons dashicons-warning"></span>',
                __( 'Take the', 'post-smtp' ),
                esc_url( admin_url( 'admin.php?page=postman/port_test' ) ),
                __( 'connectivity test', 'post-smtp' ),
                __( 'of your site to get more information about this failure.', 'post-smtp' )
            )
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
        $gmail_icon_url = POST_SMTP_URL . '/Postman/Wizard/assets/images/gmail.png';
		$localized['gmail_icon'] = $gmail_icon_url; 
        
        wp_enqueue_style( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/css/wizard.css', array(), POST_SMTP_VER );
        wp_enqueue_script( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/js/wizard.js', array( 'jquery' ), '1.23.4' );
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
                            <input type="checkbox" '.$from_email_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ).']">
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
                <span class="ps-form-control-info">'.__( 'The name that emails are sent from.', 'post-smtp' ).'</span>
                <div>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_name_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ).']">
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
            case 'mailersend_api';
                echo wp_kses( $this->render_mailersend_settings(), $this->allowed_tags );
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
            case 'sendpulse_api':
                echo wp_kses( $this->render_sendpulse_settings(), $this->allowed_tags );
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
            case 'smtp2go_api':
	            echo wp_kses( $this->render_smtp2go_settings(), $this->allowed_tags );
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
    // Get the Client ID and Client Secret from options
    $client_id = ! is_null( $this->options->getClientId() ) ? esc_attr( $this->options->getClientId() ) : '';
    $client_secret = ! is_null( $this->options->getClientSecret() ) ? esc_attr( $this->options->getClientSecret() ) : '';
    // Check if the 'success' parameter exists in URL
    $required = isset( $_GET['success'] ) && $_GET['success'] == 1 ? '' : 'required';

    // Retrieve options for premium features and extensions
    $post_smtp_pro_options = get_option( 'post_smtp_pro', [] );
    $postman_auth_token = get_option( 'postman_auth_token' );
    $bonus_extensions = isset( $post_smtp_pro_options['extensions'] ) ? $post_smtp_pro_options['extensions'] : [];
    $gmail_oneclick_enabled = in_array( 'gmail-oneclick', $bonus_extensions );
    $auth_url = get_option( 'post_smtp_gmail_auth_url' );

    // Setup classes and attributes for form visibility
    $hidden_class = $gmail_oneclick_enabled ? 'ps-hidden' : '';
    $client_id_required = $gmail_oneclick_enabled ? '' : 'required';
    $client_secret_required = $gmail_oneclick_enabled ? '' : 'required';
    $one_click_class = 'ps-enable-gmail-one-click';
    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/wizard-google.png';
    $transport_name = __( '<strong>1-Click</strong> Google Mailer Setup?', 'post-smtp' );
    $product_url = postman_is_bfcm() ? 
        'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 
        'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_gmail_one_click&utm_campaign=plugin';


    if ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) {
	    $client_id_required     = '';
        $client_secret_required = '';
    }

    // Prepare data for JSON encoding
    $data = [
        'url' => $url,
        'transport_name' => $transport_name,
        'product_url' => $product_url
    ];
    $json_data = htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );

    // Begin HTML output
    $html = '<p>' . sprintf(
        /* translators: %1$s: Google link, %2$s: Gmail mailer name, %3$s: Description */
        __( 'Our %1$s<a href="%2$s" target="_blank">%3$s</a> %4$s', 'post-smtp' ),
        __( '', 'post-smtp' ),
        esc_url( 'https://www.google.com/gmail/about/' ),
        __( 'Gmail mailer', 'post-smtp' ),
        __( 'works with any Gmail or Google Workspace account via the Google API. You can send WordPress emails from your main email address and it\'s more secure than directly connecting to Gmail using SMTP credentials.', 'post-smtp' )
    ) . '</p>';

    $html .= __( 'The configuration steps are more technical than other options, so our detailed guide will walk you through the whole process.', 'post-smtp' );
    $html .= '<hr />';
    if ( post_smtp_has_pro() ) {
        $one_click = true;
        $html .= sprintf( '<h3>%1$s</h3>', __( 'One-Click Setup', 'post-smtp' ) );
    } else {
        $html .= sprintf(
            '<h3 class="%1$s" >%1$s <span class="ps-wizard-pro-tag">%2$s</span></h3>',
            __( 'One-Click Setup', 'post-smtp' ),
            __( 'PRO', 'post-smtp' )
        );
        $one_click = 'disabled';
        $one_click_class .= ' disabled';
    }

    $html .= __( '<p>Enable the option for a quick and easy way to connect with Google without the need of manually creating an app. <p>', 'post-smtp' );

    // One-click switch control
    $html .= "<div>
        <div class='ps-form-switch-control'>
            <label class='ps-switch-1 ".(!post_smtp_has_pro() ? 'ps-gmail-one-click' : '')." '>
               
                <input type='hidden' id='ps-one-click-data' value='" . esc_attr( $json_data ) . "'>
                <input type='checkbox' class='$one_click_class' " . ( $gmail_oneclick_enabled ? 'checked' : '' ) . ">
                <span class='slider round'></span>
            </label> 
        </div>
    </div>";
    // Client ID and Secret inputs
    $html .= '<div class="ps-disable-one-click-setup ' . ( $gmail_oneclick_enabled ? 'ps-hidden' : '' ) . '">
        <p>' . sprintf(
            /* translators: %1$s: Link to Gmail setup documentation */
            __( 'Read our %1$s <a href="%2$s" target="_blank">%3$s</a> %4$s', 'post-smtp' ),
            __( '', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/gmail/' ),
            __( 'Gmail setup documentation', 'post-smtp' ),
            __( 'to learn how to create an app manually to generate the Client ID and Client Secret', 'post-smtp' )
        ) . '</p>';

    $html .= '
    <div class="ps-form-control">
        <div><label>' . __( 'Client ID', 'post-smtp' ) . '</label></div>
        <input type="text" class="ps-gmail-api-client-id" ' . esc_attr( $client_id_required ) . ' data-error="' . esc_attr( __( 'Please enter Client ID.', 'post-smtp' ) ) . '" name="postman_options[' . esc_attr( PostmanOptions::CLIENT_ID ) . ']" value="' . $client_id . '" placeholder="Client ID">
    </div>';

    $html .= '
    <div class="ps-form-control">
        <div><label>' . __( 'Client Secret', 'post-smtp' ) . '</label></div>
        <input type="text" class="ps-gmail-client-secret" ' . esc_attr( $client_secret_required ) . ' data-error="' . esc_attr( __( 'Please enter Client Secret.', 'post-smtp' ) ) . '" name="postman_options[' . esc_attr( PostmanOptions::CLIENT_SECRET ) . ']" value="' . $client_secret . '" placeholder="Client Secret">
    </div>';

    $html .= '
    <div class="ps-form-control">
        <div><label>' . __( 'Authorized JavaScript origins', 'post-smtp' ) . '</label></div>
        <input type="text" class="ps-gmail-js-origin" value="' . esc_url( site_url() ) . '" readonly>
    </div>';

    $html .= '
    <div class="ps-form-control">
        <div><label>' . __( 'Authorized redirect URI', 'post-smtp' ) . '</label></div>
        <input type="text" class="ps-gmail-redirect-uri" value="' . esc_url( admin_url( 'options-general.php?page=postman' ) ) . '" readonly>
        <span class="ps-form-control-info">
        ' . __( 'Please copy this URL into the "Authorized redirect URL" field of your Gmail account settings.', 'post-smtp' ) . '
        </span>
    </div>';

    $html .= '
    <h3>' . __( 'Authorization (Required)', 'post-smtp' ) . '</h3>
    <p>' . __( 'Before continuing, you\'ll need to allow this plugin to send emails using Gmail API.', 'post-smtp' ) . '</p>
<input type="hidden"  class="ps-gmail-warning" ' . esc_attr( $client_id_required ) . ' data-error="' . esc_attr( __( 'Please authenticate by clicking Connect to Gmail API', 'post-smtp' ) ) . '" />
    <a href="' . esc_url( admin_url( 'admin-post.php?action=postman/requestOauthGrant' ) ) . '" class="button button-primary ps-blue-btn" id="ps-wizard-connect-gmail">' . __( 'Connect to Gmail API', 'post-smtp' ) . '</a>';

    // Remove OAuth action button
    $html .= '</div>';
    $html .= '<div class="ps-disable-gmail-setup ' . ( $gmail_oneclick_enabled ? '' : 'ps-hidden' ) . '">';
    if ( post_smtp_has_pro() ) {
        if ( $postman_auth_token && isset( $postman_auth_token['user_email'] ) ) {
            $nonce = wp_create_nonce( 'remove_oauth_action' );
            $action_url = esc_url( add_query_arg(
                [
                    '_wpnonce' => $nonce,
                    'action' => 'remove_oauth_action',
                ],
                admin_url( 'admin-post.php' )
            ) );
			if ( isset( $postman_auth_token['user_email'] ) ) {
            	$html .= ' <span class="icon-circle"><span class="icon-check"></span> </span> <b class= "ps-wizard-success">' . sprintf( esc_html__('Connected with: %s', 'post-smtp'), esc_html( $postman_auth_token['user_email'] ) ) . '</b>';
            }
            $html .= '<a href="' . $action_url . '" class="ps-remove-gmail-btn ps-disable-gmail-setup wizard-btn-css">';
            $html .= esc_html__( 'Remove Authorization', 'post-smtp' );
            $html .= '</a>';
        }else {
                $html .= '<h3>' . esc_html__( 'Authorization (Required)', 'post-smtp' ) . '</h3>';
                $html .= '<p>' . esc_html__( 'Before continuing, you\'ll need to allow this plugin to send emails using Gmail API.', 'post-smtp' ) . '</p>';
                $html .= '<input type="hidden" ' . esc_attr( $required ) . ' data-error="' . esc_attr__( 'Please authenticate by clicking Connect to Gmail API', 'post-smtp' ) . '" />';
                $html .= '<a href="' . esc_url( $auth_url ) . '" class="button button-primary ps-gmail-btn">';
                $html .= esc_html__( 'Sign in with Google', 'post-smtp' );
                $html .= '</a>';
                $html .= "<p>By signing in with Google, you can send emails using different 'From' addresses. To do this, disable the 'Force From Email' setting and use your registered aliases as the 'From' address across your WordPress site.</p> <p>Removing the OAuth connection will give you the ability to redo the OAuth connection or link to another Google account.</p>";
        }
    }

    $html .= '</div>';

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
                __( 'API Key.', 'post-smtp' )
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
        $selected_region = $this->options->getSendGridRegion() ? esc_attr( $this->options->getSendGridRegion() ) : 'AG';
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
                __( 'API Key.', 'post-smtp' )
            ).'
        </div>
        ';

        // Region dropdown.
        $html .= '<div class="ps-form-control">';
        $html .= '<div><label>' . __( 'Region', 'post-smtp' ) . '</label></div>';
        $html .= '<select name="postman_options[' . esc_attr( PostmanOptions::SENDGRID_REGION ) . ']" class="ps-sendgrid-region">';
        $html .= '<option value="Global" ' . selected( $selected_region, 'Global', false ) . '>' . __( 'Global', 'post-smtp' ) . '</option>';
        $html .= '<option value="EU" ' . selected( $selected_region, 'EU', false ) . '>' . __( 'Europe (EU)', 'post-smtp' ) . '</option>';
        $html .= '</select>';
        $html .= '</div>';


        return $html;

    }


    /**
     * Render MailerSend Settings
     * 
     * @since 3.3.0
     * @version 1.0.0
     */
    public function render_mailersend_settings() {

        $api_key = null !== $this->options->getMailerSendApiKey() ? esc_attr ( $this->options->getMailerSendApiKey() ) : '';
        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://mailersend.com/' ),
            __( 'MailerSend', 'post-smtp' ),
            __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mailersend-with-post-smtp/' ),
            __( 'MailerSend Documentation', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailersend-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILERSEND_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
                __( 'Create an account at', 'post-smtp' ),
                esc_url( 'https://app.mailersend.com/' ),
                esc_attr( 'MailerSend' ),
                __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
                esc_url( 'https://app.mailersend.com/api-tokens' ),
                __( 'API Key.', 'post-smtp' )
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
                __( 'API Key.', 'post-smtp' )
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
                __( 'Domain Name.', 'post-smtp' )
            )
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Mailgun Europe Region?</label></div>
            <div class="ps-form-switch-control">
                <label class="ps-switch-1">
                    <input type="checkbox" '.$region.' name="postman_options['.esc_attr( PostmanOptions::MAILGUN_REGION ).']">
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
                __( 'API Key.', 'post-smtp' )
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
                __( 'API Key or Server API Token.', 'post-smtp' )
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
                __( 'API Key.', 'post-smtp' )
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
                __( 'API Key.', 'post-smtp' )
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
                __( 'Mailjet API and Access Key', 'post-smtp' )
            )
            .
        '</div>
        ';

        return $html;

    }

    /**
     * Render SendPulse Settings
     * 
     * @since 2.9.0
     * @version 1.0.0
     */
    public function render_sendpulse_settings() {

        $api_key = null !== $this->options->getSendpulseApiKey() ? esc_attr ( $this->options->getSendpulseApiKey() ) : '';
        $secret_key = null !== $this->options->getSendpulseSecretKey() ? esc_attr ( $this->options->getSendpulseSecretKey() ) : '';

        $html = sprintf(
            '<p>%1$s <a href="%2$s" target="_blank">SendPulse</a> %3$s</p><p>%4$s<p>%5$s <a href="%6$s" target="_blank">%7$s</a>',
            __( 'With', 'post-smtp' ),
            esc_url( 'https://sendpulse.com/features/transactional' ),
            __( 'Transactional Email, whether you need to send order confirmations, booking notifications, password resets, or any other transactional messages, You can handle it with ease and reliability.', 'post-smtp' ),
            __( 'If you\'re just starting out, the free plan allows you to send up to 12000 emails without entering your credit card details.', 'post-smtp' ),
            __( 'Let\'s get started with the documentation', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-sendpulse/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin' ),
            __( 'Configure Sendpulse with Post SMTP', 'post-smtp' )
        );

        $html .= '
        <div class="ps-form-control">
            <div><label>API ID</label></div>
            <input type="text" class="ps-sendpulse-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDPULSE_API_KEY ) .']" value="'.$api_key.'" placeholder="API ID">
        '.
        sprintf(
            '<div class="ps-form-control-info"><a href="%1$s" target="_blank">%2$s</a> %3$s</div>',
            esc_url( 'https://sendpulse.com/features/transactional' ),
            __( 'Click here', 'post-smtp' ),
            __( 'to create an account at SendPulse', 'post-smtp' )
        ).
        sprintf(
            '<div class="ps-form-control-info">%1$s<a href="%2$s" target="_blank">%3$s</a></div>',
            __( 'If you are already logged in follow this ink to get your API ID from Sendpulse ', 'post-smtp' ),
            esc_url( 'https://login.sendpulse.com/settings/#api' ),
            __( 'Get API ID', 'post-smtp' )
        ).
        '</div>'
        ;

        $html .= '
        <div class="ps-form-control">
            <div><label>API Secret</label></div>
            <input type="text" class="ps-sendpulse-secret-key" required data-error="'.__( 'Please enter Secret Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDPULSE_SECRET_KEY ) .']" value="'.$secret_key.'" placeholder="API Secret">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
            sprintf(
                '<div class="ps-form-control-info">%1$s<a href="%2$s" target="_blank">%3$s</a></div>',
                __( 'If you are already logged in follow this ink to get your API ID from Sendpulse ', 'post-smtp' ),
                esc_url( 'https://login.sendpulse.com/settings/#api' ),
                __( 'Get API Secret', 'post-smtp' )
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
                __( 'Access Key ID and Sceret Access Key', 'post-smtp' )
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
            esc_url( 'https://azure.microsoft.com/en-us/pricing/purchase-options/azure-account?icid=azurefreeaccount' ),
            __( 'Office 365', 'post-smtp' ),
            __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-office-365/' ),
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
                esc_url( 'https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0' ),
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
                esc_url( 'https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0' ),
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
                esc_url( 'https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0' ),
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
        $client_secret = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] ) ? base64_decode( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] ) : '';
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

    public function render_smtp2go_settings() {
	    ob_start();

	    $api_key = null === $this->options->getSmtp2GoApiKey() ? '' : esc_attr( $this->options->getSmtp2GoApiKey() );

	    printf(
		    '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
		    esc_url( 'https://www.smtp2go.com/' ),
		    __( 'SMTP2Go', 'post-smtp' ),
		    __( 'is known for its reliable email delivery service, featuring global infrastructure, real-time analytics, and robust security. If you’re just starting out, their free plan allows sending up to 1,000 emails per month.', 'post-smtp' ),
		    __( 'Let’s get started with our', 'post-smtp' ),
		    esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-smtp2go-with-post-smtp/' ),
		    __( 'SMTP2GO Documentation', 'post-smtp' )
	    );

	    echo '<div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-smtp2go-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SMTP2GO_API_KEY ) .']" value="'.$api_key.'" placeholder="API Key">';
	    printf(
		    '<div class="ps-form-control-info">%1$s <a href="%2$s" target="_blank">%3$s</a></div><div class="ps-form-control-info">%4$s <a href="%5$s" target="_blank">%6$s</a></div>',
		    __( 'Create an account at', 'post-smtp' ),
		    esc_url( 'https://www.smtp2go.com/' ),
		    esc_attr( 'SMTP2GO' ),
		    __( 'If you are already logged in follow this link to get an', 'post-smtp' ),
		    esc_url( 'https://app-eu.smtp2go.com/sending/apikeys/' ),
		    __( 'API Key.', 'post-smtp' )
	    );

	    echo '</div>';

	    return ob_get_clean();
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
                $sanitized['sendgrid_region']  = isset( $sanitized['sendgrid_region'] ) ? $sanitized['sendgrid_region'] : '';
                $sanitized['mandrill_api_key'] = isset( $sanitized['mandrill_api_key'] ) ? $sanitized['mandrill_api_key'] : '';
                $sanitized[PostmanOptions::MAILERSEND_API_KEY] = isset( $sanitized[PostmanOptions::MAILERSEND_API_KEY] ) ? $sanitized[PostmanOptions::MAILERSEND_API_KEY] : '';
                $sanitized['elasticemail_api_key'] = isset( $sanitized['elasticemail_api_key'] ) ? $sanitized['elasticemail_api_key'] : '';
                $sanitized[PostmanOptions::MAILJET_API_KEY] = isset( $sanitized[PostmanOptions::MAILJET_API_KEY] ) ? $sanitized[PostmanOptions::MAILJET_API_KEY] : '';
                $sanitized[PostmanOptions::MAILJET_SECRET_KEY] = isset( $sanitized[PostmanOptions::MAILJET_SECRET_KEY] ) ? $sanitized[PostmanOptions::MAILJET_SECRET_KEY] : '';
                $sanitized['basic_auth_password'] = isset( $sanitized['basic_auth_password'] ) ? $sanitized['basic_auth_password'] : '';
                $sanitized['ses_access_key_id'] = isset( $sanitized['ses_access_key_id'] ) ? $sanitized['ses_access_key_id'] : '';
                $sanitized['ses_secret_access_key'] = isset( $sanitized['ses_secret_access_key'] ) ? $sanitized['ses_secret_access_key'] : '';
                $sanitized['ses_region'] = isset( $sanitized['ses_region'] ) ? $sanitized['ses_region'] : '';
                $sanitized['enc_type'] = 'tls';
                $sanitized['auth_type'] = 'login';
				$sanitized['slack_token'] = base64_decode( $options['slack_token'] );
                foreach( $sanitized as $key => $value ) {
                    $options[$key] = $value;
                }
				
                if( $options == $_options ) {
                    $response = true;
                } else {
                    $response = update_option( PostmanOptions::POSTMAN_OPTIONS , $options );
                }
                
            }
            
        }
        //Prevent redirection
        delete_transient( PostmanSession::ACTION );

        wp_send_json( array(), 200 );

    }

    /**
     * Callback function to handle AJAX requests for updating the 'post_smtp_pro' option.
     *
     * This function listens for AJAX requests and updates the 'bonus_extensions' array
     * in the 'post_smtp_pro' option. It adds or removes the 'gmail-oneclick' extension
     * based on whether the checkbox is checked or not.
     *
     * @return void
     */
    public function update_post_smtp_pro_option_callback() {
        if ( ! isset( $_POST['enabled'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ) );
            return;
        }

        $options = get_option( 'post_smtp_pro', [] );
        if ( ! isset( $options['extensions'] ) ) {
            $options['extensions'] = [];
        }

        $enabled_value = sanitize_text_field( $_POST['enabled'] );

        if ( ! empty( $enabled_value ) ) {
            if ( ! in_array( $enabled_value, $options['extensions'] ) ) {
                $options['extensions'][] = $enabled_value;
            }
        } else {
            $options['extensions'] = array_diff( $options['extensions'], ['gmail-oneclick'] );
        }

        update_option( 'post_smtp_pro', $options );

        wp_send_json_success( array( 'message' => 'Option updated successfully!' ) );
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

    /**
     * Handles the removal of Gmail OAuth credentials from the WordPress database.
     *
     * This function processes a form submission to delete the stored OAuth access token
     * and user email associated with Gmail API integration. It validates the request's
     * nonce for security, performs the deletion, and redirects the user back to the settings
     * page with a success message.
     */
    public function post_smtp_remove_oauth_action() {
        // Verify the nonce to ensure the request is secure and valid.
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'remove_oauth_action' ) ) {
            wp_die( esc_html__( 'Nonce verification failed. Please try again.', 'post-smtp' ) );
        }

        // Remove the OAuth access token option from the WordPress database.
        delete_option( 'postman_auth_token' );

        // Redirect the user back to the settings page with a success query parameter.
        wp_redirect( admin_url( "admin.php?socket=gmail_api&step=2&page=postman/configuration_wizard" ) );

        // Terminate script execution to prevent further processing after the redirect.
        exit;
    }

    /**
     * Handles the OAuth redirect, retrieves the token parameters from the URL,
     * saves them in WordPress options, and redirects the user to a settings page.
     *
     * This function is used when OAuth authorization is completed and the user is
     * redirected back with the access token, refresh token, expiration time, message, 
     * and user email. It sanitizes the URL parameters and saves them to the WordPress 
     * options table to be used later in the application.
     *
     * After processing, the user is redirected to a settings page for confirmation.
     */
    public function handle_gmail_oauth_redirect() {
        // Check if the required OAuth parameters are present in the URL.
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'gmail_oauth_redirect' ) {
            // Sanitize and retrieve URL parameters
            $access_token  = isset( $_GET['access_token'] ) ? sanitize_text_field( $_GET['access_token'] ) : null;
 		    $refresh_token = isset( $_GET['refresh_token'] ) ? sanitize_text_field( $_GET['refresh_token'] ) : null;
            $expires_in    = isset( $_GET['expires_in'] ) ? intval( $_GET['expires_in'] ) : 0;
            $msg           = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';
            $user_email    = isset( $_GET['user_email'] ) ? sanitize_email( $_GET['user_email'] ) : '';
            $auth_token_expires = time() + $expires_in;

			if ( $access_token ) {
				$oauth_data = array(
					'access_token'      => $access_token,
					'refresh_token'     => $refresh_token,
					'auth_token_expires'=> $auth_token_expires,
					'vendor_name'       => 'google',
					'user_email'        => $user_email,
				);
            	// Save the OAuth parameters to the WordPress options table.
            	update_option( 'postman_auth_token', $oauth_data );
			}
        }
    }


}

new Post_SMTP_New_Wizard();

endif;