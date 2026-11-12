<?php
/**
 * Prevent Local WP Live Link from corrupting serialized Post SMTP options.
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LiveLinkOptionGuard {

	/** @var string[] */
	private const GUARDED_OPTIONS = array(
		'postman_options',
		'postman_office365_oauth',
		'postman_connections',
	);

	/**
	 * Persist an option while Local's pre_update_option host rewriter is detached.
	 *
	 * @param string              $option Option name.
	 * @param mixed               $value  Value to store.
	 * @param string|bool|null    $autoload Autoload flag.
	 */
	public static function updateOption( string $option, $value, $autoload = null ): bool {
		$detach = in_array( $option, self::GUARDED_OPTIONS, true );

		if ( ! $detach ) {
			if ( null === $autoload ) {
				return update_option( $option, $value );
			}

			return update_option( $option, $value, $autoload );
		}

		$removed = self::detachLiveLinkPreUpdateFilter();
		try {
			if ( null === $autoload ) {
				return update_option( $option, $value );
			}

			return update_option( $option, $value, $autoload );
		} finally {
			self::reattachLiveLinkPreUpdateFilter( $removed );
		}
	}

	/**
	 * @return list<array{0:callable,int}>
	 */
	private static function detachLiveLinkPreUpdateFilter(): array {
		$removed = array();

		if ( ! isset( $GLOBALS['wp_filter']['pre_update_option'] ) ) {
			return $removed;
		}

		$hook = $GLOBALS['wp_filter']['pre_update_option'];
		if ( ! $hook instanceof \WP_Hook ) {
			return $removed;
		}

		foreach ( $hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if (
					is_array( $function )
					&& isset( $function[1] )
					&& 'make_link_local' === $function[1]
				) {
					remove_filter( 'pre_update_option', $function, (int) $priority );
					$removed[] = array( $function, (int) $priority );
				}
			}
		}

		return $removed;
	}

	/**
	 * @param list<array{0:callable,int}> $removed Detached callbacks.
	 */
	private static function reattachLiveLinkPreUpdateFilter( array $removed ): void {
		foreach ( $removed as $pair ) {
			add_filter( 'pre_update_option', $pair[0], $pair[1] );
		}
	}
}
