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
     * Headers that Mailjet Send API v3.1 rejects in the Headers collection (send-0011).
     *
     * @see https://dev.mailjet.com/docs/email-api/send-api-v31/add-email-headers
     */
    private static $mailjet_forbidden_headers = array(
        'from', 'sender', 'subject', 'to', 'cc', 'bcc', 'return-path', 'delivered-to',
        'dkim-signature', 'domainkey-status', 'received-spf', 'authentication-results',
        'received', 'x-mailjet-prio', 'x-mailjet-debug', 'user-agent', 'x-mailer',
        'x-mj-customid', 'x-mj-eventpayload', 'x-mj-vars', 'x-mj-templateerrordeliver',
        'x-mj-templateerrorreporting', 'x-mj-templatelanguage', 'x-mailjet-trackopen',
        'x-mailjet-trackclick', 'x-mj-templateid', 'x-mj-workflowid', 'x-feedback-id',
        'x-mailjet-segmentation', 'list-id', 'x-mj-mid', 'x-mj-errormessage', 'date',
        'x-csa-complaints', 'message-id', 'x-mailjet-campaign', 'x-mj-statisticscontactslistid',
        'reply-to', 'content-type', 'content-transfer-encoding',
    );


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
                $file_type = wp_check_filetype( $file );
                $attachments[] = array(
                    'Base64Content' => base64_encode( file_get_contents( $file ) ),
                    'ContentType' => $file_type['type'],
                    'Filename' => $file_name,
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

        if ( $this->logger->isDebug() ) {
            $this->logger->debug( 'Creating Mailjet service with apiKey=' . $this->api_key );
        }

        $mailjet = new PostmanMailjet( $this->api_key, $this->secret_key );
        $sender = $message->getFromAddress();
        $senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $headers = array();

        $sender->log( $this->logger, 'From' );

        $mailjet_message = array(
            'From' => array(
                'Email' => $senderEmail,
                'Name'  => $senderName,
            ),
            'Subject' => $message->getSubject(),
            'To'      => $this->formatMailjetRecipients( $message->getToRecipients() ),
        );

        $textPart = $message->getBodyTextPart();
        if ( ! empty( $textPart ) ) {
            $this->logger->debug( 'Adding body as text' );
            $mailjet_message['TextPart'] = $textPart;
        }

        $htmlPart = $message->getBodyHtmlPart();
        if ( ! empty( $htmlPart ) ) {
            $this->logger->debug( 'Adding body as html' );
            $mailjet_message['HTMLPart'] = $htmlPart;
        }

        $get_reply_to = $message->getReplyTo();
        if ( ! empty( $get_reply_to ) ) {
            $mailjet_message['ReplyTo'] = array(
                'Email' => $get_reply_to->getEmail(),
                'Name'  => $get_reply_to->getName() ? $get_reply_to->getName() : '',
            );
        }

        foreach ( (array) $message->getHeaders() as $header ) {
            $header_name = $header['name'];
            if ( $this->isMailjetForbiddenHeader( $header_name ) ) {
                $this->logger->debug( sprintf( 'Skipping header not allowed in Mailjet Headers collection: %s', $header_name ) );
                continue;
            }
            $this->logger->debug( sprintf( 'Adding user header %s=%s', $header_name, $header['content'] ) );
            $headers[ $header_name ] = $header['content'];
        }

        $messageId = $message->getMessageId();
        if ( ! empty( $messageId ) ) {
            $mailjet_message['CustomID'] = trim( $messageId, '<>' );
        }

        if ( ! empty( $headers ) ) {
            $mailjet_message['Headers'] = $headers;
        }

        $contentType = $message->getContentType();
        if ( ! empty( $contentType ) ) {
            $this->logger->debug( 'Mailjet Send API v3.1 does not allow Content-Type in the Headers collection; body parts define content.' );
        }

        $cc_recipients = $this->formatMailjetRecipients( $message->getCcRecipients() );
        if ( ! empty( $cc_recipients ) ) {
            $mailjet_message['Cc'] = $cc_recipients;
        }

        $bcc_recipients = $this->formatMailjetRecipients( $message->getBccRecipients() );
        if ( ! empty( $bcc_recipients ) ) {
            $mailjet_message['Bcc'] = $bcc_recipients;
        }

        $this->logger->debug( 'Adding attachments' );
        $attachments = $this->addAttachmentsToMail( $message );
        if ( ! empty( $attachments ) ) {
            $mailjet_message['Attachments'] = $attachments;
        }

        $payload = array(
            'Messages' => array( $mailjet_message ),
        );

        try {
            if ( $this->logger->isDebug() ) {
                $this->logger->debug( 'Sending mail' );
            }

            $response = $mailjet->send( $payload );
            $this->validateMailjetResponse( $mailjet );

            $this->transcript = print_r( $response, true );
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $payload, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

        } catch ( Exception $e ) {

            $this->transcript = $e->getMessage();
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
            $this->transcript .= print_r( $payload, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );

            throw $e;

        }

    }

    /**
     * @param string $header_name
     * @return bool
     */
    private function isMailjetForbiddenHeader( $header_name ) {
        return in_array( strtolower( trim( $header_name ) ), self::$mailjet_forbidden_headers, true );
    }

    /**
     * @param PostmanEmailAddress[]|array $recipients
     * @return array
     */
    private function formatMailjetRecipients( $recipients ) {
        $formatted = array();
        $seen = array();

        foreach ( (array) $recipients as $recipient ) {
            $email = $recipient->getEmail();
            if ( empty( $email ) || isset( $seen[ $email ] ) ) {
                continue;
            }
            $formatted[] = array(
                'Email' => $email,
                'Name'  => $recipient->getName() ? $recipient->getName() : '',
            );
            $seen[ $email ] = true;
        }

        return $formatted;
    }

    /**
     * @param PostmanMailjet $mailjet
     */
    private function validateMailjetResponse( PostmanMailjet $mailjet ) {
        $body = $mailjet->get_response_body();
        if ( empty( $body ) ) {
            return;
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || empty( $data['Messages'] ) ) {
            return;
        }

        foreach ( $data['Messages'] as $mailjet_message ) {
            if ( empty( $mailjet_message['Status'] ) ) {
                continue;
            }
            if ( 'success' === strtolower( $mailjet_message['Status'] ) ) {
                continue;
            }
            $error_details = ! empty( $mailjet_message['Errors'] ) ? wp_json_encode( $mailjet_message['Errors'] ) : $body;
            throw new Exception( sprintf( 'Mailjet API error: %s', $error_details ) );
        }
    }
}
endif;