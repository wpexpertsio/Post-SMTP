<?php
/**
 * WordPress hooks for the Microsoft 365 Connections configure panel (free UI; Pro extends).
 *
 * @package PostSMTP\Connections
 */

namespace PostSMTP\Connections;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers default Office 365 configure bootstrap data and documents extension points for Post SMTP Pro.
 *
 * Filters:
 * - post_smtp_office365_configure_defaults — full `office365Configure` array for React admin bootstrap
 * - post_smtp_office365_configure_one_click — `office365Configure.oneClick` block (second arg: full defaults)
 * - post_smtp_office365_configure_manual_oauth — manual OAuth connected state / labels (second arg: full defaults)
 * - post_smtp_office365_provider_schema — Office 365 connection form schema (Office365Provider in Pro)
 * - post_smtp_office365_authenticate — short-circuit connection save validation (one-click / manual)
 * - post_smtp_admin_config — entire React bootstrap (includes office365Configure, isBusinessPlan from Pro)
 * - post_smtp_provider_schema — provider slug `office365_api`
 */
final class Office365ConfigurePanelHooks {

	public static function init(): void {
		add_filter( 'post_smtp_office365_configure_one_click', array( self::class, 'defaultOneClick' ), 5, 2 );
		add_filter( 'post_smtp_office365_configure_defaults', array( self::class, 'defaultConnectTab' ), 5 );
		add_filter( 'post_smtp_legacy_wizard', array( self::class, 'useRevampOAuthReturn' ), 20 );
		add_filter( 'post_smtp_office365_oauth_return_url', array( self::class, 'revampOAuthReturnUrl' ), 20 );
	}

	/**
	 * After Microsoft manual OAuth, return to the revamp admin (not the old wizard/dashboard fallthrough).
	 *
	 * @param bool $legacy Whether the legacy configuration wizard handles OAuth return URLs.
	 */
	public static function useRevampOAuthReturn( bool $legacy ): bool {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $legacy;
		}

		return false;
	}

	/**
	 * @param string $url Default wizard return URL after manual Entra OAuth.
	 */
	public static function revampOAuthReturnUrl( string $url ): string {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $url;
		}

		$connection_id = get_transient( 'post_smtp_office365_oauth_connection_id' );
		$route           = is_numeric( $connection_id ) && (int) $connection_id >= 0
			? 'connections/' . (int) $connection_id . '/edit'
			: 'connections';

		return add_query_arg(
			array(
				'page'      => 'postman',
				'ps-route'  => $route,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * @param array<string,mixed> $one_click
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function defaultOneClick( array $one_click, array $defaults ): array {
		unset( $defaults );

		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		$is_business = function_exists( 'pspro_fs' ) && pspro_fs()->is_plan( 'business' );

		return array_merge(
			array(
				'hasPro'                 => $has_pro,
				'isBusinessPlan'         => $is_business,
				'toggleDisabled'         => ! $has_pro || ! $is_business,
				'toggleChecked'          => false,
				'openUpgradeOnToggle'    => $has_pro && ! $is_business,
				'showBusinessTag'        => $has_pro && ! $is_business,
				'microsoftSignInLocked'  => ! $has_pro || ! $is_business,
			),
			$one_click
		);
	}

	/**
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function defaultConnectTab( array $defaults ): array {
		$has_pro = defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() );

		$is_business = function_exists( 'pspro_fs' ) && pspro_fs()->is_plan( 'business' );

		$defaults['defaultConnectTab'] = ( $has_pro && $is_business ) ? 'one_click' : 'manual';

		return $defaults;
	}
}
