<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanElasticEmailMailEngine' ) ):
    
require 'Services/ElasticEmail/Handler.php'; 

class PostmanElasticEmailMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;


    /**
     * @since 2.6.0
     * @version 1.0
     */
    public function __construct( $api_key ) {
        
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

    /**
     * @since 2.6.0
     * @version 1.0
     */
    public function getTranscript() {

        return $this->transcript;

    }

    private function addAttachmentsToMail( PostmanMessage $message ) {

        $attachments = $message->getAttachments();
        if ( ! is_array( $attachments ) ) {
            // WordPress may a single filename or a newline-delimited string list of multiple filenames
            $attArray = explode( PHP_EOL, $attachments );
        } else {
            $attArray = $attachments;
        }
        // otherwise WordPress sends an array
        $attachments = array();
        foreach ( $attArray as $file ) {
            if ( ! empty( $file ) ) {
                $this->logger->debug( 'Adding attachment: ' . $file );

                $file_name = basename( $file );
                $file_parts = explode( '.', $file_name );
                $file_type = wp_check_filetype( $file );
                $attachments[] = array(
                    'BinaryContent' =>  base64_encode( file_get_contents( $file ) ),
                    'Name'          =>  $file_name,
                    'ContentType'   =>  $file_type['type']
                );
            }
        }
        
        return $attachments;

    }

    /**
     * @since 2.6.0
     * @version 1.0
     */
    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();
        $email_content = array();
        
        //ElasticEmail preparation
        if ( $this->logger->isDebug() ) {

            $this->logger->debug( 'Creating SendGrid service with apiKey=' . $this->api_key );

        }

        $elasticemail = new PostmanElasticEmail( $this->api_key );
        
        //$email_content['Recipients'] = 

        //Adding to receipients
        $to = array();
        foreach ( (array)$message->getToRecipients() as $key => $recipient ) {

            $to[] = !empty( $recipient->getName() ) ? $recipient->getName() . ' <' . $recipient->getEmail() . '>' : $recipient->getEmail();

        }

        //Adding cc receipients
        $cc = array();
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

            $cc[] = !empty( $recipient->getName() ) ? $recipient->getName() . ' <' . $recipient->getEmail() . '>' : $recipient->getEmail();

        }

        //Adding bcc receipients
        $bcc = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            $bcc[] = !empty( $recipient->getName() ) ? $recipient->getName() . ' <' . $recipient->getEmail() . '>' : $recipient->getEmail();

        }

        $email_content['Recipients'] = array(
            'To'    =>  $to,
            'CC'    =>  $cc,
            'BCC'   =>  $bcc
        );

        //Adding PlainText Body
        if( !empty( $message->getBodyTextPart() ) ){

            $body = $message->getBodyTextPart();
            $this->logger->debug( 'Adding body as text' );
            $email_content['Content'] = array(
            'Body'  => array(
                0        => array(
                'ContentType'   =>  'PlainText',
                'Content'       =>  $body
                )
            )
        );
        }

        //Adding HTML Body
        if( !empty( $message->getBodyHtmlPart() ) ){

            $body = $message->getBodyHtmlPart();
            $this->logger->debug( 'Adding body as html' );
            $email_content['Content'] = array(
            'Body'  => array(
                0        => array(
                'ContentType'   =>  'HTML',
                'Content'       =>  $body
                )
            )
        );
        }

        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

        $replyTo = $message->getReplyTo();
        $replyTo_email = !empty( $replyTo ) ? $replyTo->getEmail() : '';
        

        $email_content['Content']['EnvelopeFrom'] = !empty( $senderName ) ? $senderName . ' <' . $senderEmail . '>' : $senderEmail;
        $email_content['Content']['From'] = !empty( $senderName ) ? $senderName . ' <' . $senderEmail . '>' : $senderEmail;

        if( !empty( $replyTo_email ) ) {

            $email_content['Content']['ReplyTo'] = !empty( $replyTo->getName() ) ? $replyTo->getName() . ' <' . $replyTo->getEmail() . '>' : $replyTo->getEmail();

        }

        $email_content['Content']['Subject'] = $message->getSubject();

        $attachments = $this->addAttachmentsToMail( $message );

        if( !empty( $attachments ) ) {

            $email_content['Content']['Attachments'] = $attachments;

        }

        try {

            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $response = $elasticemail->send( $email_content );
            
            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $email_content, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            
        } catch (Exception $e) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $email_content, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $e;
    
        }

    }

}
endif;