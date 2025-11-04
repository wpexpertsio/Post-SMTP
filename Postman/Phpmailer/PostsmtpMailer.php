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
			
			// Improve authentication compatibility
			$authType = $this->options->getAuthenticationType();
			if ( $authType === 'login' || $authType === 'plain' ) {
				// Explicitly set auth type for better compatibility
				$mail->AuthType = strtoupper($authType);
			}
			
			// Disable automatic TLS upgrade which can cause auth issues
			$mail->SMTPAutoTLS = false;
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

		// build the email log entry
		$log = new PostmanEmailLog();
		$log->originalTo = $to;
		$log->originalSubject = $subject;
		$log->originalMessage = $body;
		$log->originalHeaders = $headers;

		// get the transport and create the transportConfig and engine
		$transport = PostmanTransportRegistry::getInstance()->getActiveTransport();

		add_filter( 'postman_wp_mail_result', [ $this, 'postman_wp_mail_result' ] );

		try {

			$response = false;

			if ( $send_email = apply_filters( 'post_smtp_do_send_email', true ) && apply_filters( 'post_smtp_send_email', true ) ) {
				if ( $this->options->getTransportType() !== 'smtp' ) {
					$result = $postmanWpMail->send( $to, $subject, $body, $headers, $attachments );
				} else {
					$response = $this->sendSmtp();
					$result = $response;
					
					if( $response ) {
						do_action( 'post_smtp_on_success', $log, $postmanMessage, $this->transcript, $transport );
					} else {
						// Log SMTP failures
						$errorMessage = $this->ErrorInfo ?: 'SMTP sending failed';
						do_action( 'post_smtp_on_failed', $log, $postmanMessage, $this->transcript, $transport, $errorMessage );
					}
				}
			} else {
				$result = true; // Return success when bypassed by filters
				// Log bypassed emails for tracking
				do_action( 'post_smtp_on_success', $log, $postmanMessage, 'Email bypassed by filter', $transport );
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

	public function sendSmtp() {
		try {
			if (!$this->preSend()) {
				return false;
			}
			return $this->postSend();
		} catch (Exception $e) {
			// If authentication fails, try with LOGIN method as fallback
			if (strpos($e->getMessage(), 'authentication') !== false || 
				strpos($e->getMessage(), 'AUTH') !== false ||
				strpos($e->getMessage(), 'login') !== false) {
				
				$this->transcript .= "\nAuthentication failed, attempting fallback...\n";
				
				// Reset connection and try with LOGIN auth
				$this->getSMTPInstance()->reset();
				$this->AuthType = 'LOGIN';
				$this->SMTPAutoTLS = false;
				
				try {
					if (!$this->preSend()) {
						return false;
					}
					return $this->postSend();
				} catch (Exception $e2) {
					// If still failing, throw the original exception
					throw $e;
				}
			}
			throw $e;
		}
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