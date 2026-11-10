<?php
/**
 * Revamp Connections configure schemas for Pro mailers (Amazon SES, Zoho).
 *
 * UI-only: loads Figma schemas from Post SMTP Pro revamp provider classes.
 * Does not register send/auth providers (Pro hooks handle that when licensed).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RevampProMailerSchemas {

	public static function init(): void {
		add_action(
			'plugins_loaded',
			static function () {
				if ( ! CohortResolver::usesReactAdmin() ) {
					return;
				}

				add_filter( 'post_smtp_provider_schema', array( self::class, 'filterSchema' ), 50, 2 );
			},
			25
		);
	}

	/**
	 * @param array<string,mixed> $schema Provider schema.
	 * @param string              $slug   Provider slug.
	 * @return array<string,mixed>
	 */
	public static function filterSchema( array $schema, string $slug ): array {
		if ( 'amazon_ses_api' === $slug ) {
			$figma = self::amazonSesOptions();
			return is_array( $figma ) ? $figma : $schema;
		}

		if ( 'zohomail_api' === $slug ) {
			$figma = self::zohoMailOptions();
			return is_array( $figma ) ? $figma : $schema;
		}

		return $schema;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function amazonSesOptions(): ?array {
		if ( ! self::loadProClass( 'includes/revamp/AmazonSesProvider.php', 'Post_SMTP_Pro_Amazon_Ses_Provider' ) ) {
			return null;
		}

		return \Post_SMTP_Pro_Amazon_Ses_Provider::getOptions();
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function zohoMailOptions(): ?array {
		if ( ! self::loadProClass( 'includes/revamp/ZohoMailProvider.php', 'Post_SMTP_Pro_Zoho_Mail_Provider' ) ) {
			return null;
		}

		return \Post_SMTP_Pro_Zoho_Mail_Provider::getOptions();
	}

	private static function loadProClass( string $relative_path, string $class_name ): bool {
		$fqcn = '\\' . ltrim( $class_name, '\\' );

		if ( class_exists( $fqcn, false ) ) {
			return true;
		}

		if ( ! defined( 'POST_SMTP_PRO_PATH' ) ) {
			return false;
		}

		$path = POST_SMTP_PRO_PATH . $relative_path;
		if ( ! is_readable( $path ) ) {
			return false;
		}

		if ( ! interface_exists( '\PostSMTP\Kernel\Providers\ProviderInterface', false ) && defined( 'POST_SMTP_PATH' ) ) {
			$iface = POST_SMTP_PATH . '/kernel/Providers/ProviderInterface.php';
			if ( is_readable( $iface ) ) {
				require_once $iface;
			}
		}

		require_once $path;

		return class_exists( $fqcn, false );
	}
}
