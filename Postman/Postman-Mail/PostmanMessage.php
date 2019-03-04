<?php
if ( ! class_exists( 'PostmanMessage' ) ) {

	require_once 'PostmanEmailAddress.php';

	/**
	 * This class knows how to interface with Wordpress
	 * including loading/saving to the database.
	 *
	 * The various Transports available:
	 * http://framework.zend.com/manual/current/en/modules/zend.mail.smtp.options.html
	 *
	 * @author jasonhendriks
	 */
	class PostmanMessage {
		const EOL = "\r\n";

		// logger for all concrete classes - populate with setLogger($logger)
		protected $logger;

		// set by the caller
		private $from;
		private $replyTo;
		private $toRecipients;
		private $ccRecipients;
		private $bccRecipients;
		private $subject;
		private $body;
		private $bodyTextPart;
		private $bodyHtmlPart;
		private $headers;
		private $attachments;
		private $date;
		private $messageId;

		// determined by the send() method
		private $isTextHtml;
		private $contentType = 'text/plain';
		private $charset;

		private $boundary;

		/**
		 * No-argument constructor
		 */
		function __construct() {
			$this->logger = new PostmanLogger( get_class( $this ) );
			$this->headers = array();
			$this->toRecipients = array();
			$this->ccRecipients = array();
			$this->bccRecipients = array();
		}

		function __get( $name ) {
			$message = __( '<code>%1$s</code> property of a <code>PostmanMessage</code> object is <strong>not supported</strong>. For now all of this class properties are private.', Postman::TEXT_DOMAIN );

			if ( WP_DEBUG ) {
				trigger_error( sprintf( $message, $name ) );
			}
		}

		function __call($name, $args) {
			$class = new ReflectionClass(__CLASS__);
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC );

			$message = __( '<code>%1$s</code> method of a <code>PostmanMessage</code> object is <strong>not supported</strong>. Use one of the following methods <pre><code>%2$s</code></pre>', Postman::TEXT_DOMAIN );

			if ( WP_DEBUG ) {
				trigger_error( sprintf( $message, $name, print_r( $methods, true ) ) );
			}
		}

		/**
		 *
		 * @return boolean
		 */
		public function isBodyPartsEmpty() {
			return empty( $this->bodyTextPart ) && empty( $this->bodyHtmlPart );
		}

		/**
		 *
		 * @param PostmanModuleTransport $transport
		 */
		public function validate( PostmanModuleTransport $transport ) {
			if ( $transport->isEmailValidationSupported() ) {
				$this->internalValidate();
			}
		}

		/**
		 * Create body parts based on content type
		 * MyMail creates its own body parts
		 */
		public function createBodyParts() {

			// modify the content-type to include the boundary
			if ( false !== stripos( $this->contentType, 'multipart' ) && ! empty( $this->boundary ) ) {
				// Lines in email are terminated by CRLF ("\r\n") according to RFC2821
				$this->contentType = sprintf( "%s;\r\n\t boundary=\"%s\"", $this->contentType, $this->getBoundary() );
			}

			$body = $this->getBody();
			$contentType = $this->getContentType();
			// add the message content as either text or html
			if ( empty( $contentType ) || substr( $contentType, 0, 10 ) === 'text/plain' ) {
				$this->logger->debug( 'Creating text body part' );
				$this->setBodyTextPart( $body );
			} else if ( substr( $contentType, 0, 9 ) === 'text/html' ) {
				$this->logger->debug( 'Creating html body part' );
				$this->setBodyHtmlPart( $body );
			} else if ( substr( $contentType, 0, 21 ) === 'multipart/alternative' ) {
				$this->logger->debug( 'Adding body as multipart/alternative' );
				$arr = explode( PHP_EOL, $body );
				$textBody = '';
				$htmlBody = '';
				$mode = '';
				foreach ( $arr as $s ) {
					$this->logger->trace( 'mode: ' . $mode . ' bodyline: ' . $s );
					if ( substr( $s, 0, 25 ) === 'Content-Type: text/plain;' ) {
						$mode = 'foundText';
					} else if ( substr( $s, 0, 24 ) === 'Content-Type: text/html;' ) {
						$mode = 'foundHtml';
					} else if ( $mode == 'textReading' ) {
						$textBody .= $s;
					} else if ( $mode == 'htmlReading' ) {
						$htmlBody .= $s;
					} else if ( $mode == 'foundText' ) {
						$trim = trim( $s );
						if ( empty( $trim ) ) {
							$mode = 'textReading';
						}
					} else if ( $mode == 'foundHtml' ) {
						$trim = trim( $s );
						if ( empty( $trim ) ) {
							$mode = 'htmlReading';
						}
					}
				}
				$this->setBodyHtmlPart( $htmlBody );
				$this->setBodyTextPart( $textBody );
			} else {
				$this->logger->error( 'Unknown content-type: ' . $contentType );
				$this->setBodyTextPart( $body );
			}
		}

		/**
		 * Apply the WordPress filters to the email
		 */
		public function applyFilters() {
			if ( $this->logger->isDebug() ) {
				$this->logger->debug( 'Applying WordPress filters' );
			}

			/**
			 * Filter the email address to send from.
			 *
			 * @since 2.2.0
			 *
			 * @param string $from_email
			 *        	Email address to send from.
			 */
			$filteredEmail = apply_filters( 'wp_mail_from', $this->getFromAddress()->getEmail() );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'wp_mail_from: ' . $filteredEmail );
			}
			if ( $this->getFromAddress()->getEmail() !== $filteredEmail ) {
				$this->logger->debug( sprintf( 'Filtering From email address: before=%s after=%s', $this->getFromAddress()->getEmail(), $filteredEmail ) );
				$this->getFromAddress()->setEmail( $filteredEmail );
			}

			/**
			 * Filter the name to associate with the "from" email address.
			 *
			 * @since 2.3.0
			 *
			 * @param string $from_name
			 *        	Name associated with the "from" email address.
			 */
			$filteredName = apply_filters( 'wp_mail_from_name', $this->getFromAddress()->getName() );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'wp_mail_from_name: ' . $filteredName );
			}
			if ( $this->getFromAddress()->getName() !== $filteredName ) {
				$this->logger->debug( sprintf( 'Filtering From email name: before=%s after=%s', $this->getFromAddress()->getName(), $filteredName ) );
				$this->getFromAddress()->setName( $filteredName );
			}

			/**
			 * Filter the default wp_mail() charset.
			 *
			 * @since 2.3.0
			 *
			 * @param string $charset
			 *        	Default email charset.
			 */
			$filteredCharset = apply_filters( 'wp_mail_charset', $this->getCharset() );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( 'wp_mail_charset: ' . $filteredCharset );
			}
			if ( $this->getCharset() !== $filteredCharset ) {
				$this->logger->debug( sprintf( 'Filtering Charset: before=%s after=%s', $this->getCharset(), $filteredCharset ) );
				$this->setCharset( $filteredCharset );
			}

			/**
			 * Filter the wp_mail() content type.
			 *
			 * @since 2.3.0
			 *
			 * @param string $content_type
			 *        	Default wp_mail() content type.
			 */
			$filteredContentType = apply_filters( 'wp_mail_content_type', $this->getContentType() );
			if ( $this->logger->isTrace() ) {
				$this->logger->trace( sprintf( 'wp_mail_content_type: "%s"', $filteredContentType ) );
			}
			if ( $this->getContentType() != $filteredContentType ) {
				$this->logger->debug( sprintf( 'Filtering Content-Type: before=%s after=%s', $this->getContentType(), $filteredContentType ) );
				$this->setContentType( $filteredContentType );
			}

			// Postman has it's own 'user override' filter
			$options = PostmanOptions::getInstance();
			$forcedEmailAddress = $options->getMessageSenderEmail();
			if ( $options->isSenderEmailOverridePrevented() && $this->getFromAddress()->getEmail() !== $forcedEmailAddress ) {
				$this->logger->debug( sprintf( 'Forced From email address: before=%s after=%s', $this->getFromAddress()->getEmail(), $forcedEmailAddress ) );
				$this->getFromAddress()->setEmail( $forcedEmailAddress );
			}

			if ( $options->is_fallback ) {
				$fallback_email = $options->getFallbackFromEmail();
				$this->logger->debug( sprintf( 'Fallback: Forced From email address: before=%s after=%s', $this->getFromAddress()->getEmail(), $fallback_email ) );
				$this->getFromAddress()->setEmail( $fallback_email );
			}

			$forcedEmailName = $options->getMessageSenderName();
			if ( $options->isSenderNameOverridePrevented() && $this->getFromAddress()->getName() !== $forcedEmailName ) {
				$this->logger->debug( sprintf( 'Forced From email name: before=%s after=%s', $this->getFromAddress()->getName(), $forcedEmailName ) );
				$this->getFromAddress()->setName( $forcedEmailName );
			}
		}

		/**
		 * Check all email headers for errors
		 * Throw an exception if an error is found
		 */
		private function internalValidate() {
			// check the reply-to address for errors
			if ( isset( $this->replyTo ) ) {
				$this->getReplyTo()->validate( 'Reply-To' );
			}

			// check the from address for errors
			$this->getFromAddress()->validate( 'From' );

			// validate the To recipients
			foreach ( ( array ) $this->getToRecipients() as $toRecipient ) {
				$toRecipient->validate( 'To' );
			}

			// validate the Cc recipients
			foreach ( ( array ) $this->getCcRecipients() as $ccRecipient ) {
				$ccRecipient->validate( 'Cc' );
			}

			// validate the Bcc recipients
			foreach ( ( array ) $this->getBccRecipients() as $bccRecipient ) {
				$bccRecipient->validate( 'Bcc' );
			}
		}

		/**
		 *
		 * @return PostmanEmailAddress
		 */
		public function getFromAddress() {
			return $this->from;
		}

		/**
		 * Get the charset, checking first the WordPress bloginfo, then the header, then the wp_mail_charset filter.
		 *
		 * @return string
		 */
		public function getCharset() {
			return $this->charset;
		}

		/**
		 * Set the charset
		 *
		 * @param unknown $charset
		 */
		public function setCharset( $charset ) {
			$this->charset = $charset;
		}

		/**
		 * Get the content type, checking first the header, then the wp_mail_content_type filter
		 *
		 * @return string
		 */
		public function getContentType() {
			return $this->contentType;
		}
		public function setContentType( $contentType ) {
			$this->contentType = $contentType;
		}
		/**
		 *
		 * @param unknown $recipients
		 *        	Array or comma-separated list of email addresses to send message.
		 * @throws Exception
		 */
		public function addTo( $to ) {
			$this->addRecipients( $this->toRecipients, $to );
		}
		/**
		 *
		 * @param unknown $recipients
		 *        	Array or comma-separated list of email addresses to send message.
		 * @throws Exception
		 */
		public function addCc( $cc ) {
			$this->addRecipients( $this->ccRecipients, $cc );
		}
		/**
		 *
		 * @param unknown $recipients
		 *        	Array or comma-separated list of email addresses to send message.
		 * @throws Exception
		 */
		public function addBcc( $bcc ) {
			$this->addRecipients( $this->bccRecipients, $bcc );
		}
		/**
		 *
		 * @param unknown $recipients
		 *        	Array or comma-separated list of email addresses to send message.
		 * @throws Exception
		 */
		private function addRecipients( &$recipientList, $recipients ) {
			if ( ! empty( $recipients ) ) {
				$recipients = PostmanEmailAddress::convertToArray( $recipients );
				foreach ( $recipients as $recipient ) {
					if ( ! empty( $recipient ) ) {
						$this->logger->debug( sprintf( 'User added recipient: "%s"', $recipient ) );
						array_push( $recipientList, new PostmanEmailAddress( $recipient ) );
					}
				}
			}
		}

		/**
		 * For the string version, each header line (beginning with From:, Cc:, etc.) is delimited with a newline ("\r\n")
		 */
		public function addHeaders( $headers ) {
			if ( ! is_array( $headers ) ) {
				// WordPress may send a string where "each header line (beginning with From:, Cc:, etc.) is delimited with a newline ("\r\n") (advanced)"
				// this converts that string to an array
				$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
				// $headers = explode ( PHP_EOL, $headers );
			}
			// otherwise WordPress sends an array
			foreach ( $headers as $header ) {
				if ( ! empty( $header ) ) {
					// boundary may be in a header line, but it's not a header
					// eg. boundary="----=_NextPart_DC7E1BB5...
					if ( strpos( $header, ':' ) === false ) {
						if ( false !== stripos( $header, 'boundary=' ) ) {
							$parts = preg_split( '/boundary=/i', trim( $header ) );
							$this->boundary = trim( str_replace( array(
									"'",
									'"',
							), '', $parts [1] ) );
							$this->logger->debug( sprintf( 'Processing special boundary header \'%s\'', $this->getBoundary() ) );
						} else {
							$this->logger->debug( sprintf( 'Ignoring broken header \'%s\'', $header ) );
						}
						continue;
					}
					list ( $name, $content ) = explode( ':', trim( $header ), 2 );
					$this->processHeader( $name, $content );
				}
			}
		}

		/**
		 * Add the headers that were processed in processHeaders()
		 * Zend requires that several headers are specially handled.
		 *
		 * @param unknown           $name
		 * @param unknown           $value
		 * @param Postman_Zend_Mail $mail
		 */
		private function processHeader( $name, $content ) {
			$name = trim( $name );
			$content = trim( $content );
			switch ( strtolower( $name ) ) {
				case 'content-type' :
					$this->logProcessHeader( 'Content-Type', $name, $content );
					if ( strpos( $content, ';' ) !== false ) {
						list ( $type, $charset ) = explode( ';', $content );
						$this->setContentType( trim( $type ) );
						if ( false !== stripos( $charset, 'charset=' ) ) {
							$charset = trim( str_replace( array(
									'charset=',
									'"',
							), '', $charset ) );
						} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
							$this->boundary = trim( str_replace( array(
									'BOUNDARY=',
									'boundary=',
									'"',
							), '', $charset ) );
							$charset = '';
						}
						if ( ! empty( $charset ) ) {
							$this->setCharset( $charset );
						}
					} else {
						$this->setContentType( trim( $content ) );
					}
					break;
				case 'to' :
					$this->logProcessHeader( 'To', $name, $content );
					$this->addTo( $content );
					break;
				case 'cc' :
					$this->logProcessHeader( 'Cc', $name, $content );
					$this->addCc( $content );
					break;
				case 'bcc' :
					$this->logProcessHeader( 'Bcc', $name, $content );
					$this->addBcc( $content );
					break;
				case 'from' :
					$this->logProcessHeader( 'From', $name, $content );
					$this->setFrom( $content );
					break;
				case 'subject' :
					$this->logProcessHeader( 'Subject', $name, $content );
					$this->setSubject( $content );
					break;
				case 'reply-to' :
					$this->logProcessHeader( 'Reply-To', $name, $content );
					$this->setReplyTo( $content );
					break;
				case 'sender' :
					$this->logProcessHeader( 'Sender', $name, $content );
					$this->logger->warn( sprintf( 'Ignoring Sender header \'%s\'', $content ) );
					break;
				case 'return-path' :
					$this->logProcessHeader( 'Return-Path', $name, $content );
					$this->logger->warn( sprintf( 'Ignoring Return-Path header \'%s\'', $content ) );
					break;
				case 'date' :
					$this->logProcessHeader( 'Date', $name, $content );
					$this->setDate( $content );
					break;
				case 'message-id' :
					$this->logProcessHeader( 'Message-Id', $name, $content );
					$this->setMessageId( $content );
					break;
				default :
					// Add it to our grand headers array
					$this->logProcessHeader( 'other', $name, $content );
					array_push( $this->headers, array(
							'name' => $name,
							'content' => $content,
					) );
					break;
			}
		}

		/**
		 *
		 * @param unknown $desc
		 * @param unknown $name
		 * @param unknown $content
		 */
		private function logProcessHeader( $desc, $name, $content ) {
			$this->logger->debug( 'Processing ' . $desc . ' Header - ' . $name . ': ' . $content );
		}

		/**
		 * Add attachments to the message
		 *
		 * @param Postman_Zend_Mail $mail
		 */
		public function addAttachmentsToMail( Postman_Zend_Mail $mail ) {
			$attachments = $this->attachments;
			if ( ! is_array( $attachments ) ) {
				// WordPress may a single filename or a newline-delimited string list of multiple filenames
				$attArray = explode( PHP_EOL, $attachments );
			} else {
				$attArray = $attachments;
			}
			// otherwise WordPress sends an array
			foreach ( $attArray as $file ) {
				if ( ! empty( $file ) ) {
					$this->logger->debug( 'Adding attachment: ' . $file );
					$at = new Postman_Zend_Mime_Part( file_get_contents( $file ) );
					// $at->type = 'image/gif';
					$at->disposition = Postman_Zend_Mime::DISPOSITION_ATTACHMENT;
					$at->encoding = Postman_Zend_Mime::ENCODING_BASE64;
					$at->filename = basename( $file );
					$mail->addAttachment( $at );
				}
			}
		}
		function setBody( $body ) {
			$this->body = $body;
		}
		function setBodyTextPart( $bodyTextPart ) {
			$this->bodyTextPart = $bodyTextPart;
		}
		function setBodyHtmlPart( $bodyHtmlPart ) {
			$this->bodyHtmlPart = $bodyHtmlPart;
		}
		function setSubject( $subject ) {
			$this->subject = $subject;
		}
		function setAttachments( $attachments ) {
			$this->attachments = $attachments;
		}
		function setFrom( $email, $name = null ) {
			if ( ! empty( $email ) ) {
				$this->from = new PostmanEmailAddress( $email, $name );
			}
		}
		function setReplyTo( $replyTo ) {
			if ( ! empty( $replyTo ) ) {
				$this->replyTo = new PostmanEmailAddress( $replyTo );
			}
		}
		function setMessageId( $messageId ) {
			$this->messageId = $messageId;
		}
		function setDate( $date ) {
			$this->date = $date;
		}

		// return the headers
		public function getHeaders() {
			return $this->headers;
		}
		public function getBoundary() {
			return $this->boundary;
		}
		public function getToRecipients() {
			return $this->toRecipients;
		}
		public function getCcRecipients() {
			return $this->ccRecipients;
		}
		public function getBccRecipients() {
			return $this->bccRecipients;
		}
		public function getReplyTo() {
			return $this->replyTo;
		}
		public function getDate() {
			return $this->date;
		}
		public function getMessageId() {
			return $this->messageId;
		}
		public function getSubject() {
			return $this->subject;
		}
		public function getBody() {
			return $this->body;
		}
		public function getBodyTextPart() {
			return $this->bodyTextPart;
		}
		public function getBodyHtmlPart() {
			return $this->bodyHtmlPart;
		}
		public function getAttachments() {
			return $this->attachments;
		}

		/**
		 * @todo
		 * is this right? maybe extending the phpmailer class insted?
		 */

		/**
		 * Add an embedded (inline) attachment from a file.
		 * This can include images, sounds, and just about any other document type.
		 * These differ from 'regular' attachments in that they are intended to be
		 * displayed inline with the message, not just attached for download.
		 * This is used in HTML messages that embed the images
		 * the HTML refers to using the $cid value.
		 * Never use a user-supplied path to a file!
		 * @param string $path Path to the attachment.
		 * @param string $cid Content ID of the attachment; Use this to reference
		 *        the content when using an embedded image in HTML.
		 * @param string $name Overrides the attachment name.
		 * @param string $encoding File encoding (see $Encoding).
		 * @param string $type File MIME type.
		 * @param string $disposition Disposition to use
		 * @return boolean True on successfully adding an attachment
		 */
		public function addEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = '', $disposition = 'inline') {
			if (!@is_file($path)) {
				return false;
			}

			// If a MIME type is not specified, try to work it out from the file name
			if ($type == '') {
				$type = self::filenameToType($path);
			}

			$filename = basename($path);
			if ($name == '') {
				$name = $filename;
			}

			// Append to $attachment array
			$this->attachments[] = array(
				0 => $path,
				1 => $filename,
				2 => $name,
				3 => $encoding,
				4 => $type,
				5 => false, // isStringAttachment
				6 => $disposition,
				7 => $cid
			);

			return true;
		}

		/**
		 * Get the MIME type for a file extension.
		 * @param string $ext File extension
		 * @access public
		 * @return string MIME type of file.
		 * @static
		 */
		public static function _mime_types($ext = '')
		{
			$mimes = array(
				'xl'    => 'application/excel',
				'js'    => 'application/javascript',
				'hqx'   => 'application/mac-binhex40',
				'cpt'   => 'application/mac-compactpro',
				'bin'   => 'application/macbinary',
				'doc'   => 'application/msword',
				'word'  => 'application/msword',
				'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
				'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
				'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
				'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
				'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
				'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
				'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
				'class' => 'application/octet-stream',
				'dll'   => 'application/octet-stream',
				'dms'   => 'application/octet-stream',
				'exe'   => 'application/octet-stream',
				'lha'   => 'application/octet-stream',
				'lzh'   => 'application/octet-stream',
				'psd'   => 'application/octet-stream',
				'sea'   => 'application/octet-stream',
				'so'    => 'application/octet-stream',
				'oda'   => 'application/oda',
				'pdf'   => 'application/pdf',
				'ai'    => 'application/postscript',
				'eps'   => 'application/postscript',
				'ps'    => 'application/postscript',
				'smi'   => 'application/smil',
				'smil'  => 'application/smil',
				'mif'   => 'application/vnd.mif',
				'xls'   => 'application/vnd.ms-excel',
				'ppt'   => 'application/vnd.ms-powerpoint',
				'wbxml' => 'application/vnd.wap.wbxml',
				'wmlc'  => 'application/vnd.wap.wmlc',
				'dcr'   => 'application/x-director',
				'dir'   => 'application/x-director',
				'dxr'   => 'application/x-director',
				'dvi'   => 'application/x-dvi',
				'gtar'  => 'application/x-gtar',
				'php3'  => 'application/x-httpd-php',
				'php4'  => 'application/x-httpd-php',
				'php'   => 'application/x-httpd-php',
				'phtml' => 'application/x-httpd-php',
				'phps'  => 'application/x-httpd-php-source',
				'swf'   => 'application/x-shockwave-flash',
				'sit'   => 'application/x-stuffit',
				'tar'   => 'application/x-tar',
				'tgz'   => 'application/x-tar',
				'xht'   => 'application/xhtml+xml',
				'xhtml' => 'application/xhtml+xml',
				'zip'   => 'application/zip',
				'mid'   => 'audio/midi',
				'midi'  => 'audio/midi',
				'mp2'   => 'audio/mpeg',
				'mp3'   => 'audio/mpeg',
				'mpga'  => 'audio/mpeg',
				'aif'   => 'audio/x-aiff',
				'aifc'  => 'audio/x-aiff',
				'aiff'  => 'audio/x-aiff',
				'ram'   => 'audio/x-pn-realaudio',
				'rm'    => 'audio/x-pn-realaudio',
				'rpm'   => 'audio/x-pn-realaudio-plugin',
				'ra'    => 'audio/x-realaudio',
				'wav'   => 'audio/x-wav',
				'bmp'   => 'image/bmp',
				'gif'   => 'image/gif',
				'jpeg'  => 'image/jpeg',
				'jpe'   => 'image/jpeg',
				'jpg'   => 'image/jpeg',
				'png'   => 'image/png',
				'tiff'  => 'image/tiff',
				'tif'   => 'image/tiff',
				'eml'   => 'message/rfc822',
				'css'   => 'text/css',
				'html'  => 'text/html',
				'htm'   => 'text/html',
				'shtml' => 'text/html',
				'log'   => 'text/plain',
				'text'  => 'text/plain',
				'txt'   => 'text/plain',
				'rtx'   => 'text/richtext',
				'rtf'   => 'text/rtf',
				'vcf'   => 'text/vcard',
				'vcard' => 'text/vcard',
				'xml'   => 'text/xml',
				'xsl'   => 'text/xml',
				'mpeg'  => 'video/mpeg',
				'mpe'   => 'video/mpeg',
				'mpg'   => 'video/mpeg',
				'mov'   => 'video/quicktime',
				'qt'    => 'video/quicktime',
				'rv'    => 'video/vnd.rn-realvideo',
				'avi'   => 'video/x-msvideo',
				'movie' => 'video/x-sgi-movie'
			);
			if (array_key_exists(strtolower($ext), $mimes)) {
				return $mimes[strtolower($ext)];
			}
			return 'application/octet-stream';
		}

		/**
		 * Map a file name to a MIME type.
		 * Defaults to 'application/octet-stream', i.e.. arbitrary binary data.
		 * @param string $filename A file name or full path, does not need to exist as a file
		 * @return string
		 * @static
		 */
		public static function filenameToType($filename)
		{
			// In case the path is a URL, strip any query string before getting extension
			$qpos = strpos($filename, '?');
			if (false !== $qpos) {
				$filename = substr($filename, 0, $qpos);
			}
			$pathinfo = self::mb_pathinfo($filename);
			return self::_mime_types($pathinfo['extension']);
		}

		/**
		 * Multi-byte-safe pathinfo replacement.
		 * Drop-in replacement for pathinfo(), but multibyte-safe, cross-platform-safe, old-version-safe.
		 * Works similarly to the one in PHP >= 5.2.0
		 * @link http://www.php.net/manual/en/function.pathinfo.php#107461
		 * @param string $path A filename or path, does not need to exist as a file
		 * @param integer|string $options Either a PATHINFO_* constant,
		 *      or a string name to return only the specified piece, allows 'filename' to work on PHP < 5.2
		 * @return string|array
		 * @static
		 */
		public static function mb_pathinfo($path, $options = null)
		{
			$ret = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
			$pathinfo = array();
			if (preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $path, $pathinfo)) {
				if (array_key_exists(1, $pathinfo)) {
					$ret['dirname'] = $pathinfo[1];
				}
				if (array_key_exists(2, $pathinfo)) {
					$ret['basename'] = $pathinfo[2];
				}
				if (array_key_exists(5, $pathinfo)) {
					$ret['extension'] = $pathinfo[5];
				}
				if (array_key_exists(3, $pathinfo)) {
					$ret['filename'] = $pathinfo[3];
				}
			}
			switch ($options) {
				case PATHINFO_DIRNAME:
				case 'dirname':
					return $ret['dirname'];
				case PATHINFO_BASENAME:
				case 'basename':
					return $ret['basename'];
				case PATHINFO_EXTENSION:
				case 'extension':
					return $ret['extension'];
				case PATHINFO_FILENAME:
				case 'filename':
					return $ret['filename'];
				default:
					return $ret;
			}
		}

	}
}
