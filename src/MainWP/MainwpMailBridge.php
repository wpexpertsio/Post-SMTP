<?php
/**
 * Routes revamp pipeline mail through MainWP parent when the child is managed remotely.
 *
 * Legacy cohort continues to use Postman/Extensions/MainWP-Child/psmwp-functions.php.
 *
 * @package PostSMTP\MainWP
 */

namespace PostSMTP\MainWP;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MainwpMailBridge {

	public static function init(): void {
		add_filter( 'pre_wp_mail', array( self::class, 'handle' ), 0, 2 );
	}

	/**
	 * @param null|bool $short_circuit Existing short-circuit value.
	 * @param array     $atts          wp_mail arguments.
	 * @return null|bool|\WP_Error
	 */
	public static function handle( $short_circuit, array $atts ) {
		if ( null !== $short_circuit ) {
			return $short_circuit;
		}

		if ( ! MainwpPolicy::isChildUsingParentMailer() ) {
			return null;
		}

		// Avoid competing with the legacy MainWP wp_mail replacement on the same request.
		if ( ! CohortResolver::usesRevampMailPipeline() ) {
			return null;
		}

		$request_class = self::requestClass();
		if ( null === $request_class ) {
			return null;
		}

		$to          = $atts['to'] ?? '';
		$subject     = $atts['subject'] ?? '';
		$message     = $atts['message'] ?? '';
		$headers     = $atts['headers'] ?? '';
		$attachments = $atts['attachments'] ?? array();

		$request  = new $request_class();
		$response = $request->process_email( $to, $subject, $message, $headers, $attachments );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return (bool) $response;
	}

	/**
	 * @return class-string|null
	 */
	private static function requestClass(): ?string {
		if ( class_exists( 'Post_SMTP_MainWP_Child_Request', false ) ) {
			return 'Post_SMTP_MainWP_Child_Request';
		}

		if ( ! defined( 'POST_SMTP_PATH' ) ) {
			return null;
		}

		$path = POST_SMTP_PATH . '/Postman/Extensions/MainWP-Child/includes/classes/class-psmwp-request.php';
		if ( ! is_readable( $path ) ) {
			return null;
		}

		require_once $path;

		return class_exists( 'Post_SMTP_MainWP_Child_Request', false ) ? 'Post_SMTP_MainWP_Child_Request' : null;
	}
}