<?php
/**
 * Send Test Email tool (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Providers\ProviderRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TestEmailService {

	/**
	 * @return array<string,mixed>
	 */
	public function getBootstrap(): array {
		$connections = $this->listTestableConnections();
		$primary_id  = 0;

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			$repo       = ConnectionRepositoryFactory::make();
			$options    = $repo->getOptions();
			$primary_id = (int) ( $options['selected_primary'] ?? $options['primary_connection'] ?? 0 );
		}

		$has_usable = false;
		foreach ( $connections as $row ) {
			if ( ! empty( $row['is_usable'] ) ) {
				$has_usable = true;
				break;
			}
		}

		$user = wp_get_current_user();

		return array(
			'connections'           => $connections,
			'default_connection_id' => $primary_id,
			'has_usable_connection' => $has_usable,
			'defaults'              => array(
				'subject' => TestEmailMessageBuilder::defaultSubject(),
			),
			'copy'                  => array(
				'success' => __( 'Test email sent successfully. Check your recipient inbox.', 'post-smtp' ),
			),
			'links'                 => array(
				'connections' => admin_url( 'admin.php?page=postman#/connections' ),
				'docs'          => 'https://postmansmtp.com/documentation/',
			),
			'current_user_email'    => $user instanceof \WP_User ? sanitize_email( (string) $user->user_email ) : '',
		);
	}

	/**
	 * @param array<string,mixed> $payload Request body.
	 * @return array{success:bool,message:string,transcript?:string,time_ms?:int}
	 */
	public function send( array $payload ): array {
		$connection_id = isset( $payload['connection_id'] ) ? (int) $payload['connection_id'] : 0;
		$recipient     = sanitize_email( (string) ( $payload['recipient'] ?? '' ) );
		$subject       = trim( (string) ( $payload['subject'] ?? '' ) );
		$message       = (string) ( $payload['message'] ?? '' );

		if ( '' === $recipient || ! is_email( $recipient ) ) {
			return array(
				'success' => false,
				'message' => __( 'Enter a valid recipient email address.', 'post-smtp' ),
			);
		}

		if ( '' === $subject ) {
			return array(
				'success' => false,
				'message' => __( 'Subject cannot be empty.', 'post-smtp' ),
			);
		}

		$connection = $this->resolveConnection( $connection_id );
		if ( empty( $connection ) ) {
			return array(
				'success' => false,
				'message' => __( 'Select a configured connection before sending a test email.', 'post-smtp' ),
			);
		}

		$validate = $this->validateConnectionRow( $connection );
		if ( ! $validate['success'] ) {
			return $validate;
		}

		$header  = 'Content-Type: multipart/alternative;' . "\r\n";
		$header .= 'MIME-Version: 1.0' . "\r\n";
		$body    = TestEmailMessageBuilder::buildMultipartBody( $message );

		$skip_fallback = static function () {
			return true;
		};
		$test_mode = static function () {
			return true;
		};

		add_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		add_filter( 'postman_test_email', $test_mode );

		if ( ! ConnectionRepositoryFactory::isLegacyMode() && class_exists( 'Postman_Connection_Resolver' ) ) {
			set_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT, $connection_id, 120 );
		}

		$email_args = apply_filters(
			'postman_test_email_args',
			array(
				'email'   => $recipient,
				'subject' => $subject,
				'message' => $body,
				'header'  => $header,
			)
		);

		$success = wp_mail(
			$email_args['email'],
			$email_args['subject'],
			$email_args['message'],
			$email_args['header']
		);

		remove_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		remove_filter( 'postman_test_email', $test_mode );

		if ( ! ConnectionRepositoryFactory::isLegacyMode() && class_exists( 'Postman_Connection_Resolver' ) ) {
			delete_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT );
		}

		$result = apply_filters( 'postman_wp_mail_result', null );
		if ( ! is_array( $result ) ) {
			$result = array();
		}

		if ( $success ) {
			return array(
				'success' => true,
				'message' => __( 'Test email sent successfully. Check your recipient inbox.', 'post-smtp' ),
				'time_ms' => isset( $result['time'] ) ? (int) $result['time'] : 0,
			);
		}

		$error_message = __( 'The test email could not be sent. Check your connection settings and try again.', 'post-smtp' );
		if ( isset( $result['exception'] ) && $result['exception'] instanceof \Exception ) {
			$error_message = sanitize_text_field( $result['exception']->getMessage() );
		}

		return array(
			'success'    => false,
			'message'    => $error_message,
			'transcript' => isset( $result['transcript'] ) ? (string) $result['transcript'] : '',
		);
	}

	/**
	 * @return array{success:bool,preview?:array<string,string>,message?:string}
	 */
	public function preview( array $payload ): array {
		$subject = trim( (string) ( $payload['subject'] ?? '' ) );
		$message = (string) ( $payload['message'] ?? '' );

		if ( '' === $subject ) {
			$subject = TestEmailMessageBuilder::defaultSubject();
		}

		return array(
			'success' => true,
			'preview' => TestEmailMessageBuilder::previewParts( $subject, $message ),
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function listTestableConnections(): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $this->legacyConnectionOptions();
		}

		$service     = new ConnectionService();
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $service->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );
		$catalog     = MailerCatalog::all();
		$labels      = array();
		foreach ( $catalog as $item ) {
			if ( ! empty( $item['slug'] ) ) {
				$labels[ (string) $item['slug'] ] = (string) ( $item['label'] ?? $item['slug'] );
			}
		}

		$out = array();
		foreach ( $connections as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$role_meta = ConnectionService::deriveRole( (int) $index, $connections );
			$provider = (string) ( $row['provider'] ?? $row['transport_type'] ?? '' );
			$title    = trim( (string) ( $row['title'] ?? $row['connection_title'] ?? '' ) );
			$mailer   = $labels[ $provider ] ?? ucwords( str_replace( '_', ' ', $provider ) );
			$label    = $title !== '' ? $title : $mailer;

			$out[] = array(
				'id'           => (int) $index,
				'provider'     => $provider,
				'label'        => $label,
				'mailer_label' => $mailer,
				'role'         => (string) ( $role_meta['role'] ?? 'inactive' ),
				'role_label'   => (string) ( $role_meta['role_label'] ?? '' ),
				'is_primary'   => 0 === (int) $index,
				'is_usable'    => $this->validateConnectionRow( $row )['success'],
			);
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function legacyConnectionOptions(): array {
		$configured = false;
		$transport  = 'default';
		$label      = __( 'Primary connection', 'post-smtp' );

		if ( class_exists( '\PostmanTransportRegistry' ) && class_exists( '\PostmanOptions' ) ) {
			$active     = \PostmanTransportRegistry::getInstance()->getActiveTransport();
			$configured = $active && $active->isConfiguredAndReady();
			$transport  = (string) \PostmanOptions::getInstance()->getTransportType();
			$label      = ucwords( str_replace( '_', ' ', $transport ) );
		}

		return array(
			array(
				'id'           => 0,
				'provider'     => $transport,
				'label'        => $label,
				'mailer_label' => $label,
				'role'         => 'primary',
				'role_label'   => __( 'Primary', 'post-smtp' ),
				'is_primary'   => true,
				'is_usable'    => (bool) $configured,
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function resolveConnection( int $connection_id ): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() ) {
			return $connection_id === 0 ? array( 'provider' => 'legacy', 'id' => 0 ) : array();
		}

		$repo = ConnectionRepositoryFactory::make();
		$row  = $repo->getConnection( $connection_id );
		return is_array( $row ) ? $row : array();
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @return array{success:bool,message:string}
	 */
	private function validateConnectionRow( array $row ): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() || ( isset( $row['provider'] ) && 'legacy' === $row['provider'] ) ) {
			if ( class_exists( '\PostmanTransportRegistry' ) ) {
				$active = \PostmanTransportRegistry::getInstance()->getActiveTransport();
				if ( $active && $active->isConfiguredAndReady() ) {
					return array( 'success' => true, 'message' => '' );
				}
			}
			return array(
				'success' => false,
				'message' => __( 'Configure a mailer connection before sending a test email.', 'post-smtp' ),
			);
		}

		$slug = (string) ( $row['provider'] ?? '' );
		if ( '' === $slug ) {
			return array(
				'success' => false,
				'message' => __( 'This connection is missing a mailer type.', 'post-smtp' ),
			);
		}

		$registry = new ProviderRegistry();
		$provider = $registry->create( $slug );
		$result   = $provider->authenticate( $row );
		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => $result['message'] ?? __( 'This connection is not ready to send.', 'post-smtp' ),
			);
		}

		return array( 'success' => true, 'message' => '' );
	}
}
