<?php
/**
 * Send Test Email tool (Tools module).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Connections\ConnectionLegacySendBridge;
use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Pro\Connections\Office365ConnectionTokens;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\SecretCodec;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use PostSMTP\Mail\Modern\Envelope\EnvelopeFactory;
use PostSMTP\Mail\Modern\Legacy\RevampLegacyPostmanSend;
use PostSMTP\Rollout\CohortResolver;

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
		$recipient_raw = $payload['recipient'] ?? $payload['email'] ?? '';
		$recipient     = sanitize_email( (string) $recipient_raw );
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

		$header = 'Content-Type: text/html; charset=UTF-8';
		$body   = TestEmailMessageBuilder::buildHtmlEmail( $message );

		$skip_fallback = static function () {
			return true;
		};
		$test_mode = static function () {
			return true;
		};
		$block_revamp_fallback = static function ( $try_fallback ) {
			return false;
		};

		add_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		add_filter( 'postman_test_email', $test_mode );
		add_filter( 'post_smtp_should_try_fallback', $block_revamp_fallback );

		if ( class_exists( 'Postman_Connection_Resolver' ) && ! empty( \Postman_Connection_Resolver::get_connections() ) ) {
			set_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT, $connection_id, 120 );
			if ( class_exists( ConnectionLegacySendBridge::class ) ) {
				$route_row = \Postman_Connection_Resolver::get_connection( $connection_id );
				if ( ! empty( $route_row ) ) {
					ConnectionLegacySendBridge::apply_runtime_patch_for_connection( $route_row, false );
				}
			}
		}

		$provider = (string) ( $connection['provider'] ?? $connection['transport_type'] ?? '' );

		$email_args = apply_filters(
			'postman_test_email_args',
			array(
				'email'               => $recipient,
				'subject'             => $subject,
				'message'             => $body,
				'header'              => $header,
				'connection_provider' => $provider,
				'connection_id'       => $connection_id,
			)
		);

		$mail_headers = $email_args['header'] ?? $header;
		if ( is_array( $mail_headers ) ) {
			$mail_headers = array_values(
				array_filter(
					array_map( 'trim', $mail_headers ),
					static function ( $line ) {
						return is_string( $line ) && '' !== $line;
					}
				)
			);
		} else {
			$mail_headers = (string) $mail_headers;
		}

		$wp_mail_failed_message = '';
		$capture_wp_mail_failed = static function ( $error ) use ( &$wp_mail_failed_message ) {
			if ( $error instanceof \WP_Error && '' === $wp_mail_failed_message ) {
				$wp_mail_failed_message = sanitize_text_field( (string) $error->get_error_message() );
			}
		};
		add_action( 'wp_mail_failed', $capture_wp_mail_failed, 10, 1 );

		$success = wp_mail(
			(string) ( $email_args['email'] ?? $recipient ),
			(string) ( $email_args['subject'] ?? $subject ),
			(string) ( $email_args['message'] ?? $body ),
			$mail_headers
		);

		remove_action( 'wp_mail_failed', $capture_wp_mail_failed, 10 );
		remove_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		remove_filter( 'postman_test_email', $test_mode );
		remove_filter( 'post_smtp_should_try_fallback', $block_revamp_fallback );

		if ( class_exists( 'Postman_Connection_Resolver' ) ) {
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
		} elseif ( '' !== $wp_mail_failed_message ) {
			$error_message = $wp_mail_failed_message;
		}

		return array(
			'success'    => false,
			'message'    => $error_message,
			'transcript' => isset( $result['transcript'] ) ? (string) $result['transcript'] : '',
		);
	}

	/**
	 * Send a test email using a staged connection row (setup wizard draft).
	 *
	 * @param array<string,mixed> $connection_row Decoded connection row.
	 * @param array<string,mixed> $payload        recipient, subject, message.
	 * @return array{success:bool,message:string,transcript?:string,time_ms?:int}
	 */
	public function sendWithDraftRow( array $connection_row, array $payload ): array {
		$recipient_raw = $payload['recipient'] ?? $payload['email'] ?? '';
		$recipient     = sanitize_email( (string) $recipient_raw );
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

		$row = SecretCodec::decodeConnectionRow( $connection_row );
		$validate = $this->validateConnectionRow( $row );
		if ( ! $validate['success'] ) {
			return $validate;
		}

		$header = 'Content-Type: text/html; charset=UTF-8';
		$body   = TestEmailMessageBuilder::buildHtmlEmail( $message );

		$skip_fallback = static function () {
			return true;
		};
		$test_mode = static function () {
			return true;
		};
		$block_revamp_fallback = static function ( $try_fallback ) {
			return false;
		};

		add_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		add_filter( 'postman_test_email', $test_mode );
		add_filter( 'post_smtp_should_try_fallback', $block_revamp_fallback );

		ConnectionLegacySendBridge::apply_runtime_patch_for_connection( $row, false );
		if ( class_exists( 'Postman_Connection_Resolver' ) ) {
			set_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT, 0, 120 );
		}

		$provider = (string) ( $row['provider'] ?? $row['transport_type'] ?? '' );

		$email_args = apply_filters(
			'postman_test_email_args',
			array(
				'email'               => $recipient,
				'subject'             => $subject,
				'message'             => $body,
				'header'              => $header,
				'connection_provider' => $provider,
				'connection_id'       => 0,
			)
		);

		$mail_headers = $email_args['header'] ?? $header;
		if ( is_array( $mail_headers ) ) {
			$mail_headers = array_values(
				array_filter(
					array_map( 'trim', $mail_headers ),
					static function ( $line ) {
						return is_string( $line ) && '' !== $line;
					}
				)
			);
		} else {
			$mail_headers = (string) $mail_headers;
		}

		$wp_mail_failed_message = '';
		$capture_wp_mail_failed = static function ( $error ) use ( &$wp_mail_failed_message ) {
			if ( $error instanceof \WP_Error && '' === $wp_mail_failed_message ) {
				$wp_mail_failed_message = sanitize_text_field( (string) $error->get_error_message() );
			}
		};

		$slug            = sanitize_key( $provider );
		$registry        = new ProviderRegistry();
		$has_native      = isset( $registry->all()[ $slug ] );
		$use_revamp      = CohortResolver::usesRevampMailPipeline();
		$failure_default = __( 'The test email could not be sent. Check your connection settings and try again.', 'post-smtp' );

		$wp_atts = array(
			'to'          => (string) ( $email_args['email'] ?? $recipient ),
			'subject'     => (string) ( $email_args['subject'] ?? $subject ),
			'message'     => (string) ( $email_args['message'] ?? $body ),
			'headers'     => $mail_headers,
			'attachments' => array(),
		);

		if ( $use_revamp && $has_native ) {
			do_action( 'post_smtp_revamp_before_provider_send', $row, false, 0 );
			$envelope    = EnvelopeFactory::fromWpMailArgs( $wp_atts );
			$send_result = $registry->create( $slug )->send(
				$envelope->atts,
				null,
				$row,
				$envelope->processed
			);
		} elseif ( $use_revamp ) {
			$send_result = RevampLegacyPostmanSend::send( $wp_atts, $failure_default );
		} else {
			add_action( 'wp_mail_failed', $capture_wp_mail_failed, 10, 1 );

			$legacy_success = wp_mail(
				$wp_atts['to'],
				$wp_atts['subject'],
				$wp_atts['message'],
				$wp_atts['headers']
			);

			remove_action( 'wp_mail_failed', $capture_wp_mail_failed, 10 );

			$result = apply_filters( 'postman_wp_mail_result', null );
			if ( ! is_array( $result ) ) {
				$result = array();
			}

			$error_message = $failure_default;
			if ( isset( $result['exception'] ) && $result['exception'] instanceof \Exception ) {
				$error_message = sanitize_text_field( $result['exception']->getMessage() );
			} elseif ( '' !== $wp_mail_failed_message ) {
				$error_message = $wp_mail_failed_message;
			}

			$send_result = array(
				'success'    => (bool) $legacy_success,
				'message'    => $legacy_success ? __( 'Test email sent successfully. Check your recipient inbox.', 'post-smtp' ) : $error_message,
				'transcript' => isset( $result['transcript'] ) ? (string) $result['transcript'] : '',
			);
		}

		remove_filter( 'post_smtp_test_email_skip_fallback', $skip_fallback );
		remove_filter( 'postman_test_email', $test_mode );
		remove_filter( 'post_smtp_should_try_fallback', $block_revamp_fallback );

		if ( class_exists( 'Postman_Connection_Resolver' ) ) {
			delete_transient( \Postman_Connection_Resolver::ROUTE_TRANSIENT );
		}

		if ( ! empty( $send_result['success'] ) ) {
			return array(
				'success' => true,
				'message' => __( 'Test email sent successfully. Check your recipient inbox.', 'post-smtp' ),
				'time_ms' => isset( $send_result['time'] ) ? (int) $send_result['time'] : 0,
			);
		}

		return array(
			'success'    => false,
			'message'    => (string) ( $send_result['message'] ?? $failure_default ),
			'transcript' => isset( $send_result['transcript'] ) ? (string) $send_result['transcript'] : '',
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
			if ( class_exists( 'Postman_Connection_Resolver' ) && ! empty( \Postman_Connection_Resolver::get_connections() ) ) {
				$row = \Postman_Connection_Resolver::get_connection( $connection_id );
				if ( ! empty( $row ) ) {
					return $row;
				}
			}
			if ( 0 === $connection_id ) {
				return array( 'provider' => 'legacy', 'id' => 0 );
			}
			return array();
		}

		if ( class_exists( 'Postman_Connection_Resolver' ) ) {
			$row = \Postman_Connection_Resolver::get_connection( $connection_id );
			if ( ! empty( $row ) ) {
				return $row;
			}
		}

		$repo = ConnectionRepositoryFactory::make();
		$row  = $repo->getConnection( $connection_id );
		if ( is_array( $row ) && ! empty( $row ) ) {
			return SecretCodec::decodeConnectionRow( $row );
		}

		return array();
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

		if ( 'office365_api' === $slug && class_exists( Office365ConnectionTokens::class ) ) {
			$decoded   = SecretCodec::decodeConnectionRow( $row );
			$has_oauth = Office365ConnectionTokens::isConnectedStored( $decoded );
			if ( ! $has_oauth && ! Office365ConnectionTokens::usesPerConnectionTokenStore() ) {
				$oauth = get_option( 'postman_office365_oauth', array() );
				$has_oauth = is_array( $oauth ) && '' !== (string) ( $oauth['refresh_token'] ?? '' );
			}
			if ( ! $has_oauth ) {
				return array(
					'success' => false,
					'message' => __( 'Connect to Microsoft API on this connection before sending a test email.', 'post-smtp' ),
				);
			}
		}

		return array( 'success' => true, 'message' => '' );
	}
}
