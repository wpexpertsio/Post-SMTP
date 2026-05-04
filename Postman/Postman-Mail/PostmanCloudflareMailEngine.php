<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PostmanCloudflareMailEngine' ) ) :

require_once 'Services/Cloudflare/Handler.php';

class PostmanCloudflareMailEngine implements PostmanMailEngine {

	protected $logger;
	private $transcript;
	private $api_token;
	private $account_id;

	public function __construct( $api_token, $account_id ) {
		assert( ! empty( $api_token ) );
		assert( ! empty( $account_id ) );

		$this->api_token  = $api_token;
		$this->account_id = $account_id;
		$this->logger     = new PostmanLogger( get_class( $this ) );
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
					'content'     => base64_encode( file_get_contents( $file ) ),
					'filename'    => $file_name,
					'type'        => ! empty( $file_type['type'] ) ? $file_type['type'] : 'application/octet-stream',
					'disposition' => 'attachment',
				);
			}
		}

		return $result;
	}

	/**
	 * Cloudflare Email Service (REST) allows only a whitelist of custom headers plus X-* headers.
	 * Platform-controlled headers (Message-ID, Date, Content-Type, …) and first-class fields
	 * (From, To, …) must not be set via `headers` — see Cloudflare email headers reference.
	 *
	 * @param string $name Header name.
	 * @return bool
	 */
	private function isHeaderAllowedByCloudflare( $name ) {
		$n = strtolower( trim( (string) $name ) );
		if ( '' === $n ) {
			return false;
		}
		if ( 0 === strncmp( $n, 'x-', 2 ) ) {
			return (bool) preg_match( '/^x-[a-z0-9\-_]+$/', $n );
		}
		$allowed = array(
			'in-reply-to',
			'references',
			'list-unsubscribe',
			'list-unsubscribe-post',
			'list-id',
			'list-archive',
			'list-help',
			'list-owner',
			'list-post',
			'list-subscribe',
			'precedence',
			'auto-submitted',
			'content-language',
			'keywords',
			'comments',
			'importance',
			'sensitivity',
			'organization',
			'require-recipient-valid-since',
			'archived-at',
		);
		return in_array( $n, $allowed, true );
	}

	public function send( PostmanMessage $message ) {
		$options     = PostmanOptions::getInstance();
		$cloudflare  = new PostmanCloudflare( $this->api_token, $this->account_id );
		$payload     = array();
		$metaHeaders = array();

		$sender      = $message->getFromAddress();
		$senderEmail = ! empty( $sender->getEmail() ) ? $sender->getEmail() : $options->getMessageSenderEmail();
		$senderName  = ! empty( $sender->getName() ) ? $sender->getName() : $options->getMessageSenderName();
		$sender->log( $this->logger, 'From' );

		if ( ! empty( $senderName ) ) {
			$payload['from'] = array(
				'address' => $senderEmail,
				'name'    => $senderName,
			);
		} else {
			$payload['from'] = $senderEmail;
		}

		$to = array();
		foreach ( (array) $message->getToRecipients() as $recipient ) {
			if ( ! empty( $recipient->getName() ) ) {
				$to[] = $recipient->getName() . ' <' . $recipient->getEmail() . '>';
			} else {
				$to[] = $recipient->getEmail();
			}
		}
		if ( 1 === count( $to ) ) {
			$payload['to'] = $to[0];
		} else {
			$payload['to'] = $to;
		}

		$payload['subject'] = $message->getSubject();

		$textPart = $message->getBodyTextPart();
		$htmlPart = $message->getBodyHtmlPart();
		if ( ! empty( $textPart ) ) {
			$payload['text'] = $textPart;
		}
		if ( ! empty( $htmlPart ) ) {
			$payload['html'] = $htmlPart;
		}

		$replyTo = $message->getReplyTo();
		if ( ! empty( $replyTo ) && ! empty( $replyTo->getEmail() ) ) {
			$payload['reply_to'] = ! empty( $replyTo->getName() )
				? $replyTo->getName() . ' <' . $replyTo->getEmail() . '>'
				: $replyTo->getEmail();
		}

		$cc = array();
		foreach ( (array) $message->getCcRecipients() as $recipient ) {
			$cc[] = ! empty( $recipient->getName() )
				? $recipient->getName() . ' <' . $recipient->getEmail() . '>'
				: $recipient->getEmail();
		}
		if ( ! empty( $cc ) ) {
			$payload['cc'] = $cc;
		}

		$bcc = array();
		foreach ( (array) $message->getBccRecipients() as $recipient ) {
			$bcc[] = ! empty( $recipient->getName() )
				? $recipient->getName() . ' <' . $recipient->getEmail() . '>'
				: $recipient->getEmail();
		}
		if ( ! empty( $bcc ) ) {
			$payload['bcc'] = $bcc;
		}

		if ( ! $options->isStealthModeEnabled() ) {
			$pluginData = apply_filters( 'postman_get_plugin_metadata', null );
			$metaHeaders['X-Mailer'] = sprintf( 'Postman SMTP %s for WordPress (%s)', $pluginData['version'], 'https://wordpress.org/plugins/post-smtp/' );
		}

		foreach ( (array) $message->getHeaders() as $header ) {
			if ( empty( $header['name'] ) || ! isset( $header['content'] ) ) {
				continue;
			}
			if ( ! $this->isHeaderAllowedByCloudflare( $header['name'] ) ) {
				continue;
			}
			$value = (string) $header['content'];
			if ( '' === $value ) {
				continue;
			}
			$metaHeaders[ $header['name'] ] = $value;
		}

		if ( ! empty( $metaHeaders ) ) {
			$payload['headers'] = $metaHeaders;
		}

		$attachments = $this->addAttachmentsToMail( $message );
		if ( ! empty( $attachments ) ) {
			$payload['attachments'] = $attachments;
		}

		try {
			$response = $cloudflare->send( $payload );
			$body     = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['success'] ) ) {
				throw new Exception( $this->extractErrorMessage( $body, wp_remote_retrieve_response_code( $response ) ) );
			}

			$this->transcript  = print_r( $response, true );
			$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
			$this->transcript .= print_r( $payload, true );
			$this->logger->debug( 'Transcript=' . $this->transcript );
		} catch ( Exception $e ) {
			$this->transcript  = $e->getMessage();
			$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
			$this->transcript .= print_r( $payload, true );
			$this->logger->debug( 'Transcript=' . $this->transcript );
			throw $e;
		}
	}

	private function extractErrorMessage( $body, $status_code ) {
		if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
			$error = reset( $body['errors'] );
			if ( ! empty( $error['message'] ) ) {
				return sprintf( 'Cloudflare error (%1$s): %2$s', $error['code'], $error['message'] );
			}
		}

		switch ( (int) $status_code ) {
			case 400:
				return __( 'Invalid request format or email content for Cloudflare Email Routing.', 'post-smtp' );
			case 403:
				return __( 'Cloudflare sending is disabled or token has insufficient permissions.', 'post-smtp' );
			case 429:
				return __( 'Cloudflare rate limit exceeded. Please try again later.', 'post-smtp' );
			case 500:
				return __( 'Cloudflare internal server error. Please try again later.', 'post-smtp' );
			default:
				return sprintf( __( 'Cloudflare request failed with status code %d.', 'post-smtp' ), $status_code );
		}
	}
}
endif;
