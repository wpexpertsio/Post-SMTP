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
        'h3'            =>  array(
            'class'         =>  array(),
        ),
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

    private $existing_db_version = '';

    /**
     * Constructor for the class
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function __construct() {

        $this->socket_sequence = array(
            'gmail_api',
            'sendgrid_api',
            'sendinblue_api',
            'postmark_api',
            'maileroo_api',
            'mailtrap_api',
            'mailersend_api',
            'emailit_api',
            'sweego_api',
            'resend_api',
            'elasticemail_api',
            'mailgun_api',
            'smtp2go_api',
            'mandrill_api',
            'sparkpost_api',
            'mailjet_api',
            'sendpulse_api',
            'maileroo_api'
            
        );
        
        
        $this->socket_sequence[] = 'smtp';
        $this->socket_sequence[] = 'default';

        if( !is_plugin_active( 'post-smtp-pro/post-smtp-pro.php' ) ) {

            $this->socket_sequence[] = 'office365_api';
            $this->socket_sequence[] = 'aws_ses_api';
            $this->socket_sequence[] = 'zohomail_api';

        }
        
        add_filter( 'post_smtp_legacy_wizard', '__return_false' );
        add_action( 'post_smtp_new_wizard', array( $this, 'load_wizard' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_ps-save-wizard', array( $this, 'save_wizard' ) );
        add_action( 'wp_ajax_update_post_smtp_pro_option', array( $this, 'update_post_smtp_pro_option_callback' ) );
        add_action( 'wp_ajax_update_post_smtp_pro_option_office365', array( $this, 'update_post_smtp_pro_option_office365_callback' ) );
        add_action( 'wp_ajax_ps_get_office365_auth_url', array( $this, 'ajax_get_office365_auth_url' ) );
        add_action( 'wp_ajax_ps_get_gmail_auth_url', array( $this, 'ajax_get_gmail_auth_url' ) );
        add_action( 'admin_action_zoho_auth_request', array( $this, 'auth_zoho' ) );
        add_action( 'admin_post_remove_oauth_action', array( $this, 'post_smtp_remove_oauth_action' ) );
        add_action( 'admin_init', array( $this, 'handle_gmail_oauth_redirect' ) );
		add_action( 'admin_init', array( $this, 'handle_office365_oauth_redirect' ) );
		add_action( 'admin_post_remove_365_oauth_action', array( $this, 'post_smtp_remove_365_oauth_action' ) );
		add_action( 'wp_ajax_postman_delete_connection', array( $this, 'postman_handle_delete_connection' ) );
		add_action( 'wp_ajax_nopriv_postman_delete_connection', array( $this, 'postman_handle_delete_connection' ) );
        add_action( 'wp_ajax_ps_expire_client_transients', array( $this, 'ps_expire_client_transients' ) );

        if( isset( $_GET['wizard'] ) && $_GET['wizard'] == 'legacy' ) {

            add_filter( 'post_smtp_legacy_wizard', '__return_true' );

        }

        $this->options_array = get_option( PostmanOptions::POSTMAN_OPTIONS );

        $this->existing_db_version = get_option( 'postman_db_version' );
        
    }
    
    /**
	* Expire stored OAuth client credentials after wizard completion.
	*
	* This function deletes the client_id and client_secret transients,
	* ensuring sensitive credentials are removed from the database once
	* the authentication flow is finished. It is typically called via AJAX
	* after the wizard or "Thank You" step to enhance security.
	*
	* @since 3.5.0
	* @version 1.0.0
	*/
	public static function expire_client_transients() {
	    delete_transient('client_id');
		delete_transient('client_secret');
		wp_send_json_success();
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
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $selected_connection = 0;
        $postman_connections = get_option( 'postman_connections' );
        $db_version = get_option( 'postman_db_version' );
        // Add popup trigger file
        require_once POST_SMTP_PATH. '/Postman/Popup/popup.php';
        ?>
 
        
        <div class="wrap">
            <div class="ps-wizard-top">
                <div class="ps-logo">
                    <img src="<?php echo esc_attr( POST_SMTP_ASSETS ) . '/images/logos/post-smtp-logo-large.svg'; ?>" width="250px" />
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=postman' ) ); ?>" class="button ps-back-dashboard">
                    <?php esc_html_e( 'Back to Dashboard', 'post-smtp' ); ?>
                </a>
            </div>
            <div class="ps-wizard">
                <div class="ps-wizard-outer <?php echo esc_attr( $socket ); ?>">
                    <div class="ps-wizard-section">
                        <div class="ps-wizard-nav">
                        <table>
                                <tr class="ps-wizard-step-start <?php echo esc_attr( $in_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Choose your SMTP Mailer', 'post-smtp' ) ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="1"></span></td>
                                </tr>
                                <tr class="ps-wizard-step-between <?php echo esc_attr( $is_active ) ?>">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Configure Mailer Settings', 'post-smtp' ) ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="2"></span></td>
                                </tr>
                                <tr class="ps-wizard-step-between ps-in-active-nav">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Send Test Email', 'post-smtp' ) ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="3"></span></td>
                                </tr>
                                <tr class="ps-wizard-step-end ps-in-active-nav finished">
                                    <td class="ps-wizard-circle"><span class="ps-tick dashicons dashicons-yes-alt"><span class="ps-wizard-line"></span></span></td>
                                    <td class="ps-wizard-text"><?php _e( 'Finish', 'post-smtp' ); ?></td>
                                    <td class="ps-wizard-edit"><span class="dashicons dashicons-edit" data-step="4"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="ps-wizard-pages">
                            <form id="ps-wizard-form">
                                <?php wp_nonce_field( 'post-smtp', 'security' );  ?>
                                <div class="ps-wizard-screens-container">
                                    <div class="ps-wizard-step ps-wizard-step-1">
                                        <p style="width: 100%; margin-bottom: 10px;color:#707070"><?php echo esc_html__( 'Choose a mailer from the following options.', 'post-smtp' ); ?></p>
                                        <div class="ps-wizard-sockets">      
                                        <?php

                                        $row  = 0;
                                        $in_pro_row = false;

                                        $transports = array_merge( array_flip( $this->socket_sequence ), $transports );
                                        
                                        foreach( $transports as $key => $transport ) {
                                            $class = '';
                                            $urls = array(
                                                'default'           =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp.svg',
                                                'smtp'              =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp.svg',
                                                'gmail_api'         =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/gmail.png',
                                                'mandrill_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mandrill.png',
                                                'sendgrid_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendgrid.png',
                                                'mailersend_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailersend.png',
                                                'mailgun_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailgun.png',
                                                'sendinblue_api'    =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/brevo.svg',
                                                'mailtrap_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailtrap.png',
                                                'postmark_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/postmark.png',
                                                'sparkpost_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sparkpost.png',
                                                'mailjet_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/mailjet.png',
                                                'sendpulse_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sendpulse.png',
                                                'smtp2go_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/smtp2go.png',
                                                'office365_api'     =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png',
                                                'elasticemail_api'  =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/elasticemail.png',
                                                'aws_ses_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png',
                                                'zohomail_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png',
                                                'resend_api'        =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/resend.png',
                                                'emailit_api'       =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/emailit.png',
                                                'maileroo_api'      =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/maileroo.png',
                                                'sweego_api'        =>  POST_SMTP_URL . '/Postman/Wizard/assets/images/sweego.png'

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

                                                    if( $db_version == POST_SMTP_DB_VERSION ){
                                                        if ( isset( $id ) ) {
                                                             $selected_connection = $postman_connections[$id]['provider'];
                                                             if( $key == $selected_connection ){
                                                                 $checked = 'checked';
                                                             }
                                                         }
                                                     }else{
                                                        $checked = 'checked';
                                                     }

                                                }
                                                
                                                $slug = $transport->getSlug();
                                                $transport_name = $transport->getName();

                                                if( $db_version == POST_SMTP_DB_VERSION ){
                                                   if ( isset( $id ) ) {
                                                        $selected_connection = $postman_connections[$id]['provider'];
                                                        if( $key == $selected_connection ){
                                                            $checked = 'checked';
                                                        }
                                                    }
                                                }

                                            }
                                            else {
                                                $transport_slug = $key;

                                                if( $transport_slug == 'office365_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Microsoft 365';
                                                    $is_pro = 'ps-pro-extension';
                                                    $product_url = 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_microsoft&utm_campaign=plugin';

                                                }
                                              if( $transport_slug == 'zohomail_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/zoho.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Zoho';
                                                    $is_pro = 'ps-pro-extension';
                                                    $product_url = 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_zoho&utm_campaign=plugin';

                                                }
                                                if( !class_exists( 'Post_Smtp_Amazon_Ses' ) && $transport_slug == 'aws_ses_api' ) {
                                                    
                                                    $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/amazon.png';
                                                    $slug = $transport_slug;
                                                    $transport_name = 'Amazon SES';
                                                    $is_pro = 'ps-pro-extension';
                                                    $product_url = 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_amazonses&utm_campaign=plugin';

                                                }

                                            }

                                            // When we hit the first PRO mailer, close the current
                                            // sockets row and start a dedicated PRO row so all
                                            // PRO mailers appear together on their own line.
                                            if ( ! empty( $is_pro ) && ! $in_pro_row ) {

                                                $in_pro_row = true;
                                                $row = 0;
                                                ?>
                                                
                                                <?php

                                            }

                                            // Regular (non‑PRO) mailers are grouped in rows of 4.
                                            if( $row >= 4 && empty( $is_pro ) ) {

                                                $row = 0;
                                                ?>
                                               
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
                                                                <div class="ps-wizard-socket-tick">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                                                    <path d="M6 1C3.245 1 1 3.245 1 6C1 8.755 3.245 11 6 11C8.755 11 11 8.755 11 6C11 3.245 8.755 1 6 1ZM8.39 4.85L5.555 7.685C5.485 7.755 5.39 7.795 5.29 7.795C5.19 7.795 5.095 7.755 5.025 7.685L3.61 6.27C3.465 6.125 3.465 5.885 3.61 5.74C3.755 5.595 3.995 5.595 4.14 5.74L5.29 6.89L7.86 4.32C8.005 4.175 8.245 4.175 8.39 4.32C8.535 4.465 8.535 4.7 8.39 4.85Z" fill="#214A72"/>
                                                                    </svg>
                                                                </div>
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
                                        <div class="wizrd_footer">
                                            <div class="box">
                                                <p>
                                                    <?php echo sprintf(
                                                        '%1$s <i><a style="color: #707070;" target="_blank" href="%2$s">%3$s</a></i>',
                                                        __( 'Need help in choosing one? Check out our ', 'post-smtp' ),
                                                        esc_url( 'https://postmansmtp.com/docs/mailers/a-complete-guide-to-post-smtp-mailers/' ),
                                                        __( ' Mailer Guide.', 'post-smtp' )
                                                    ); ?>
                                                    </p>

                                                    <p>
                                                        <?php echo sprintf(
                                                        '%1$s <i><a style="color: #707070;" target="_blank" href="%2$s">%3$s</a></i>',
                                                        __( 'Did we miss out on what you are looking for? Feel free to ', 'post-smtp' ),
                                                        esc_url( 'https://postmansmtp.com/roadmap/' ),
                                                        __( 'Suggest your Mailer.', 'post-smtp' )
                                                    ); ?>
                                            </div>
                                            <div class="box">
                                                <div class="ps-wizard-step ps-wizard-step-1">
                                                    <p class="ps-wizard-error"></p>
                                                    <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="1"><?php _e( 'Continue', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                                    <div style="clear: both"></div>
                                                </div>         
                                            </div>
                                        </div>
                                        
                                        </p>
                                    </div>
                                    <div class="ps-wizard-step ps-wizard-step-2">
                                    <?php if( isset( $_GET['id'] ) ){ ?>
                                      <input type="hidden" class="postman_fallback_edit" name="postman_fallback_edit" value="<?php echo esc_attr(  $_GET['id'] ); ?>" >
                                    <?php } ?>
                                    <?php if( isset( $_GET['access_token'] ) || isset( $_GET['refresh_token'] ) ){ ?>
                                      <input type="hidden" name="access_token" value="<?php echo esc_attr( $_GET['access_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="refresh_token" value="<?php echo esc_attr( $_GET['refresh_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="token_expires" value="<?php echo esc_attr( $_GET['expires_in'] ?? '' ); ?>" >
                                    <?php } ?>
                                    <?php if( isset( $_GET['o_access_token'] ) || isset( $_GET['o_refresh_token'] ) ){ ?>
                                      <input type="hidden" name="access_token" value="<?php echo esc_attr( $_GET['o_access_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="refresh_token" value="<?php echo esc_attr( $_GET['o_refresh_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="token_expires" value="<?php echo esc_attr( $_GET['o_expires_in'] ?? '' ); ?>" >
                                    <?php } ?>
                                    <?php if( isset( $_GET['g_access_token'] ) || isset( $_GET['g_refresh_token'] ) ){ ?>
                                      <input type="hidden" name="access_token" value="<?php echo esc_attr( $_GET['g_access_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="refresh_token" value="<?php echo esc_attr( $_GET['g_refresh_token'] ?? '' ); ?>" >
                                      <input type="hidden" name="token_expires" value="<?php echo esc_attr( $_GET['g_expires_in'] ?? '' ); ?>" >
                                    <?php } ?>
                                        <a href="" data-step="1" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span>Back</a>
                                        <?php
                                        if( !empty( $this->sockets ) ) {

                                                $this->render_name_email_settings();
                                         
                                                foreach( $this->sockets as $key => $title ) {

                                                $active_socket = ( isset( $_GET['socket'] ) && $_GET['socket'] == $key ) ? 'style="display: block;"' : '';

                                                ?>
                                                <div class="ps-form-ui ps-wizard-socket <?php echo esc_attr( $key ); ?>" <?php echo $active_socket; ?>>
                                                    <?php
                                                    // Custom display title for Office 365 transport
                                                    $display_title = $title;
                                                    if ( 'office365_api' === $key ) {
                                                        $display_title = 'Microsoft 365 / Outlook';
                                                    }
                                                    ?>
                                                    <h3><?php echo $display_title == 'Default' ? '' : esc_attr( $display_title ); ?></h3>
                                                    <?php $this->render_socket_settings( $key ); ?>
                                                </div>
                                                <?php

                                            }

                                        }
                                        ?>
                                    </div>
                                    <div class="ps-wizard-step ps-wizard-step-3">
                                        <p style="color: #707070;"><?php _e( 'This step allows you to send an email message for testing. If there is a problem, Post SMTP will give up after 60 seconds.', 'post-smtp' ); ?></p>
                                        <div class="ps-form-ui">
                                            <div class="ps-form-control">
                                                <div><label><?php _e( 'Recipient Email Address', 'post-smtp' ) ?></label></div>
                                                <input type="text" class="ps-test-to" required data-error="Enter Recipient Email Address" name="postman_test_options[test_email]" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" placeholder="Recipient Email Address">
                                                <span class="ps-form-control-info"><?php _e( 'Enter the email address where you want to send a test email message.', 'post-smtp' ) ?></span>
                                                <p style="color: #B3B3B3;" class="ps-form-control-info"><?php _e( 'Are your WordPress emails getting broken? Check out our guide on', 'post-smtp' ) ?> <a href="https://postmansmtp.com/fix-for-broken-emails/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin" target="_blank"><?php _e( 'how to fix Broken Emails', 'post-smtp' ) ?></a>.</p>
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
                                        <h4 class="ps-feedback-heading"><span class="ps-heart">❤</span><?php _e( 'Share Your Feedback', 'post-smtp' ) ?></h4>
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
                                        <div class="ps-home-middle-right" style="background: #E2E9FB;">
                                            <div class="ps-mobile-notice-content">
                                                <img src="<?php echo esc_url( POST_SMTP_URL . '/Postman/Wizard/assets/images/success-img.svg' ); ?>" >
                                            </div> 
                                            <div class="ps-mobile-notice-content">
                                                <p class="ps-mobile-notice-content-title"><?php _e( 'The First & Only WP SMTP Plugin With a Mobile App', 'post-smtp' ); ?></p>
                                                <div class="ps-mobile-notice-features">
                                                    <div class="ps-mobile-feature-left">
                                                    <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Easy Email Tracking', 'post-smtp' ) ?>
                                                        <br>
                                                        <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Quickly View Error Details', 'post-smtp' ) ?>
                                                        <br>
                                                        <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Easy Email Tracking', 'post-smtp' ) ?>
                                                    </div>
                                                    <div class="ps-mobile-feature-right">
                                                         <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Get Email Preview', 'post-smtp' ) ?>                                               
                                                        <br>
                                                        <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Resend Failed Emails', 'post-smtp' ) ?>                                                    
                                                        <br>
                                                        <span class="ps-mobile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                                                            <g clip-path="url(#clip0_1886_7278)">
                                                                <path d="M8.40768 4.41333V4.75329C8.40723 5.54157 8.15198 6.30859 7.67999 6.93995C7.208 7.57131 6.54457 8.03318 5.78864 8.25669C5.03271 8.48019 4.22479 8.45335 3.48536 8.18017C2.74592 7.90699 2.11461 7.40211 1.68557 6.74081C1.25652 6.07952 1.05274 5.29726 1.1046 4.51068C1.15647 3.72411 1.46121 2.97538 1.97337 2.37615C2.48553 1.77692 3.17768 1.3593 3.94658 1.18558C4.71548 1.01186 5.51993 1.09134 6.23997 1.41217"
                                                                stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M8.77395 1.46289L4.7529 5.48394L3.65625 4.38729" stroke="#00B888" stroke-width="0.7311" stroke-linecap="round" stroke-linejoin="round" />
                                                            </g>
                                                            <defs>
                                                                <clipPath id="clip0_1886_7278">
                                                                    <rect width="9.86985" height="9.86985" fill="white" />
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                        </span>
                                                        <?php _e( 'Support multiple sites', 'post-smtp' ) ?>                                                      
                                                    </div>
                                                </div>
                                                <div style="display: flex;">
                                                    <div class="ps-app-download-button">
                                                        <a href="https://play.google.com/store/apps/details?id=com.postsmtp&referrer=utm_source%3Dplugin%26utm_medium%3Ddashboard%26utm_campaign%3Dplugin%26anid%3Dadmob" target="_blank"><img src="<?php echo esc_url( POST_SMTP_URL . '/Postman/Wizard/assets/images/androidicon.png' ); ?>"><div><p style="font-size: 8px;">Get it On</p><p style="font-size: 9px; font-weight: 750">Google Play</p></div></a>
                                                    </div>
                                                    <div class="ps-app-download-button">
                                                        <a href="https://apps.apple.com/us/app/post-smtp/id6473368559?utm_source=plugin&utm_medium=dashboard&utm_campaign=plugin" target="_blank"><img src="<?php echo esc_url( POST_SMTP_URL . '/Postman/Wizard/assets/images/apple-icon.png' ); ?>"><div><p style="font-size: 8px;">Download on the</p><p style="font-size: 9px; font-weight: 750;">App Store</p></div></a>
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
                        </div>
                        <div class="ps-wizard-footer-right">
                            
                           
                            <div class="ps-wizard-step ps-wizard-step-2">
                                <p class="ps-wizard-success"><?php echo ( isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <p class="ps-wizard-error"><?php echo ( !isset( $_GET['success'] ) && isset( $_GET['msg'] ) ) ? sanitize_text_field( $_GET['msg'] ) : ''; ?></p>
                                <a href="" data-step="1" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span><?php _e( 'Back', 'post-smtp' ); ?></a>
                                <button class="button button-primary ps-blue-btn ps-wizard-next-btn" data-step="2"></span><?php _e( 'Save and Continue', 'post-smtp' ) ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
                                <div style="clear: both"></div>
                            </div>
                            <div class="ps-wizard-step ps-wizard-step-3">
                                <a href="" data-step="2" class="ps-wizard-back"><span class="dashicons dashicons-arrow-left-alt"></span><?php _e( 'Back', 'post-smtp' ) ?></a>
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
                                    <div><a href="<?php echo esc_url( admin_url( 'admin.php?page=postman_email_log' ) ); ?>" class="button button-primary ps-blue-btn"><?php esc_html_e( 'View logs', 'post-smtp' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a></div>
                                    <div style="text-align:center"><a href="<?php echo esc_url( admin_url( 'admin.php?page=postman' ) ); ?>" style="font-size: 12px; color: #999999;"><?php esc_html_e( 'Skip to dashboard', 'post-smtp' ); ?></a></div>
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
           // 'seeMoreLabel'      => __( 'See More', 'post-smtp' ),
           // 'seeLessLabel'      => __( 'See Less', 'post-smtp' ),
            'adminURL'          => admin_url(),
            'connectivityTestMsg'  => sprintf( 
                '%1$s %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s',
                '<span class="dashicons dashicons-warning"></span>',
                __( 'Take the', 'post-smtp' ),
                esc_url( admin_url( 'admin.php?page=postman/port_test' ) ),
                __( 'connectivity test', 'post-smtp' ),
                __( 'of your site to get more information about this failure.', 'post-smtp' )
            ),
            // Add the nonce for pro option AJAX
            'pro_option_nonce' => wp_create_nonce('update_post_smtp_pro_option'),
             // Nonce and messages for Gmail One-Click auth AJAX
            'office365_auth_nonce' => wp_create_nonce( 'ps_get_office365_auth_url' ),
            'office365AuthErrorText' => __( 'Failed to start Office 365 authentication. Please reload the page and try again.', 'post-smtp' ),
            // Nonce and messages for Gmail One-Click auth AJAX
            'gmail_auth_nonce' => wp_create_nonce( 'ps_get_gmail_auth_url' ),
            'gmailAuthErrorText' => __( 'Failed to start Google authentication. Please reload the page and try again.', 'post-smtp' ),
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
        $localized['tenantId'] = apply_filters( 'post_smtp_office365_tenant_id', 'common' ); 
        
        $office365_icon_url = POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png';
		$localized['office365_icon'] = $office365_icon_url; 

        wp_enqueue_style( 'post-smtp-wizard', POST_SMTP_URL . '/Postman/Wizard/assets/css/wizard.css', array(), POST_SMTP_VER  );
        // and place it at that path so this enqueue works.
        wp_enqueue_script(
            'post-smtp-party',
            POST_SMTP_URL . '/Postman/Wizard/assets/js/party.min.js',
            array(),
            POST_SMTP_VER ,
            true
        );

        wp_enqueue_script(
            'post-smtp-wizard',
            POST_SMTP_URL . '/Postman/Wizard/assets/js/wizard.js',
            array( 'jquery', 'post-smtp-party' ),
            POST_SMTP_VER,
            true
        );

 		$localized['delete_connection_nonce'] = wp_create_nonce( 'postman_delete_connection_nonce' );
        $localized['save_title_nonce'] = wp_create_nonce( 'postman_save_title_nonce' );
        wp_localize_script( 'post-smtp-wizard', 'PostSMTPWizard', $localized );

    }


    /**
     * Render Name and Email Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_name_email_settings() {

        $postman_connections = get_option( 'postman_connections' );
        if ( ! is_array( $postman_connections ) ) {
            $postman_connections = array();
        }
        if( $this->existing_db_version == POST_SMTP_DB_VERSION ){
            if ( isset( $_GET['id'] ) && isset( $postman_connections[ $_GET['id'] ] ) ) {
                // Use specific connection (edit case)
                $from_email = $postman_connections[ $_GET['id'] ]['sender_email'] ?? '';
                $from_name  = $postman_connections[ $_GET['id'] ]['sender_name'] ?? '';
                $from_name_enforced = $postman_connections[ $_GET['id'] ]['prevent_sender_name_override'] ?? '';
                $from_email_enforced = $postman_connections[ $_GET['id'] ]['prevent_sender_email_override'] ?? '';
            } elseif ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'add' ) {
                // No ID given — use the last connection
                $last_connection = end( $postman_connections );
                $from_email = is_array( $last_connection ) && isset( $last_connection['sender_email'] ) ? $last_connection['sender_email'] : '';
                $from_name  = is_array( $last_connection ) && isset( $last_connection['sender_name'] ) ? $last_connection['sender_name'] : '';
                $from_name_enforced = $last_connection['prevent_sender_name_override'] ?? '';
                $from_email_enforced = $last_connection['prevent_sender_email_override'] ?? '';
            }else{
                $from_email =  '';
                $from_name  = '';
                $from_name_enforced = '';
                $from_email_enforced ='';
            }
            // Fix: ensure checked if value is 1 or '1'
            $from_email_enforced = ( $from_email_enforced == 1 || $from_email_enforced === '1') ? 'checked' : '';
            $from_name_enforced = ( $from_name_enforced == 1 || $from_name_enforced === '1') ? 'checked' : '';
        }else {
            // Fallback to stored options
            $from_name  = null !== $this->options->getMessageSenderName() ? esc_attr( $this->options->getMessageSenderName() ) : '';
            $from_email = null !== $this->options->getMessageSenderEmail() ? esc_attr( $this->options->getMessageSenderEmail() ) : '';
            $from_name_enforced = $this->options->isPluginSenderNameEnforced() ? 'checked' : '';
            $from_email_enforced = $this->options->isPluginSenderEmailEnforced() ? 'checked' : '';
        }

        $html = '
        <div class="ps-form-ui ps-name-email-settings">
            <div class="ps-form-control">
                <h3 class="ps-step-heading">Configure Mailer Settings</h3>
                <h3 class="ps-from-address">From Address</h3>
                <p class="ps-from-description">'. sprintf(
                    '%1$s',
                    esc_html__( 'It is important to indicate the origin (email and name) of a message for the receiver. The “From Address” provides these details.', 'post-smtp' )
                ) .'</p>
                <p class="ps-from-description">'. sprintf(
                    '%1$s',
                    esc_html__( 'You may edit the following field if you do not wish to use default settings.', 'post-smtp' )
                ) .'</p>
                <div><label class="ps-from-label">From Email</label></div>
                <input type="text" class="ps-from-email" required data-error="'.__( 'Please enter From Email.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_EMAIL ).']" value="'.$from_email.'" placeholder="Email address that emails are sent from">

                 <div class="ps-force ps-force-email">
                   <p class="ps-force-heading">'.esc_html__( 'Force From Email', 'post-smtp' ).'</p>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_email_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_EMAIL_OVERRIDE ).']">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b>',
                        __( 'Enable this option to prevent other plugins or themes from modifying the', 'post-smtp' ),
                        __( 'From Email', 'post-smtp' )
                    ).'.</span>
                </div> 
            </div>
            <div class="ps-form-control">
                <div><label class="ps-from-label">From Name</label></div>
                <input type="text" class="ps-from-name" required data-error="'.__( 'Please enter From Name.', 'post-smtp' ).'" name="postman_options['.esc_attr( PostmanOptions::MESSAGE_SENDER_NAME ).']" value="'.$from_name.'" placeholder="Name that is sending the emails">
                <div class="ps-force ps-force-name">
                    <p class="ps-force-heading">'.esc_html__( 'Force From Name', 'post-smtp' ).'</p>
                    <div class="ps-form-switch-control">
                        <label class="ps-switch-1">
                            <input type="checkbox" '.$from_name_enforced.' name="postman_options['.esc_attr( PostmanOptions::PREVENT_MESSAGE_SENDER_NAME_OVERRIDE ).']">
                            <span class="slider round"></span>
                        </label> 
                    </div>
                    <span>'.
                    sprintf( 
                        '%1$s <b>%2$s</b>',
                        __( 'Enable this option to prevent other plugins or themes from modifying the', 'post-smtp' ),
                        __( 'From Name', 'post-smtp' )
                    ).
                    '</span>
                </div>
                <div class="ps-wizard-divider"></div>
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
            case 'emailit_api':
                echo wp_kses( $this->render_emailit_settings(), $this->allowed_tags );
            break;
            case 'maileroo_api':
                echo wp_kses( $this->render_maileroo_settings(), $this->allowed_tags );
            break;
            case 'sweego_api':
                echo wp_kses( $this->render_sweego_settings(), $this->allowed_tags );
            break;
            case 'sendgrid_api':
                echo wp_kses( $this->render_sendgrid_settings(), $this->allowed_tags );
            break;
            case 'mailersend_api':  
                echo wp_kses( $this->render_mailersend_settings(), $this->allowed_tags );
            break;
            case 'mailgun_api':
                echo wp_kses( $this->render_mailgun_settings(), $this->allowed_tags );
            break;
            case 'sendinblue_api':
                echo wp_kses( $this->render_brevo_settings(), $this->allowed_tags );
            break;
            case 'mailtrap_api':
                echo wp_kses( $this->render_mailtrap_settings(), $this->allowed_tags );
            break;
            case 'resend_api':
                echo wp_kses( $this->render_resend_settings(), $this->allowed_tags );
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
            case 'aws_ses_api':
                echo wp_kses( $this->render_amazonses_settings(), $this->allowed_tags );
            break;
            case 'office365_api':
                echo wp_kses( $this->render_office365_settings(), $this->allowed_tags );
            break;
            case 'zohomail_api':
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
        $mail_connections = get_option( 'postman_connections' );
        $hostname = '';
        $port = '';
        $username = '';
        $password = '';
        if( $this->existing_db_version == POST_SMTP_DB_VERSION ){
            $mail_connections = get_option('postman_connections');
            $id = $_GET['id'] ?? null;
            if ( isset( $mail_connections[$id] ) ) {
                $hostname = $mail_connections[$id]['hostname'] ?? '';;
                $port     = $mail_connections[$id]['port'] ?? '';;
                $username = $mail_connections[$id]['basic_auth_username'] ?? '';;
                $password = $mail_connections[$id]['basic_auth_password'] ?? '';;    
            }
        }else{
            $hostname = null !== $this->options->getHostname() ? esc_attr ( $this->options->getHostname() ) : '';
            $port = null !== $this->options->getPort() ? esc_attr ( $this->options->getPort() ) : '';
            $username = null !== $this->options->getUsername() ? esc_attr ( $this->options->getUsername() ) : '';
            $password = null !== $this->options->getPassword() ? esc_attr ( $this->options->getPassword() ) : '';    
        }


        $html = '<p>' . esc_html__( 'You can set up any SMTP of your choice, but it is important to remember that custom SMTP may not have adequate security.', 'post-smtp' ) . '</p>';
        $html .= '<p>' . esc_html__( 'Kindly check our ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-other-smtp-with-post-smtp/" target="_blank">' . esc_html__( 'SMTP documentation', 'post-smtp' ) . '</a>' . esc_html__( ' before implementation.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>Host Name</label></div>
            <input type="text" class="ps-smtp-host-name" required data-error="'.__( 'Please enter Host Name.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::HOSTNAME ) .']" value="'.$hostname.'" placeholder="Outgoing Mail Server Hostname">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Port</label></div>
            <input type="text" class="ps-smtp-port" required data-error="'.__( 'Please enter Port.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::PORT ) .']" value="'.$port.'" placeholder="Outgoing Mail Server Port">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Username</label></div>
            <input type="text" class="ps-smtp-username" required data-error="'.__( 'Please enter Username.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::BASIC_AUTH_USERNAME ) .']" value="'.$username.'" placeholder="The Username is usually the same as “From Email” Address">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Password</label></div>
            <input type="text" class="ps-smtp-password" required data-error="'.__( 'Please enter Password.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::BASIC_AUTH_PASSWORD ) .']" value="'.$password.'" placeholder="App Password">
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
        
            $mail_connections = get_option( 'postman_connections' );
            $client_id = '';
            $client_secret = '';
            // Retrieve options for premium features and extensions
            $post_smtp_pro_options = get_option( 'post_smtp_pro', [] );
            $postman_auth_token = get_option( 'postman_auth_token' );
            $bonus_extensions = isset( $post_smtp_pro_options['extensions'] ) ? $post_smtp_pro_options['extensions'] : [];
            $gmail_oneclick_enabled = in_array( 'gmail-oneclick', $bonus_extensions );
            $auth_url = get_option( 'post_smtp_gmail_auth_url' );

            if( $this->existing_db_version == POST_SMTP_DB_VERSION ){
                $id = $_GET['id'] ?? null;
                $action = isset($_GET['action']) ? $_GET['action'] : '';
                
                if ( isset( $mail_connections[ $id ] ) ) {
                    // Use the selected connection for editing
                    $client_id     = $mail_connections[ $id ]['oauth_client_id'] ?? '';
                    $client_secret = $mail_connections[ $id ]['oauth_client_secret'] ?? '';
                } elseif ( $action === 'add' ) {
                    $client_id     = '';
                    $client_secret = '';
                } elseif ( ! empty( $mail_connections ) && is_array( $mail_connections ) && empty( $gmail_oneclick_enabled ) ) {
                    // No ID? Use the last Gmail connection or get last credentials
                    $gmail_credentials = PostmanOptions::get_last_gmail_credentials( $mail_connections );
                    $client_id     = $gmail_credentials['client_id'] ?? '';
                    $client_secret = $gmail_credentials['client_secret'] ?? '';
                } else {
                    $client_id     = '';
                    $client_secret = '';
                }
            }else{
                $client_id = null !== $this->options->getClientId() ? esc_attr ( $this->options->getClientId() ) : '';
                $client_secret = null !== $this->options->getClientSecret() ? esc_attr ( $this->options->getClientSecret() ) : '';
            }
            $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';


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
            // Check if we have OAuth token based on database version and connection context
            $has_oauth_token = false;
            $oauth_email = '';
            
            if( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
                // New connection system - check specific connection or last connection
                $id = $_GET['id'] ?? null;
                $action = isset($_GET['action']) ? $_GET['action'] : '';
                
                if ( isset( $mail_connections[ $id ] ) && isset( $mail_connections[ $id ]['access_token'] ) ) {
                    // Use the selected connection for editing
                    $has_oauth_token = !empty( $mail_connections[ $id ]['access_token'] );
                    $oauth_email = $mail_connections[ $id ]['sender_email'] ?? '';
                } elseif ( $action !== 'add' && ! empty( $mail_connections ) && is_array( $mail_connections ) ) {
                    // No ID and not adding? Use the last Gmail connection
                    $last_gmail_connection = null;
                    foreach( array_reverse( $mail_connections, true ) as $conn_id => $connection ) {
                        if ( isset( $connection['provider'] ) && $connection['provider'] === 'gmail_api' ) {
                            $last_gmail_connection = $connection;
                            break;
                        }
                    }
                    if ( $last_gmail_connection ) {
                        $has_oauth_token = !empty( $last_gmail_connection['access_token'] );
                        $oauth_email = $last_gmail_connection['sender_email'] ?? '';
                    }
                }
            } else {
                // Legacy system - use global token
                $has_oauth_token = $postman_auth_token && isset( $postman_auth_token['user_email'] );
                $oauth_email = $has_oauth_token ? $postman_auth_token['user_email'] : '';
            }

            if ( $has_oauth_token || !empty( $oauth_email ) ) {
                $nonce = wp_create_nonce( 'remove_oauth_action' );
                $action_url = esc_url( add_query_arg(
                    [
                        '_wpnonce' => $nonce,
                        'action' => 'remove_oauth_action',
                        'id' => $id,
                    ],
                    admin_url( 'admin-post.php' )
                ) );
                $html .= ' <span class="icon-circle"><span class="icon-check"></span> </span> <b class= "ps-wizard-success">' . sprintf( esc_html__('Connected with: %s', 'post-smtp'), esc_html( $oauth_email ) ) . '</b>';
                $html .= '<a href="' . $action_url . '" class="ps-remove-gmail-btn ps-disable-gmail-setup wizard-btn-css">';
                $html .= esc_html__( 'Remove Authorization', 'post-smtp' );
                $html .= '</a>';
            } else {
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
     * Render Emailit Settings
     */
    public function render_emailit_settings() {
        
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['emailit_api_key'] ) ) {
            $api_key = $mail_connections[$id]['emailit_api_key'];
        }
		 $api_key = $api_key ?: esc_attr( $this->options->getEmailitApiKey() ?? '' );
        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p>',
            esc_url( 'https://emailit.com/' ),
            __( 'Emailit', 'post-smtp' ),
            __( 'is a transactional email provider. Enter your API Key and Endpoint below.', 'post-smtp' )
        );
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-emailit-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::EMAILIT_API_KEY ) .']" value="'.$api_key.'" placeholder="">
            <div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.emailit.com/dashboard" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your Emailit account.', 'post-smtp' ) . '</div>
        </div>';
        return $html;
    }

    /**
     * Render Sweego Settings
     */
    public function render_sweego_settings() {
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['sweego_api_key'] ) ) {
            $api_key = $mail_connections[$id]['sweego_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSweegoApiKey() ?? '' );

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://sweego.com/' ),
            __( 'Sweego', 'post-smtp' ),
            __( 'is a transactional email provider. Enter your API Key and Endpoint below.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sweego-with-post-smtp/' ),
            __( 'Sweego Documentation', 'post-smtp' )
        );

        $html .= '<div class="ps-form-control"><div><label>API Key</label></div>       <input type="text" class="ps-sweego-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SWEEGO_API_KEY ) .']" value="' . $api_key . '" placeholder="API Key"></div>';

        return $html;
    }

    /**
     * Render Maileroo Settings
     */
    public function render_maileroo_settings() {
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['maileroo_api_key'] ) ) {
            $api_key = $mail_connections[$id]['maileroo_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getMailerooApiKey() ?? '' );

        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://maileroo.com/' ),
            __( 'Maileroo', 'post-smtp' ),
            __( 'is a transactional email provider. Enter your API Key and Endpoint below.', 'post-smtp' ),
            __( 'Let\'s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-maileroo-with-post-smtp/' ),
            __( 'Maileroo Documentation', 'post-smtp' )
        );

        $html .= '<div class="ps-form-control"><div><label>API Key</label></div>       <input type="text" class="ps-maileroo-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILEROO_API_KEY ) .']" value="' . $api_key . '" placeholder="API Key"></div>';

        return $html;
    }

    /**
     * Render Mandrill Settings
     * 
     * @since 2.7.0
     * @version 1.0.0
     */
    public function render_mandrill_settings() {

        $id = $_GET['id'] ?? null;
        $api_key = '';
        $mail_connections = get_option( 'postman_connections' );
        
        // Check if 'id' exists and 'mandrill_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['mandrill_api_key'] ) ) {
            $api_key = $mail_connections[$id]['mandrill_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getMandrillApiKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Mandrill mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mandrill-with-post-smtp/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mandrill-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MANDRILL_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://mandrillapp.com/settings/index" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a>' . esc_html__( ' in your Mandrill account.', 'post-smtp' ) . '</div>'
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

        $id = $_GET['id'] ?? null;
        $api_key = '';
        $mail_connections = get_option( 'postman_connections' );
        
        // Check if 'id' exists and 'sendgrid_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['sendgrid_api_key'] ) ) {
            $api_key = $mail_connections[$id]['sendgrid_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSendGridApiKey() ?? '' );

        $selected_region = $this->options->getSendGridRegion() ? esc_attr( $this->options->getSendGridRegion() ) : 'AG';

        $html = '<p>' . esc_html__( 'It is easy to integrate SendGrid mailer to your WordPress website. We recommend you to check the ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendgrid-with-post-smtp/" target="_blank">' . esc_html__( 'documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';

        $html .= '<div class="ps-wizard-divider"></div>';

        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-sendgrid-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDGRID_API_KEY ) .']" value="'.$api_key.'" placeholder="">
            <div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.sendgrid.com/settings/api_keys" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a> ' . esc_html__( ' above in your SendGrid account.', 'post-smtp' ) . '</div>' .
        '</div>';

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
        
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['mailersend_api_key'] ) ) {
            $api_key = $mail_connections[$id]['mailersend_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getMailerSendApiKey() ?? '' );
        
        $html = sprintf(
            '<p><a href="%1$s" target="_blank">%2$s</a> %3$s</p><p>%4$s <a href="%5$s" target="_blank">%6$s</a></p>',
            esc_url( 'https://mailersend.com/' ),
            __( 'MailerSend', 'post-smtp' ),
            __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details.', 'post-smtp' ),
            __( 'Let’s get started with our', 'post-smtp' ),
            esc_url( 'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mailersend-with-post-smtp/' ),
            __( 'MailerSend Documentation', 'post-smtp' )
        );

        $html = '<p>' . esc_html__( 'It is easy to integrate MailerSend API mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mailersend-with-post-smtp/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailersend-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILERSEND_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
         '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.mailersend.com/api-tokens" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your MailerSend account.', 'post-smtp' ) . '</div>'
            .'
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

        $id = $_GET['id'] ?? null;
        $api_key = '';
        $domain_name = '';
        $region = '';

        $mail_connections = get_option( 'postman_connections' );
        $region = null !== $this->options->getMailgunRegion() ? ' checked' : '';
        
        // Check if 'id' exists and 'mailgun_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) ) {
            $api_key = $mail_connections[$id]['mailgun_api_key'] ?? '';
            $domain_name = $mail_connections[$id]['mailgun_domain_name'] ?? '';
        }
        $api_key = $api_key ?: esc_attr( $this->options->getMailgunApiKey() ?? '' );
        $domain_name = $api_key ?: esc_attr( $this->options->getMailgunDomainName() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Mailgun mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-mail-gun/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailgun-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILGUN_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.mailgun.com/settings/api_security" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a>' . esc_html__( ' in your Mailgun account.', 'post-smtp' ) . '</div>'
            .'
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Domain Name</label></div>
            <input type="text" class="ps-mailgun-domain-name" required data-error="'.__( 'Please Domain Name.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILGUN_DOMAIN_NAME ) .']" value="'.$domain_name.'" placeholder="">
            <span class="ps-form-control-info">'.
            '<div class="ps-form-control-info">' . esc_html__( 'You can find the ', 'post-smtp' ) . '<a href="https://app.mailgun.com/app/sending/domains" target="_blank">' . esc_html__( 'Domain', 'post-smtp' ) . '</a>' . esc_html__( ' in your Mailgun account.', 'post-smtp' ) . '</div>'
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control ps-force" >
            <div><label>Mailgun Europe Region</label></div>
            <div class="ps-form-switch-control">
                <label class="ps-switch-1">
                    <input type="checkbox" '.$region.' name="postman_options['.esc_attr( PostmanOptions::MAILGUN_REGION ).']">
                    <span class="slider round"></span>
                </label> 
            </div>
            '.

            '<div class="ps-form-control-info">' . esc_html__( 'Set your endpoint in Europe if your business operates under EU laws. ', 'post-smtp' ) . '<a href="https://www.mailgun.com/about/regions/" target="_blank">' . esc_html__( 'Learn more about Mailgun regions.', 'post-smtp' ) . '</a></div>'
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

        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['sendinblue_api_key'] ) ) {
            $api_key = $mail_connections[$id]['sendinblue_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSendinblueApiKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Brevo mailer to your WordPress website. We recommend you to check the ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-brevo-with-post-smtp/" target="_blank">' . esc_html__( 'documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-brevo-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDINBLUE_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
             '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://login.brevo.com/?target=https%3A%2F%2Fapp.brevo.com%2Fsettings%2Fkeys%2Fapi" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your Brevo account.', 'post-smtp' ) . '</div>'
            .
        '</div>
        ';

        return $html;

    }

    /**
     * Render Mailtrap Settings
     * 
     * @since 2.9.0
     * @version 1.0.0
     */
    public function render_mailtrap_settings() {
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';

        if ( isset( $_GET['id'] ) && isset( $mail_connections[ $id ]['mailtrap_api_key'] ) ) {
            $api_key = $mail_connections[ $id ]['mailtrap_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getMailtrapApiKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Mailtrap mailer to your WordPress website. We recommend you to check the ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-mailtrap-with-post-smtp" target="_blank">' . esc_html__( 'documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Token</label></div>
            <input type="text" class="ps-mailtrap-api-key" required data-error="'.__( 'Please enter API Token.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILTRAP_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */
             '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://mailtrap.io/api-tokens" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your Mailtrap API account.', 'post-smtp' ) . '</div>'
            .
        '</div>
        ';

        return $html;

    }


    /**
     * Render Resend Settings
     * 
     * @since 3.2.0
     * @version 1.0.0
     */
    public function render_resend_settings() {

        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['resend_api_key'] ) ) {
            $api_key = $mail_connections[$id]['resend_api_key'];
        }
		 $api_key = $api_key ?: esc_attr( $this->options->getResendApiKey() ?? '' );

        $html = '<p>' . esc_html__( 'It is easy to integrate Resend mailer to your WordPress website. We recommend you to check the ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-resend-with-post-smtp" target="_blank">' . esc_html__( 'documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-resend-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::RESEND_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://resend.com/api-keys" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your Resend account.', 'post-smtp' ) . '</div>'
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

        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        
        // Check if 'id' exists and 'postmark_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['postmark_api_key'] ) ) {
            $api_key = $mail_connections[$id]['postmark_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getPostmarkApiKey() ?? '' );

        $html = '<p>' . esc_html__( 'It is easy to integrate Postmark mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/postmark/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-postmark-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::POSTMARK_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://account.postmarkapp.com/api_tokens" target="_blank">' . esc_html__( 'the API tokens', 'post-smtp' ) . '</a>' . esc_html__( ' in your Postmark account.', 'post-smtp' ) . '</div>'
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

        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        
        // Check if 'id' exists and 'sparkpost_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['sparkpost_api_key'] ) ) {
            $api_key = $mail_connections[$id]['sparkpost_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSparkPostApiKey() ?? '' );

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
            <input type="text" class="ps-sparkpost-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SPARKPOST_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.sparkpost.com/account/api-keys" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a>' . esc_html__( ' in your SparkPost account.', 'post-smtp' ) . '</div>'
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

        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        
        // Check if 'id' exists and 'elasticemail_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['elasticemail_api_key'] ) ) {
            $api_key = $mail_connections[$id]['elasticemail_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getElasticEmailApiKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Elastic Email mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-elastic-mail-with-post-smtp/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-elasticemail-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::ELASTICEMAIL_API_KEY ) .']" value="'.$api_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://elasticemail.com/account#/settings/new/manage-api" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a>' . esc_html__( ' in your Elastic Email account.', 'post-smtp' ) . '</div>'
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
        $id = $_GET['id'] ?? null;
        $api_key = '';
        $secret_key = '';
        $mail_connections = get_option( 'postman_connections' );
        
        // Check if 'id' exists and 'mailjet_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) ) {
            $api_key = $mail_connections[$id]['mailjet_api_key'] ?? '';
            $secret_key = $mail_connections[$id]['mailjet_secret_key'] ?? '';
        }

        $api_key = $api_key ?: esc_attr( $this->options->getMailjetApiKey() ?? '' );
        $secret_key = $secret_key ?: esc_attr( $this->options->getMailjetSecretKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate Mailjet mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-mailjet-with-post-smtp/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-mailjet-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILJET_API_KEY ) .']" value="'.$api_key.'" placeholder=""></div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Secret Key</label></div>
            <input type="text" class="ps-mailjet-secret-key" required data-error="'.__( 'Please enter Secret Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::MAILJET_SECRET_KEY ) .']" value="'.$secret_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app.mailjet.com/account/apikeys" target="_blank">' . esc_html__( 'API and Access information', 'post-smtp' ) . '</a>' . esc_html__( ' in your Mailjet account.', 'post-smtp' ) . '</div>'
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
        $id = $_GET['id'] ?? null;
        $api_key = '';
        $secret_key = '';
        $mail_connections = get_option( 'postman_connections' );
        
        // Check if 'id' exists and 'sendpulse_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) ) {
            $api_key = $mail_connections[$id]['sendpulse_api_key'] ?? '';
            $secret_key = $mail_connections[$id]['sendpulse_secret_key'] ?? '';
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSendpulseApiKey() ?? '' );
        $secret_key = $secret_key ?: esc_attr( $this->options->getSendpulseSecretKey() ?? '' );


        $html = '<p>' . esc_html__( 'It is easy to integrate SendPulse mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-sendpulse/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>API ID</label></div>
            <input type="text" class="ps-sendpulse-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDPULSE_API_KEY ) .']" value="'.$api_key.'" placeholder="">
        '.
        // sprintf(
        //     '<div class="ps-form-control-info"><a href="%1$s" target="_blank">%2$s</a> %3$s</div>',
        //     esc_url( 'https://sendpulse.com/features/transactional' ),
        //     __( 'Click here', 'post-smtp' ),
        //     __( 'to create an account at SendPulse', 'post-smtp' )
        // ).
        // sprintf(
        //     '<div class="ps-form-control-info">%1$s<a href="%2$s" target="_blank">%3$s</a></div>',
        //     __( 'If you are already logged in follow this ink to get your API ID from Sendpulse ', 'post-smtp' ),
        //     esc_url( 'https://login.sendpulse.com/settings/#api' ),
        //     __( 'Get API ID', 'post-smtp' )
        // ).
        '</div>'
        ;

        $html .= '
        <div class="ps-form-control">
            <div><label>API Secret</label></div>
            <input type="text" class="ps-sendpulse-secret-key" required data-error="'.__( 'Please enter Secret Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SENDPULSE_SECRET_KEY ) .']" value="'.$secret_key.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://login.sendpulse.com/settings/#api" target="_blank">' . esc_html__( 'the API credentials', 'post-smtp' ) . '</a>' . esc_html__( ' in your SendPulse account.', 'post-smtp' ) . '</div>'
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
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        if ( isset( $_GET['id'] ) ) {
            $access_key_id = $mail_connections[$id][ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_ACCESS_KEY_ID ];
            $access_key_secret = $mail_connections[$id][ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_SECRET_ACCESS_KEY ];
            $region = $mail_connections[$id][ PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_REGION ];
        }


        $html = '<p>' . esc_html__( 'Due to the technical nature of this SMTP implementation, it is recommended to study this ', 'post-smtp' ) . '<a href="' . esc_url( 'https://postmansmtp.com/docs/mailers/new-amazon-ses/' ) . '" target="_blank">' . esc_html__( 'step-by-step guide', 'post-smtp' ) . '</a>' . esc_html__( ' at the time of setup.', 'post-smtp' ) . '</p>';
        $html .= '<p>⚠️ ' . esc_html__( 'You must have a working SSL certificate installed on your WordPress site to use it with Amazon SES.', 'post-smtp' ) . '</p>';
        $html .= '<div class="ps-wizard-divider"></div>';
        $html .= '
        <div class="ps-form-control">
            <div><label>Access Key ID</label></div>
            <input type="text" class="ps-amazon-key-id" required data-error="'.__( 'Please enter Access Key ID', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_ACCESS_KEY_ID ) .']" value="'.$access_key_id.'" placeholder=""></div>';

        $html .= '
        <div class="ps-form-control">
            <div><label>Access Key Secret</label></div>
            <input type="text" class="ps-amazon-key-secret" required data-error="'.__( 'Please enter Access Key Secret', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_SECRET_ACCESS_KEY ) .']" value="'.$access_key_secret.'" placeholder="">'.
            /**
             * Translators: %1$s Text, %2$s URL, %3$s URL Text, %4$s Text, %5$s URL, %6$s URL Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'If you are already logged in, ', 'post-smtp' ) . '<a href="https://us-east-1.console.aws.amazon.com/iamv2/home#/users" target="_blank">' . esc_html__( 'visit this link', 'post-smtp' ) . '</a>' . esc_html__( ' to get the Access Key ID and Secret Access Key.', 'post-smtp' ) . '</div>'
            .
        '</div>
        ';


        $html .= '
        <div class="ps-form-control">
            <div><label>SES Region</label></div>
            <input type="text" class="ps-amazon-region" required data-error="'.__( 'Please enter SES Region', 'post-smtp' ).'" name="postman_options['. esc_attr( PostSMTPSES\PostSmtpAmazonSesTransport::OPTION_REGION ) .']" value="'.$region.'" placeholder="Enter the correct region">
        </div>
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

        $mail_connections  = get_option( 'postman_connections' );
        $options = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
        if ( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
            $id = $_GET['id'] ?? null;
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            $app_client_id = '';
            $app_client_secret = '';
            if ( isset( $mail_connections[ $id ] ) ) {
                // Selected connection exists → use it
                $app_client_id     = $mail_connections[ $id ]['office365_app_id'] ?? '';
                $app_client_secret = $mail_connections[ $id ]['office365_app_password'] ?? '';
            } elseif ( $action === 'add' ) {
                $app_client_id     = '';
                $app_client_secret = '';
            } elseif ( ! empty( $mail_connections ) && is_array( $mail_connections ) ) {
                // No ID? Use last Office 365 connection, or any connection as fallback
                $last_connection   = PostmanOptions::get_last_office365_credentials( $mail_connections );
                $app_client_id     = $last_connection['office365_app_id'] ?? '';
                $app_client_secret = $last_connection['office365_app_password'] ?? '';
            } else {
                // No connections → empty values
                $app_client_id     = '';
                $app_client_secret = '';
            }

        } else {
            // Old DB version → use base64 decoded stored values
            $app_client_id     = isset( $options['office365_app_id'] ) ? base64_decode( $options['office365_app_id'] ) : '';
            $app_client_secret = isset( $options['office365_app_password'] ) ? base64_decode( $options['office365_app_password'] ) : '';
        }

        $redirect_uri = admin_url();
        
        // Check if access token exists for Office 365
        $office365_oauth = get_option( 'postman_office365_oauth' );
        $has_access_token = $office365_oauth && isset( $office365_oauth['access_token'] ) && ! empty( $office365_oauth['access_token'] );
        
        // Retrieve options for premium features and extensions
        $post_smtp_pro_options = get_option( 'post_smtp_pro', [] );
        $postman_office365_auth_token = get_option( 'postman_office365_oauth' );
        $extensions = isset( $post_smtp_pro_options['extensions'] ) ? $post_smtp_pro_options['extensions'] : [];
        $office365_oneclick_enabled = in_array( 'microsoft-one-click', $extensions );
        $office365_auth_url = get_option( 'post_smtp_office365_auth_url' );

        $html = '<p>' . esc_html__( 'To establish a SMTP connection, you will need to create an app in your Azure account. This ', 'post-smtp' ) . ' <a href="' . esc_url( 'https://postmansmtp.com/docs/mailers/how-to-setup-office-365-with-post-smtp/' ) . '" target="_blank">' . esc_html__( 'step-by-step guide', 'post-smtp' ) . '</a> ' . esc_html__( 'will walk you through the whole process.', 'post-smtp' ) . '</p>';
        // Setup classes and attributes for form visibility
        $hidden_class = $office365_oneclick_enabled ? 'ps-hidden' : '';
        // Conditional 'required' attribute for the fields - consider access token when one-click is enabled
        $client_secret_required = $office365_oneclick_enabled ? '' : 'required';
        $client_id_required = $office365_oneclick_enabled ? '' : 'required';
        $one_click_class = 'ps-enable-office365-one-click';
        $url = POST_SMTP_URL . '/Postman/Wizard/assets/images/ms365.png';
        $transport_name = __( '<strong>One-Click</strong> Microsoft Mailer Setup?', 'post-smtp' );
        $product_url = postman_is_bfcm() ? 
            'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 
            'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard_microsoft&utm_campaign=plugin';

        // Prepare data for JSON encoding
        $data = [
            'url' => $url,
            'transport_name' => $transport_name,
            'product_url' => $product_url
        ];
        $json_data = htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );

            // Determine whether we have both token and email stored for Office365
            // Only treat stored user_email as valid when one-click is enabled.
            $has_email = false;
            if ( $office365_oneclick_enabled && $office365_oauth && isset( $office365_oauth['user_email'] ) && ! empty( $office365_oauth['user_email'] ) ) {
                $has_email = true;
            }

            // Set required based on context:
            // - For one-click: skip if success param is set OR both access token and email exist
            // - For normal setup: skip if success param is set OR both access token and email exist
            if ( $office365_oneclick_enabled ) {
                $required = ( ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) || ( $has_access_token && $has_email ) ) ? '' : 'required';
            } else {
                $required = ( ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) || ( $has_access_token && $has_email ) ) && $client_id_required ? '' : 'required';
            }


        if ( post_smtp_has_pro() ) {
            $one_click = true;
            $html .= sprintf( '<div class="ps-force"><h3>%1$s</h3>', __( 'One-Click Setup', 'post-smtp' ) );
        } else {
            $html .= sprintf(
                '<div class="ps-force"><h3>%1$s <span class="ps-wizard-pro-tag">%2$s</span></h3>',
                __( 'One-Click Setup', 'post-smtp' ),
                __( 'PRO', 'post-smtp' )
            );
            $one_click = 'disabled';
            $one_click_class .= ' disabled';
        }

        $html .= __( 'Enable the option for a quick, easy way to connect to Microsoft 365 without manually creating an app.', 'post-smtp' );

        // Check if user has business plan for Office 365 one-click
        $is_business_plan = false;
        if ( function_exists( 'pspro_fs' ) && pspro_fs()->is_plan( 'business' ) ) {
            $is_business_plan = true;
        }

        // Check Post SMTP Pro version if user has business plan
        $show_version_warning = false;
        $required_pro_version = '1.5.0';
        if ( $is_business_plan && defined( 'POST_SMTP_PRO_VERSION' ) ) {
            $current_pro_version = POST_SMTP_PRO_VERSION;
            if ( version_compare( $current_pro_version, $required_pro_version, '<' ) ) {
                $show_version_warning = true;
            }
        }

        // One-click switch control
        $html .= "<div>
            <div class='ps-form-switch-control'>
                <label class='ps-switch-1" . ( (!$is_business_plan && post_smtp_has_pro()) || $show_version_warning ? ' ps-office365-upgrade-required' : '' ) . "'>
                    <input type='hidden' id='ps-one-click-data-office365' value='" . esc_attr( $json_data ) . "'>
                    <input type='checkbox' class='$one_click_class' " . ( $office365_oneclick_enabled && $is_business_plan && !$show_version_warning ? 'checked' : '' ) . ( (!$is_business_plan && post_smtp_has_pro()) || $show_version_warning ? ' disabled' : '' ) . ">
                    <span class='slider round'></span>
                </label> 
            </div>
        </div></div>";

        // Show business plan upgrade notice if needed
        if ( post_smtp_has_pro() && !$is_business_plan ) {
            $html .= '<div class="ps-business-plan-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            $html .= '<p style="margin: 0; color: #856404;"><strong>' . __( 'Microsoft 365 (Outlook)', 'post-smtp' ) . '</strong><br>';
            $html .= __( ' One-Click Setup is available only with Business plan. Click on the toggle to update.', 'post-smtp' ) . '</p>';
            $html .= '</div>';
            // Inject conditional CSS
            add_action( 'admin_footer', function () {
                echo '<style>
                    .office365_api-outer .ps-wizard-footer-left .ps-in-active-nav .ps-wizard-line:after {
                        height: 1089px !important;
                    }
                </style>';
            });

        }

        // Show version warning if user has business plan but outdated Post SMTP Pro version
        if ( $show_version_warning ) {
            $html .= '<div class="ps-version-warning-notice" style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            $html .= '<p style="margin: 0; color: #721c24;">' . __( 'Please update your Post SMTP Pro plugin to use this feature.', 'post-smtp' ) . '</p>';
            $html .= '</div>';
        }
 	  
		$html .= '<div class="ps-disable-one-click-setup ' . ( $office365_oneclick_enabled ? 'ps-hidden' : '' ) . '">';
		
        // $html .= sprintf(
        //     '<p><a href="%1$s" target="_blank">%2$s</a> %3$s </p><a href="%4$s" target="_blank">%5$s</a>',
        //     esc_url( 'https://azure.microsoft.com/en-us/pricing/purchase-options/azure-account?icid=azurefreeaccount' ),
        //     __( 'Office 365', 'post-smtp' ),
        //     __( 'is a popular transactional email provider that sends more than 35 billion emails every month. If you\'re just starting out, the free plan allows you to send up to 100 emails each day without entering your credit card details', 'post-smtp' ),
        //     esc_url( 'https://postmansmtp.com/docs/mailers/microsoft-365-one-click-smtp/' ),
        //     __( 'Read how to setup Office 365', 'post-smtp' )
        // );
       
		$html .= '<hr /> <h3>Manual Setup</h3>';

        $html .= '
        <div class="ps-form-control">
            <div><label>'.__( 'Application (Client) ID', 'post-smtp' ).'</label></div>
            <input type="text" class="ps-office365-client-id" ' . $client_id_required . '  data-error="'.__( 'Please enter Application (Client) ID.', 'post-smtp' ).'" name="postman_options[office365_app_id]" value="'.$app_client_id.'" placeholder="">
            <span class="ps-form-control-info">'.
            '<div class="ps-form-control-info">' . esc_html__( 'You can find the ', 'post-smtp' ) . '<a href="https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0" target="_blank">' . esc_html__( 'client id', 'post-smtp' ) . '</a>' . esc_html__( ' here.', 'post-smtp' ) . '</div>'
            .'</span>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>'.__( 'Client Secret (Value)', 'post-smtp' ).'</label></div>
            <input type="text" class="ps-office365-client-secret" ' . $client_secret_required . '  data-error="'.__( 'Please enter Client Secret (Value).', 'post-smtp' ).'" name="postman_options[office365_app_password]" value="'.$app_client_secret.'" placeholder="">
            <span class="ps-form-control-info">'.
            /**
             * Translators: %1$s URL, %2$s URL Text, %3$s Text
             */

            '<div class="ps-form-control-info">' . esc_html__( 'You can find the ', 'post-smtp' ) . '<a href="https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0" target="_blank">' . esc_html__( 'client secret', 'post-smtp' ) . '</a>' . esc_html__( ' here.', 'post-smtp' ) . '</div>'
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

            '<div class="ps-form-control-info">' . esc_html__( 'You can place the ', 'post-smtp' ) . '<a href="https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?redirect_uri=https%3A%2F%2Fportal.azure.com%2Fsignin%2Findex%2F&response_type=code%20id_token&scope=https%3A%2F%2Fmanagement.core.windows.net%2F%2Fuser_impersonation%20openid%20email%20profile&state=OpenIdConnect.AuthenticationProperties%3D3ck4pNl3uhpmQz6zVF-QK4L_RKv9glDdJlITzamc-wID4UbZU1Qb1lYDEbqyr7cc4qml3HIpLuGSbrYKEdzvAnslPezoXRu-_TkLEDHNWCPkZE2SqMJPPkcruP29vocPdJeuKpQbUtwtOQkHhU_0dJU_drkiHPqXROXPu9GJQZyJCyQ5rGsQWp0iZFhlRou7VL8PQOzgBoaCvcVH6XzNgZJFgmeYXjmxj7qK_RUQAcm1BkN2p30gkxAiDgtXHUBNFg-qk0aK_n2Nu-eACOL9oW1dZ2PckrjpZNo7SNgCoxG7dzqRAl3nH-hMoqrCq7HyvoA6LQQ9Bx6r071wB-cbwQA6oNP5E4GLAu9WpGs-tsFJvqnq-QR0PM-FZlD1ZupsKIuyNAWm0s4SlLneNh5hi8aMbVo5AJA5G7221N3Vz3zk3jVsD6kq5JZnJZLALPq6BdmTuBvZZfAF6_pSO47bgxdh6hUVNsRSCtGOqTsGcd8&response_mode=form_post&nonce=638717524432120598.YjE5MDc1ZDctYThiZS00NzZhLTgzOGMtZGYwMzMxMTAxNzA3MjFhMWE0OGQtMjIxMS00NDRlLWI5Y2UtODg1YmFjOTNmNTIw&client_id=c44b4083-3bb0-49c1-b47d-974e53cbdf3c&site_id=501430&client-request-id=844ca630-139b-496b-b93c-9a7b66797706&x-client-SKU=ID_NET472&x-client-ver=7.5.0.0" target="_blank">' . esc_html__( 'redirect url', 'post-smtp' ) . '</a>' . esc_html__( ' as needed.', 'post-smtp' ) . '</div>'
            .'</span>
        </div>
        ';

        $html .= '
        <h3>Authorization (Required)</h3>
        <p>'.__( 'Before continuing, you\'ll need to allow this plugin to send emails using your Office 365 account.', 'post-smtp' ).'</p>
          <input class="office_365-require" type="hidden" '.$required.'  data-error="Please authenticate by clicking Connect to Office 365" />
        <a class="button button-primary ps-blue-btn" id="ps-wizard-connect-office365">Connect to Office 365</a>';
	
        $html .= '</div>';
            
        $html .= '<div class="ps-disable-office365-setup ' . ( $office365_oneclick_enabled ? '' : 'ps-hidden' ) . '">';
        if ( post_smtp_has_pro() ) {
            if ( $postman_office365_auth_token  && isset( $postman_office365_auth_token['user_email'] ) ) {
                $nonce = wp_create_nonce( 'remove_365_oauth_action' );
                $action_url = esc_url( add_query_arg(
                    [
                        '_wpnonce' => $nonce,
                        'action' => 'remove_365_oauth_action',
                    ],
                    admin_url( 'admin-post.php' )
                ) );
                if ( isset( $postman_office365_auth_token['user_email'] ) ) {
                $html .= '<span class="icon-circle"><span class="icon-check"></span> </span> <b>' . sprintf( esc_html__('Connected with: %s', 'post-smtp'), esc_html( $postman_office365_auth_token['user_email'] ) ) . '</b>';
                }
                $html .= '<a href="' . $action_url . '" class="button button-secondary ps-remove-office365-btn">';
                $html .= esc_html__( 'Remove Authorization', 'post-smtp' );
                $html .= '</a>';
            }else {
                $html .= '<h3>' . esc_html__( 'Authorization (Required)', 'post-smtp' ) . '</h3>';
                $html .= '<p>' . 'Before proceeding, you’ll need to authorize this plugin to send emails using the Office 365 API. This <a href="https://postmansmtp.com/docs/mailers/microsoft-365-one-click-setup/" target="_blank">step-by-step guide</a> will walk you through the entire process.</p>';
                $html .= '<input class="office_365-require" type="hidden" ' . esc_attr( $required ) . ' value="' . ( ( $has_access_token && $has_email ) ? '1' : '' ) . '" data-error="' . esc_attr__( 'Please authenticate by clicking Connect to Office 365 API', 'post-smtp' ) . '" />';
                $html .= '<a href="#" class="button button-primary ps-office365-btn">';
                $html .= esc_html__( 'Sign in with Microsoft', 'post-smtp' );
                $html .= '</a>';
            }
        }

        $html .= '</div>';
        
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

        if ( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
            $mail_connections = get_option( 'postman_connections' );
            $id = $_GET['id'] ?? null;
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            $client_id = '';
            $client_secret = '';
            if ( isset( $mail_connections[ $id ] ) ) {
                // Selected connection
                $client_id     = $mail_connections[ $id ]['zohomail_client_id'] ?? '';
                $client_secret = $mail_connections[ $id ]['zohomail_client_secret'] ?? '';
            } elseif ( $action === 'add' ) {
                $client_id     = '';
                $client_secret = '';
            } elseif ( ! empty( $mail_connections ) && is_array( $mail_connections ) ) {
                // No ID → use helper function to find last Zoho credentials
                $zoho_credentials = PostmanOptions::get_last_zoho_credentials( $mail_connections );
                $client_id     = $zoho_credentials['client_id'];
                $client_secret = $zoho_credentials['client_secret'];
            } else {
                // Nothing found
                $client_id     = '';
                $client_secret = '';
            }

        } else {
            $client_id     = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ] )
                                ? $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ]
                                : '';
            $client_secret = isset( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] )
                                ? base64_decode( $this->options_array[ ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ] )
                                : '';
        }

        $required = ( isset( $_GET['success'] ) && $_GET['success'] == 1 ) ? '' : 'required';


        $html = '<p>' . esc_html__( 'It is recommended to study the ', 'post-smtp' ) . '<a href="https://postmansmtp.com/docs/mailers/how-to-setup-zoho-with-post-smtp/" target="_blank">' . esc_html__( 'Zoho Mail integration doc', 'post-smtp' ) . '</a>' . esc_html__( ' at the time of setup.', 'post-smtp' ) . '</p>';

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
            <input type="text" class="ps-zoho-client-id" required data-error="'.__( 'Please enter Client ID.', 'post-smtp' ).'" name="postman_options['. esc_attr( ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_ID ) .']" value="'.$client_id.'" placeholder="">
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Client Secret</label></div>
            <input type="text" class="ps-zoho-client-secret" required data-error="'.__( 'Please enter Client Secret.', 'post-smtp' ).'" name="postman_options['. esc_attr( ZohoMailPostSMTP\ZohoMailTransport::OPTION_CLIENT_SECRET ) .']" value="'.$client_secret.'" placeholder="">
            <div class="ps-form-control-info">
                ' . esc_html__( 'Check your ', 'post-smtp' ) . '<a href="https://api-console.zoho.com/" target="_blank">' . esc_html__( 'Zoho API credentials', 'post-smtp' ) . '</a>' . esc_html__( ' to find the Client ID and Secret.', 'post-smtp' ) . '
            </div>
        </div>
        ';

        $html .= '
        <div class="ps-form-control">
            <div><label>Redirect URI</label></div>
            <input type="text" class="ps-zoho-redirect-uri" value="'.admin_url( 'admin.php?page=postman/' ).'" readonly>
            <span class="ps-form-control-info">
            '.sprintf( __( 'Please copy this URL into the %1$s"Redirect URI"%2$s field of your Zoho account settings.', 'post-smtp' ), '<b>', '</b>' ).'
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
        $mail_connections = get_option( 'postman_connections' );
        $id = $_GET['id'] ?? null;
        $api_key = '';
        
        // Check if 'id' exists and 'smtp2go_api_key' is set in the connection.
        if ( isset( $_GET['id'] ) && isset( $mail_connections[$id]['smtp2go_api_key'] ) ) {
            $api_key = $mail_connections[$id]['smtp2go_api_key'];
        }
        $api_key = $api_key ?: esc_attr( $this->options->getSmtp2GoApiKey() ?? '' );


        echo '<p>' . esc_html__( 'It is easy to integrate SMTP2GO mailer to your WordPress website. We recommend you to ', 'post-smtp' ) . '<a href="https://postmansmtp.com/documentation/sockets-addons/how-to-setup-smtp2go-with-post-smtp/" target="_blank">' . esc_html__( 'check the documentation', 'post-smtp' ) . '</a>' . esc_html__( ' for a successful integration.', 'post-smtp' ) . '</p>';
        echo '<div class="ps-wizard-divider"></div>';
        echo '<div class="ps-form-control">
            <div><label>API Key</label></div>
            <input type="text" class="ps-smtp2go-api-key" required data-error="'.__( 'Please enter API Key.', 'post-smtp' ).'" name="postman_options['. esc_attr( PostmanOptions::SMTP2GO_API_KEY ) .']" value="'.$api_key.'" placeholder="">';

        echo '<div class="ps-form-control-info">' . esc_html__( 'You can find ', 'post-smtp' ) . '<a href="https://app-eu.smtp2go.com/sending/apikeys/" target="_blank">' . esc_html__( 'the API key', 'post-smtp' ) . '</a>' . esc_html__( ' in your SMTP2GO account.', 'post-smtp' ) . '</div>';

        echo '</div>';

        return ob_get_clean();
    }


    /**
     * Save Wizard | AJAX Callback
     *
     * @since 2.7.0
     * @version 1.1.0
     */
    public function save_wizard() {

        $form_data = array();
        parse_str( $_POST['FormData'], $form_data );

        if (
            isset( $_POST['action'] ) &&
            'ps-save-wizard' === $_POST['action'] &&
            wp_verify_nonce( $form_data['security'], 'post-smtp' )
        ) {
            if ( $this->existing_db_version === '1.0.1' ) {
                $response = $this->handle_legacy_save( $form_data );
                delete_transient( PostmanSession::ACTION );
                wp_send_json( array(), 200 );
            } else {
                $response_data = $this->handle_new_version_save( $form_data );
                delete_transient( PostmanSession::ACTION );
                wp_send_json_success( $response_data );
            }
        }
        wp_send_json( array(), 200 );

    }

    /**
     * AJAX callback to generate a fresh Office 365 One-Click OAuth URL.
     *
     * This endpoint is called when the user clicks the "Sign in with Office 365" button
     * for the Office 365 One-Click setup. It validates the request nonce and current user
     * capability, then uses the shared helper `post_smtp_get_office365_auth_url()` to
     * create an auth URL that contains a fresh `office365_oauth_redirect` nonce.
     * AJAX callback to generate a fresh Gmail One-Click OAuth URL.
     *
     * This endpoint is called when the user clicks the "Sign in with Google" button
     * for the Gmail One-Click setup. It validates the request nonce and current user
     * capability, then uses the shared helper `post_smtp_get_gmail_auth_url()` to
     * create an auth URL that contains a fresh `gmail_oauth_redirect` nonce.
     *
     * The URL is returned as JSON and the browser is redirected client-side.
     *
     * @since 3.1.0
     */
    public function ajax_get_office365_auth_url() {

        // Capability check: Only allow administrators.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
        }

        // Nonce check for CSRF protection.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ps_get_office365_auth_url' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid or missing nonce.' ), 400 );
        }

        if ( ! function_exists( 'post_smtp_get_office365_auth_url' ) ) {
            wp_send_json_error( array( 'message' => 'Office 365 One-Click is not available.' ), 500 );
        }

        $auth_url = post_smtp_get_office365_auth_url();

        if ( empty( $auth_url ) ) {
            wp_send_json_error( array( 'message' => 'Failed to generate Office 365 auth URL.' ), 500 );
        }

        wp_send_json_success( array( 'auth_url' => esc_url_raw( $auth_url ) ) );
    }

     /**
     * AJAX callback to generate a fresh Gmail One-Click OAuth URL.
     *
     * This endpoint is called when the user clicks the "Sign in with Google" button
     * for the Gmail One-Click setup. It validates the request nonce and current user
     * capability, then uses the shared helper `post_smtp_get_gmail_auth_url()` to
     * create an auth URL that contains a fresh `gmail_oauth_redirect` nonce.
     *
     * The URL is returned as JSON and the browser is redirected client-side.
     *
     * @since 3.1.0
     */
    public function ajax_get_gmail_auth_url() {

        // Capability check: Only allow administrators.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
        }

        // Nonce check for CSRF protection.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ps_get_office365_auth_url' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid or missing nonce.' ), 400 );
        }

        if ( ! function_exists( 'post_smtp_get_office365_auth_url' ) ) {
            wp_send_json_error( array( 'message' => 'Office 365 One-Click is not available.' ), 500 );
        }

        $auth_url = post_smtp_get_office365_auth_url();

        if ( empty( $auth_url ) ) {
            wp_send_json_error( array( 'message' => 'Failed to generate Office 365 auth URL.' ), 500 );
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ps_get_gmail_auth_url' ) ) {
                wp_send_json_error( array( 'message' => 'Invalid or missing nonce.' ), 400 );
            }

            if ( ! function_exists( 'post_smtp_get_gmail_auth_url' ) ) {
                wp_send_json_error( array( 'message' => 'Gmail One-Click is not available.' ), 500 );
            }

            $auth_url = post_smtp_get_gmail_auth_url();

            if ( empty( $auth_url ) ) {
                wp_send_json_error( array( 'message' => 'Failed to generate Gmail auth URL.' ), 500 );
            }

            wp_send_json_success( array( 'auth_url' => esc_url_raw( $auth_url ) ) );
        }
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

        // Capability check: Only allow admins
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
            return;
        }

        // Nonce check for CSRF protection
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update_post_smtp_pro_option' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid or missing nonce.' ) );
            return;
        }

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

        // Fallback response
        wp_send_json_error();
    }

    /**
     * Handle saving for version 1.0.1
     *
     * @since 2.7.0
     * @version
     */
    private function handle_legacy_save( $form_data ) {

        if ( ! isset( $form_data['postman_options'] ) || empty( $form_data['postman_options'] ) ) {
            return false;
        }

        $sanitized = post_smtp_sanitize_array( $form_data['postman_options'] );
        $options = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );
        $original_options = $options;

        // Normalize checkboxes
        $sanitized['prevent_sender_email_override'] = isset( $sanitized['prevent_sender_email_override'] ) ? 1 : '';
        $sanitized['prevent_sender_name_override'] = isset( $sanitized['prevent_sender_name_override'] ) ? 1 : '';
        $sanitized['envelope_sender'] = isset( $sanitized['sender_email'] ) ? $sanitized['sender_email'] : '';
        $sanitized['slack_token'] = base64_decode( $options['slack_token'] ?? '' ) ?: '';
        $sanitized['pushover_user'] = base64_decode( $options['pushover_user'] ?? '' ) ?: '';
        $sanitized['pushover_token'] = base64_decode( $options['pushover_token'] ?? '' ) ?: '';

        // Map of keys to preserve
        $keys = array(
            'office365_app_id', 'office365_app_password', PostmanOptions::SENDINBLUE_API_KEY,
            'sparkpost_api_key', 'postmark_api_key', 'mailgun_api_key', 'mailersend_api_key', 'emailit_api_key',
            'resend_api_key', PostmanOptions::SENDGRID_API_KEY, 'mandrill_api_key', 'elasticemail_api_key',
            PostmanOptions::MAILJET_API_KEY, PostmanOptions::MAILJET_SECRET_KEY,
            'basic_auth_password', 'ses_access_key_id', 'ses_secret_access_key', 'ses_region'
        );

        foreach ( $keys as $key ) {
            $sanitized[ $key ] = isset( $sanitized[ $key ] ) ? $sanitized[ $key ] : '';
        }

        $sanitized['enc_type'] = 'tls';
        $sanitized['auth_type'] = 'login';

        $options = array_merge( $options, $sanitized );

        return $options === $original_options ? true : update_option( PostmanOptions::POSTMAN_OPTIONS, $options );
    }

    /**
     * Handle saving for newer DB versions
     *
     * @since 2.7.0
     * @version
     */
    private function handle_new_version_save( $form_data ) {

        $sanitized = post_smtp_sanitize_array( $form_data['postman_options'] );
        $transport_type = isset( $sanitized['transport_type'] ) ? $sanitized['transport_type'] : '';
        $api_keys = $this->get_transport_type_keys( $transport_type );
        $postman_options = get_option( 'postman_options', array() );
        
        $new_connection = array(
            'provider' => $transport_type,
            'sender_email' => isset( $sanitized['sender_email'] ) ? $sanitized['sender_email'] : '',
            'sender_name' => isset( $sanitized['sender_name'] ) ? $sanitized['sender_name'] : '',
            'prevent_sender_email_override' => isset( $sanitized['prevent_sender_email_override'] ) ? 1 : '',
            'prevent_sender_name_override' => isset( $sanitized['prevent_sender_name_override'] ) ? 1 : '',
        );

        foreach ( $api_keys as $key ) {
            if ( isset( $sanitized[ $key ] ) ) {
                $new_connection[ $key ] = sanitize_text_field( $sanitized[ $key ] );
            }
        }

        $this->handle_special_providers( $transport_type, $form_data, $sanitized, $new_connection );
       // $this->update_sender_meta( $sanitized, $transport_type );

        $mail_connections = get_option( 'postman_connections', array() );

        if ( isset( $form_data['postman_fallback_edit'] ) ) {
            $id = $form_data['postman_fallback_edit'];
            $mail_connections[ $id ] = array_merge( $mail_connections[ $id ], $new_connection );
        } else {

          	if ( isset( $form_data['access_token'] ) && ! empty( $form_data['access_token'] ) ) {
              // ✅ Update token values for the last connection (assumes wizard OAuth success redirect)
				$id = array_key_last( $mail_connections );
				$mail_connections[ $id ] = array_merge( $mail_connections[ $id ], $new_connection );
				update_option( 'postman_connections', $mail_connections );
				
				return array(
					'index'  => $id,
					'status' => 'updated_token_only',
				);
				
            }else{					
	            $mail_connections[] = $new_connection;
    	        $id = array_key_last( $mail_connections );
			}
        }
        
        $saved = update_option( 'postman_connections', $mail_connections );

          $postman_options = array_merge( 
			$postman_options, array(
				'sender_email'   => $new_connection['sender_email'],
				'sender_name'    => $new_connection['sender_name'],
				'slack_token'    => base64_decode( $postman_options['slack_token'] ?? '' ) ?: '',
				'pushover_user'  => base64_decode( $postman_options['pushover_user'] ?? '' ) ?: '',
				'pushover_token' => base64_decode( $postman_options['pushover_token'] ?? '' ) ?: '',
        	)
		);

        update_option( 'postman_options', $postman_options );
            
        return array(
            'index'  => $id,
            'status' => $saved ? 'updated' : 'not_updated',
        );
    }

    /**
     * Handle provider-specific logic
     *
     * @since 2.7.0
     * @version 2.0
     */
    private function handle_special_providers( $type, $form_data, $sanitized, &$connection ) {

        // Common token keys
        $token_keys = array( 'access_token', 'refresh_token', 'token_expires' );

        switch ( $type ) {
            case 'zohomail_api':
                $connection = array_merge( $connection, array(
                    'zohomail_client_id'     => $sanitized['zohomail_client_id'] ?? '',
                    'zohomail_client_secret' => $sanitized['zohomail_client_secret'] ?? '',
                    'timestamp'              => time() + 3600,
                ) );
                break;

            case 'office365_api':
                $connection = array_merge( $connection, array(
                    'office365_app_id'       => $sanitized['office365_app_id'] ?? '',
                    'office365_app_password' => $sanitized['office365_app_password'] ?? '',
                    'timestamp'              => time() + 3600,
                ) );
                break;

            case 'gmail_api':
                $connection = array_merge( $connection, array(
                    'oauth_client_id'     => $sanitized['oauth_client_id'] ?? '',
                    'oauth_client_secret' => $sanitized['oauth_client_secret'] ?? '',
                    'auth_token_expires'  => $form_data['token_expires'] ?? '',
                    'timestamp'           => time() + 3600,
                ) );
                break;
        }

        // Apply token fields (if exist) for all 3 types.
        if ( in_array( $type, array( 'zohomail_api', 'office365_api', 'gmail_api' ), true ) ) {
            foreach ( $token_keys as $field ) {
                if ( isset( $form_data[ $field ] ) ) {
                    $connection[ $field ] = sanitize_text_field( $form_data[ $field ] );
                }
            }
        }
    }


    /**
     * Update sender email/name in PostmanOptions
     *
     * @since 2.7.0
     * @version
     */
    private function update_sender_meta( $sanitized, $transport_type ) {

        $options = get_option( PostmanOptions::POSTMAN_OPTIONS, array() );

        $options['sender_email'] = isset( $sanitized['sender_email'] ) ? sanitize_text_field( $sanitized['sender_email'] ) : '';
        $options['sender_name']  = isset( $sanitized['sender_name'] ) ? sanitize_text_field( $sanitized['sender_name'] ) : '';

        if ( $transport_type === 'office365_api' ) {
            $options['office365_app_id'] = isset( $sanitized['office365_app_id'] ) ? sanitize_text_field( $sanitized['office365_app_id'] ) : '';
            $options['office365_app_password'] = isset( $sanitized['office365_app_password'] ) ? sanitize_text_field( $sanitized['office365_app_password'] ) : '';
        }

        update_option( PostmanOptions::POSTMAN_OPTIONS, $options );
    }

    /**
     * Update Post SMTP Pro Option for Office365 One-Click
     * 
     * @since 2.7.0
     * @version 1.0.0
     *
     * @return void
     */
    public function update_post_smtp_pro_option_office365_callback() {

        // Capability check: Only allow admins
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
            return;
        }

        // Nonce check for CSRF protection
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update_post_smtp_pro_option' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid or missing nonce.' ) );
            return;
        }

        if ( ! isset( $_POST['enabled'] ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ) );
            return;
        }

        $options = get_option( 'post_smtp_pro', [] );
        if ( ! isset( $options['extensions'] ) ) {
            $options['extensions'] = [];
        }

        $enabled_value = sanitize_text_field( $_POST['enabled'] );

        // Check version requirement for Office 365 one-click
        if ( ! empty( $enabled_value ) ) {
            $required_pro_version = '1.5.0';
            if ( defined( 'POST_SMTP_PRO_VERSION' ) ) {
                $current_pro_version = POST_SMTP_PRO_VERSION;
                if ( version_compare( $current_pro_version, $required_pro_version, '<' ) ) {
                    wp_send_json_error( array( 
                        'message' => sprintf( 
                            __( 'Post SMTP Pro version %1$s or higher is required for Office 365 One-Click Setup. Current version: %2$s', 'post-smtp' ), 
                            $required_pro_version, 
                            $current_pro_version 
                        )
                    ) );
                    return;
                }
            } else {
                wp_send_json_error( array( 'message' => 'Post SMTP Pro version could not be determined.' ) );
                return;
            }
        }

        // Remove existing Office 365 related extensions
        $options['extensions'] = array_diff( $options['extensions'], ['microsoft-365', 'microsoft-one-click'] );

        if ( ! empty( $enabled_value ) ) {
            // If one-click is enabled, add both microsoft-365 and microsoft-one-click
            $options['extensions'][] = 'microsoft-365';
            $options['extensions'][] = 'microsoft-one-click';
        } else {
            // If one-click is disabled, only add microsoft-365
            $options['extensions'][] = 'microsoft-365';
        }

        // Remove duplicates
        $options['extensions'] = array_unique( $options['extensions'] );

        update_option( 'post_smtp_pro', $options );

        // Check for Office 365 OAuth token and user email
        $office365_oauth = get_option( 'postman_office365_oauth' );
        $has_access_token = false;
        $has_email = false;
        $user_email = '';

        if ( $office365_oauth && is_array( $office365_oauth ) ) {
            if ( isset( $office365_oauth['access_token'] ) && ! empty( $office365_oauth['access_token'] ) ) {
                $has_access_token = true;
            }

            if ( isset( $office365_oauth['user_email'] ) && ! empty( $office365_oauth['user_email'] ) ) {
                $has_email = true;
                $user_email = sanitize_email( $office365_oauth['user_email'] );
            }
        }

        wp_send_json_success( array( 
            'message' => 'Option updated successfully!',
            'has_access_token' => $has_access_token,
            'has_email' => $has_email,
            'user_email' => $user_email,
        ) );
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
     * Handles the removal of Office 365 OAuth credentials from the WordPress database.
     *
     * This function removes only the sensitive fields (tokens, email, and expiration)
     * from the stored Office 365 OAuth option instead of deleting the entire record.
     */
    public function post_smtp_remove_365_oauth_action() {
        // Verify nonce for security
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'remove_365_oauth_action' ) ) {
            wp_die( esc_html__( 'Nonce verification failed. Please try again.', 'post-smtp' ) );
        }

        // Get the saved Office 365 OAuth data
        $oauth_data = get_option( 'postman_office365_oauth', [] );

        if ( ! empty( $oauth_data ) && is_array( $oauth_data ) ) {
            // Unset only sensitive fields
            unset(
                $oauth_data['access_token'],
                $oauth_data['refresh_token'],
                $oauth_data['token_expires'],
                $oauth_data['user_email']
            );

            // Update the option with sanitized data
            update_option( 'postman_office365_oauth', $oauth_data );
        }

        // Redirect back to configuration wizard page
        wp_redirect( admin_url( "admin.php?socket=office365_api&step=2&page=postman/configuration_wizard" ) );
        exit;
    }

	/**
	 * Handles the Office 365 OAuth redirect, retrieves the token parameters from the URL,
	 * saves them in WordPress options, and redirects the user to a settings page.
	 *
	 * This function is used when OAuth authorization is completed and the user is
	 * redirected back with the access token, refresh token, expiration time, message, 
	 * and user email. It sanitizes the URL parameters and saves them to the WordPress 
	 * options table to be used later in the application.
	 *
	 * After processing, the user is redirected to a settings page for confirmation.
	 */
	public function handle_office365_oauth_redirect() {
		// Check if the required OAuth parameters are present in the URL.
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'office365_oauth_redirect' ) {
			                     
            // Capability check: Only allow administrators to update OAuth tokens
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'post-smtp' ) );
            }
            
            // CSRF protection: Verify nonce (required by security report)
            if ( ! isset( $_GET['_wpnonce'] ) || empty( $_GET['_wpnonce'] ) ) {
                wp_die( esc_html__( 'Security check failed. Nonce is missing.', 'post-smtp' ) );
            }
            
            // Verify the nonce
            $nonce = sanitize_text_field( $_GET['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'office365_oauth_redirect' ) ) {
                wp_die( esc_html__( 'Security check failed. Invalid nonce. Please try again.', 'post-smtp' ) );
            }

            // Sanitize and retrieve URL parameters
			$access_token  = sanitize_text_field( $_GET['access_token'] );
			$refresh_token = isset( $_GET['refresh_token'] ) ? sanitize_text_field( $_GET['refresh_token'] ) : null;
			$expires_in    = isset( $_GET['expires_in'] ) ? intval( $_GET['expires_in'] ) : 0;
			$msg           = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';
			$user_email    = isset( $_GET['user_email'] ) ? sanitize_email( $_GET['user_email'] ) : '';
			$auth_token_expires = time() + $expires_in;
            $redirect_uri = admin_url();
			// Prepare the OAuth data array for storing in WordPress options
			$oauth_data = array(
				'access_token'      => $access_token,
				'refresh_token'     => $refresh_token,
				'token_expires'        => $auth_token_expires,
				'user_email'        => $user_email,
                'OAUTH_REDIRECT_URI'       => $redirect_uri,
                'OAUTH_SCOPES'             => 'openid profile offline_access Mail.Send Mail.Send.Shared',
                'OAUTH_AUTHORITY'          => 'https://login.microsoftonline.com/common',
                'OAUTH_AUTHORIZE_ENDPOINT' => '/oauth2/v2.0/authorize',
                'OAUTH_TOKEN_ENDPOINT'     => '/oauth2/v2.0/token',
			);

			// Save the OAuth parameters to the WordPress options table.
			update_option( 'postman_office365_oauth', $oauth_data );
		}
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

        $redirect_url = admin_url( "admin.php?page=postman/configuration_wizard" );

        if ( $this->existing_db_version == POST_SMTP_DB_VERSION ) {
            $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            if ( $id ) {
                $mail_connections = get_option( 'postman_connections', array() );

                if ( isset( $mail_connections[ $id ] ) && isset( $mail_connections[ $id ]['provider'] ) && $mail_connections[ $id ]['provider'] === 'gmail_api' ) {
                  // Reset sensitive tokens to empty values
                    $mail_connections[ $id ]['access_token']        = '';
                    $mail_connections[ $id ]['refresh_token']       = '';
                    $mail_connections[ $id ]['token_expires']       = '';
                    $mail_connections[ $id ]['auth_token_expires']  = '';
					$mail_connections[ $id ]['sender_email']  = '';
					$mail_connections[ $id ]['sender_name']  = '';
                 
                    update_option( 'postman_connections', $mail_connections );

                    // Redirect back to the same Gmail mailer ID wizard
                    $redirect_url = admin_url( "admin.php?socket=gmail_api&id={$id}&step=2&page=postman/configuration_wizard" );
                }
            }
        } else {
            delete_option( 'postman_auth_token' );
            $redirect_url = admin_url( "admin.php?socket=gmail_api&step=2&page=postman/configuration_wizard" );
        }

        // Redirect the user back with success
        wp_redirect( $redirect_url );
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
            
        // Ignore unrelated callbacks that accidentally reuse the same action parameter.
        if ( ! isset( $_GET['access_token'] ) && ! isset( $_GET['error'] ) ) {
            return;
        }
            
        // Capability check: Only allow administrators to update OAuth tokens
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'post-smtp' ) );
        }
        
        // CSRF protection: Verify nonce (required by security report)
        if ( ! isset( $_GET['_wpnonce'] ) || empty( $_GET['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Security check failed. Nonce is missing.', 'post-smtp' ) );
        }
        
        // Verify the nonce
        $nonce = sanitize_text_field( $_GET['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'gmail_oauth_redirect' ) ) {
            wp_die( esc_html__( 'Security check failed. Invalid nonce. Please try again.', 'post-smtp' ) );
        }
            
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

     /* Get transport type keys based on the provided transport type.
     *
     * This function returns an array of keys required for a specific
     * transport type. Each transport type may require different API keys
     * or configuration values.
     *
     * @param string $transport_type The transport type (e.g., 'smtp', 'mandrill').
     * @return array The array of keys specific to the transport type.
     * 
     * @since 3.0.1
     * @version 1.0.0
     */
    public function get_transport_type_keys( $transport_type ) {
        // Define API key mappings for each transport type.
        $api_keys_definitions = array(
            'smtp'           => array(
                'enc_type', 
                'hostname', 
                'port', 
                'sender_email', 
                'envelope_sender', 
                'basic_auth_username', 
                'basic_auth_password',
            ),
            'mandrill_api'       => array( 'mandrill_api_key' ),
            'sendgrid_api'   => array( 'sendgrid_api_key' ),
            'sendinblue_api' => array( 'sendinblue_api_key' ),
            'mailjet_api'    => array( 
                'mailjet_api_key', 
                'mailjet_secret_key', 
            ),
            'sendpulse_api'  => array( 
                'sendpulse_api_key', 
                'sendpulse_secret_key', 
            ),
            'postmark_api'   => array( 'postmark_api_key' ),
            'sparkpost_api'  => array( 'sparkpost_api_key' ),
            'mailgun_api'    => array( 
                'mailgun_api_key', 
                'mailgun_domain_name', 
            ),
            'elasticemail_api' => array( 'elasticemail_api_key' ),
            'mailtrap_api'   => array( 'mailtrap_api_key' ),
            'maileroo_api'   => array( 'maileroo_api_key' ),
            'sweego_api'     => array( 'sweego_api_key' ),
            'smtp2go_api'    => array( 'smtp2go_api_key' ),
            'aws_ses_api'  => array( 'ses_access_key_id', 'ses_secret_access_key', 'ses_region' ),
            'mailersend_api' => array( 'mailersend_api_key' ),
            'emailit_api' => array( 'emailit_api_key' ),
            'resend_api' => array( 'resend_api_key' ),
        );

        /**
         * Filter the API keys definitions array to allow modification.
         *
         * @param array $api_keys_definitions An associative array of transport types and their keys.
         */
        $api_keys_definitions = apply_filters( 'post_smtp_transport_type_keys', $api_keys_definitions );

        // Return the keys for the specific transport type, or an empty array if not found.
        return isset( $api_keys_definitions[ $transport_type ] ) ? $api_keys_definitions[ $transport_type ] : array();
    }
	
	/**
	 * Handles the deletion of a specific SMTP connection via AJAX.
	 *
	 * - Verifies the nonce for security.
	 * - Sanitizes and validates the incoming connection ID.
	 * - Updates the 'postman_connections' option in the database.
	 * - Optionally, can clear the primary/fallback setting if the deleted connection was selected.
	 *
	 * @return void Sends a JSON response back to the AJAX request.
	 */
	public function postman_handle_delete_connection() {
		// Direct inline nonce verification.
		check_ajax_referer( 'postman_delete_connection_nonce' );

		$connection_id = sanitize_text_field( $_POST['connection_id'] ?? '' );

		if ( $connection_id == '' ) {
			wp_send_json_error( 'Invalid connection ID.' );
		}

		$connections = get_option( 'postman_connections', array() );

		if ( isset( $connections[ $connection_id ] ) ) {
			unset( $connections[ $connection_id ] );
			update_option( 'postman_connections', $connections );
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Connection not found.' );
		}
	}

    /**
     * Handles the deletion of specific OAuth-related transients via AJAX.
     *
     * - Verifies the nonce for security.
     * - Deletes the 'client_id' and 'client_secret' transients from the database.
     * - Sends a JSON response indicating success or failure.
     *
     * @return void Sends a JSON response back to the AJAX request.
     */
    public function ps_expire_client_transients() {

        // Delete the transients
        $deleted_client_id     = delete_transient( 'client_id' );
        $deleted_client_secret = delete_transient( 'client_secret' );

        if ( $deleted_client_id || $deleted_client_secret ) {
            wp_send_json_success( array(
                'message' => 'Client ID and Client Secret transients cleared successfully.'
            ) );
        } else {
            wp_send_json_error( array(
                'message' => 'No transients found to delete.'
            ) );
        }
    }


}

new Post_SMTP_New_Wizard();

endif;