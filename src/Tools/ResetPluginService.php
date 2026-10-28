<?php
/**
 * Reset Plugin tool (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Kernel\Data\ConnectionSchema;
use PostmanAdminController;
use PostmanEmailLogPurger;
use PostmanOAuthToken;
use PostmanOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResetPluginService {

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		return array(
			'copy' => array(
				'title'              => __( 'Reset Plugin', 'post-smtp' ),
				'lead'               => __( 'Clear all settings and start over.', 'post-smtp' ),
				'section_label'      => __( 'Reset plugin', 'post-smtp' ),
				'section_hint'       => __( 'Removes Post SMTP settings and data from this site. This cannot be undone.', 'post-smtp' ),
				'confirm_label'      => __( 'Yes, I understand this deletes my settings.', 'post-smtp' ),
				'reset_button'       => __( 'Reset plugin', 'post-smtp' ),
				'confirm_required'   => __( 'Check the box to confirm before resetting.', 'post-smtp' ),
				'reset_success'      => __( 'Plugin data was removed.', 'post-smtp' ),
				'reset_error'        => __( 'Could not reset the plugin.', 'post-smtp' ),
			),
		);
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array{success:bool,message:string,redirect?:string}
	 */
	public function reset( array $payload ): array {
		if ( empty( $payload['confirmed'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Check the box to confirm before resetting.', 'post-smtp' ),
			);
		}

		/**
		 * Fires before resetting plugin.
		 *
		 * @since 2.1.4
		 */
		do_action( 'post_smtp_before_reset_plugin' );

		delete_option( PostmanOptions::POSTMAN_OPTIONS );
		delete_option( PostmanOAuthToken::OPTIONS_NAME );
		delete_option( PostmanAdminController::TEST_OPTIONS );
		delete_option( ConnectionSchema::OPTION_CONNECTIONS );
		delete_option( 'postman_auth_token' );
		delete_option( 'postman_rat' );
		delete_transient( 'ps_rat_has_sent' );

		$preserve_logs = ! empty( $payload['preserve_email_logs'] );
		if ( ! $preserve_logs && class_exists( PostmanEmailLogPurger::class ) ) {
			$log_purger = new PostmanEmailLogPurger();
			$log_purger->removeAll();
		}

		/**
		 * Fires after resetting plugin.
		 *
		 * @since 2.1.4
		 */
		do_action( 'post_smtp_after_reset_plugin' );

		return array(
			'success'  => true,
			'message'  => __( 'Plugin data was removed.', 'post-smtp' ),
			'redirect' => admin_url( 'admin.php?page=postman#/connections' ),
		);
	}
}
