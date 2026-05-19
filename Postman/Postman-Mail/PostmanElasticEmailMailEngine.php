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
        foreach ( (array) $message->getToRecipients() as $recipient ) {

            $to[] = $recipient->getEmail();

        }

        //Adding cc receipients
        $cc = array();
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

            $cc[] = $recipient->getEmail();

        }

        //Adding bcc receipients
        $bcc = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            $bcc[] = $recipient->getEmail();

        }

        $recipients = array( 'To' => $to );
        if ( ! empty( $cc ) ) {
            $recipients['CC'] = $cc;
        }
        if ( ! empty( $bcc ) ) {
            $recipients['BCC'] = $bcc;
        }
        $email_content['Recipients'] = $recipients;

        $charset = 'UTF-8';
        $body_parts = array();
        $plain_message = '';

        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $plain_message = $textPart;
            $body_parts[] = array(
                'ContentType' => 'PlainText',
                'Content'     => $textPart,
                'Charset'     => $charset,
            );
        }

        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            if ( empty( $plain_message ) ) {
                $plain_message = wp_strip_all_tags( $htmlPart );
            }
            $body_parts[] = array(
                'ContentType' => 'HTML',
                'Content'     => $htmlPart,
                'Charset'     => $charset,
            );
        }

        if ( empty( $body_parts ) ) {
            $fallback_body = $message->getBody();
            if ( ! empty( $fallback_body ) ) {
                $this->logger->debug( 'Adding body from raw message content' );
                $content_type = $message->getContentType();
                $is_html = ! empty( $content_type ) && substr( $content_type, 0, 9 ) === 'text/html';
                $plain_message = $is_html ? wp_strip_all_tags( $fallback_body ) : $fallback_body;
                $body_parts[] = array(
                    'ContentType' => $is_html ? 'HTML' : 'PlainText',
                    'Content'     => $fallback_body,
                    'Charset'     => $charset,
                );
            }
        }

        $sender = $message->getFromAddress();
        $senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

        $replyTo = $message->getReplyTo();
        $replyTo_email = ! empty( $replyTo ) ? $replyTo->getEmail() : '';

        $email_content['Content'] = array(
            'From'    => ! empty( $senderName ) ? $senderName . ' <' . $senderEmail . '>' : $senderEmail,
            'Subject' => $message->getSubject(),
            'Body'    => $body_parts,
        );

        if ( ! empty( $replyTo_email ) ) {
            $email_content['Content']['ReplyTo'] = ! empty( $replyTo->getName() )
                ? $replyTo->getName() . ' <' . $replyTo->getEmail() . '>'
                : $replyTo->getEmail();
        }

        $attachments = $this->addAttachmentsToMail( $message );
        if ( ! empty( $attachments ) ) {
            $email_content['Content']['Attachments'] = $attachments;
        }

        $email_content['message'] = $plain_message;

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