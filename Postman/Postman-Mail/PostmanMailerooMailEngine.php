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

    /**
     * Maileroo reserved headers that must never be sent in payload headers.
     *
     * @return string[]
     */
    private function getReservedHeaders() {
        return array(
            'mime-version',
        );
    }

    /**
     * Normalize header name for reliable reserved-header checks.
     *
     * @param string $name
     * @return string
     */
    private function normalizeHeaderName( $name ) {
        $name = strtolower( trim( (string) $name ) );
        $name = rtrim( $name, ':' );
        return preg_replace( '/\s+/', '-', $name );
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
                    'file_name'    => $fileName,
                    'content_type' => $fileType['type'] ?: 'application/octet-stream',
                    'content'      => base64_encode( file_get_contents( $file ) ),
                    'inline'       => false,
                ];
            }
        }
        return $result;
    }

    public function send( PostmanMessage $message ) {

        $options  = PostmanOptions::getInstance();
        $maileroo = new PostmanMaileroo( $this->api_key );

        $to_recipients = [];
        $duplicates = [];

        $sender      = $message->getFromAddress();
        $senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
        $senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
        $sender->log( $this->logger, 'From' );

        foreach ( (array) $message->getToRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $to_recipients[] = [
                    'address'      => $recipient->getEmail(),
                    'display_name' => $recipient->getName() ?: '',
                ];
                $duplicates[] = $recipient->getEmail();
            }
        }
    
        $cc_recipients  = [];
        foreach ( (array) $message->getCcRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $cc_recipients[] = [
                    'address'      => $recipient->getEmail(),
                    'display_name' => $recipient->getName() ?: '',
                ];
                $duplicates[] = $recipient->getEmail();
            }
        }

        $bcc_recipients = [];
        foreach ( (array) $message->getBccRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $bcc_recipients[] = [
                    'address'      => $recipient->getEmail(),
                    'display_name' => $recipient->getName() ?: '',
                ];
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

        $plainContent = wp_strip_all_tags( $textPart ?: $htmlPart );
        $content = [
            'from'    => [
                'address'      => $senderEmail,
                'display_name' => $senderName,
            ],
            'to'      => $to_recipients,
            'subject' => $subject,
            'html'    => $htmlContent,
            'plain'   => $plainContent,
        ];

        if ( ! empty( $cc_recipients ) ) {
            $content['cc'] = $cc_recipients;
        }
        if ( ! empty( $bcc_recipients ) ) {
            $content['bcc'] = $bcc_recipients;
        }

        $replyTo = $message->getReplyTo();
        if ( ! empty( $replyTo ) && $replyTo->getEmail() ) {
            $content['reply_to'] = [
                'address'      => $replyTo->getEmail(),
                'display_name' => $replyTo->getName() ?: '',
            ];
        }

        // Forward only custom headers (excluding Maileroo system-reserved headers).
        $custom_headers = [];
        $reserved       = $this->getReservedHeaders();
        foreach ( (array) $message->getHeaders() as $header ) {
            if ( empty( $header['name'] ) || ! isset( $header['content'] ) ) {
                continue;
            }

            $raw_name   = trim( (string) $header['name'] );
            $check_name = $this->normalizeHeaderName( $raw_name );
            if ( in_array( $check_name, $reserved, true ) ) {
                $this->logger->debug( sprintf( 'Skipping Maileroo reserved header: %s', $raw_name ) );
                continue;
            }

            $custom_headers[ $raw_name ] = (string) $header['content'];
            $this->logger->debug( sprintf( 'Adding custom header: %s', $raw_name ) );
        }
        if ( ! empty( $custom_headers ) ) {
            $content['headers'] = $custom_headers;
        }

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