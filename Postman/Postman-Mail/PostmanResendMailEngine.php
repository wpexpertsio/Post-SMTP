<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanResendMailEngine' ) ):
    
require 'Services/Resend/Handler.php'; 

class PostmanResendMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function __construct( $api_key ) {
        
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

    /**
     * @since 3.2.0
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
                    'filename' => $file_name,
                    'content_type' => $file_type['type'],
                );
            }
        }

        return $attachments;

    }

    /**
     * @since 3.2.0
     * @version 1.0
     */
    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();
        
        //Resend preparation
        if ( $this->logger->isDebug() ) {
            $this->logger->debug( 'Creating Resend service with apiKey=' . $this->api_key );
        }

        $resend = new PostmanResend( $this->api_key);
        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $headers = array();
        
        $sender->log( $this->logger, 'From' );

        $sendEmail['from'] = $senderName . ' <' . $senderEmail . '>';
        
        $tos = array();
        $duplicates = array();

        // add the to recipients
        foreach ( (array)$message->getToRecipients() as $key => $recipient ) {
                    
            if ( !array_key_exists( $recipient->getEmail(), $duplicates ) ) {

                if( !empty( $recipient->getName() ) ) {
                    $tos[] = $recipient->getName() . ' <' . $recipient->getEmail() . '>';
                } else {
                    $tos[] = $recipient->getEmail();
                }
                
                $duplicates[] = $recipient->getEmail();

            }

        }
        $sendEmail['to'] = $tos;
        
        $sendEmail['subject'] = $message->getSubject();
  
        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $sendEmail['text'] = $textPart;
        }

        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $sendEmail['html'] = $htmlPart;
        }
        
        // add the reply-to
        $replyTo = $message->getReplyTo();
        // $replyTo is null or a PostmanEmailAddress object
        if ( isset( $replyTo ) ) {
            if( !empty( $replyTo->getName() ) ) {
                $sendEmail['reply_to'] = array( $replyTo->getName() . ' <' . $replyTo->getEmail() . '>' );
            } else {
                $sendEmail['reply_to'] = array( $replyTo->getEmail() );
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
            $sendEmail['headers'] = $headers;
        }

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
                if( !empty( $recipient->getName() ) ) {
                    $cc[] = $recipient->getName() . ' <' . $recipient->getEmail() . '>';
                } else {
                    $cc[] = $recipient->getEmail();
                }
                
                $duplicates[] = $recipient->getEmail();

            }

        }
        if( !empty( $cc ) ) {
            $sendEmail['cc'] = $cc;
        }

        $bcc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Bcc');
                if( !empty( $recipient->getName() ) ) {
                    $bcc[] = $recipient->getName() . ' <' . $recipient->getEmail() . '>';
                } else {
                    $bcc[] = $recipient->getEmail();
                }

                $duplicates[] = $recipient->getEmail();

            }

        }
        
        if( !empty( $bcc ) ) {
            $sendEmail['bcc'] = $bcc;
        }

        // add attachments
        $this->logger->debug( 'Adding attachments' );

        $attachments = $this->addAttachmentsToMail( $message );

        if( !empty( $attachments ) ) {
            $sendEmail['attachments'] = $attachments;
        }
        
        try {

            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $response = $resend->send( $sendEmail );
            
            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $sendEmail, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            
        } catch (Exception $e) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $sendEmail, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $e;
    
        }

    }

    /**
     * @since 3.2.0
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
                $message = sprintf( __( 'ERROR: Forbidden. Make sure you have proper permissions. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 404:
                $message = sprintf( __( 'ERROR: Resource not found. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 422:
                $message = sprintf( __( 'ERROR: Validation Error. Check your input data. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 429:
                $message = sprintf( __( 'ERROR: Too many requests. You have exceeded the rate limit. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            case 500:
                $message = sprintf( __( 'ERROR: Internal server error. Please try again later. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
            default:
                $message = sprintf( __( 'ERROR: An unknown error occurred. Status code is %1$s', 'post-smtp' ), $error_code );
                break;
        }

        return $message;
    }
}
endif;
