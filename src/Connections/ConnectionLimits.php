<?php
/**
 * Plan limits for connections (Free vs Pro).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionLimits {

	public const FREE_MAX_CONNECTIONS        = 10;
	public const PRO_MAX_CONNECTIONS         = 10;
	public const FREE_MAX_ENABLED_FALLBACKS  = 1;
	public const PRO_MAX_ENABLED_FALLBACKS   = 2;

	public static function isPro(): bool {
		if ( defined( 'POST_SMTP_PRO_VERSION' ) ) {
			return true;
		}
		if ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() ) {
			return true;
		}
		return (bool) apply_filters( 'post_smtp_is_pro', false );
	}

	/**
	 * @return array{is_pro:bool,max_connections:int,max_enabled_fallbacks:int,max_fallback_slot_index:int}
	 */
	public static function get(): array {
		$is_pro = self::isPro();
		$max_fallbacks = $is_pro ? self::PRO_MAX_ENABLED_FALLBACKS : self::FREE_MAX_ENABLED_FALLBACKS;
		return array(
			'is_pro'                   => $is_pro,
			'max_connections'          => $is_pro ? self::PRO_MAX_CONNECTIONS : self::FREE_MAX_CONNECTIONS,
			'max_enabled_fallbacks'    => $max_fallbacks,
			'max_fallback_slot_index'  => $max_fallbacks,
		);
	}
}
