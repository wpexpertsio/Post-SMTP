<?php
/**
 * Wires revamp transport resolution into the legacy mail engine extension point.
 *
 * @package PostSMTP\Mail\Modern\Legacy
 */

namespace PostSMTP\Mail\Modern\Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RevampLegacyMailHooks {

	public static function init(): void {
		add_filter( 'post_smtp_wp_mail_transport', array( self::class, 'filterTransport' ), 10, 1 );
	}

	/**
	 * @param mixed $transport Existing transport from filter default (null).
	 * @return \PostmanModuleTransport|null
	 */
	public static function filterTransport( $transport ) {
		if ( $transport instanceof \PostmanModuleTransport ) {
			return $transport;
		}

		$resolved = RevampLegacyTransportResolver::resolve();
		return $resolved instanceof \PostmanModuleTransport ? $resolved : $transport;
	}
}