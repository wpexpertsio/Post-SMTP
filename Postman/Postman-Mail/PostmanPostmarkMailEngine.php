<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanPostmarkMailEngine' ) ):

require_once 'Services/PostMark/Handler.php';

class PostmanPostmarkMailEngine implements PostmanMailEngine {

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

    public function send( PostmanMessage $message ) { 

        $options = PostmanOptions::getInstance();

        if ( $this->logger->isDebug() ) {
            $this->logger->debug( 'Creating PostMark service with api_Key=' . $this->api_key );
        }

        $post_mark  = new PostmanPostMark( $this->api_key );
        $body       = $this->get_email_body( $message );

        try {
            $response = $post_mark->send( $body );
            // send the message
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $body, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

        } catch(Exception $exception) {

            $this->transcript = $exception->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $body, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $exception;
        }
    }

    private function get_email_body( $message ) {

        if( is_a( $message, 'PostmanMessage' ) ) {

            $options = PostmanOptions::getInstance();
            
            $sender = $message->getFromAddress();
            $senderEmail = !empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
            
            $sender->log( $this->logger, 'From' );

            $body = $tos = $headers = $duplicates = $cc = $bcc = $email_attachments = array();
            $body['From'] = $senderEmail;
            
            // add the to recipients
            foreach ( (array)$message->getToRecipients() as $recipient ) {
                        
                if ( !array_key_exists( $recipient->getEmail(), $duplicates ) ) {

                    $tos[] = $recipient->getEmail();
                    
                    $duplicates[] = $recipient->getEmail();

                }

            }
            
            $body['To'] = implode( ",", $tos );
            $body['Subject'] = $message->getSubject();

            $textPart = $message->getBodyTextPart();
            if ( ! empty( $textPart ) ) {
                $this->logger->debug( 'Adding body as text' );
                $body['TextBody'] = $textPart;
            }

            $htmlPart = $message->getBodyHtmlPart();
            if ( ! empty( $htmlPart ) ) {
                $this->logger->debug( 'Adding body as html' );
                $body['HtmlBody'] = $htmlPart;
            }

            // add the reply-to
            $replyTo = $message->getReplyTo();
            // $replyTo is null or a PostmanEmailAddress object
            if ( isset( $replyTo ) ) {
                $body['ReplyTo'] = $replyTo->getEmail();
            } else {
                $body['ReplyTo'] = "";
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
            
            $body['Headers'][] = $headers;
            // add the messageId
            $messageId = $message->getMessageId();
            if ( ! empty( $messageId ) ) {
                $headers['message-id'] = $messageId;
            }

            // if the caller set a Content-Type header, use it
            $contentType = $message->getContentType();
            if ( ! empty( $contentType ) ) {
                $this->logger->debug( 'Some header keys are reserved. You may not include any of the following reserved headers: x-sg-id, x-sg-eid, received, dkim-signature, Content-Type, Content-Transfer-Encoding, To, From, Subject, Reply-To, CC, BCC.' );
            }

            $duplicates = array();
            foreach ( ( array ) $message->getCcRecipients() as $recipient ) {

                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                    $recipient->log($this->logger, 'Cc');

                    $cc[] = $recipient->getEmail();
                    
                    $duplicates[] = $recipient->getEmail();

                }

            }

            if( !empty( $cc ) ) {
                $body['Cc'] = implode( ",", $cc );
            } else {
                $body['Cc'] = "";
            }

            
            $duplicates = array();
            foreach ( ( array ) $message->getBccRecipients() as $recipient ) {

                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {

                    $recipient->log($this->logger, 'Bcc');
                    $bcc[] = $recipient->getEmail();

                    $duplicates[] = $recipient->getEmail();

                }

            }
            
            if( !empty( $bcc ) ) {
                $body['Bcc'] = implode( ",", $bcc );
            } else {
                $body['Bcc'] = "";
            }

            // add attachments
            $this->logger->debug( 'Adding attachments' );

            $attachments = $this->addAttachmentsToMail( $message );

            $body['Attachments'] = array();
            if( !empty( $attachments ) ) {
            
                foreach ( $attachments as $index => $attachment ) {

                    $email_attachments[] = array(
                        'name'          =>  $attachment['file_name'],
                        'content'       =>  $attachment['content'],
                        'ContentType'   =>  $attachment['type']
                    );
                }

                $body['Attachments'] = $email_attachments;
            
            }

            $body['MessageStream'] = 'outbound';

            // Handle apostrophes in email address From names by escaping them for the Postmark API.
            $from_regex = "/(\"From\": \"[a-zA-Z\\d]+)*[\\\\]{2,}'/";

            return $body;

        }

        return;
    }
}

endif;