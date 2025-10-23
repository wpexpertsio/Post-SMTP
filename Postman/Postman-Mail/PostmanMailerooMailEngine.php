<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'PostmanMailerooMailEngine' ) ):
    
require_once 'Services/Maileroo/Handler.php'; 

class PostmanMailerooMailEngine implements PostmanMailEngine {

    protected $logger;
    private $transcript;
    private $api_key;

    public function __construct( $api_key ) {
        assert( !empty( $api_key ) );
        $this->api_key = $api_key;
        $this->logger = new PostmanLogger( get_class( $this ) );
    }

    public function getTranscript() {
        return $this->transcript;
    }

    private function addAttachmentsToMail( PostmanMessage $message ) {
        $attachments = $message->getAttachments();
        $attArray = is_array( $attachments ) ? $attachments : explode( PHP_EOL, $attachments );
        $result = [];
        foreach ( $attArray as $file ) {
            if ( ! empty( $file ) ) {
                $this->logger->debug( 'Adding attachment: ' . $file );
                $fileName = basename( $file );
                $fileType = wp_check_filetype( $file );
                $result[] = [
                    'content'     => base64_encode( file_get_contents( $file ) ),
                    'type'        => $fileType['type'],
                    'filename'    => $fileName,
                    'disposition' => 'attachment',
                    'name'        => pathinfo( $fileName, PATHINFO_FILENAME ),
                ];
            }
        }
        return $result;
    }

    public function send( PostmanMessage $message ) {
        $options  = PostmanOptions::getInstance();
        $maileroo = new PostmanMaileroo( $this->api_key );

        $recipients = [];
        $duplicates = [];

        $sender      = $message->getFromAddress();
        $senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $sender->log( $this->logger, 'From' );

        foreach ( (array) $message->getToRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $recipients[] = $recipient->getEmail();
                $duplicates[] = $recipient->getEmail();
            }
        }

        $subject     = $message->getSubject();
        $textPart    = $message->getBodyTextPart();
        $htmlPart    = $message->getBodyHtmlPart();
        $htmlContent = ! empty( $htmlPart ) ? $htmlPart : nl2br( $textPart );
        if ( empty( $htmlContent ) ) {
            $htmlContent = '<p>(No content)</p>';
        }

        $content = [
            'from'    => [
                'address'      => $senderEmail,
                'display_name' => $senderName,
            ],
            'to'      => array_map(function($email) { return ['address' => $email]; }, $recipients),
            'subject' => $subject,
            'html'    => $htmlContent,
            'text'    => wp_strip_all_tags( $textPart ?: $htmlPart ),
        ];

        $attachments = $this->addAttachmentsToMail( $message );
        if ( ! empty( $attachments ) ) {
            $content['attachments'] = $attachments;
        }

        try {
            $this->logger->debug( 'Sending mail via Maileroo' );
            $response = $maileroo->send( $content );
            $responseCode = wp_remote_retrieve_response_code( $response );
            $responseBody = wp_remote_retrieve_body( $response );

            if ( $responseCode === 200 || $responseCode === 202 ) {
                $this->transcript  = 'Email sent successfully.' . PHP_EOL;
                $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS . PHP_EOL;
                $this->transcript .= print_r( $content, true );
                $this->logger->debug( 'Transcript=' . $this->transcript );
            } else {
                $decodedBody  = json_decode( $responseBody, true );
                $errorMessage = $this->extractErrorMessage( $decodedBody, $responseCode );
                throw new Exception( $errorMessage );
            }
        } catch ( Exception $e ) {
            $this->transcript  = $e->getMessage() . PHP_EOL;
            $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS . PHP_EOL;
            $this->transcript .= print_r( $content, true );
            $this->logger->debug( 'Transcript=' . $this->transcript );
            throw $e;
        }
    }

    private function extractErrorMessage( $decodedBody, $responseCode ) {
        if ( is_array( $decodedBody ) ) {
            if ( isset( $decodedBody['message'] ) && is_string( $decodedBody['message'] ) ) {
                return $decodedBody['message'];
            }
            if ( isset( $decodedBody['error'] ) ) {
                return is_string( $decodedBody['error'] )
                    ? $decodedBody['error']
                    : ( $decodedBody['error']['message'] ?? $this->getErrorMessageFromCode( $responseCode ) );
            }
        }
        return $this->getErrorMessageFromCode( $responseCode );
    }

    private function getErrorMessageFromCode( $response_code ) {
        switch ( $response_code ) {
            case 400:
                return __( 'Bad request. Please check your email data.', 'maileroo' );
            case 401:
                return __( 'Unauthorized. Please check your API key.', 'maileroo' );
            case 403:
                return __( 'Forbidden. Access denied.', 'maileroo' );
            case 404:
                return __( 'Not found. Please check the API endpoint.', 'maileroo' );
            case 422:
                return __( 'Domain verification required. Your sending domain must be verified in Maileroo before you can send emails. Please verify your domain in your Maileroo dashboard.', 'maileroo' );
            case 429:
                return __( 'Rate limit exceeded. Please try again later.', 'maileroo' );
            case 500:
                return __( 'Internal server error. Please try again later.', 'maileroo' );
            default:
                return sprintf( __( 'HTTP error %d occurred.', 'maileroo' ), $response_code );
        }
    }
}
endif;
