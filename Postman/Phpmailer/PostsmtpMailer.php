<?php
require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';

add_action('plugins_loaded', function() {
    global $phpmailer;

    $phpmailer = new PostsmtpMailer(true);
});

class PostsmtpMailer extends PHPMailer {

    private $options;

    private $error;

    public function __construct($exceptions = null)
    {
        parent::__construct($exceptions);

        $this->options = PostmanOptions::getInstance();
        add_filter( 'postman_wp_mail_result', [ $this, 'postman_wp_mail_result' ] );
    }

    public function send()
    {
        require_once dirname(__DIR__) . '/PostmanWpMail.php';

        // create a PostmanWpMail instance
        $postmanWpMail = new PostmanWpMail();
        $postmanWpMail->init();

        $senderEmail = $this->options->getMessageSenderEmail();
        $senderName = $this->options->getMessageSenderName();

        $this->addCustomHeader('X-Mailer', 'PostSMTP/' . POST_SMTP_VER );

        // create a PostmanMessage instance
        $message = $postmanWpMail->createNewMessage();

        $message->setFrom( $senderEmail, $senderName );
        $message->addHeaders( $this->getCustomHeaders() );
        $message->setBodyTextPart( $this->AltBody );
        $message->setBodyHtmlPart( $this->Body );
        $message->setBody( $this->Body );
        $message->setSubject( $this->Subject );
        $message->addTo( $this->flatArray($this->getToAddresses() ) );
        $message->setReplyTo( $this->flatArray( $this->getReplyToAddresses() ) );
        $message->addCc( $this->flatArray($this->getCcAddresses() ) );
        $message->addBCc( $this->flatArray( $this->getBccAddresses() ) );
        $message->setReplyTo( $this->flatArray( $this->getReplyToAddresses() ) );
        $message->setAttachments( $this->getAttachments() );

        // create a PostmanEmailLog instance
        $log = new PostmanEmailLog();

        $log->originalTo = $this->flatArray($this->getToAddresses() );
        $log->originalSubject = $this->Subject;
        $log->originalMessage = $this->Body;
        $log->originalHeaders = $this->getCustomHeaders();

        try {
            return $postmanWpMail->sendMessage( $message, $log );
        } catch (phpmailerException $exc) {

            $this->error = $exc;

            $this->mailHeader = '';
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }

    }

    public function postman_wp_mail_result() {
        $result = [
            'exception' => $this->error,
            'transcript' => '',
        ];
        return $result;
    }

    private function flatArray($arr) {
        $result = [];
        foreach ( $arr as $key => $value ) {
            if ( is_array( $value ) ) {
                foreach ($value as $k => $v ) {
                    if ( empty( $v ) ) {
                        continue;
                    }
                    $value = $v;
                }
            }

            $result[] = $value;
        }

        return implode(',', $result );
    }
}