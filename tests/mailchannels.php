<?php
/** Offline integration checks: php tests/mailchannels.php (PHP 7.0+). */
if ( PHP_SAPI !== 'cli' ) {
	exit;
}
define( 'ABSPATH', __DIR__ );
define( 'POST_SMTP_ASSETS', '/assets/' );
define( 'WP_DEBUG', true );
error_reporting( E_ALL & ~E_DEPRECATED );
set_error_handler( function ( $level, $message, $file, $line ) {
	if ( error_reporting() & $level ) {
		throw new ErrorException( $message, 0, $level, $file, $line );
	}
} );

// Only the WordPress environment is stubbed; message, options, transport,
// sanitizer, SDK, HTTP messages and serialization are the shipped code.
$stored_options = array();
function get_option( $name ) { return $GLOBALS['stored_options']; }
function is_multisite() { return false; }
function __( $text, $domain = '' ) { return $text; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_html__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_url( $text ) { return $text; }
function add_action( $name, $callback ) {}
function add_filter( $name, $callback ) {}
function apply_filters( $name, $value ) { return $value; }
function is_plugin_active( $name ) { return false; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_check_filetype( $file ) { return array( 'type' => false ); }
class PostmanLogger {
	const ERROR_INT = 400;
	public static $messages = array();
	public function __construct( $name ) {}
	public function __call( $name, $args ) {
		self::$messages = array_merge( self::$messages, $args );
		return false;
	}
}
class PostmanNonOAuthScribe { public function __construct( $host ) {} }
class PostmanSession {
	public static $action = '';
	public static function getInstance() { return new self(); }
	public function getAction() { return self::$action; }
}

$root = dirname( __DIR__ );
require $root . '/Postman/PostmanOptions.php';
require $root . '/Postman/PostmanUtils.php';
require $root . '/Postman/PostmanInputSanitizer.php';
require $root . '/Postman/Postman-Mail/PostmanModuleTransport.php';
require $root . '/Postman/Postman-Mail/PostmanTransportRegistry.php';
require $root . '/Postman/Postman-Mail/PostmanMailEngine.php';
require $root . '/Postman/Postman-Mail/PostmanMessage.php';
require $root . '/Postman/Postman-Mail/PostmanMailChannelsTransport.php';
require $root . '/Postman/Postman-Mail/PostmanMailChannelsMailEngine.php';
require $root . '/Postman/Wizard/NewWizard.php';

$checks = 0;
function check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	$GLOBALS['checks']++;
}
function configure( $options ) {
	$GLOBALS['stored_options'] = $options;
	PostmanOptions::getInstance()->reload();
}
function fails( $callback, $expected ) {
	try {
		$callback();
	} catch ( Exception $e ) {
		check( strpos( $e->getMessage(), $expected ) !== false, 'Unexpected error: ' . $e->getMessage() );
		return;
	}
	throw new RuntimeException( 'Expected failure: ' . $expected );
}

$transport = new PostmanMailChannelsTransport( '/postman-smtp.php' );
$transport->init();
check( ! $transport->isConfiguredAndReady(), 'Missing credentials must not be ready.' );
$key = 'mc-test-secret-not-a-real-key';
configure( array( 'mailchannels_api_key' => base64_encode( $key ), 'sender_email' => 'sender@example.com' ) );
$transport->init();
check( PostmanOptions::getInstance()->getMailChannelsApiKey() === $key, 'Stored key must decode.' );
check( $transport->prepareOptionsForExport( array() )['mailchannels_api_key'] === $key, 'Export must round trip through sanitizer.' );
ob_start();
$transport->mailchannelsApiKeyCallback();
$field = ob_get_clean();
check( strpos( $field, $key ) === false && strpos( $field, 'type="password"' ) !== false, 'Settings must mask the key.' );
check( strpos( $field, 'required' ) !== false, 'API key field must be required.' );
PostmanTransportRegistry::getInstance()->registerTransport( $transport );
$wizard = new Post_SMTP_New_Wizard();
check( strpos( $wizard->render_mailchannels_settings(), 'postman_options[mailchannels_api_key]' ) !== false, 'New wizard must render key field.' );
check( strpos( $wizard->render_mailchannels_settings(), $key ) === false, 'Wizard must mask existing key.' );

$sanitizer = new PostmanInputSanitizer();
foreach ( array( $key, str_repeat( '*', strlen( $key ) ) ) as $input ) {
	$output = array();
	$sanitizer->sanitizePassword( 'MailChannels API Key', 'mailchannels_api_key', array( 'mailchannels_api_key' => $input ), $output, $key, false );
	check( $output['mailchannels_api_key'] === base64_encode( $key ), 'New and masked keys must round trip.' );
}
PostmanSession::$action = 'already-sanitized';
$sanitizer->sanitizePassword( 'MailChannels API Key', 'mailchannels_api_key', $output, $output, $key, false );
check( $output['mailchannels_api_key'] === base64_encode( $key ), 'Second sanitization must not encode twice.' );
PostmanSession::$action = '';
check( strpos( implode( ' ', PostmanLogger::$messages ), $key ) === false, 'Sanitization must not log the key.' );
check( strpos( implode( ' ', PostmanLogger::$messages ), base64_encode( $key ) ) === false, 'Sanitization must not log the encoded key.' );

class TestMailChannelsEngine extends PostmanMailChannelsMailEngine {
	public $testClient;
	public function productionClient() { return parent::createClient(); }
	protected function createClient() { return $this->testClient ?: parent::createClient(); }
}
$engine = new TestMailChannelsEngine( $key );
if ( PHP_VERSION_ID < 80200 ) {
	check( ! $transport->isConfiguredAndReady(), 'Older PHP must report unavailability.' );
	fails( function () use ( $engine ) { $engine->send( new PostmanMessage() ); }, 'requires PHP 8.2' );
	check( ! class_exists( 'PostSMTP\\Vendor\\MailChannels\\Client', false ), 'Older PHP must not load SDK.' );
	echo "Passed {$checks} compatibility checks on PHP " . PHP_VERSION . "\n";
	exit;
}
check( $transport->isConfiguredAndReady(), 'Configured transport must be ready on PHP 8.2+.' );
$client = $engine->productionClient();
check( $client instanceof PostSMTP\Vendor\MailChannels\Client, 'Must load the official, isolated SDK.' );
check( $client->config()->baseUrl === 'https://api.mailchannels.net/tx/v1', 'Endpoint must be fixed.' );
check( $client->config()->httpClient->getConfig( 'timeout' ) > 0, 'HTTP requests must have a timeout.' );
check( ! class_exists( 'MailChannels\\Client', false ), 'Must not pollute the global SDK namespace.' );

class FakeMailChannelsHttp implements PostSMTP\Vendor\Psr\Http\Client\ClientInterface {
	public $requests = array();
	public $response;
	public function sendRequest( PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request ): PostSMTP\Vendor\Psr\Http\Message\ResponseInterface {
		$this->requests[] = $request;
		if ( $this->response instanceof Exception ) {
			throw $this->response;
		}
		return $this->response;
	}
}
$http = new FakeMailChannelsHttp();
$factory = new PostSMTP\Vendor\GuzzleHttp\Psr7\HttpFactory();
$engine->testClient = new PostSMTP\Vendor\MailChannels\Client( $key, 'https://api.mailchannels.net/tx/v1', $http, $factory, $factory );
function response( $status, $body, $headers = array() ) {
	return new PostSMTP\Vendor\GuzzleHttp\Psr7\Response( $status, $headers, json_encode( $body ) );
}
function accepted() {
	return response( 202, array( 'request_id' => 'request-123', 'results' => array( array( 'index' => 0, 'status' => 'sent', 'message_id' => 'message-123' ) ) ) );
}
$message = new PostmanMessage();
$message->setFrom( 'sender@example.com', 'Sender' );
$message->addTo( 'Recipient <to@example.com>' );
$message->addTo( 'TO@example.com' );
$message->addCc( 'cc@example.com' );
$message->addCc( 'to@example.com' );
$message->addBcc( 'hidden@example.com' );
$message->setReplyTo( 'Support <reply@example.com>' );
$message->setSubject( 'Order confirmation ✓' );
$message->setBodyTextPart( '0' );
$message->setBodyHtmlPart( '<p>Private message body</p>' );
$message->addHeaders( array( 'X-Order-ID: order-123', 'Message-ID: post-smtp-generated@example.com' ) );
$file = tempnam( sys_get_temp_dir(), 'mailchannels-' );
file_put_contents( $file, "\x00\x01\xffattachment" );
$message->setAttachments( $file . "\r\n" );
try {
	$http->response = accepted();
	$engine->send( $message );
	$request = $http->requests[0];
	$payload = json_decode( (string) $request->getBody(), true );
	check( $request->getMethod() === 'POST' && (string) $request->getUri() === 'https://api.mailchannels.net/tx/v1/send', 'SDK must POST to /send.' );
	check( $request->getHeaderLine( 'X-Api-Key' ) === $key, 'SDK must authenticate.' );
	check( count( $payload['personalizations'][0]['to'] ) === 1, 'Recipients must deduplicate.' );
	check( count( $payload['personalizations'][0]['cc'] ) === 1, 'CC must deduplicate across recipient groups.' );
	check( $payload['personalizations'][0]['bcc'][0]['email'] === 'hidden@example.com', 'BCC must preserve blind recipients.' );
	check( $payload['reply_to'] === array( 'email' => 'reply@example.com', 'name' => 'Support' ), 'Reply-to must preserve address and name.' );
	check( $payload['content'][0]['value'] === '0' && $payload['content'][1]['type'] === 'text/html', 'Multipart body must preserve zero and HTML.' );
	check( $payload['headers'] === array( 'X-Order-ID' => 'order-123' ), 'Custom headers must survive without generated Message-ID.' );
	check( base64_decode( $payload['attachments'][0]['content'] ) === file_get_contents( $file ), 'Binary attachment must round trip.' );
	check( $payload['attachments'][0]['type'] === 'application/octet-stream', 'Unknown MIME type must have a fallback.' );
	check( strpos( $engine->getTranscript(), 'message-123' ) !== false, 'Transcript must identify message.' );
	foreach ( array( $key, 'hidden@example.com', 'Private message body', $payload['attachments'][0]['content'] ) as $secret ) {
		check( strpos( $engine->getTranscript(), $secret ) === false, 'Transcript must omit sensitive payload.' );
	}
	foreach ( array( 400, 401, 403, 413, 429, 500 ) as $status ) {
		$http->response = response( $status, array( 'message' => 'Rejected ' . $key ), array( 'X-Request-ID' => 'failure-123', 'Retry-After' => '60' ) );
		$before = count( $http->requests );
		fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'Rejected [redacted]' );
		check( count( $http->requests ) === $before + 1, 'Must not silently retry a send.' );
		check( strpos( $engine->getTranscript(), 'HTTP ' . $status ) !== false, 'Transcript must identify HTTP status.' );
		check( strpos( $engine->getTranscript(), 'failure-123' ) !== false, 'Transcript must identify failed request.' );
		check( strpos( $engine->getTranscript(), $key ) === false, 'Error transcript must redact credentials.' );
	}
	check( strpos( $engine->getTranscript(), 'Retry-After: 60' ) !== false, 'Retry metadata must be preserved.' );
	$http->response = response( 202, array( 'results' => array( array( 'status' => 'failed', 'reason' => 'Sender domain is not authorized' ) ) ) );
	fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'Sender domain is not authorized' );
	foreach ( array( response( 202, array() ), response( 200, array( 'data' => array( 'dry run' ) ) ), response( 202, array( 'results' => 'invalid' ) ) ) as $bad_response ) {
		$http->response = $bad_response;
		fails( function () use ( $engine, $message ) { $engine->send( $message ); }, '' );
	}
	$http->response = new PostSMTP\Vendor\GuzzleHttp\Exception\ConnectException( 'Connection timed out', $request );
	fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'Connection timed out' );
	$http->response = accepted();
	$before = count( $http->requests );
	foreach ( array( '/missing-mailchannels-attachment', 'https://example.com/file' ) as $invalid_file ) {
		$message->setAttachments( array( $invalid_file ) );
		fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'could not read an attachment' );
	}
	check( count( $http->requests ) === $before, 'Unreadable attachments must fail before sending.' );
	$message->setAttachments( array() );
	$message->setBodyHtmlPart( null );
	$engine->send( $message );
	$payload = json_decode( (string) end( $http->requests )->getBody(), true );
	check( count( $payload['content'] ) === 1 && ! isset( $payload['attachments'] ), 'Plain text without attachments must work.' );
	$message->setBodyTextPart( null );
	$message->setBodyHtmlPart( '<p>HTML only</p>' );
	$engine->send( $message );
	$payload = json_decode( (string) end( $http->requests )->getBody(), true );
	check( count( $payload['content'] ) === 1 && $payload['content'][0]['type'] === 'text/html', 'HTML-only mail must work.' );
	$message->setAttachments( array( $file ) );
	$engine->send( $message );
	$payload = json_decode( (string) end( $http->requests )->getBody(), true );
	check( count( $payload['attachments'] ) === 1, 'Array attachments must work.' );
	$before = count( $http->requests );
	$message->setBodyHtmlPart( null );
	fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'content' );
	$message->setBodyTextPart( 'Body' );
	$message->setSubject( '' );
	fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'subject' );
	$message->setSubject( 'Subject' );
	$message->addHeaders( array( 'Content-Transfer-Encoding: base64' ) );
	fails( function () use ( $engine, $message ) { $engine->send( $message ); }, 'reserved message headers' );
	check( count( $http->requests ) === $before, 'SDK validation errors must fail before HTTP.' );
} finally {
	unlink( $file );
}
define( 'POST_SMTP_API_KEY', 'constant-key' );
check( PostmanOptions::getInstance()->getMailChannelsApiKey() === 'constant-key', 'Constant credentials must take precedence.' );
echo "Passed {$checks} checks on PHP " . PHP_VERSION . "\n";
