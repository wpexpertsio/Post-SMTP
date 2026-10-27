<?php
/**
 * Feature flags for phased rollout.
 *
 * @package PostSMTP\Rollout
 */

namespace PostSMTP\Rollout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FeatureFlags {

	/**
	 * @param string $flag Flag name.
	 */
	public static function enabled( string $flag ): bool {
		$flags = get_option(
			'post_smtp_feature_flags',
			array(
				'react_admin'       => false,
				'new_mail_pipeline' => false,
			)
		);

		if ( ! is_array( $flags ) ) {
			return false;
		}

		if ( isset( $flags[ $flag ] ) ) {
			return (bool) $flags[ $flag ];
		}

		return (bool) apply_filters( 'post_smtp_feature_flag_' . $flag, false );
	}

	/**
	 * @param string $flag  Flag name.
	 * @param bool   $value Enabled state.
	 */
	public static function set( string $flag, bool $value ): void {
		$flags         = get_option( 'post_smtp_feature_flags', array() );
		$flags         = is_array( $flags ) ? $flags : array();
		$flags[ $flag ] = $value;
		update_option( 'post_smtp_feature_flags', $flags );
	}
}
