<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanMailjetMailEngine' ) ):
    
require 'Services/Mailjet/Handler.php'; 

class PostmanMailjetMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;
    private $secret_key;


    /**
     * @since 2.7
     * @version 1.0
     */
    public function __construct( $api_key, $secret_key ) {
        
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

    /**
     * @since 2.7
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
     * @since 2.7
     * @version 1.0
     */
    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();

        //Mailjet preparation
        if ( $this->logger->isDebug() ) {

            $this->logger->debug( 'Creating Mailjet service with apiKey=' . $this->apiKey );

        }

        $mailjet = new PostmanMailjet( $this->api_key, $this->secret_key );
        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $headers = array();
        
        $sender->log( $this->logger, 'From' );

        //Add FromEmail and Name
        $sendSmtpEmail['FromEmail'] = $senderEmail;
        $sendSmtpEmail['FromName'] = $senderName;

        //Add subject
        $sendSmtpEmail['Subject'] = $message->getSubject();
        
        
        //add the To recipients  
        $tos = array();
        foreach ( (array)$message->getToRecipients() as $key => $recipient ) {

                $tos[] = !empty( $recipient->getName() ) ? $recipient->getName() . ' <' . $recipient->getEmail() . '>' : $recipient->getEmail();
        }

        if( !empty( $tos ) ){

			$sendSmtpEmail['To'] = $tos[0];

            if( sizeof($tos)>1 ){

                for( $i=1 ; $i<sizeof($tos) ; $i++ ){

                    $sendSmtpEmail['To'] = $sendSmtpEmail['To'] . ',' . $tos[$i];
        
                }

            }					
       	} 

        //Add text part if any
        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $sendSmtpEmail['Text-part'] = $textPart;
        }
        
        //Add html part if any
        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $sendSmtpEmail['Html-part'] = $htmlPart;
        }
        
        // add the reply-to
        $replyTo = $message->getReplyTo();

        $To=null;
        if ( isset( $replyTo ) ) {
            $To = !empty( $replyTo->getName() ) ? $replyTo->getName() . ' <' . $replyTo->getEmail() . '>' : $replyTo->getEmail();

            if(!empty($tos)){

                $sendSmtpEmail['To'] = $sendSmtpEmail['To'] . ',' . $To;

            }
            else{

                $sendSmtpEmail['To'] = $To;

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

        $sendSmtpEmail['Headers'] = $headers;

        // if the caller set a Content-Type header, use it
        $contentType = $message->getContentType();
        if ( ! empty( $contentType ) ) {
            $this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
        }

        //Add CC
        $cc = array();
    
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

        	$cc[]  = !empty( $recipient->getName() ) ? $recipient->getName() . '<' . $recipient->getEmail() .'>' :   $recipient->getEmail() ;

            }
		
        if( !empty( $cc ) ){

			$sendSmtpEmail['CC'] = $cc[0];

            if( sizeof($cc)>1 ){

                for( $i=1 ; $i<sizeof($cc) ; $i++ ){

                    $sendSmtpEmail['CC'] = $sendSmtpEmail['CC'] . ',' . $cc[$i];
        
                }

            }					
       	}  
    
    //Add BCC
    $bcc = array();

    foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

        $bcc[]  = !empty( $recipient->getName() ) ? $recipient->getName() . '<' . $recipient->getEmail() .'>' :   $recipient->getEmail() ;

        }

    if( !empty( $bcc ) ){

        $sendSmtpEmail['Bcc'] = $bcc[0];

            if( sizeof($bcc)>1 ){

                for( $i=1 ; $i<sizeof($bcc) ; $i++ ){

                    $sendSmtpEmail['Bcc'] = $sendSmtpEmail['Bcc'] . ',' . $bcc[$i];
            
                }

            }      
        }  

        // add attachments
        $this->logger->debug( 'Adding attachments' );

        $attachments = $this->addAttachmentsToMail( $message );

        $email_attachments = array();
        
        if( !empty( $attachments ) ) {
        
            foreach ( $attachments as $index => $attachment ) {

                $email_attachments[] = array(
                    'Filename'      =>  $attachment['file_name'],
                    'Content-type' => $attachment['type'],
                    'Content'   =>  $attachment['content']
                );
            }

            $sendSmtpEmail['Attachments'] = $email_attachments;
        
        }
         
        try {

            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $response = $mailjet->send( $sendSmtpEmail );
            
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
}
endif;