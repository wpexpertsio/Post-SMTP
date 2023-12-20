<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanSendinblueMailEngine' ) ):
    
require 'Services/Sendinblue/Handler.php'; 

class PostmanSendinblueMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;


    /**
     * @since 2.1
     * @version 1.0
     */
    public function __construct( $api_key ) {
        
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

    /**
     * @since 2.1
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
                    'content' => base64_encode( file_get_contents( $file ) ),
                    'type' => $file_type['type'],
                    'file_name' => $file_name,
                    'disposition' => 'attachment',
                    'id' => $file_parts[0],
                );
            }
        }

        return $attachments;

    }

    /**
     * @since 2.1
     * @version 1.0
     */
    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();
        //Sendinblue preparation
        if ( $this->logger->isDebug() ) {

            $this->logger->debug( 'Creating SendGrid service with apiKey=' . $this->apiKey );

        }

        $sendinblue = new PostmanSendinblue( $this->api_key);
        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $headers = array();
        
        $sender->log( $this->logger, 'From' );

        $sendSmtpEmail['sender'] = array(
            'name'  =>  $senderName, 
            'email' =>  $senderEmail
        );
        
        $tos = array();
        $duplicates = array();

        // add the to recipients
        foreach ( (array)$message->getToRecipients() as $key => $recipient ) {
                    
            if ( !array_key_exists( $recipient->getEmail(), $duplicates ) ) {

                $tos[] = array(
                    'email' =>  $recipient->getEmail()
                );

                if( !empty( $recipient->getName() ) ) {

                    $tos[$key]['name'] = $recipient->getName();

                }
                
                $duplicates[] = $recipient->getEmail();

            }

        }
        $sendSmtpEmail['to'] = $tos;
        
        $sendSmtpEmail['subject'] = $message->getSubject();
  
        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $sendSmtpEmail['textContent'] = $textPart;
        }
        
        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $sendSmtpEmail['htmlContent'] = $htmlPart;
        }
        
        // add the reply-to
        $replyTo = $message->getReplyTo();
        // $replyTo is null or a PostmanEmailAddress object
        if ( isset( $replyTo ) ) {
            $sendSmtpEmail['replyTo'] = array(
                'email' => $replyTo->getEmail()
            );
            
            if( !empty( $replyTo->getName() ) ) {
                 $sendSmtpEmail['name'] = $replyTo->getName();
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

        $sendSmtpEmail['headers'] = $headers;

        // if the caller set a Content-Type header, use it
        $contentType = $message->getContentType();
        if ( ! empty( $contentType ) ) {
            $this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
        }

        $cc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Cc');
                $cc[] = array(
                    'email' =>  $recipient->getEmail()
                );
                
                if( !empty( $recipient->getName() ) ) {
                    $cc['name'] = $recipient->getName();
                }
                
                $duplicates[] = $recipient->getEmail();

            }

        }
        if( !empty( $cc ) )
            $sendSmtpEmail['cc'] = $cc;

        $bcc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Bcc');
                $bcc[] = array(
                    'email'  =>  $recipient->getEmail()
                );
                
                if( !empty( $recipient->getName() ) ) {
                    $bcc['name'] = $recipient->getName();
                }

                $duplicates[] = $recipient->getEmail();

            }

        }
        
        if( !empty( $bcc ) )
            $sendSmtpEmail['bcc'] = $bcc;

        // add attachments
        $this->logger->debug( 'Adding attachments' );

        $attachments = $this->addAttachmentsToMail( $message );

        $email_attachments = array();
        
        if( !empty( $attachments ) ) {
        
            foreach ( $attachments as $index => $attachment ) {

                $email_attachments[] = array(
                    'name'      =>  $attachment['file_name'],
                    'content'   =>  $attachment['content']
                );
            }

            $sendSmtpEmail['attachment'] = $email_attachments;
        
        }
        
        
         
         
        try {

            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $response = $sendinblue->send( $sendSmtpEmail );
            
            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $sendSmtpEmail, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            
        } catch (Exception $e) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $sendSmtpEmail, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $e;
    
        }

    }

    /**
     * @since 2.1
     * @version 1.0
     */
    private function errorCodesMap( $error_code ) {
        switch ( $error_code ) {
            case 400:
                $message = sprintf( __( 'ERROR: Request is invalid. Check the error code in JSON. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 401:
                $message = sprintf( __( 'ERROR: You have not been authenticated. Make sure the provided api-key is correct. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 402:
                $message = sprintf( __( 'ERROR: Make sure you\'re account is activated and that you\'ve sufficient credits. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 403:
                $message = sprintf( __( 'ERROR: You do not have the rights to access the resource. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 404:
                $message =  sprintf( __( 'ERROR: Make sure your calling an existing endpoint and that the parameters (object id etc.) in the path are correct. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 405:
                $message =  sprintf( __( 'ERROR: The verb you\'re using is not allowed for this endpoint. Make sure you\'re using the correct method (GET, POST, PUT, DELETE). Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 406:
                $message =  sprintf( __( 'ERROR: The value of contentType for PUT or POST request in request headers is not application/json. Make sure the value is application/json only and not empty. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 429:
                $message =  sprintf( __( 'ERROR: The expected rate limit is exceeded. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            default:
                $message = sprintf( __( 'ERROR: Status code is %1$s', 'post-smtp' ), $error_code );
        }

        return $message;
    }

}
endif;