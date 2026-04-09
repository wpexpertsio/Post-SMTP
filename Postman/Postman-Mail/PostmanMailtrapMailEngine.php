<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanMailtrapMailEngine' ) ):
    
require 'Services/Mailtrap/Handler.php'; 

class PostmanMailtrapMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;


    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function __construct( $api_key ) {
        
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

    /**
     * @since 2.9.0
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
                $file_type = wp_check_filetype( $file );
                $attachments[] = array(
                    'content' => base64_encode( file_get_contents( $file ) ),
                    'type' => $file_type['type'],
                    'filename' => $file_name,
                    'disposition' => 'attachment',
                );
            }
        }

        return $attachments;

    }

    /**
     * @since 2.9.0
     * @version 1.0
     */
    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();
        
        if ( empty( $this->api_key ) ) {
            throw new Exception( 'Mailtrap API Key is not configured' );
        }
        
        if ( $this->logger->isDebug() ) {
            $this->logger->debug( 'Creating Mailtrap service with apiKey=' . substr( $this->api_key, 0, 10 ) . '...' );
        }

        $mailtrap = new PostmanMailtrap( $this->api_key);
        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $headers = array();
        
        $sender->log( $this->logger, 'From' );

        // Prepare email payload
        $emailPayload = array();
        
        // Set from address
        $emailPayload['from'] = array(
            'email' =>  $senderEmail
        );
        
        if( !empty( $senderName ) ) {
            $emailPayload['from']['name'] = $senderName;
        }
        
        $tos = array();
        $duplicates = array();

        // add the to recipients
        foreach ( (array)$message->getToRecipients() as $key => $recipient ) {
                    
            if ( !in_array( $recipient->getEmail(), $duplicates ) ) {

                $to = array(
                    'email' =>  $recipient->getEmail()
                );

                if( !empty( $recipient->getName() ) ) {
                    $to['name'] = $recipient->getName();
                }
                
                $tos[] = $to;
                $duplicates[] = $recipient->getEmail();

            }

        }
        $emailPayload['to'] = $tos;
        
        $emailPayload['subject'] = $message->getSubject();
  
        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $emailPayload['text'] = $textPart;
        }
        
        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $emailPayload['html'] = $htmlPart;
        }
        
        // add the reply-to
        $replyTo = $message->getReplyTo();
        // $replyTo is null or a PostmanEmailAddress object
        if ( isset( $replyTo ) ) {
            $emailPayload['reply_to'] = array(
                'email' => $replyTo->getEmail()
            );
            
            if( !empty( $replyTo->getName() ) ) {
                 $emailPayload['reply_to']['name'] = $replyTo->getName();
            }
        }

        // add the Postman signature - append it to whatever the user may have set
        if ( ! $options->isStealthModeEnabled() ) {
            $pluginData = apply_filters( 'postman_get_plugin_metadata', null );
            $headers['X-Mailer'] = sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData ['version'], 'https://wordpress.org/plugins/post-smtp/' );
        }

        foreach ( ( array ) $message->getHeaders() as $header ) {
            $this->logger->debug( sprintf( 'Adding user header %s=%s', $header ['name'], $header ['content'] ) );
            $headers[$header['name']] = $header ['content'];
        }

        // add the messageId
        $messageId = $message->getMessageId();
        if ( ! empty( $messageId ) ) {
            $headers['message-id'] = $messageId;
        }

        if( !empty( $headers ) ) {
            $emailPayload['headers'] = $headers;
        }

        // if the caller set a Content-Type header, use it
        $contentType = $message->getContentType();
        if ( ! empty( $contentType ) ) {
            $this->logger->debug( 'Content-Type header is reserved and will be set automatically by Mailtrap API.' );
        }

        $cc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Cc');
                $ccItem = array(
                    'email' =>  $recipient->getEmail()
                );
                
                if( !empty( $recipient->getName() ) ) {
                    $ccItem['name'] = $recipient->getName();
                }
                
                $cc[] = $ccItem;
                $duplicates[] = $recipient->getEmail();

            }

        }
        if( !empty( $cc ) )
            $emailPayload['cc'] = $cc;

        $bcc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Bcc');
                $bccItem = array(
                    'email'  =>  $recipient->getEmail()
                );
                
                if( !empty( $recipient->getName() ) ) {
                    $bccItem['name'] = $recipient->getName();
                }

                $bcc[] = $bccItem;
                $duplicates[] = $recipient->getEmail();

            }

        }
        
        if( !empty( $bcc ) )
            $emailPayload['bcc'] = $bcc;

        // add attachments
        $this->logger->debug( 'Adding attachments' );

        $attachments = $this->addAttachmentsToMail( $message );

        if( !empty( $attachments ) ) {
            $emailPayload['attachments'] = $attachments;
        }
        
        try {

            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail via Mailtrap API' );
                $this->logger->debug( 'Payload: ' . print_r( $emailPayload, true ) );
            }

            $response = $mailtrap->send( $emailPayload );
            
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Mailtrap API Response: ' . print_r( $response, true ) );
            }
            
            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $emailPayload, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            
        } catch (Exception $e) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $emailPayload, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            $this->logger->error( 'Mailtrap send error: ' . $e->getMessage() );

            throw $e;
    
        }

    }

}
endif;
