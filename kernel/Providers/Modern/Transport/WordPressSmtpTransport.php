<?php
/**
 * SMTP delivery via WordPress PHPMailer (no legacy MailEngine).
 *
 * @package PostSMTP\Kernel\Providers\Modern\Transport
 */

namespace PostSMTP\Kernel\Providers\Modern\Transport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WordPressSmtpTransport {

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @param array<string,mixed> $processed  Normalized envelope data.
	 * @return array{success:bool,message:string,send:bool,error_code?:int|string,transcript?:string}
	 */
	public function send( array $connection, array $processed ): array {
		$mailer = $this->createMailer( $connection );
		if ( is_wp_error( $mailer ) ) {
			return array(
				'success'    => false,
				'message'    => $mailer->get_error_message(),
				'send'       => false,
				'error_code' => $mailer->get_error_code(),
			);
		}

		$from_email = sanitize_email( (string) ( $connection['sender_email'] ?? $connection['from_email'] ?? '' ) );
		$from_name  = sanitize_text_field( (string) ( $connection['sender_name'] ?? $connection['from_name'] ?? '' ) );

		try {
			$mailer->setFrom( $from_email, $from_name, false );
		} catch ( \Exception $exception ) {
			return array(
				'success'    => false,
				'message'    => $exception->getMessage(),
				'send'       => false,
				'error_code' => 500,
			);
		}

		foreach ( (array) ( $processed['to'] ?? array() ) as $recipient ) {
			if ( empty( $recipient['email'] ) ) {
				continue;
			}
			$mailer->addAddress( $recipient['email'], $recipient['name'] ?? '' );
		}

		$mailer->Subject = (string) ( $processed['subject'] ?? '' );
		$mailer->Body    = (string) ( $processed['message'] ?? '' );
		$mailer->isHTML( true );

		$this->applyHeaders( $mailer, $processed['headers'] ?? '' );
		$this->applyAttachments( $mailer, $processed['attachments'] ?? array() );

		$sent = $mailer->send();
		if ( ! $sent ) {
			return array(
				'success'    => false,
				'message'    => $mailer->ErrorInfo ?: __( 'SMTP send failed.', 'post-smtp' ),
				'send'       => false,
				'error_code' => 500,
				'transcript' => $mailer->ErrorInfo,
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'Email sent successfully.', 'post-smtp' ),
			'send'       => true,
			'transcript' => '',
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return \PHPMailer\PHPMailer\PHPMailer|\WP_Error
	 */
	private function createMailer( array $connection ) {
		if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		$hostname = (string) ( $connection['hostname'] ?? '' );
		$port     = (int) ( $connection['port'] ?? 587 );
		if ( '' === $hostname || $port <= 0 ) {
			return new \WP_Error( 'post_smtp_smtp_config', __( 'SMTP host and port are required.', 'post-smtp' ) );
		}

		$mailer             = new \PHPMailer\PHPMailer\PHPMailer( true );
		$mailer->CharSet    = 'UTF-8';
		$mailer->isSMTP();
		$mailer->Host       = $hostname;
		$mailer->Port       = $port;
		$mailer->SMTPAuth   = ! empty( $connection['basic_auth_username'] );
		$mailer->Username   = (string) ( $connection['basic_auth_username'] ?? '' );
		$mailer->Password   = (string) ( $connection['basic_auth_password'] ?? '' );
		$mailer->SMTPSecure = $this->resolveEncryption( $connection, $port );
		$mailer->Timeout    = 30;

		return $mailer;
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 */
	private function resolveEncryption( array $connection, int $port ): string {
		$enc = strtolower( (string) ( $connection['enc_type'] ?? '' ) );
		if ( in_array( $enc, array( 'ssl', 'tls', 'none' ), true ) ) {
			return 'none' === $enc ? '' : $enc;
		}
		if ( 465 === $port ) {
			return 'ssl';
		}
		return 'tls';
	}

	/**
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer  Mailer instance.
	 * @param string|array<int,string>       $headers Header lines.
	 */
	private function applyHeaders( $mailer, $headers ): void {
		if ( empty( $headers ) ) {
			return;
		}

		$lines = $this->headerLines( $headers );
		foreach ( $lines as $line ) {
			if ( ! is_string( $line ) || false === strpos( $line, ':' ) ) {
				continue;
			}
			list( $name, $value ) = explode( ':', $line, 2 );
			$name  = trim( $name );
			$value = trim( preg_replace( '/[\r\n]+/', ' ', (string) $value ) );
			if ( '' === $name || '' === $value ) {
				continue;
			}

			$lower = strtolower( $name );
			if ( in_array( $lower, array( 'from', 'to', 'subject', 'mime-version', 'date', 'message-id' ), true ) ) {
				continue;
			}

			if ( 'content-type' === $lower ) {
				if ( false !== stripos( $value, 'text/plain' ) ) {
					$mailer->isHTML( false );
				}
				continue;
			}

			if ( 'cc' === $lower ) {
				foreach ( preg_split( '/[,;]/', $value ) as $address ) {
					$address = trim( $address );
					if ( '' !== $address ) {
						$mailer->addCC( $address );
					}
				}
				continue;
			}

			if ( 'bcc' === $lower ) {
				foreach ( preg_split( '/[,;]/', $value ) as $address ) {
					$address = trim( $address );
					if ( '' !== $address ) {
						$mailer->addBCC( $address );
					}
				}
				continue;
			}

			if ( 'reply-to' === $lower ) {
				$mailer->addReplyTo( $value );
				continue;
			}

			try {
				$mailer->addCustomHeader( $name, $value );
			} catch ( \Exception $exception ) {
				continue;
			}
		}
	}

	/**
	 * @param string|array<int,mixed> $headers wp_mail headers argument.
	 * @return list<string>
	 */
	private function headerLines( $headers ): array {
		if ( is_string( $headers ) ) {
			$headers = str_replace( array( "\r\n", "\r" ), "\n", $headers );
			return array_values(
				array_filter(
					array_map( 'trim', explode( "\n", $headers ) ),
					static function ( $line ) {
						return '' !== $line;
					}
				)
			);
		}

		$lines = array();
		foreach ( (array) $headers as $header ) {
			if ( is_string( $header ) ) {
				foreach ( $this->headerLines( $header ) as $line ) {
					$lines[] = $line;
				}
			}
		}

		return $lines;
	}

	/**
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer      Mailer instance.
	 * @param string|array<int,string>       $attachments Attachment paths.
	 */
	private function applyAttachments( $mailer, $attachments ): void {
		foreach ( (array) $attachments as $attachment ) {
			if ( is_string( $attachment ) && is_readable( $attachment ) ) {
				$mailer->addAttachment( $attachment );
			}
		}
	}
}
