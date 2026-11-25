<?php
/**
 * Revamp setup wizard — draft staging and primary commit.
 *
 * @package PostSMTP\SetupWizard
 */

namespace PostSMTP\SetupWizard;

use PostSMTP\Connections\ConnectionLegacySendBridge;
use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\OAuthAdminReturnContext;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Data\SecretCodec;
use PostSMTP\Tools\TestEmailMessageBuilder;
use PostSMTP\Tools\TestEmailService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SetupWizardService {

	public const OPTION_COMPLETE = 'post_smtp_revamp_wizard_complete';

	private const DRAFT_TTL = WEEK_IN_SECONDS;

	/**
	 * @return array<string,mixed>
	 */
	public function bootstrap(): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		$has         = ! empty( $connections );
		$primary     = null;

		if ( $has && isset( $connections[0] ) && is_array( $connections[0] ) ) {
			$service = new ConnectionService();
			$row     = $service->normalizeOnRead( $connections, $repo->getOptions() )[0] ?? array();
			$provider = (string) ( $row['provider'] ?? $row['transport_type'] ?? '' );
			$primary  = array(
				'provider'     => $provider,
				'title'        => (string) ( $row['title'] ?? $row['connection_title'] ?? '' ),
				'sender_email' => (string) ( $row['sender_email'] ?? $row['from_email'] ?? '' ),
			);
		}

		$draft = $this->getDraftForClient();
		$user  = wp_get_current_user();

		return array(
			'success'                => true,
			'catalog'                => array(
				'items'  => MailerCatalog::all(),
				'limits' => \PostSMTP\Connections\ConnectionLimits::get(),
			),
			'has_connections'        => $has,
			'primary'                => $primary,
			'draft'                  => $draft,
			'setup_wizard_complete'  => $this->isWizardComplete(),
			'current_user_email'     => $user instanceof \WP_User ? sanitize_email( (string) $user->user_email ) : '',
			'default_test_subject'   => TestEmailMessageBuilder::defaultSubject(),
		);
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array<string,mixed>
	 */
	public function validate( array $payload ): array {
		$provider = sanitize_key( (string) ( $payload['provider'] ?? '' ) );
		$row      = $payload['row'] ?? array();
		if ( ! is_array( $row ) ) {
			$row = array();
		}
		if ( '' === $provider ) {
			return array(
				'success' => false,
				'message' => __( 'Choose a mailer first.', 'post-smtp' ),
			);
		}

		$service = new ConnectionService();
		return $service->validateStagedRow( $row, $provider );
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array<string,mixed>
	 */
	public function saveDraft( array $payload ): array {
		$provider = sanitize_key( (string) ( $payload['provider'] ?? '' ) );
		$row      = $payload['row'] ?? array();
		if ( ! is_array( $row ) ) {
			$row = array();
		}
		if ( '' === $provider ) {
			return array(
				'success' => false,
				'message' => __( 'Choose a mailer first.', 'post-smtp' ),
			);
		}

		$check = $this->validate( $payload );
		if ( empty( $check['success'] ) ) {
			return $check;
		}

		$sanitized = $check['row'] ?? ConnectionSchema::sanitizeRow( array_merge( $row, array( 'provider' => $provider ) ) );
		$sanitized['provider'] = $provider;

		$service = new ConnectionService();
		$persist = $service->upsertWizardPrimary( $sanitized, true );
		if ( empty( $persist['success'] ) ) {
			return $persist;
		}

		$repo    = ConnectionRepositoryFactory::make();
		$primary = $repo->getConnections()[0] ?? array();
		$stored  = is_array( $primary ) ? $primary : SecretCodec::encodeConnectionRow( $sanitized );

		ConnectionLegacySendBridge::sync_primary_to_options();

		if ( is_user_logged_in() ) {
			OAuthAdminReturnContext::rememberPsRoute( OAuthAdminReturnContext::ROUTE_SETUP_WIZARD );
			OAuthAdminReturnContext::rememberPsRouteForConnection( 0, OAuthAdminReturnContext::ROUTE_SETUP_WIZARD );
		}

		$existing = $this->getDraftRaw();
		$draft    = array(
			'provider'       => $provider,
			'row'            => $stored,
			'test_passed'    => ! empty( $existing['test_passed'] ),
			'last_recipient' => (string) ( $existing['last_recipient'] ?? '' ),
			'tested_at'      => (string) ( $existing['tested_at'] ?? '' ),
			'updated_at'     => time(),
		);

		// Changing mailer or connection fields invalidates prior test.
		if ( ! empty( $existing['provider'] ) && $existing['provider'] !== $provider ) {
			$draft['test_passed']    = false;
			$draft['last_recipient'] = '';
			$draft['tested_at']      = '';
		} elseif ( ! empty( $existing['row'] ) && wp_json_encode( $existing['row'] ) !== wp_json_encode( $stored ) ) {
			$draft['test_passed']    = false;
			$draft['last_recipient'] = '';
			$draft['tested_at']      = '';
		}

		set_transient( $this->draftKey(), $draft, self::DRAFT_TTL );

		return array(
			'success'     => true,
			'draft'       => $this->getDraftForClient(),
			'connections' => $service->listForAdmin()['connections'] ?? array(),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function discardDraft(): array {
		delete_transient( $this->draftKey() );
		return array( 'success' => true );
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array<string,mixed>
	 */
	public function sendTestEmail( array $payload ): array {
		$draft = $this->getDraftRaw();
		if ( empty( $draft['provider'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Complete connection setup before sending a test email.', 'post-smtp' ),
			);
		}

		$row = $this->resolveWizardPrimaryRow();
		if ( empty( $row ) ) {
			return array(
				'success' => false,
				'message' => __( 'Save your connection on step 2 before sending a test email.', 'post-smtp' ),
			);
		}

		$subject = trim( (string) ( $payload['subject'] ?? '' ) );
		if ( '' === $subject ) {
			$subject = TestEmailMessageBuilder::defaultSubject();
		}

		$service = new TestEmailService();
		$result  = $service->sendWithDraftRow(
			$row,
			array(
				'recipient' => $payload['recipient'] ?? '',
				'subject'   => $subject,
				'message'   => (string) ( $payload['message'] ?? '' ),
			)
		);

		if ( ! empty( $result['success'] ) ) {
			$recipient = sanitize_email( (string) ( $payload['recipient'] ?? '' ) );
			$draft['test_passed']    = true;
			$draft['last_recipient'] = $recipient;
			$draft['tested_at']      = gmdate( 'c' );
			$draft['updated_at']     = time();
			set_transient( $this->draftKey(), $draft, self::DRAFT_TTL );
		}

		$result['draft'] = $this->getDraftForClient();
		return $result;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function domainAuth(): array {
		$draft = $this->getDraftRaw();
		if ( empty( $draft['provider'] ) ) {
			return array(
				'success'     => true,
				'unavailable' => true,
				'messages'    => array( __( 'No staged connection for domain authentication.', 'post-smtp' ) ),
			);
		}

		$row = $this->resolveWizardPrimaryRow();
		if ( empty( $row ) ) {
			return array(
				'success'     => true,
				'unavailable' => true,
				'messages'    => array( __( 'Save your connection on step 2 first.', 'post-smtp' ) ),
			);
		}

		$provider = (string) ( $draft['provider'] ?? $row['provider'] ?? '' );
		$from     = $this->resolveSenderEmailFromRow( $row );
		$api_key  = $this->extractApiKeyFromRow( $row, $provider );

		if ( '' === $from ) {
			return array(
				'success'     => true,
				'unavailable' => true,
				'messages'    => array( __( 'Add a From email to check domain authentication.', 'post-smtp' ) ),
			);
		}

		if ( ! class_exists( 'Postman_Email_Tester' ) && defined( 'POST_SMTP_PATH' ) ) {
			$tester_file = POST_SMTP_PATH . '/Postman/Postman-Mail-Tester/PostmanEmailTester.php';
			if ( is_readable( $tester_file ) ) {
				require_once $tester_file;
			}
		}

		if ( ! class_exists( 'Postman_Email_Tester' ) ) {
			return array(
				'success'     => true,
				'unavailable' => true,
				'messages'    => array( __( 'Domain authentication is not available.', 'post-smtp' ) ),
			);
		}

		$tester = \Postman_Email_Tester::get_instance();
		if ( ! method_exists( $tester, 'check_domain_auth_for_api' ) ) {
			return array(
				'success'     => true,
				'unavailable' => true,
				'messages'    => array( __( 'Domain authentication is not available.', 'post-smtp' ) ),
			);
		}

		return $tester->check_domain_auth_for_api( $from, $provider, $api_key );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function complete(): array {
		$service = new ConnectionService();

		if ( $this->isWizardComplete() ) {
			return array(
				'success'               => true,
				'message'               => __( 'Setup already complete.', 'post-smtp' ),
				'setup_wizard_complete' => true,
				'connections'           => $service->listForAdmin()['connections'] ?? array(),
			);
		}

		$draft = $this->getDraftRaw();
		if ( empty( $draft['row'] ) || empty( $draft['provider'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Finish setup before completing the wizard.', 'post-smtp' ),
			);
		}

		if ( empty( $draft['test_passed'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Send a successful test email before completing setup.', 'post-smtp' ),
			);
		}

		$row = $this->resolveWizardPrimaryRow();
		if ( empty( $row ) ) {
			return array(
				'success' => false,
				'message' => __( 'Save your connection on step 2 before completing setup.', 'post-smtp' ),
			);
		}

		update_option( self::OPTION_COMPLETE, true, false );
		delete_transient( $this->draftKey() );

		ConnectionLegacySendBridge::sync_primary_to_options();

		return array(
			'success'               => true,
			'message'               => __( 'Setup complete. Your primary connection is ready.', 'post-smtp' ),
			'setup_wizard_complete' => true,
			'connections'           => $service->listForAdmin()['connections'] ?? array(),
		);
	}

	public function isWizardComplete(): bool {
		return (bool) get_option( self::OPTION_COMPLETE, false );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function getDraftForClient(): ?array {
		$raw = $this->getDraftRaw();
		if ( empty( $raw ) || empty( $raw['provider'] ) ) {
			return null;
		}

		$row = isset( $raw['row'] ) && is_array( $raw['row'] )
			? ConnectionSchema::maskSecrets(
				ConnectionService::mapRowForConfigureForm( SecretCodec::decodeConnectionRow( $raw['row'] ) )
			)
			: array();

		return array(
			'provider'       => (string) $raw['provider'],
			'row'            => $row,
			'test_passed'    => ! empty( $raw['test_passed'] ),
			'last_recipient' => (string) ( $raw['last_recipient'] ?? '' ),
			'tested_at'      => (string) ( $raw['tested_at'] ?? '' ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getDraftRaw(): array {
		$draft = get_transient( $this->draftKey() );
		return is_array( $draft ) ? $draft : array();
	}

	private function draftKey(): string {
		return 'post_smtp_setup_wizard_draft_' . get_current_user_id();
	}

	/**
	 * Primary connection row for wizard test send / domain auth (postman_connections[0]).
	 *
	 * @return array<string,mixed>
	 */
	private function resolveWizardPrimaryRow(): array {
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $repo->getConnections();
		$draft       = $this->getDraftRaw();
		$provider    = (string) ( $draft['provider'] ?? '' );

		if ( ! empty( $connections[0] ) && is_array( $connections[0] ) ) {
			$service    = new ConnectionService();
			$options    = $repo->getOptions();
			$normalized = $service->normalizeOnRead( $connections, $options );
			$row        = $normalized[0] ?? array();
			if ( '' !== $provider ) {
				$row['provider'] = $provider;
			}
			return $row;
		}

		if ( empty( $draft['row'] ) || ! is_array( $draft['row'] ) ) {
			return array();
		}

		$row = SecretCodec::decodeConnectionRow( $draft['row'] );
		if ( '' !== $provider ) {
			$row['provider'] = $provider;
		}

		return $row;
	}

	/**
	 * @param array<string,mixed> $row Decoded connection row.
	 */
	private function resolveSenderEmailFromRow( array $row ): string {
		foreach ( array( 'sender_email', 'from_email', 'envelope_sender' ) as $key ) {
			if ( empty( $row[ $key ] ) || ! is_string( $row[ $key ] ) ) {
				continue;
			}
			$email = sanitize_email( trim( $row[ $key ] ) );
			if ( '' !== $email ) {
				return $email;
			}
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $row Connection row (decoded).
	 */
	private function extractApiKeyFromRow( array $row, string $provider ): string {
		$candidates = array(
			'sendgrid_api_key',
			'mailgun_api_key',
			'postmark_api_key',
			'sendinblue_api_key',
			'sparkpost_api_key',
			'smtp2go_api_key',
			'sendpulse_api_key',
			'ses_access_key_id',
			'api_key',
		);

		foreach ( $candidates as $key ) {
			if ( ! empty( $row[ $key ] ) && is_string( $row[ $key ] ) ) {
				return sanitize_text_field( $row[ $key ] );
			}
		}

		return '';
	}
}