<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
	if ( ! class_exists( '\PHPMailer', false ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
	}

} else {
	if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
	}

	if ( ! class_exists( '\PHPMailer\PHPMailer\Exception', false ) ) {
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
	}

	if ( ! class_exists( '\PHPMailer\PHPMailer\SMTP', false ) ) {
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	}

	class_alias( PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
	class_alias( PHPMailer\PHPMailer\SMTP::class, 'SMTP' );
	class_alias( PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
}


add_action('plugins_loaded', function() {
	global $phpmailer;

	$phpmailer = new PostsmtpMailer(true);
});

class PostsmtpMailer extends PHPMailer {

	private $mail_args = array();

	private $options;

	private $error;

	private $transcript = '';

	public function __construct($exceptions = null)
	{
		parent::__construct($exceptions);

		$this->set_vars();
		$this->hooks();

	}

	public function set_vars() {
		$this->options = PostmanOptions::getInstance();
		$this->Debugoutput = function($str, $level) {
			$this->transcript .= $str;
		};
	}

	public function hooks() {
		add_filter( 'wp_mail', array( $this, 'get_mail_args' ) );
		if ( $this->options->getTransportType() == 'smtp' ) {
			add_action( 'phpmailer_init', array( $this, 'phpmailer_smtp_init' ), 999 );
		}
	}

	public function get_mail_args( $atts ) {
		$this->mail_args = array();
		$this->mail_args[] = @$atts['to'];
		$this->mail_args[] = @$atts['subject'];
		$this->mail_args[] = @$atts['message'];
		$this->mail_args[] = @$atts['headers'];
		$this->mail_args[] = @$atts['attachments'];

		return $atts;
	}

	/**
	 * @param PHPMailer $mail
	 */
	public function phpmailer_smtp_init($mail) {
		$mail->SMTPDebug = 3;
		$mail->isSMTP();
		$mail->Host = $this->options->getHostname();
		$mail->Hostname = $this->options->getHostname();

		if ( $this->options->getAuthenticationType() !== 'none' ) {
			$mail->SMTPAuth   = true;
			$mail->Username   = $this->options->getUsername();
			$mail->Password   = $this->options->getPassword();
		}

		if ( $this->options->getEncryptionType() !== 'none' ) {
			$mail->SMTPSecure = $this->options->getEncryptionType();
		}

		$mail->Port = $this->options->getPort();

		if ( $this->options->isPluginSenderEmailEnforced() ) {
			$mail->setFrom( $this->options->getMessageSenderEmail() , $this->options->getMessageSenderName () );
		}
	}

	public function send()
	{
		require_once dirname(__DIR__) . '/PostmanWpMail.php';

		// create a PostmanWpMail instance
		$postmanWpMail = new PostmanWpMail();
		$postmanWpMail->init();

		list($to, $subject, $body, $headers, $attachments) = array_pad( $this->mail_args, 5, null );

		// build the message
		$postmanMessage = $postmanWpMail->processWpMailCall( $to, $subject, $body, $headers, $attachments );

		/*
		 * Build the email log entry from the *final* PostmanMessage, after the
		 * wp_mail filter has run, so the log matches what PHPMailer actually sends.
		 */
		$log = new PostmanEmailLog();
		$log->originalTo      = $this->format_recipients_for_log( $postmanMessage->getToRecipients() );
		$log->originalSubject = $postmanMessage->getSubject();
		$log->originalMessage = $postmanMessage->getBody();
		$log->originalHeaders = $this->flatten_headers_for_log( $postmanMessage );

		// get the transport and create the transportConfig and engine
		$transport = PostmanTransportRegistry::getInstance()->getActiveTransport();

		add_filter( 'postman_wp_mail_result', [ $this, 'postman_wp_mail_result' ] );

		try {

			$response = false;

			if ( $send_email = apply_filters( 'post_smtp_do_send_email', true ) ) {
				$result = $this->options->getTransportType() !== 'smtp' ?
					$postmanWpMail->send( $to, $subject, $body, $headers, $attachments ) :
					$response = $this->sendSmtp();

					if( $response ) {

						do_action( 'post_smtp_on_success', $log, $postmanMessage, $this->transcript, $transport );

					}

			}

			return $result;

		} catch (Exception $exc) {

			$this->error = $exc;

			$this->mailHeader = '';

			$this->setError($exc->getMessage());

            do_action( 'post_smtp_on_failed', $log, $postmanMessage,  $this->transcript, $transport, $exc->getMessage() );

			if ($this->exceptions) {
				throw $exc;
			}
			return false;
		}

	}

	/**
	 * Convert PostmanEmailAddress recipients to a string suitable for logging
	 * and for passing back into wp_mail() when resending.
	 *
	 * @param array $recipients Array of PostmanEmailAddress objects.
	 *
	 * @return string Comma separated list of recipients in "Name <email>" format.
	 */
	private function format_recipients_for_log( $recipients ) {
		if ( empty( $recipients ) || ! is_array( $recipients ) ) {
			return '';
		}

		$emails = array();

		foreach ( $recipients as $recipient ) {
			if ( $recipient instanceof PostmanEmailAddress ) {
				$emails[] = $recipient->format();
			}
		}

		return implode( ', ', $emails );
	}

	/**
	 * Build a wp_mail()-compatible headers string from the PostmanMessage.
	 *
	 * @param PostmanMessage $message
	 *
	 * @return string Header lines separated by "\r\n".
	 */
	private function flatten_headers_for_log( PostmanMessage $message ) {
		$lines = array();

		foreach ( (array) $message->getHeaders() as $header ) {
			if ( isset( $header['name'], $header['content'] ) && '' !== trim( $header['content'] ) ) {
				$lines[] = trim( $header['name'] ) . ': ' . trim( $header['content'] );
			}
		}

		if ( $message->getReplyTo() instanceof PostmanEmailAddress ) {
			$lines[] = 'Reply-To: ' . $message->getReplyTo()->format();
		}

		if ( $message->getContentType() ) {
			$header = 'Content-Type: ' . $message->getContentType();
			if ( $message->getCharset() ) {
				$header .= '; charset=' . $message->getCharset();
			}
			$lines[] = $header;
		}

		return implode( "\r\n", array_unique( $lines ) );
	}

	public function sendSmtp() {
		if (!$this->preSend()) {
			return false;
		}
		return $this->postSend();
	}


	public  function postman_wp_mail_result() {
		$result = [
			'time' => '',
			'exception' => $this->error,
			'transcript' => $this->transcript,
		];
		return $result;
	}
}