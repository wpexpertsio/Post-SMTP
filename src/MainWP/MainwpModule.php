<?php
/**
 * MainWP feature bootstrap.
 *
 * @package PostSMTP\MainWP
 */

namespace PostSMTP\MainWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MainwpModule {

	public static function init(): void {
		MainwpConnectionGuard::init();
		MainwpMailBridge::init();
	}
}