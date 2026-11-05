<?php
/**
 * Delegates to legacy Postman transports until fully migrated.
 *
 * @package PostSMTP\Kernel\Providers\Legacy
 */

namespace PostSMTP\Kernel\Providers\Legacy;

use PostSMTP\Kernel\Providers\ProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyProviderAdapter implements ProviderInterface {

	private string $slug;

	public function __construct( string $slug ) {
		$this->slug = $slug;
	}

	public static function slug(): string {
		return 'legacy';
	}

	public static function getOptions(): array {
		return array();
	}

	public function authenticate( array $connection ): array {
		return array(
			'success' => true,
			'message' => __( 'Legacy provider — use wizard to test.', 'post-smtp' ),
		);
	}

	public function send( array $atts, ?int $log_id, array $connection, array $processed_data ): array {
		if ( ! empty( $GLOBALS['post_smtp_revamp_mail_in_progress'] ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: provider slug */
					__( 'Revamp mail has no native provider for "%s". Register a ProviderInterface implementation.', 'post-smtp' ),
					$this->slug
				),
				'send'    => false,
			);
		}

		if ( ! class_exists( '\PostmanWpMail' ) && function_exists( 'post_setupPostman' ) ) {
			post_setupPostman();
		}

		if ( function_exists( 'wp_mail' ) ) {
			$result = wp_mail(
				$atts['to'] ?? '',
				$atts['subject'] ?? '',
				$atts['message'] ?? '',
				$atts['headers'] ?? '',
				$atts['attachments'] ?? array()
			);

			return array(
				'success' => (bool) $result,
				'message' => $result ? __( 'Sent via legacy pipeline.', 'post-smtp' ) : __( 'Legacy send failed.', 'post-smtp' ),
				'send'    => (bool) $result,
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Legacy mail pipeline unavailable.', 'post-smtp' ),
			'send'    => false,
		);
	}
}
