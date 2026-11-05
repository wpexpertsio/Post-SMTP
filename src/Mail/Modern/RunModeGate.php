<?php
/**
 * Security run mode checks for the revamp mail pipeline.
 *
 * @package PostSMTP\Mail\Modern
 */

namespace PostSMTP\Mail\Modern;

use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RunModeGate {

	public static function currentMode(): string {
		if ( ! class_exists( '\PostmanOptions', false ) ) {
			require_once POST_SMTP_PATH . '/Postman/PostmanOptions.php';
		}

		return (string) PostmanOptions::getInstance()->getRunMode();
	}

	public static function isIgnore(): bool {
		return PostmanOptions::RUN_MODE_IGNORE === self::currentMode();
	}

	public static function isLogOnly(): bool {
		return PostmanOptions::RUN_MODE_LOG_ONLY === self::currentMode();
	}

	public static function isProduction(): bool {
		return PostmanOptions::RUN_MODE_PRODUCTION === self::currentMode();
	}

	public static function allowsOutboundDelivery(): bool {
		return self::isProduction();
	}
}
