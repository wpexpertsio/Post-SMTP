<?php
/**
 * Multisite global-settings policy helpers.
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisitePolicy {

	public const OPTION_SYNCED     = 'post_smtp_multisite_synced';
	public const OPTION_SYNCED_AT  = 'post_smtp_multisite_synced_at';

	public const NETWORK_GLOBAL    = 'post_smtp_global_settings';
	public const NETWORK_OVERWRITE = 'post_smtp_allow_overwrite';

	public const ALLOWED_NETWORK_KEYS = array(
		self::NETWORK_GLOBAL,
		self::NETWORK_OVERWRITE,
	);

	public static function isMultisiteGlobalSettingsEnabled(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$network = get_site_option( \PostmanOptions::POSTMAN_NETWORK_OPTIONS, array() );
		if ( ! is_array( $network ) ) {
			return false;
		}

		return ! empty( $network[ self::NETWORK_GLOBAL ] ) && '1' === (string) $network[ self::NETWORK_GLOBAL ];
	}

	public static function getMainSiteBlogId(): int {
		if ( ! is_multisite() ) {
			return get_current_blog_id();
		}

		return (int) apply_filters( 'post_smtp_default_site_option', 1 );
	}

	public static function isNetworkMainSite( ?int $blog_id = null ): bool {
		$blog_id = $blog_id ?? get_current_blog_id();
		return (int) $blog_id === self::getMainSiteBlogId();
	}

	public static function isSyncInProgress(): bool {
		return (bool) apply_filters( 'post_smtp_multisite_sync_in_progress', false );
	}

	public static function shouldBlockChildMailerWrites(): bool {
		if ( self::isSyncInProgress() ) {
			return false;
		}

		if ( ! self::isMultisiteGlobalSettingsEnabled() ) {
			return false;
		}

		return ! self::isNetworkMainSite();
	}

	public static function isChildSyncedFlagSet( ?int $blog_id = null ): bool {
		$switched = false;
		if ( null !== $blog_id && get_current_blog_id() !== $blog_id ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		$flag = get_option( self::OPTION_SYNCED, '' );
		$set  = '1' === (string) $flag;

		if ( $switched ) {
			restore_current_blog();
		}

		return $set;
	}

	public static function isChildSyncedWithMain( ?int $blog_id = null ): bool {
		if ( ! is_multisite() || self::isNetworkMainSite( $blog_id ) ) {
			return false;
		}

		if ( ! self::isMultisiteGlobalSettingsEnabled() ) {
			return false;
		}

		if ( ! self::isChildSyncedFlagSet( $blog_id ) ) {
			return false;
		}

		return MultisiteSyncService::isSiteMailerConfigured( $blog_id ?? get_current_blog_id() );
	}

	/**
	 * @return array{success:false,message:string,code?:string}|null
	 */
	public static function blockedWriteResponse(): ?array {
		if ( ! self::shouldBlockChildMailerWrites() ) {
			return null;
		}

		return array(
			'success' => false,
			'message' => __( 'Mailer settings are managed on the main site. Ask a network administrator to sync sites.', 'post-smtp' ),
			'code'    => 'multisite_mailer_locked',
		);
	}

	/**
	 * Sanitize network option payload to allowed keys only.
	 *
	 * @param array<string,mixed> $options Raw network options.
	 * @return array<string,string>
	 */
	public static function sanitizeNetworkOptions( array $options ): array {
		$clean = array();
		foreach ( self::ALLOWED_NETWORK_KEYS as $key ) {
			if ( ! isset( $options[ $key ] ) ) {
				continue;
			}
			$value = $options[ $key ];
			if ( '1' === (string) $value ) {
				$clean[ $key ] = '1';
			}
		}

		return $clean;
	}
}