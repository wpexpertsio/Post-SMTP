<?php
/**
 * Transactional delivery through the official MailChannels PHP SDK.
 *
 * @package Postman
 */

defined( 'ABSPATH' ) || exit;

class PostmanMailChannelsMailEngine implements PostmanMailEngine {
	private $apiKey;
	private $client;
	private $transcript = '';

	public function __construct( $apiKey ) {
		$this->apiKey = $apiKey;
	}

	/** Lazy loading keeps the SDK's PHP requirement local to this transport. */
	protected function createClient() {
		if ( PHP_VERSION_ID < 80200 ) {
			throw new RuntimeException( __( 'MailChannels requires PHP 8.2 or later.', 'post-smtp' ) );
		}
		static $loader;
		if ( ! $loader ) {
			require_once __DIR__ . '/libs/vendor/autoload.php';
			// The existing Composer loader has an authoritative class map.
			$loader = new Composer\Autoload\ClassLoader();
			$loader->addPsr4( 'PostSMTP\\Vendor\\MailChannels\\', __DIR__ . '/libs/vendor_prefixed/mailchannels/mailchannels-php/src' );
			$loader->register();
		}
		$options = PostmanOptions::getInstance();
		$http = new PostSMTP\Vendor\GuzzleHttp\Client( array(
			'connect_timeout' => (float) $options->getConnectionTimeout(),
			'timeout'         => (float) $options->getReadTimeout(),
		) );
		$factory = new PostSMTP\Vendor\GuzzleHttp\Psr7\HttpFactory();
		// Explicit dependencies avoid discovery and conflicts with other plugins.
		return new PostSMTP\Vendor\MailChannels\Client(
			$this->apiKey,
			'https://api.mailchannels.net/tx/v1',
			$http,
			$factory,
			$factory
		);
	}

	public function send( PostmanMessage $message ) {
		$this->transcript = '';
		try {
			if ( ! $this->client ) {
				$this->client = $this->createClient();
			}
			$response = $this->client->emails->send( $this->createPayload( $message ) );
			$this->transcript = sprintf( 'MailChannels HTTP %d; request ID: %s', $response->statusCode, $response->requestId() );
			// A 202 can contain a failed personalization. Never log that as sent.
			if ( $response->statusCode !== 202 || count( (array) $response->results ) !== 1 ) {
				throw new RuntimeException( __( 'MailChannels returned an unexpected send response.', 'post-smtp' ) );
			}
			$result = $response->results[0];
			if ( $result->status !== 'sent' ) {
				throw new RuntimeException( 'MailChannels: ' . ( $result->reason ?: __( 'The message was not accepted.', 'post-smtp' ) ) );
			}
			$this->transcript .= sprintf( '; message ID: %s; accepted for delivery.', $result->messageId );
		} catch ( Throwable $exception ) {
			$details = $exception->getMessage();
			if ( $exception instanceof PostSMTP\Vendor\MailChannels\Exception\MailChannelsException ) {
				$details .= sprintf( ' (HTTP %s; request ID: %s)', $exception->getStatusCode(), $exception->getRequestId() );
				if ( $exception->getRetryAfter() !== null ) {
					$details .= '; Retry-After: ' . $exception->getRetryAfter();
				}
			}
			// Do not retain credentials, message bodies or attachment data in transcripts.
			$details = $this->redact( $details );
			$this->transcript = $this->redact( $this->transcript . "\n" . $details );
			throw new RuntimeException( $details, (int) $exception->getCode() );
		}
	}

	protected function createPayload( PostmanMessage $message ) {
		$payload = array(
			'from'    => $this->address( $message->getFromAddress() ),
			'subject' => (string) $message->getSubject(),
		);
		$seen = array();
		$recipients = array(
			'to'  => $message->getToRecipients(),
			'cc'  => $message->getCcRecipients(),
			'bcc' => $message->getBccRecipients(),
		);
		foreach ( $recipients as $type => $addresses ) {
			foreach ( (array) $addresses as $address ) {
				$email = strtolower( $address->getEmail() );
				if ( ! isset( $seen[$email] ) ) {
					$payload[$type][] = $this->address( $address );
					$seen[$email] = true;
				}
			}
		}
		foreach ( array( 'text' => $message->getBodyTextPart(), 'html' => $message->getBodyHtmlPart() ) as $type => $body ) {
			if ( $body !== null && $body !== '' ) {
				$payload[$type] = $body;
			}
		}
		if ( $message->getReplyTo() ) {
			$payload['reply_to'] = $this->address( $message->getReplyTo() );
		}
		foreach ( (array) $message->getHeaders() as $header ) {
			// The SDK validates reserved headers; do not override structured fields.
			$payload['headers'][$header['name']] = $header['content'];
		}
		$attachments = $message->getAttachments();
		if ( ! is_array( $attachments ) ) {
			$attachments = preg_split( '/\r\n|\r|\n/', (string) $attachments );
		}
		foreach ( $attachments as $file ) {
			if ( $file === '' ) {
				continue;
			}
			// wp_mail attachments are local files, never remote URLs or streams.
			if ( ! is_string( $file ) || strpos( $file, '://' ) !== false || ! is_file( $file ) || ! is_readable( $file ) ) {
				throw new RuntimeException( __( 'MailChannels could not read an attachment.', 'post-smtp' ) );
			}
			$data = file_get_contents( $file );
			if ( $data === false ) {
				throw new RuntimeException( __( 'MailChannels could not read an attachment.', 'post-smtp' ) );
			}
			$type = wp_check_filetype( $file );
			$payload['attachments'][] = array(
				'filename'    => basename( $file ),
				'type'        => $type['type'] ?: 'application/octet-stream',
				'content'     => base64_encode( $data ),
				'disposition' => 'attachment',
			);
		}
		return $payload;
	}

	private function address( PostmanEmailAddress $address ) {
		$result = array( 'email' => $address->getEmail() );
		if ( $address->getName() !== null && $address->getName() !== '' ) {
			$result['name'] = $address->getName();
		}
		return $result;
	}

	private function redact( $text ) {
		return $this->apiKey ? str_replace( $this->apiKey, '[redacted]', $text ) : $text;
	}

	public function getTranscript() {
		return $this->transcript;
	}
}
