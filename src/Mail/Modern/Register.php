<?php
/**
 * Registers the connections-based mail pipeline (wp_mail handler + options reconciler).
 *
 * @package PostSMTP\Mail\Modern
 */

namespace PostSMTP\Mail\Modern;

use PostSMTP\Mail\Modern\Settings\OptionsReconciler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Register {

	public static function init(): void {
		WpMailHandler::init();
		OptionsReconciler::init();
	}
}
