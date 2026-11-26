<?php
/**
 * Multisite feature bootstrap.
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteModule {

	public static function init(): void {
		MultisiteConnectionGuard::init();
		MultisiteCapabilities::init();
		MultisiteAdminExperience::init();
		MultisiteNetworkUi::init();
		MultisiteEmailLogSupport::init();
	}
}