<?php
/**
 * Resolve legacy module transports from runtime-patched options (revamp sends only).
 *
 * @package PostSMTP\Mail\Modern\Legacy
 */

namespace PostSMTP\Mail\Modern\Legacy;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RevampLegacyTransportResolver {

	/**
	 * Whether revamp should override legacy getActiveTransport() for this request.
	 */
	public static function shouldOverrideLegacyTransport(): bool {
		if ( ! empty( $GLOBALS['post_smtp_revamp_mail_in_progress'] ) ) {
			return true;
		}

		if ( ! apply_filters( 'postman_test_email', false ) ) {
			return false;
		}

		return CohortResolver::usesRevampMailPipeline();
	}

	/**
	 * Use patched transport_type — never fall back to PHP mail() when a mailer slug is set.
	 *
	 * @return \PostmanModuleTransport|null
	 */
	public static function resolve() {
		if ( ! self::shouldOverrideLegacyTransport() ) {
			return null;
		}

		if ( ! class_exists( '\PostmanTransportRegistry', false ) || ! class_exists( '\PostmanOptions', false ) ) {
			return null;
		}

		$registry = \PostmanTransportRegistry::getInstance();
		$options  = \PostmanOptions::getInstance();
		$slug     = (string) $options->getTransportType();
		$all      = $registry->getTransports();

		if ( $options->is_fallback ) {
			if ( '' !== $slug && 'default' !== $slug && isset( $all[ $slug ] ) ) {
				return $all[ $slug ];
			}
			if ( isset( $all['smtp'] ) ) {
				return $all['smtp'];
			}
		}

		if ( '' !== $slug && 'default' !== $slug && isset( $all[ $slug ] ) ) {
			return $all[ $slug ];
		}

		$selected = $registry->getSelectedTransport();
		if ( $selected instanceof \PostmanModuleTransport && 'default' !== $selected->getSlug() ) {
			return $selected;
		}

		return null;
	}
}