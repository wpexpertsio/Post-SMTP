<?php
/**
 * Demo entry point disabled for security.
 *
 * The plugin loads QR generation via qrlib.php only; this file must not be web-accessible.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

status_header( 403 );
exit;
