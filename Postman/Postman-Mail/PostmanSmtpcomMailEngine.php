<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanSmtpcomMailEngine' ) ) :

require_once 'Services/SMTPcom/Handler.php';

class PostmanSmtpcomMailEngine implements PostmanMailEngine {

	protected $logger;
	private $transcript;
	private $api_key;
	private $channel;

	public function __construct( $api_key, $channel = '' ) {
		assert( ! empty( $api_key ) );

		$this->api_key = $api_key;
		$this->channel = $channel;
		$this->logger  = new PostmanLogger( get_class( $this ) );
	}

	public function getTranscript() {
		return $this->transcript;
	}

	private function addAttachmentsToMail( PostmanMessage $message ) {
		$attachments = $message->getAttachments();
		$attArray    = is_array( $attachments ) ? $attachments : explode( PHP_EOL, $attachments );
		$result      = array();

		foreach ( $attArray as $file ) {
			if ( ! empty( $file ) ) {
				$this->logger->debug( 'Adding attachment: ' . $file );
				$file_name = basename( $file );
				$file_type = wp_check_filetype( $file );
				$result[]  = array(
					'type'        => ! empty( $file_type['type'] ) ? $file_type['type'] : 'application/octet-stream',
					'filename'    => $file_name,
					'disposition' => 'attachment',
					'content'     => base64_encode( file_get_contents( $file ) ),
				);
			}
		}

		return $result;
	}

	private function formatRecipient( $recipient ) {
		$formatted = array(
			'address' => $recipient->getEmail(),
		);

		if ( ! empty( $recipient->getName() ) ) {
			$formatted['name'] = $recipient->getName();
		}

		return $formatted;
	}

	public function send( PostmanMessage $message ) {
		$options = PostmanOptions::getInstance();

		if ( $this->logger->isDebug() ) {
			$this->logger->debug( 'Creating SMTP.com service with apiKey=' . $this->api_key );
		}

		$smtpcom     = new PostmanSmtpcom( $this->api_key );
		$sender      = $message->getFromAddress();
		$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
		$senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
		$sender->log( $this->logger, 'From' );

		$sendEmail = array(
			'subject'    => $message->getSubject(),
			'originator' => array(
				'from' => array(
					'name'    => $senderName,
					'address' => $senderEmail,
				),
			),
			'recipients' => array(),
			'body'       => array(
				'parts' => array(),
			),
		);

		if ( ! empty( $this->channel ) ) {
			$sendEmail['channel'] = $this->channel;
		}

		$tos        = array();
		$duplicates = array();

		foreach ( (array) $message->getToRecipients() as $recipient ) {
			if ( ! in_array( $recipient->getEmail(), $duplicates, true ) ) {
				$tos[]        = $this->formatRecipient( $recipient );
				$duplicates[] = $recipient->getEmail();
			}
		}
		$sendEmail['recipients']['to'] = $tos;

		$textPart = $message->getBodyTextPart();
		if ( ! empty( $textPart ) ) {
			$this->logger->debug( 'Adding body as text' );
			$sendEmail['body']['parts'][] = array(
				'type'    => 'text/plain',
				'charset' => 'UTF-8',
				'content' => $textPart,
			);
		}

		$htmlPart = $message->getBodyHtmlPart();
		if ( ! empty( $htmlPart ) ) {
			$this->logger->debug( 'Adding body as html' );
			$sendEmail['body']['parts'][] = array(
				'type'    => 'text/html',
				'charset' => 'UTF-8',
				'content' => $htmlPart,
			);
		}

		$replyTo = $message->getReplyTo();
		if ( isset( $replyTo ) ) {
			$sendEmail['originator']['reply_to'] = $this->formatRecipient( $replyTo );
		}

		$custom_headers = array();

		if ( ! $options->isStealthModeEnabled() ) {
			$pluginData                 = apply_filters( 'postman_get_plugin_metadata', null );
			$custom_headers['X-Mailer'] = sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData['version'], 'https://wordpress.org/plugins/post-smtp/' );
		}

		foreach ( (array) $message->getHeaders() as $header ) {
			$this->logger->debug( sprintf( 'Adding user header %s=%s', $header['name'], $header['content'] ) );
			$custom_headers[ $header['name'] ] = $header['content'];
		}

		$messageId = $message->getMessageId();
		if ( ! empty( $messageId ) ) {
			$custom_headers['message-id'] = $messageId;
		}

		if ( ! empty( $custom_headers ) ) {
			$sendEmail['custom_headers'] = $custom_headers;
		}

		$cc         = array();
		$duplicates = array();
		foreach ( (array) $message->getCcRecipients() as $recipient ) {
			if ( ! in_array( $recipient->getEmail(), $duplicates, true ) ) {
				$recipient->log( $this->logger, 'Cc' );
				$cc[]         = $this->formatRecipient( $recipient );
				$duplicates[] = $recipient->getEmail();
			}
		}
		if ( ! empty( $cc ) ) {
			$sendEmail['recipients']['cc'] = $cc;
		}

		$bcc        = array();
		$duplicates = array();
		foreach ( (array) $message->getBccRecipients() as $recipient ) {
			if ( ! in_array( $recipient->getEmail(), $duplicates, true ) ) {
				$recipient->log( $this->logger, 'Bcc' );
				$bcc[]        = $this->formatRecipient( $recipient );
				$duplicates[] = $recipient->getEmail();
			}
		}
		if ( ! empty( $bcc ) ) {
			$sendEmail['recipients']['bcc'] = $bcc;
		}

		$this->logger->debug( 'Adding attachments' );
		$attachments = $this->addAttachmentsToMail( $message );
		if ( ! empty( $attachments ) ) {
			$sendEmail['body']['attachments'] = $attachments;
		}

		try {
			if ( $this->logger->isDebug() ) {
				$this->logger->debug( 'Sending mail' );
			}

			$response = $smtpcom->send( $sendEmail );

			$this->transcript  = print_r( $response, true );
			$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
			$this->transcript .= print_r( $sendEmail, true );
			$this->logger->debug( 'Transcript=' . $this->transcript );
		} catch ( Exception $e ) {
			$this->transcript  = $e->getMessage();
			$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
			$this->transcript .= print_r( $sendEmail, true );
			$this->logger->debug( 'Transcript=' . $this->transcript );

			throw $e;
		}
	}
}
endif;
