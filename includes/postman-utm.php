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

if ( ! function_exists( 'post_smtp_utm_in_plugin_source' ) ) :
	/**
	 * utm_source for links from this plugin to postmansmtp.com.
	 *
	 * @return string
	 */
	function post_smtp_utm_in_plugin_source() {
		return 'plugin';
	}
endif;

if ( ! function_exists( 'post_smtp_utm_in_plugin_medium' ) ) :
	/**
	 * utm_medium for in-plugin marketing links.
	 *
	 * @return string
	 */
	function post_smtp_utm_in_plugin_medium() {
		return 'in-plugin';
	}
endif;

if ( ! function_exists( 'post_smtp_utm_placements' ) ) :
	/**
	 * Registered utm_content placement slugs (legacy + guide names).
	 *
	 * @return string[]
	 */
	function post_smtp_utm_placements() {
		$placements = array(
			// Legacy (frozen in GA4 — keep registering for debug).
			'sub_menu',
			'plugins_page',
			'dashboard_pro_banner',
			'dashboard_pro_grid',
			'dashboard_side_banner',
			'dashboard_gmail_one_click',
			'wp_dashboard_widget',
			'extension_screen_pop_up',
			'test_email_dns_check',
			'wizard_gmail_one_click',
			'mainwp_banner',
			'admin-notice_pro-bar',
			// Guide placements.
			'dashboard_mainwp-banner',
			'dashboard_spam-score-banner',
			'dashboard_documentation',
			'dashboard_troubleshooting-dns-check',
			'dashboard_office-one-click',
			'dashboard_pro-list-microsoft-365',
			'dashboard_pro-list-amazon-ses',
			'dashboard_pro-list-email-log-attachment',
			'dashboard_pro-list-report-and-tracking',
			'dashboard_pro-list-advanced-email-delivery',
			'dashboard_pro-list-twilio',
			'dashboard_pro-list-zoho-mail',
			'settings_mobile-app-guide',
			'settings_google-play',
			'settings_app-store',
			'dashboard_google-play',
			'dashboard_app-store',
			'wizard_google-play',
			'wizard_app-store',
			'popup_claim-offer',
			'admin_bundles-banner-headline',
			'admin_bundles-banner-learn-more',
			'extensions_popup-cta',
			'test-email_fix-broken-emails',
			'wizard',
			'wizard_microsoft',
			'wizard_microsoft-one-click',
			'wizard_zoho',
			'wizard_amazonses',
			'wizard_fix-broken-emails',
			'wizard_sendpulse',
			'wizard_sendpulse-docs',
			'mailer_card',
			'settings_mobile-app-docs',
			'email-log_promo-banner',
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
				'4.0.3'
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
		if ( 'dns-check' === $campaign ) {
			$campaign = 'domain-health-checker';
		}
		return $campaign;
	}
endif;

if ( ! function_exists( 'post_smtp_utm_resolve_in_plugin_tags' ) ) :
	/**
	 * Map legacy placement/campaign inputs to utm_campaign + utm_content.
	 *
	 * @param string $campaign  Requested campaign.
	 * @param string $placement Requested placement (legacy slug).
	 * @param string $path      Marketing site path (for extension list links).
	 * @return array{campaign:string,content:string}
	 */
	function post_smtp_utm_resolve_in_plugin_tags( $campaign, $placement, $path = '' ) {
		$campaign  = post_smtp_utm_normalize_campaign( $campaign );
		$placement = post_smtp_utm_sanitize_token( $placement );
		$path      = strtolower( ltrim( (string) $path, '/' ) );

		$composite = $campaign . '::' . $placement;
		$by_composite = array(
			'upgrade::wizard'           => array(
				'campaign' => 'upgrade',
				'content'  => 'popup_claim-offer',
			),
			'setup-wizard::wizard'      => array(
				'campaign' => 'docs',
				'content'  => 'test-email_fix-broken-emails',
			),
		);
		if ( isset( $by_composite[ $composite ] ) ) {
			return $by_composite[ $composite ];
		}

		if ( 'dashboard_pro_grid' === $placement && '' !== $path ) {
			$extension_map = array(
				'office-365-extension-for-post-smtp' => 'dashboard_pro-list-microsoft-365',
				'post-smtp-extension-for-amazon-ses' => 'dashboard_pro-list-amazon-ses',
				'email-log-attachment'             => 'dashboard_pro-list-email-log-attachment',
				'reporting-and-tracking-extension' => 'dashboard_pro-list-report-and-tracking',
				'advanced-email-delivery'          => 'dashboard_pro-list-advanced-email-delivery',
				'twilio-extension-pro'             => 'dashboard_pro-list-twilio',
				'zoho-mail-pro-extension'          => 'dashboard_pro-list-zoho-mail',
			);
			foreach ( $extension_map as $needle => $content ) {
				if ( false !== strpos( $path, $needle ) ) {
					return array(
						'campaign' => 'upgrade',
						'content'  => $content,
					);
				}
			}
		}

		$by_placement = array(
			'mainwp_banner'              => array(
				'campaign' => 'mainwp',
				'content'  => 'dashboard_mainwp-banner',
			),
			'dashboard_side_banner'      => array(
				'campaign' => 'domain-health-checker',
				'content'  => 'dashboard_spam-score-banner',
			),
			'settings_mobile-app-docs'   => array(
				'campaign' => 'docs',
				'content'  => 'settings_mobile-app-guide',
			),
			'wizard_fix-broken-emails'   => array(
				'campaign' => 'docs',
				'content'  => 'wizard_fix-broken-emails',
			),
			'wizard_sendpulse-docs'      => array(
				'campaign' => 'docs',
				'content'  => 'wizard_sendpulse',
			),
			'test_email_dns_check'       => array(
				'campaign' => 'domain-health-checker',
				'content'  => 'test_email_dns_check',
			),
			'extension_screen_pop_up'    => array(
				'campaign' => 'upgrade',
				'content'  => 'extensions_popup-cta',
			),
			'admin-notice_pro-bar'       => array(
				'campaign' => 'upgrade',
				'content'  => 'admin_bundles-banner-headline',
			),
			'admin_bundles-banner-headline' => array(
				'campaign' => 'upgrade',
				'content'  => 'admin_bundles-banner-headline',
			),
			'admin_bundles-banner-learn-more' => array(
				'campaign' => 'upgrade',
				'content'  => 'admin_bundles-banner-learn-more',
			),
			'popup_claim-offer'          => array(
				'campaign' => 'upgrade',
				'content'  => 'popup_claim-offer',
			),
			'test-email_fix-broken-emails' => array(
				'campaign' => 'docs',
				'content'  => 'test-email_fix-broken-emails',
			),
			'settings_google-play'       => array(
				'campaign' => 'mobile-app',
				'content'  => 'settings_google-play',
			),
			'settings_app-store'         => array(
				'campaign' => 'mobile-app',
				'content'  => 'settings_app-store',
			),
			'dashboard_google-play'      => array(
				'campaign' => 'mobile-app',
				'content'  => 'dashboard_google-play',
			),
			'dashboard_app-store'        => array(
				'campaign' => 'mobile-app',
				'content'  => 'dashboard_app-store',
			),
			'wizard_google-play'         => array(
				'campaign' => 'mobile-app',
				'content'  => 'wizard_google-play',
			),
			'wizard_app-store'           => array(
				'campaign' => 'mobile-app',
				'content'  => 'wizard_app-store',
			),
			'settings_mobile-app'        => array(
				'campaign' => 'mobile-app',
				'content'  => 'settings_google-play',
			),
			'dashboard_mobile-app'       => array(
				'campaign' => 'mobile-app',
				'content'  => 'dashboard_google-play',
			),
		);

		if ( isset( $by_placement[ $placement ] ) ) {
			$resolved = $by_placement[ $placement ];
			if ( ! isset( $resolved['campaign'] ) || '' === $resolved['campaign'] ) {
				$resolved['campaign'] = $campaign;
			}
			return $resolved;
		}

		return array(
			'campaign' => $campaign,
			'content'  => $placement,
		);
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
	 * @param string $campaign  Purpose: upgrade|docs|setup-wizard|gmail-one-click|promo slug.
	 * @param string $placement utm_content placement slug (legacy or guide).
	 * @return string Escaped URL.
	 */
	function post_smtp_utm_url( $path, $campaign, $placement ) {
		$resolved = post_smtp_utm_resolve_in_plugin_tags( $campaign, $placement, $path );

		post_smtp_utm_validate_placement( $resolved['content'] );

		/**
		 * Filter in-plugin UTM campaign before the URL is built.
		 *
		 * @param string $campaign  Campaign slug.
		 * @param string $placement utm_content.
		 * @param string $path      Marketing site path.
		 */
		$campaign = apply_filters( 'post_smtp_utm_campaign', $resolved['campaign'], $resolved['content'], $path );
		$campaign = post_smtp_utm_normalize_campaign( $campaign );

		$args = array(
			'utm_source'   => post_smtp_utm_in_plugin_source(),
			'utm_medium'   => post_smtp_utm_in_plugin_medium(),
			'utm_campaign' => $campaign,
			'utm_content'  => $resolved['content'],
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
			'utm_campaign' => 'product-update_email-report',
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
	 * @param string $path    Path on postmansmtp.com.
	 * @param string $content utm_content, e.g. link_top|link_body.
	 * @return string Escaped URL.
	 */
	function post_smtp_wporg_readme_url( $path, $content = 'link_body' ) {
		$content = post_smtp_utm_sanitize_token( $content );
		if ( '' === $content ) {
			$content = 'link_body';
		}

		$args = array(
			'utm_source'   => 'wordpress.org',
			'utm_medium'   => 'wp-org-listing',
			'utm_campaign' => 'readme',
			'utm_content'  => $content,
		);

		$base = 'https://postmansmtp.com/' . ltrim( (string) $path, '/' );

		return esc_url( add_query_arg( $args, $base ) );
	}
endif;

if ( ! function_exists( 'post_smtp_utm_build_in_plugin_query_args' ) ) :
	/**
	 * Build utm query args for store / external URLs.
	 *
	 * @param string $campaign  Campaign slug.
	 * @param string $placement Placement slug.
	 * @param string $path      Optional path for resolution.
	 * @return array<string, string>
	 */
	function post_smtp_utm_build_in_plugin_query_args( $campaign, $placement, $path = '' ) {
		if ( function_exists( 'postman_is_bfcm' ) && postman_is_bfcm() ) {
			$campaign = post_smtp_utm_promo_campaign();
		} else {
			$resolved = post_smtp_utm_resolve_in_plugin_tags( $campaign, $placement, $path );
			$campaign = apply_filters( 'post_smtp_utm_campaign', $resolved['campaign'], $resolved['content'], $path );
			$campaign = post_smtp_utm_normalize_campaign( $campaign );
			$placement = $resolved['content'];
		}

		return array(
			'utm_source'   => post_smtp_utm_in_plugin_source(),
			'utm_medium'   => post_smtp_utm_in_plugin_medium(),
			'utm_campaign' => $campaign,
			'utm_content'  => post_smtp_utm_sanitize_token( $placement ),
		);
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
		$args = post_smtp_utm_build_in_plugin_query_args( $campaign, $placement, 'mobile-app' );
		return rawurlencode( build_query( $args ) );
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
		$args = post_smtp_utm_build_in_plugin_query_args( $campaign, $placement, 'mobile-app' );

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
			'proBanner'          => post_smtp_utm_pricing_url( 'dashboard_pro_banner', 'upgrade' ),
			'proBannerPromo'     => post_smtp_utm_promo_url( 'cyber-monday-sale', 'dashboard_pro_banner' ),
			'proGrid'            => post_smtp_utm_pricing_url( 'dashboard_pro_grid', 'upgrade' ),
			'gmailOneClick'      => post_smtp_utm_url( 'pricing', 'gmail-one-click', 'dashboard_gmail_one_click' ),
			'officeOneClick'     => post_smtp_utm_url( 'pricing', 'setup-wizard', 'wizard_microsoft-one-click' ),
			'mainwp'             => post_smtp_utm_url( 'mainwp-post-smtp/', 'mainwp', 'mainwp_banner' ),
			'spamScore'          => post_smtp_utm_url( 'domain-health-checker/', 'domain-health-checker', 'dashboard_side_banner' ),
			'troubleshootingDns' => post_smtp_utm_url( 'domain-health-checker/', 'domain-health-checker', 'dashboard_troubleshooting-dns-check' ),
			'headerDocs'         => post_smtp_utm_url( 'documentation/', 'docs', 'dashboard_documentation' ),
		);
	}
endif;
