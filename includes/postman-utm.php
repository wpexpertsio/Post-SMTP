<?php
/**
 * Centralized UTM URL builders for Post SMTP marketing links.
 *
 * @package PostSMTP
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'post_smtp_utm_sanitize_token' ) ) :
	/**
	 * Sanitize a UTM token value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function post_smtp_utm_sanitize_token( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/\s+/', '-', $value );
		return trim( preg_replace( '/[^a-z0-9_\-.]/', '', $value ), '/' );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_placements' ) ) :
	/**
	 * Registered utm_content placement slugs.
	 *
	 * @return string[]
	 */
	function post_smtp_utm_placements() {
		$placements = array(
			'sub_menu',
			'plugins_page',
			'dashboard_pro_banner',
			'dashboard_pro_grid',
			'dashboard_side_banner',
			'dashboard_documentation',
			'dashboard_gmail_one_click',
			'wp_dashboard_widget',
			'extension_screen_pop_up',
			'test_email_dns_check',
			'wizard',
			'wizard_microsoft',
			'wizard_gmail_one_click',
			'wizard_zoho',
			'wizard_amazonses',
			'wizard_fix-broken-emails',
			'wizard_sendpulse-docs',
			'mailer_card',
			'mainwp_banner',
			'settings_mobile-app-docs',
			'email-log_promo-banner',
			'admin-notice_pro-bar',
			'dashboard_mobile-app',
			'settings_mobile-app',
		);

		/**
		 * Filter registered in-plugin UTM placement names.
		 *
		 * @param string[] $placements Placement slugs.
		 */
		return apply_filters( 'post_smtp_utm_placements', $placements );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_validate_placement' ) ) :
	/**
	 * Warn in debug when placement is not registered.
	 *
	 * @param string $placement utm_content.
	 */
	function post_smtp_utm_validate_placement( $placement ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$placement = post_smtp_utm_sanitize_token( $placement );
		if ( '' === $placement ) {
			return;
		}
		if ( ! in_array( $placement, post_smtp_utm_placements(), true ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: utm_content placement slug */
					__( 'Unknown UTM placement "%s". Register it via post_smtp_utm_placements filter.', 'post-smtp' ),
					esc_html( $placement )
				),
				'3.0.0'
			);
		}
	}
endif;

if ( ! function_exists( 'post_smtp_utm_normalize_campaign' ) ) :
	/**
	 * Ensure campaign is never empty or the legacy "plugin" value.
	 *
	 * @param string $campaign Campaign slug.
	 * @return string
	 */
	function post_smtp_utm_normalize_campaign( $campaign ) {
		$campaign = post_smtp_utm_sanitize_token( $campaign );
		if ( '' === $campaign || 'plugin' === $campaign ) {
			$campaign = 'upgrade';
		}
		return $campaign;
	}
endif;

if ( ! function_exists( 'post_smtp_utm_promo_campaign' ) ) :
	/**
	 * Active promo campaign slug (changeable without release).
	 *
	 * @return string
	 */
	function post_smtp_utm_promo_campaign() {
		$default = get_option( 'post_smtp_utm_promo_campaign', 'bfcm-2026' );
		$default = post_smtp_utm_sanitize_token( $default );
		if ( '' === $default ) {
			$default = 'bfcm-2026';
		}

		/**
		 * Filter promo UTM campaign slug.
		 *
		 * @param string $campaign Promo campaign slug.
		 */
		return apply_filters( 'post_smtp_utm_promo_campaign', $default );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_url' ) ) :
	/**
	 * Build a tagged URL to postmansmtp.com from inside the plugin.
	 *
	 * @param string $path      Path on postmansmtp.com, e.g. 'pricing'.
	 * @param string $campaign  Purpose: upgrade|docs|setup-wizard|dns-check|gmail-one-click|promo slug.
	 * @param string $placement utm_content placement slug.
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_url( $path, $campaign, $placement ) {
		$campaign  = post_smtp_utm_normalize_campaign( $campaign );
		$placement = post_smtp_utm_sanitize_token( $placement );

		post_smtp_utm_validate_placement( $placement );

		/**
		 * Filter in-plugin UTM campaign before the URL is built.
		 *
		 * @param string $campaign  Campaign slug.
		 * @param string $placement utm_content.
		 * @param string $path      Marketing site path.
		 */
		$campaign = apply_filters( 'post_smtp_utm_campaign', $campaign, $placement, $path );
		$campaign = post_smtp_utm_normalize_campaign( $campaign );

		$args = array(
			'utm_source'   => 'post-smtp',
			'utm_medium'   => 'in-plugin',
			'utm_campaign' => $campaign,
			'utm_content'  => $placement,
		);

		$base = 'https://postmansmtp.com/' . ltrim( (string) $path, '/' );

		return esc_url( add_query_arg( $args, $base ) );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_promo_url' ) ) :
	/**
	 * Build a promo-tagged marketing URL (campaign = promo slug, placement preserved).
	 *
	 * @param string $path      Path on postmansmtp.com during promo, e.g. 'cyber-monday-sale'.
	 * @param string $placement utm_content placement slug.
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_promo_url( $path, $placement ) {
		return post_smtp_utm_url( $path, post_smtp_utm_promo_campaign(), $placement );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_pricing_url' ) ) :
	/**
	 * Pricing or promo sale URL for a placement.
	 *
	 * @param string $placement utm_content.
	 * @param string $campaign  Campaign when not in promo mode.
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_pricing_url( $placement, $campaign = 'upgrade' ) {
		if ( function_exists( 'postman_is_bfcm' ) && postman_is_bfcm() ) {
			return post_smtp_utm_promo_url( 'cyber-monday-sale', $placement );
		}
		return post_smtp_utm_url( 'pricing', $campaign, $placement );
	}
endif;

if ( ! function_exists( 'post_smtp_wizard_upgrade_url' ) ) :
	/**
	 * Wizard upgrade/pricing link for a mailer step placement.
	 *
	 * @param string $placement e.g. wizard_microsoft.
	 * @return string Escaped URL.
	 */
	function post_smtp_wizard_upgrade_url( $placement ) {
		return post_smtp_utm_pricing_url( $placement, 'setup-wizard' );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_email_audience' ) ) :
	/**
	 * Audience segment for email UTMs.
	 *
	 * @return string free-users|pro-users|all-users
	 */
	function post_smtp_utm_email_audience() {
		if ( function_exists( 'post_smtp_has_pro' ) && post_smtp_has_pro() ) {
			return 'pro-users';
		}
		return 'free-users';
	}
endif;

if ( ! function_exists( 'post_smtp_utm_email_url' ) ) :
	/**
	 * Build a tagged URL for links inside plugin-sent emails.
	 *
	 * @param string      $path            Path on postmansmtp.com.
	 * @param string      $content_section Section slug (without text-link_ prefix).
	 * @param string|null $audience        utm_term override.
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_email_url( $path, $content_section, $audience = null ) {
		$section = post_smtp_utm_sanitize_token( $content_section );
		if ( null === $audience || '' === trim( (string) $audience ) ) {
			$audience = post_smtp_utm_email_audience();
		} else {
			$audience = post_smtp_utm_sanitize_token( $audience );
		}

		$args = array(
			'utm_source'   => 'email',
			'utm_medium'   => 'email',
			'utm_campaign' => 'product-update_post-smtp_email-report',
			'utm_content'  => 'text-link_' . $section,
			'utm_term'     => $audience,
		);

		$base = 'https://postmansmtp.com/' . ltrim( (string) $path, '/' );

		return esc_url( add_query_arg( $args, $base ) );
	}
endif;

if ( ! function_exists( 'post_smtp_wporg_readme_url' ) ) :
	/**
	 * Build a WordPress.org readme link (reference / release tooling; not runtime in readme.txt).
	 *
	 * @param string $path   Path on postmansmtp.com.
	 * @param string $medium utm_medium token, e.g. read_me.
	 * @return string Escaped URL.
	 */
	function post_smtp_wporg_readme_url( $path, $medium ) {
		$medium = post_smtp_utm_sanitize_token( $medium );
		if ( '' === $medium ) {
			$medium = 'read_me';
		}

		$args = array(
			'utm_source' => 'wp_org',
			'utm_medium' => $medium,
		);

		$base = 'https://postmansmtp.com/' . ltrim( (string) $path, '/' );

		return esc_url( add_query_arg( $args, $base ) );
	}
endif;

if ( ! function_exists( 'post_smtp_android_store_referrer' ) ) :
	/**
	 * Google Play referrer query value with standardized in-plugin UTMs.
	 *
	 * @param string $placement utm_content.
	 * @param string $campaign  Campaign when not promo.
	 * @return string URL-encoded referrer string.
	 */
	function post_smtp_android_store_referrer( $placement, $campaign = 'upgrade' ) {
		if ( function_exists( 'postman_is_bfcm' ) && postman_is_bfcm() ) {
			$campaign = post_smtp_utm_promo_campaign();
		} else {
			$campaign = post_smtp_utm_normalize_campaign( $campaign );
			$campaign = apply_filters( 'post_smtp_utm_campaign', $campaign, $placement, 'mobile-app' );
			$campaign = post_smtp_utm_normalize_campaign( $campaign );
		}

		$placement = post_smtp_utm_sanitize_token( $placement );

		$query = build_query(
			array(
				'utm_source'   => 'post-smtp',
				'utm_medium'   => 'in-plugin',
				'utm_campaign' => $campaign,
				'utm_content'  => $placement,
			)
		);

		return rawurlencode( $query );
	}
endif;

if ( ! function_exists( 'post_smtp_ios_store_url' ) ) :
	/**
	 * Apple App Store URL with in-plugin UTMs.
	 *
	 * @param string $placement utm_content.
	 * @param string $campaign  Campaign when not promo.
	 * @return string Escaped URL.
	 */
	function post_smtp_ios_store_url( $placement, $campaign = 'upgrade' ) {
		if ( function_exists( 'postman_is_bfcm' ) && postman_is_bfcm() ) {
			$campaign = post_smtp_utm_promo_campaign();
		} else {
			$campaign = post_smtp_utm_normalize_campaign( $campaign );
			$campaign = apply_filters( 'post_smtp_utm_campaign', $campaign, $placement, 'mobile-app' );
			$campaign = post_smtp_utm_normalize_campaign( $campaign );
		}

		$placement = post_smtp_utm_sanitize_token( $placement );

		$args = array(
			'utm_source'   => 'post-smtp',
			'utm_medium'   => 'in-plugin',
			'utm_campaign' => $campaign,
			'utm_content'  => $placement,
		);

		return esc_url(
			add_query_arg(
				$args,
				'https://apps.apple.com/us/app/post-smtp/id6473368559'
			)
		);
	}
endif;

if ( ! function_exists( 'post_smtp_android_store_url' ) ) :
	/**
	 * Google Play URL with referrer UTMs.
	 *
	 * @param string $placement utm_content.
	 * @param string $campaign  Campaign when not promo.
	 * @return string Escaped URL.
	 */
	function post_smtp_android_store_url( $placement, $campaign = 'upgrade' ) {
		$referrer = post_smtp_android_store_referrer( $placement, $campaign );

		return esc_url(
			'https://play.google.com/store/apps/details?id=com.postsmtp&referrer=' . $referrer . '%26anid%3Dadmob'
		);
	}
endif;

if ( ! function_exists( 'post_smtp_utm_dashboard_documentation_url' ) ) :
	/**
	 * Dashboard documentation tile link.
	 *
	 * @param string $path Path on postmansmtp.com.
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_dashboard_documentation_url( $path ) {
		return post_smtp_utm_url( $path, 'docs', 'dashboard_documentation' );
	}
endif;

if ( ! function_exists( 'post_smtp_dashboard_marketing_urls' ) ) :
	/**
	 * Marketing URLs for the Vue dashboard bundle.
	 *
	 * @return array<string, string>
	 */
	function post_smtp_dashboard_marketing_urls() {
		return array(
			'proBanner'      => post_smtp_utm_pricing_url( 'dashboard_pro_banner', 'upgrade' ),
			'proGrid'        => post_smtp_utm_pricing_url( 'dashboard_pro_grid', 'upgrade' ),
			'gmailOneClick'  => post_smtp_utm_url( 'pricing', 'gmail-one-click', 'dashboard_gmail_one_click' ),
			'mainwp'         => post_smtp_utm_url( 'mainwp-post-smtp/', 'upgrade', 'mainwp_banner' ),
			'spamScore'      => post_smtp_utm_url( 'domain-health-checker/', 'dns-check', 'dashboard_side_banner' ),
			'headerDocs'     => post_smtp_utm_url( 'documentation/', 'docs', 'dashboard_documentation' ),
		);
	}
endif;
