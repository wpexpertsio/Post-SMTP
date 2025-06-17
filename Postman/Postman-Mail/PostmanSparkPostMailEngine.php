<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanSparkPostMailEngine' ) ):

require_once 'Services/SparkPost/Handler.php'; 

class PostmanSparkPostMailEngine implements PostmanMailEngine {

    protected $logger;

    private $transcript;

    private $api_key;

    /**
     * @since 2.2
     * @version 1.0
     */
    public function __construct( $api_key ) {
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;

        // create the logger
        $this->logger = new PostmanLogger( get_class( $this ) );
        
    }

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
                    'name' => $file_name,
                    'type' => $file_type['type'],
                    'data' => base64_encode( file_get_contents( $file ) )
                );
            }
        }

        return $attachments;

    }

    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();

        if ( $this->logger->isDebug() ) {
            $this->logger->debug( 'Creating SparkPost service with api_key=' . $this->api_key );
        }

        $spark_post = new PostmanSparkPost( $this->api_key );

        $sender = $message->getFromAddress();
        $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = !empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();

        $sender->log( $this->logger, 'From' );

        $body = [
            'content' => [
                'from' => [
                    'name' => $senderName,
                    'email' => $senderEmail,
                ]
            ]
        ];

        $body['content']['subject'] =  $message->getSubject();

        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $body['content']['html'] = $htmlPart;
        }

        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $body['content']['text'] = $textPart;
        }

        // add attachments
        $this->logger->debug( 'Adding attachments' );

        $attachments = $this->addAttachmentsToMail( $message );
        $body['content']['attachments'] = $attachments;

        $tos = array();
        $duplicates = array();

        // add the to recipients
        foreach ( (array)$message->getToRecipients() as $recipient ) {
                    
            if ( !array_key_exists( $recipient->getEmail(), $duplicates ) ) {

                $tos[] = array(
                    'address' => array(
                        'email' =>  $recipient->getEmail()
                    ),
                );
                
                $duplicates[] = $recipient->getEmail();

            }

        }

        $body['recipients'] = $tos;

        //Add cc
        $cc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Cc');

                $cc[] = array(
                    'address' => array(
                        'email' =>  $recipient->getEmail()
                    ),
                );
                
                $duplicates[] = $recipient->getEmail();

            }

        }
        if( !empty( $cc ) ) {
            $body['cc'] = $cc;
        }

        //Add bcc
        $bcc = array();
        $duplicates = array();
        foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                $recipient->log($this->logger, 'Bcc');
                $bcc[] = array(
                    'address' => array(
                        'email' =>  $recipient->getEmail()
                    ),
                );

                $duplicates[] = $recipient->getEmail();

            }

        }
        
        if( !empty( $bcc ) ) {
            $body['bcc'] = $bcc;
        }
        // add the reply-to
        $replyTo = $message->getReplyTo();
        // $replyTo is null or a PostmanEmailAddress object
        if ( isset( $replyTo ) ) {
            $body['content']['reply_to'] = $replyTo->getEmail();
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

        $body['headers'] = $headers;

        // if the caller set a Content-Type header, use it
        $contentType = $message->getContentType();
        if ( ! empty( $contentType ) ) {
            $this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
        }

        //Send Email
        try {

            $response = $spark_post->send( $body );

            if ( $this->logger->isDebug() ) {

                $this->logger->debug( 'Sending mail' );

            }
            
            
            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $body, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

        } catch ( Exception $e ) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $body, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $e;

        }

    }
}

endif;