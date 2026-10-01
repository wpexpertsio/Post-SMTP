<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostmanBirdMailEngine implements PostmanMailEngine {
	private $settings;
	private $transcript = '';

	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function getTranscript() {
		return $this->transcript;
	}

	private static function address( PostmanEmailAddress $address ) {
		$result = ['email' => $address->getEmail()];
		$name = trim( (string) $address->getName(), '" ' );
		if ( $name !== '' ) {
			$result['name'] = $name;
		}
		return $result;
	}

	private function payload( PostmanMessage $message, &$idempotencyKey ) {
		$sender = $message->getFromAddress();
		if ( ! $sender ) {
			throw new RuntimeException( esc_html__( 'Set a sender email address in Post SMTP or the message From header.', 'post-smtp' ) );
		}
		$contentType = strtolower( trim( explode( ';', (string) $message->getContentType(), 2 )[0] ) );
		// Post SMTP's test email is boundary-free; its parser can lose later MIME boundary parameters.
		$parsedAlternative = $contentType === 'multipart/alternative' && (string) $message->getBoundary() === ''
			&& ! preg_match( '/(?:\A|\r|\n)--/', (string) $message->getBody() )
			&& $message->getBodyTextPart() !== null && $message->getBodyTextPart() !== ''
			&& $message->getBodyHtmlPart() !== null && $message->getBodyHtmlPart() !== '';
		if ( ! in_array( $contentType, ['', 'text/plain', 'text/html'], true ) && ! $parsedAlternative ) {
			throw new RuntimeException( esc_html__( 'Bird does not support this message content type. Use text/plain or text/html, or supply separate text and HTML body parts.', 'post-smtp' ) );
		}
		$charset = strtolower( trim( (string) $message->getCharset() ) );
		if ( ! in_array( $charset, ['', 'utf-8', 'utf8', 'us-ascii', 'ascii'], true ) ) {
			throw new RuntimeException( esc_html__( 'Bird requires UTF-8 or US-ASCII email content. Update the message charset and body before sending.', 'post-smtp' ) );
		}
		$category = $this->settings['category'] ?? 'transactional';
		if ( ! in_array( $category, ['transactional', 'marketing'], true ) ) {
			throw new RuntimeException( esc_html__( 'Choose a valid Bird email category.', 'post-smtp' ) );
		}
		$payload = [
			'from' => self::address( $sender ),
			'subject' => $message->getSubject(),
			'category' => $category,
			'track_opens' => false,
			'track_clicks' => false,
		];
		foreach ( ['to' => 'getToRecipients', 'cc' => 'getCcRecipients', 'bcc' => 'getBccRecipients'] as $field => $getter ) {
			$recipients = (array) $message->$getter();
			if ( $recipients ) {
				$payload[$field] = array_map( [self::class, 'address'], $recipients );
			}
		}
		foreach ( ['html' => 'getBodyHtmlPart', 'text' => 'getBodyTextPart'] as $field => $getter ) {
			$body = $message->$getter();
			if ( $body !== null && $body !== '' ) {
				$payload[$field] = $body;
			}
		}
		if ( $message->getReplyToRaw() ) {
			$replies = PostmanEmailAddress::convertToArray( $message->getReplyToRaw() );
			$payload['reply_to'] = array_map( static function ( $reply ) { return self::address( new PostmanEmailAddress( $reply ) ); }, $replies );
		}
		if ( ! PostmanOptions::getInstance()->isStealthModeEnabled() ) {
			$metadata = apply_filters( 'postman_get_plugin_metadata', null );
			$payload['headers']['X-Mailer'] = sprintf( 'Postman SMTP %s for WordPress (%s)', $metadata['version'], 'https://wordpress.org/plugins/post-smtp/' );
		}
		foreach ( (array) $message->getHeaders() as $header ) {
			$name = strtolower( $header['name'] );
			if ( $name === 'x-bird-idempotency-key' ) {
				$idempotencyKey = $header['content'];
				if ( $idempotencyKey === '' || strlen( $idempotencyKey ) > 255 || preg_match( '/[\r\n]/', $idempotencyKey ) ) {
					throw new RuntimeException( esc_html__( 'Use an idempotency key of 1 to 255 characters without line breaks.', 'post-smtp' ) );
				}
			} elseif ( $name === 'x-mailer' ) {
				$payload['headers']['X-Mailer'] = $header['content'];
			} elseif ( $name !== 'mime-version' ) {
				// The delivery provider owns MIME-Version and rejects it as a custom header.
				$payload['headers'][$header['name']] = $header['content'];
			}
		}
		if ( $message->getMessageId() && ( $idempotencyKey === null || ! $message->isMessageIdGenerated() ) ) {
			$payload['headers']['Message-ID'] = $message->getMessageId();
		}
		if ( $message->getDate() ) {
			$payload['headers']['Date'] = $message->getDate();
		}
		$attachments = $message->getAttachments();
		if ( is_string( $attachments ) ) {
			$attachments = preg_split( '/\r?\n/', $attachments );
		}
		$encodedSize = 0;
		foreach ( (array) $attachments as $file ) {
			if ( $file === '' ) {
				continue;
			}
			if ( ! is_string( $file ) || strpos( $file, '://' ) !== false || ! is_file( $file ) || ! is_readable( $file ) ) {
				throw new RuntimeException( esc_html__( 'A Bird email attachment is not a readable local file.', 'post-smtp' ) );
			}
			$size = filesize( $file );
			if ( $size === false || $size > 15000000 || $encodedSize + 4 * ceil( $size / 3 ) > 20000000 ) {
				throw new RuntimeException( esc_html__( 'The Bird email attachments exceed the message size limit.', 'post-smtp' ) );
			}
			$contents = file_get_contents( $file );
			if ( $contents === false ) {
				throw new RuntimeException( esc_html__( 'Could not read a Bird email attachment.', 'post-smtp' ) );
			}
			$encodedSize += 4 * ceil( strlen( $contents ) / 3 );
			$attachment = ['filename' => basename( $file ), 'content' => base64_encode( $contents )];
			$type = wp_check_filetype( $file );
			if ( ! empty( $type['type'] ) ) {
				$attachment['content_type'] = $type['type'];
			}
			$payload['attachments'][] = $attachment;
		}
		return $payload;
	}

	private static function diagnostic( $value, $key ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = str_replace( $key, '[redacted]', $value );
		$value = preg_replace( '/\b(?:bk|bt)_[A-Za-z0-9]+_[A-Za-z0-9_-]+/', '[redacted]', $value );
		return substr( trim( preg_replace( '/[^\x20-\x7E]/', '', strip_tags( $value ) ) ), 0, 160 );
	}

	public function send( PostmanMessage $message ) {
		$this->transcript = '';
		try {
			$key = $this->settings['api_key'] ?? '';
			$endpoint = PostmanBirdSettings::endpoint( $key );
			$idempotencyKey = null;
			$body = wp_json_encode( $this->payload( $message, $idempotencyKey ) );
			if ( $body === false || strlen( $body ) > 21000000 ) {
				throw new RuntimeException( esc_html__( 'The Bird email could not be encoded within the request size limit.', 'post-smtp' ) );
			}
			$headers = ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
			if ( $idempotencyKey !== null ) {
				$headers['Idempotency-Key'] = $idempotencyKey;
			}
			$response = wp_remote_post( $endpoint . '/v1/email/messages', [
				'headers' => $headers,
				'body' => $body,
				'timeout' => 30,
				'redirection' => 0,
				'sslverify' => true,
				'data_format' => 'body',
				'limit_response_size' => 65536,
			] );
			if ( is_wp_error( $response ) ) {
				throw new RuntimeException( 'Bird: ' . $response->get_error_message() );
			}
			$status = wp_remote_retrieve_response_code( $response );
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $status !== 202 ) {
				$errorBody = isset( $data['error'] ) && is_array( $data['error'] ) ? $data['error'] : [];
				$detail = isset( $errorBody['message'] ) && is_string( $errorBody['message'] ) ? $errorBody['message'] : __( 'Request failed.', 'post-smtp' );
				if ( isset( $errorBody['remediation'] ) && is_string( $errorBody['remediation'] ) ) {
					$detail .= ' ' . $errorBody['remediation'];
				}
				if ( $status === 401 ) {
					$detail .= ' ' . __( 'Reconnect Bird with an active API key.', 'post-smtp' );
				}
				$requestId = self::diagnostic( $errorBody['request_id'] ?? '', $key ) ?: self::diagnostic( wp_remote_retrieve_header( $response, 'x-request-id' ), $key );
				$retryAfter = self::diagnostic( wp_remote_retrieve_header( $response, 'retry-after' ), $key );
				if ( $requestId !== '' ) {
					$detail .= ' Request ID: ' . $requestId . '.';
				}
				if ( $retryAfter !== '' ) {
					$detail .= ' Retry-After: ' . $retryAfter . '.';
				}
				throw new RuntimeException( 'Bird HTTP ' . $status . ': ' . $detail, $status );
			}
			if ( ! isset( $data['id'] ) || ! is_string( $data['id'] ) || ! preg_match( '/\Aem_[A-Za-z0-9]+\z/', $data['id'] ) ) {
				throw new RuntimeException( esc_html__( 'Bird accepted the request but returned no valid message ID. Check the Bird email log before retrying.', 'post-smtp' ) );
			}
			$this->transcript = 'Bird HTTP 202: accepted as ' . $data['id'] . '. Delivery is asynchronous.';
		} catch ( Exception $error ) {
			$detail = is_string( $key ) && $key !== '' ? str_replace( $key, '[redacted]', $error->getMessage() ) : $error->getMessage();
			$detail = preg_replace( '/\b(?:bk|bt)_[A-Za-z0-9]+_[A-Za-z0-9_-]+/', '[redacted]', $detail );
			$this->transcript = $detail;
			throw new RuntimeException( esc_html( $detail ), (int) $error->getCode() );
		}
	}
}
