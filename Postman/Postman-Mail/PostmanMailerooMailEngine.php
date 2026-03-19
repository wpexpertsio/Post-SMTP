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
     * System-reserved headers managed by Maileroo. Must not be sent in the API "headers" field.
     *
     * @return string[]
     */
    private function get_system_reserved_headers() {
        return array(
            'mime-version',
            'content-type',
            'content-transfer-encoding',
            'message-id',
            'date',
            'to',
            'from',
            'subject',
            'reply-to',
            'cc',
            'bcc',
            'received',
            'dkim-signature',
            'sender',
            'return-path',
            'list-unsubscribe',
            'list-id',
            'precedence',
            'auto-submitted',
            'x-priority',
        );
    }

    /**
     * Build only custom headers for Maileroo API. System-reserved headers (MIME-Version,
     * Content-Type, Message-ID, Date, etc.) are excluded; Maileroo generates those.
     *
     * @param PostmanMessage $message
     * @return array<string, string> Header name => value
     */
    private function get_custom_headers_only( PostmanMessage $message ) {
        $reserved = $this->get_system_reserved_headers();
        $custom   = array();
        foreach ( (array) $message->getHeaders() as $header ) {
            $name = isset( $header['name'] ) ? trim( $header['name'] ) : '';
            if ( $name === '' ) {
                continue;
            }
            $name_lower = strtolower( $name );
            if ( in_array( $name_lower, $reserved, true ) ) {
                $this->logger->debug( sprintf( 'Skipping system-reserved header for Maileroo: %s', $name ) );
                continue;
            }
            $content = isset( $header['content'] ) ? trim( $header['content'] ) : '';
            $custom[ $name ] = $content;
        }
        return $custom;
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
                $result[] = array(
                    'file_name'    => $fileName,
                    'content_type' => $fileType['type'],
                    'content'      => base64_encode( file_get_contents( $file ) ),
                    'inline'       => false,
                );
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

        $content = array(
            'from'    => array(
                'address'      => $senderEmail,
                'display_name' => $senderName,
            ),
            'to'      => $recipients,
            'subject' => $subject,
            'html'    => $htmlContent,
            'plain'   => $plainContent,
        );

        $reply_to = $message->getReplyTo();
        if ( ! empty( $reply_to ) ) {
            $reply_list = is_array( $reply_to ) ? $reply_to : array( $reply_to );
            $first      = reset( $reply_list );
            if ( $first instanceof PostmanEmailAddress && $first->getEmail() ) {
                $content['reply_to'] = array( 'address' => $first->getEmail() );
                if ( $first->getName() !== '' && $first->getName() !== null ) {
                    $content['reply_to']['display_name'] = $first->getName();
                }
            }
        }

        $cc_recipients = array();
        foreach ( (array) $message->getCcRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $item = array( 'address' => $recipient->getEmail() );
                if ( $recipient->getName() !== '' && $recipient->getName() !== null ) {
                    $item['display_name'] = $recipient->getName();
                }
                $cc_recipients[] = $item;
                $duplicates[] = $recipient->getEmail();
            }
        }
        if ( ! empty( $cc_recipients ) ) {
            $content['cc'] = count( $cc_recipients ) === 1 ? $cc_recipients[0] : $cc_recipients;
        }

        $bcc_recipients = array();
        foreach ( (array) $message->getBccRecipients() as $recipient ) {
            if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                $item = array( 'address' => $recipient->getEmail() );
                if ( $recipient->getName() !== '' && $recipient->getName() !== null ) {
                    $item['display_name'] = $recipient->getName();
                }
                $bcc_recipients[] = $item;
                $duplicates[] = $recipient->getEmail();
            }
        }
        if ( ! empty( $bcc_recipients ) ) {
            $content['bcc'] = $bcc_recipients;
        }

        $custom_headers = $this->get_custom_headers_only( $message );
        if ( ! empty( $custom_headers ) ) {
            $content['headers'] = $custom_headers;
        }

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

        // Forward additional/custom mail headers (e.g. from Contact Form 7) to Maileroo API.
        $custom_headers = [];
        foreach ( (array) $message->getHeaders() as $header ) {
            if ( ! empty( $header['name'] ) && isset( $header['content'] ) ) {
                $custom_headers[ $header['name'] ] = $header['content'];
                $this->logger->debug( sprintf( 'Adding custom header: %s', $header['name'] ) );
            }
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
