<?php
/**
 * Reproduce the bundled SDK from an official v2.2.0 source checkout.
 * Usage (PHP 8.2+): php tools/vendor-mailchannels.php /path/to/sdk
 */

if ( PHP_SAPI !== 'cli' || PHP_VERSION_ID < 80200 ) {
	exit( "Run with PHP 8.2+ on the command line.\n" );
}

$source = isset( $argv[1] ) ? realpath( $argv[1] ) : false;
if ( ! $source || ! is_file( $source . '/src/Client.php' ) ) {
	exit( "Provide the official SDK source directory.\n" );
}

$target = dirname( __DIR__ ) . '/Postman/Postman-Mail/libs/vendor_prefixed/mailchannels/mailchannels-php';
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source . '/src', FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	$relative = substr( $file->getPathname(), strlen( $source ) + 1 );
	// Framework plugins have optional dependencies that WordPress does not use.
	if ( strpos( $relative, 'src/Plugins/' ) === 0 || $file->getExtension() !== 'php' ) {
		continue;
	}
	$output = '';
	foreach ( token_get_all( file_get_contents( $file->getPathname() ) ) as $token ) {
		if ( ! is_array( $token ) ) {
			$output .= $token;
			continue;
		}
		list( $type, $text ) = $token;
		// Change only PHP names, preserving upstream strings, comments and logic.
		if ( in_array( $type, array( T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING ), true ) &&
			preg_match( '/^\\\\?(MailChannels|Psr|Http|GuzzleHttp|Symfony)(\\\\|$)/', $text ) ) {
			$text = ( $text[0] === '\\' ? '\\' : '' ) . 'PostSMTP\\Vendor\\' . ltrim( $text, '\\' );
		}
		$output .= $text;
	}
	$destination = $target . '/' . $relative;
	if ( ! is_dir( dirname( $destination ) ) ) {
		mkdir( dirname( $destination ), 0777, true );
	}
	file_put_contents( $destination, $output );
}
copy( $source . '/LICENSE', $target . '/LICENSE' );
copy( $source . '/composer.json', $target . '/composer.json' );
