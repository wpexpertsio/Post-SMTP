<?php

class PhpmailerInit {

    /**
     * @var array
     */
    private $mail_error;

    /**
     * @var
     */
    private $transcript;

    /**
     * @var PostmanMessage
     */
    private $message;

    /**
     * @var PostmanOptions
     */
    private $options;

    /**
     * PhpmailerInit constructor.
     */
    public function __construct()
    {
        $this->set_vars();
        $this->hooks();
    }

    public function set_vars() {
        $this->options = PostmanOptions::getInstance();
    }

    public function hooks()
    {
        add_action( 'phpmailer_init', [ $this, 'phpmailer_init'] );
        add_action( 'wp_mail_failed', [ $this, 'wp_mail_failed' ] );
        add_filter( 'postman_wp_mail_result', [ $this, 'postman_wp_mail_result'] );
    }

    /**
     * @param PHPMailer $mailer
     */
    public function phpmailer_init($mailer) {

        if ( $this->options->getTransportType() !== 'smtp' ) {
            return $mailer;
        }

        $mailer->SMTPDebug = 2;
        $mailer->isSMTP();
        $mailer->Host = $this->options->getHostname();
        $mailer->SMTPAuth = $this->options->getAuthenticationType() !== 'none';
        $mailer->AuthType = $this->options->getAuthenticationType() !== 'none' ? $this->options->getAuthenticationType() : '';
        $mailer->Port = $this->options->getPort();
        $mailer->Username = $this->options->getUsername();
        $mailer->Password = $this->options->getPassword();
        $mailer->SMTPSecure = $this->options->getEncryptionType();
        $mailer->Debugoutput = function($str, $level)  {
            $this->transcript = $str;
        };

        $this->build_message($mailer);
    }

    /**
     * @param PHPMailer $mailer
     * @throws Exception
     */
    private function build_message($mailer) {
        require_once dirname(__DIR__) . '/PostmanWpMail.php';

        // create a PostmanWpMail instance
        $postmanWpMail = new PostmanWpMail();
        $postmanWpMail->init();

        $senderEmail = $this->options->getMessageSenderEmail();
        $senderName = $this->options->getMessageSenderName();

        // create a PostmanMessage instance
        $this->message = $postmanWpMail->createNewMessage();

        $this->message->setFrom( $senderEmail, $senderName );
        $this->message->addHeaders( $mailer->getCustomHeaders() );
        $this->message->setBodyTextPart( $mailer->AltBody );
        $this->message->setBodyHtmlPart( $mailer->Body );
        $this->message->setBody( $mailer->AltBody  . $mailer->Body );
        $this->message->setSubject( $mailer->Subject );
        $this->message->addTo( $this->flatArray($mailer->getToAddresses() ) );
        $this->message->addCc( $this->flatArray($mailer->getCcAddresses() ) );
        $this->message->addBCc( $this->flatArray( $mailer->getBccAddresses() ) );
        $this->message->setReplyTo( $this->flatArray( $mailer->getReplyToAddresses() ) );
        $this->message->setAttachments( $mailer->getAttachments() );
    }

    private function flatArray($arr) {
        $result = [];
        foreach ( $arr as $key => $value ) {
            if ( is_array( $value ) ) {
                foreach ($value as $k => $v ) {
                    $value = $v;
                }
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * @param WP_Error $error
     */
    public function wp_mail_failed( $error ) {
        $error_code = 0;
        $error_message = $error->get_error_message();

        $e = new Exception( $error_message, $error_code );
        $this->mail_error = [
            'time' => null,
            'exception' => $e,
            'transcript' => $this->transcript
        ];

        $this->save_log( $error );
        $this->check_fallback( $error->get_error_data() );
    }

    private function check_fallback( $data ) {
        if ( ! $this->options->is_fallback ) {
            $this->options->is_fallback = true;
            extract( $data );

            wp_mail( $to, $subject, $message, $headers, $attachments );
        } else {
            $this->options->is_fallback = false;
        }
    }

    /**
     * @param WP_Error $error
     */
    private function save_log( $error ) {
        require_once dirname(__DIR__) . '/Postman-Email-Log/PostmanEmailLogService.php';

        $data = $error->get_error_data();

        // build the email log entry
        $log = new PostmanEmailLog();
        $log->success = false;
        $log->originalTo = $data['to'];
        $log->originalSubject = $data['subject'];
        $log->originalMessage = $data['message'];
        $log->originalHeaders = $data['headers'];
        $log->statusMessage = $error->get_error_message();
        $log->sessionTranscript = $this->transcript;

        PostmanEmailLogService::getInstance()->writeFailureLog( $log, $this->message, $this->transcript, new PostmanSmtpModuleTransport(POST_BASE), $error->get_error_message() );
    }

    public function postman_wp_mail_result() {
        return $this->mail_error;
    }
}
new PhpmailerInit();