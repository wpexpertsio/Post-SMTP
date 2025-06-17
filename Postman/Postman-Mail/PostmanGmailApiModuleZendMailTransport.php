<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use \PostSMTP\Vendor\Google\Service\Gmail\Message;
use \PostSMTP\Vendor\Google\Http\MediaFileUpload;

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Postman_Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 *
 * @see Postman_Zend_Mime
 */
// require_once 'Zend/Mime.php';

/**
 *
 * @see Postman_Zend_Mail_Protocol_Smtp
 */
// require_once 'Zend/Mail/Protocol/Smtp.php';

/**
 *
 * @see Postman_Zend_Mail_Transport_Abstract
 */
// require_once 'Zend/Mail/Transport/Abstract.php';

/**
 * SMTP connection object
 *
 * Loads an instance of Postman_Zend_Mail_Protocol_Smtp and forwards smtp transactions
 *
 * @category Zend
 * @package Postman_Zend_Mail
 * @subpackage Transport
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */
if (! class_exists ( 'PostmanGmailApiModuleZendMailTransport' )) {
	class PostmanGmailApiModuleZendMailTransport extends Postman_Zend_Mail_Transport_Abstract {
		const SERVICE_OPTION = 'service';
		const MESSAGE_SENDER_EMAIL_OPTION = 'sender_email';
		private $logger;
		private $message;
		private $transcript;
		private $api_base_url;
		
		/**
		 * EOL character string used by transport
		 *
		 * @var string
		 * @access public
		 */
		public $EOL = "\n";
		
		/**
		 * Remote smtp hostname or i.p.
		 *
		 * @var string
		 */
		protected $_host;
		
		/**
		 * Port number
		 *
		 * @var integer|null
		 */
		protected $_port;
		
		/**
		 * Local client hostname or i.p.
		 *
		 * @var string
		 */
		protected $_name = 'localhost';
		
		/**
		 * Authentication type OPTIONAL
		 *
		 * @var string
		 */
		protected $_auth;
		
		/**
		 * Config options for authentication
		 *
		 * @var array
		 */
		protected $_config;
		
		/**
		 * Instance of Postman_Zend_Mail_Protocol_Smtp
		 *
		 * @var Postman_Zend_Mail_Protocol_Smtp
		 */
		protected $_connection;
		
		/**
		 * Constructor.
		 *
		 * @param string $host
		 *        	OPTIONAL (Default: 127.0.0.1)
		 * @param array $config
		 *        	OPTIONAL (Default: null)
		 * @return void
		 *
		 * @todo Someone please make this compatible
		 *       with the SendMail transport class.
		 */
		public function __construct($host = '127.0.0.1', Array $config = array()) {
			if (isset ( $config ['name'] )) {
				$this->_name = $config ['name'];
			}
			if (isset ( $config ['port'] )) {
				$this->_port = $config ['port'];
			}
			if (isset ( $config ['auth'] )) {
				$this->_auth = $config ['auth'];
			}
			
			$this->_host = $host;
			$this->_config = $config;
			$this->logger = new PostmanLogger ( get_class ( $this ) );
			// Define API base URL for middleware.
			$this->api_base_url = 'https://connect.postmansmtp.com/wp-json/gmail-oauth/v1/send-email';
			// Check if Gmail One Click is enabled.
			$this->gmail_oneclick_enabled = in_array(
				'gmail-oneclick',
				isset( get_option( 'post_smtp_pro', array() )['extensions'] )
					? get_option( 'post_smtp_pro', array() )['extensions']
					: array(),
				true
			);
		}
		
		/**
		 * Class destructor to ensure all open connections are closed
		 *
		 * @return void
		 */
		public function __destruct() {
			if ($this->_connection instanceof Postman_Zend_Mail_Protocol_Smtp) {
				try {
					$this->_connection->quit ();
				} catch ( Postman_Zend_Mail_Protocol_Exception $e ) {
					// ignore
				}
				$this->_connection->disconnect ();
			}
		}
		
		/**
		 * Sets the connection protocol instance
		 *
		 * @param Postman_Zend_Mail_Protocol_Abstract $client        	
		 *
		 * @return void
		 */
		public function setConnection(Postman_Zend_Mail_Protocol_Abstract $connection) {
			$this->_connection = $connection;
		}
		
		/**
		 * Gets the connection protocol instance
		 *
		 * @return Postman_Zend_Mail_Protocol_Abstract|null
		 */
		public function getConnection() {
			return $this->_connection;
		}
		
		/**
		 * Send an email via the Gmail API
		 *
		 * Uses URI https://www.googleapis.com
		 *
		 *
		 * @return void
		 * @todo Rename this to sendMail, it's a public method...
		 */
		public function _sendMail() {

			// Prepare the message in message/rfc822
			$message = $this->header . Postman_Zend_Mime::LINEEND . $this->body;
			$this->message = $message;
			// The message needs to be encoded in Base64URL
			$encodedMessage = rtrim ( strtr ( base64_encode ( $message ), '+/', '-_' ), '=' );
		
 
			$file_size = strlen($message);

			$result = array ();
			try {
				if ( $this->gmail_oneclick_enabled ) {
				  // Prepare payload.
					$payload = array(
						'message' => $encodedMessage,
						'headers' => $this->header,
						'site_url'  => get_site_url(),
					);
					
					$response = wp_remote_post(
						$this->api_base_url,
						array(
							'method'    => 'POST',
							'body'      => wp_json_encode( $payload ),
							'headers'   => array(
								'Content-Type' => 'application/json',
							),
							'timeout'   => 30,
						)
					);

					$body           = wp_remote_retrieve_body( $response );
					$result_output  = json_decode( $body, true );
					$result         = isset( $result_output['data'] ) ? $result_output['data'] : array();
   					
					// ✅ Check for HTTP errors.
					if ( is_wp_error( $response ) ) {
						throw new Exception( 'Error in PostSMTP GMAIL API Request: ' . $response->get_error_message() );
					}

					$response_code = wp_remote_retrieve_response_code( $response );
					$body = wp_remote_retrieve_body( $response );
					$result_output = json_decode( $body, true );
					if ( $response_code !== 200 || empty( $result_output ) ) {
						
    				$error_code = $response_code;

			    	throw new Exception("PostSMTP GMAIL API Error: $error_message (HTTP Code: $error_code)");
						
					}
					// ✅ Ensure email send response contains "data".
					if ( !isset( $result_output['data'] ) ) {
						throw new Exception( "PostSMTP GMAIL API Error: Missing 'data' key in response: " . print_r( $result_output, true ) );
					}
					
					$result = $result_output['data'];
				}else{
				    $googleApiMessage = new Message ();
				    $googleService = $this->_config [self::SERVICE_OPTION];
				    $googleClient = $googleService->getClient();
				    $googleClient->setDefer(true);
				    $result = $googleService->users_messages->send ( 'me', $googleApiMessage, array('uploadType' => 'resumable') );	
					$chunkSizeBytes = 1 * 1024 * 1024;

					// create mediafile upload
					$media = new MediaFileUpload(
						$googleClient,
						$result,
						'message/rfc822',
						$message,
						true,
						$chunkSizeBytes
					);
					$media->setFileSize($file_size);

					$status = false;
					while (! $status) {
						$status = $media->nextChunk();
					}
					$result = false;

					// Reset to the client to execute requests immediately in the future.
					$googleClient->setDefer(false);

					$googleMessageId = $status->getId();
				}
				if ($this->logger->isInfo ()) {
					$this->logger->info ( sprintf ( 'Message %d accepted for delivery', PostmanState::getInstance ()->getSuccessfulDeliveries () + 1 ) );
				}
				$this->transcript = print_r ( $result, true );
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= $message;
			} catch ( Exception $e ) {
				$this->transcript = $e->getMessage ();
				$this->transcript .= PostmanModuleTransport::RAW_MESSAGE_FOLLOWS;
				$this->transcript .= $message;
				throw $e;
			}
		}

		public function getMessage() {
			return $this->message;
		}
		public function getTranscript() {
			return $this->transcript;
		}
		
		/**
		 * Format and fix headers
		 *
		 * Some SMTP servers do not strip BCC headers. Most clients do it themselves as do we.
		 *
		 * @access protected
		 * @param array $headers        	
		 * @return void
		 * @throws Postman_Zend_Transport_Exception
		 */
		protected function _prepareHeaders($headers) {
			if (! $this->_mail) {
				/**
				 *
				 * @see Postman_Zend_Mail_Transport_Exception
				 */
				// require_once 'Zend/Mail/Transport/Exception.php';
				throw new Postman_Zend_Mail_Transport_Exception ( '_prepareHeaders requires a registered Postman_Zend_Mail object' );
			}
			
			// google will unset the Bcc header for us.
			// unset ( $headers ['Bcc'] );
			
			// Prepare headers
			parent::_prepareHeaders ( $headers );
		}
	}
}