<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanEmailItMailEngine' ) ) {

    require_once 'Services/Emailit/Handler.php'; // Create this to handle actual sending

    /**
     * Sends mail with the EmailIt API
     */
    class PostmanEmailItMailEngine implements PostmanMailEngine {

        protected $logger;
        private $transcript;
        private $apiKey;

        public function __construct( $apiKey ) {
            assert( ! empty( $apiKey ) );
            $this->apiKey = $apiKey;
            $this->logger = new PostmanLogger( get_class( $this ) );
        }

        public function send( PostmanMessage $message ) {
            $options = PostmanOptions::getInstance();
            $emailit = new PostmanEmailIt( $this->apiKey );

            $content = [];
            $headers = [];
            $recipients = [];
            $duplicates = [];

            // Sender
            $sender = $message->getFromAddress();
            $senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
            $senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
            $content['from'] = ['email' => $senderEmail, 'name' => $senderName];
            $sender->log( $this->logger, 'From' );

            // To Recipients
            foreach ( (array) $message->getToRecipients() as $recipient ) {
                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                    $content['personalizations'][0]['to'][] = [
                        'email' => $recipient->getEmail(),
                        'name'  => $recipient->getName()
                    ];
                    $duplicates[] = $recipient->getEmail();
                }
            }

            // Subject
            if ( null !== $message->getSubject() ) {
                $content['subject'] = $message->getSubject();
            }

            // Message Body
            $textPart = $message->getBodyTextPart();
            if ( ! empty( $textPart ) ) {
                $this->logger->debug( 'Adding body as text' );
                $content['content'][] = ['type' => 'text/plain', 'value' => $textPart];
            }

            $htmlPart = $message->getBodyHtmlPart();
            if ( ! empty( $htmlPart ) ) {
                $this->logger->debug( 'Adding body as html' );
                $content['content'][] = ['type' => 'text/html', 'value' => $htmlPart];
            }

            // Reply-To
            $replyTo = $message->getReplyTo();
            if ( isset( $replyTo ) ) {
                $content['reply_to'] = [
                    'email' => $replyTo->getEmail(),
                    'name'  => $replyTo->getName()
                ];
            }

            // Custom Headers
            foreach ( (array) $message->getHeaders() as $header ) {
                $this->logger->debug( sprintf( 'Adding user header %s=%s', $header['name'], $header['content'] ) );
                $headers[$header['name']] = $header['content'];
            }

            // CC
            foreach ( (array) $message->getCcRecipients() as $recipient ) {
                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                    $recipient->log( $this->logger, 'Cc' );
                    $content['personalizations'][0]['cc'][] = [
                        'email' => $recipient->getEmail(),
                        'name'  => $recipient->getName()
                    ];
                    $duplicates[] = $recipient->getEmail();
                }
            }

            // BCC
            foreach ( (array) $message->getBccRecipients() as $recipient ) {
                if ( ! in_array( $recipient->getEmail(), $duplicates ) ) {
                    $recipient->log( $this->logger, 'Bcc' );
                    $content['personalizations'][0]['bcc'][] = [
                        'email' => $recipient->getEmail(),
                        'name'  => $recipient->getName()
                    ];
                    $duplicates[] = $recipient->getEmail();
                }
            }

            if ( ! empty( $headers ) ) {
                $content['headers'] = $headers;
            }

            // Attachments
            $attachments = $this->addAttachmentsToMail( $message );
            if ( ! empty( $attachments ) ) {
                $content['attachments'] = $attachments;
            }

            // Send
            try {
                $this->logger->debug( 'Sending mail' );
                $response = $emailit->send( $content );
                $this->transcript = print_r( $response, true );
                $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
                $this->transcript .= print_r( $content, true );
                $this->logger->debug( 'Transcript=' . $this->transcript );
            } catch ( Exception $e ) {
                $this->transcript = $e->getMessage();
                $this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
                $this->transcript .= print_r( $content, true );
                $this->logger->debug( 'Transcript=' . $this->transcript );
                throw $e;
            }
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

        public function getTranscript() {
            return $this->transcript;
        }
    }
}
