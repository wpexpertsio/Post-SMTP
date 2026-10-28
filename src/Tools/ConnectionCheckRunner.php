<?php
/**
 * API-oriented connection health checks (no SMTP port scanning).
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Kernel\Providers\ProviderRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ConnectionCheckRunner {

	/**
	 * @param array<string,mixed> $connection Full connection row (includes secrets).
	 * @return array<string,mixed>
	 */
	public function run( int $connection_id, array $connection ): array {
		$slug     = (string) ( $connection['provider'] ?? '' );
		$registry = new ProviderRegistry();
		$provider = $registry->create( $slug );

		$checks   = array();
		$checks[] = $this->checkConfiguration( $provider, $connection );

		$transport = $this->transportLabel( $slug, $connection );
		if ( 'SMTP' === $transport ) {
			$checks[] = $this->checkSmtpSettings( $connection );
		} else {
			$checks = array_merge( $checks, $this->checkApiMailer( $slug, $connection ) );
		}

		$checks[] = $this->checkReadyToSend( $checks );

		/**
		 * Filter connection check rows for a provider.
		 *
		 * @param array<int,array<string,mixed>> $checks       Built-in checks.
		 * @param int                            $connection_id Connection index.
		 * @param array<string,mixed>            $connection    Connection row.
		 * @param string                         $slug          Provider slug.
		 */
		$checks = apply_filters( 'post_smtp_connection_check_checks', $checks, $connection_id, $connection, $slug );

		$overall = $this->deriveOverallStatus( $checks );

		return array(
			'connection_id'  => $connection_id,
			'checked_at'     => gmdate( 'c' ),
			'overall_status' => $overall,
			'checks'         => array_values( $checks ),
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 */
	public function transportLabel( string $slug, array $connection ): string {
		if ( 'smtp' === $slug || 'default' === $slug ) {
			return 'SMTP';
		}
		if ( 'gmail_api' === $slug || 'office365_api' === $slug || 'zohomail_api' === $slug ) {
			return 'OAuth';
		}
		if ( ! empty( $connection['refresh_token'] ) || ! empty( $connection['access_token'] ) ) {
			return 'OAuth';
		}

		return 'API';
	}

	/**
	 * @param array<int,array<string,mixed>> $checks Check rows.
	 */
	private function deriveOverallStatus( array $checks ): string {
		$has_failed  = false;
		$has_warning = false;
		$has_passed  = false;

		foreach ( $checks as $check ) {
			$status = (string) ( $check['status'] ?? '' );
			if ( 'failed' === $status ) {
				$has_failed = true;
			} elseif ( 'warning' === $status ) {
				$has_warning = true;
			} elseif ( 'passed' === $status ) {
				$has_passed = true;
			}
		}

		if ( $has_failed ) {
			return 'failed';
		}
		if ( $has_warning ) {
			return 'warning';
		}
		if ( $has_passed ) {
			return 'passed';
		}

		return 'none';
	}

	/**
	 * @param object $provider Provider instance.
	 * @param array<string,mixed> $connection Connection row.
	 * @return array<string,mixed>
	 */
	private function checkConfiguration( $provider, array $connection ): array {
		$result = $provider->authenticate( $connection );
		if ( ! empty( $result['success'] ) ) {
			return $this->row(
				'configuration',
				__( 'Connection configuration', 'post-smtp' ),
				'passed',
				__( 'Required mailer settings are present.', 'post-smtp' )
			);
		}

		return $this->row(
			'configuration',
			__( 'Connection configuration', 'post-smtp' ),
			'failed',
			(string) ( $result['message'] ?? __( 'Complete the connection setup in Connections.', 'post-smtp' ) )
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array<string,mixed>
	 */
	private function checkSmtpSettings( array $connection ): array {
		$host = (string) ( $connection['hostname'] ?? '' );
		$port = (string) ( $connection['port'] ?? '' );
		if ( '' === $host || '' === $port ) {
			return $this->row(
				'smtp_settings',
				__( 'SMTP server settings', 'post-smtp' ),
				'failed',
				__( 'Hostname and port are required.', 'post-smtp' )
			);
		}

		return $this->row(
			'smtp_settings',
			__( 'SMTP server settings', 'post-smtp' ),
			'passed',
			sprintf(
				/* translators: 1: SMTP host, 2: port */
				__( 'Saved server %1$s on port %2$s. Use Send Test Email to verify delivery.', 'post-smtp' ),
				sanitize_text_field( $host ),
				sanitize_text_field( $port )
			)
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array<int,array<string,mixed>>
	 */
	private function checkApiMailer( string $slug, array $connection ): array {
		$checks = array();

		$checks[] = $this->checkCredentialsOrOAuth( $slug, $connection );

		$endpoint = $this->providerReachabilityTarget( $slug );
		if ( $endpoint ) {
			$checks[] = $this->probeEndpoint(
				'api_reachable',
				__( 'API endpoint reachable', 'post-smtp' ),
				$endpoint['url'],
				$endpoint['args'] ?? array(),
				'',
				false
			);
		} else {
			$checks[] = $this->row(
				'api_reachable',
				__( 'API endpoint reachable', 'post-smtp' ),
				'unavailable',
				__( 'Reachability test is not available for this mailer.', 'post-smtp' )
			);
		}

		$auth_check = $this->providerAuthProbe( $slug, $connection );
		if ( $auth_check ) {
			$checks[] = $auth_check;
		}

		return $checks;
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array<string,mixed>
	 */
	private function checkCredentialsOrOAuth( string $slug, array $connection ): array {
		if ( 'gmail_api' === $slug || 'office365_api' === $slug || 'zohomail_api' === $slug ) {
			$has_oauth = ! empty( $connection['refresh_token'] ) || ! empty( $connection['access_token'] );
			/**
			 * Filter OAuth credential presence for connection checks.
			 *
			 * @param bool|null           $has_oauth  Override detection, or null to use defaults.
			 * @param string              $slug       Provider slug.
			 * @param array<string,mixed> $connection Connection row.
			 */
			$filtered = apply_filters( 'post_smtp_connection_check_has_oauth', null, $slug, $connection );
			if ( is_bool( $filtered ) ) {
				$has_oauth = $filtered;
			}

			if ( $has_oauth ) {
				return $this->row(
					'credentials',
					__( 'Credentials / OAuth token OK', 'post-smtp' ),
					'passed',
					__( 'Token stored for this connection.', 'post-smtp' )
				);
			}

			$has_manual = ! empty( $connection['oauth_client_id'] ) && ! empty( $connection['oauth_client_secret'] );
			if ( $has_manual ) {
				return $this->row(
					'credentials',
					__( 'Credentials / OAuth token OK', 'post-smtp' ),
					'warning',
					__( 'OAuth client saved — connect your Google account in Connections if you have not already.', 'post-smtp' )
				);
			}

			return $this->row(
				'credentials',
				__( 'Credentials / OAuth token OK', 'post-smtp' ),
				'failed',
				__( 'OAuth is not connected for this mailer.', 'post-smtp' )
			);
		}

		$key = $this->firstApiKey( $connection );
		if ( '' !== $key ) {
			return $this->row(
				'credentials',
				__( 'Credentials / API key OK', 'post-smtp' ),
				'passed',
				__( 'API key is stored for this connection.', 'post-smtp' )
			);
		}

		return $this->row(
			'credentials',
			__( 'Credentials / API key OK', 'post-smtp' ),
			'failed',
			__( 'Add an API key in Connections.', 'post-smtp' )
		);
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 * @return array<string,mixed>|null
	 */
	private function providerAuthProbe( string $slug, array $connection ): ?array {
		switch ( $slug ) {
			case 'sendgrid_api':
				$key = (string) ( $connection['sendgrid_api_key'] ?? '' );
				if ( '' === $key ) {
					return null;
				}
				return $this->probeEndpoint(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'https://api.sendgrid.com/v3/user/profile',
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . sanitize_text_field( $key ),
						),
					),
					__( 'SendGrid accepted your API key.', 'post-smtp' )
				);

			case 'postmark_api':
				$key = (string) ( $connection['postmark_api_key'] ?? '' );
				if ( '' === $key ) {
					return null;
				}
				return $this->probeEndpoint(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'https://api.postmarkapp.com/server',
					array(
						'headers' => array(
							'Accept'                  => 'application/json',
							'X-Postmark-Server-Token' => sanitize_text_field( $key ),
						),
					),
					__( 'Postmark accepted your server token.', 'post-smtp' )
				);

			case 'sendinblue_api':
				$key = (string) ( $connection['sendinblue_api_key'] ?? '' );
				if ( '' === $key ) {
					return null;
				}
				return $this->probeEndpoint(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'https://api.brevo.com/v3/account',
					array(
						'headers' => array(
							'accept'  => 'application/json',
							'api-key' => sanitize_text_field( $key ),
						),
					),
					__( 'Brevo accepted your API key.', 'post-smtp' )
				);

			case 'mailgun_api':
				$key = (string) ( $connection['mailgun_api_key'] ?? '' );
				if ( '' === $key ) {
					return null;
				}
				return $this->probeEndpoint(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'https://api.mailgun.net/v3/domains',
					array(
						'headers' => array(
							'Authorization' => 'Basic ' . base64_encode( 'api:' . sanitize_text_field( $key ) ),
						),
					),
					__( 'Mailgun accepted your API key.', 'post-smtp' )
				);

			case 'gmail_api':
				/**
				 * Filter Gmail provider auth probe result.
				 *
				 * @param array<string,mixed>|null $check      Return a check row to override default.
				 * @param array<string,mixed>      $connection Connection row.
				 */
				$gmail = apply_filters( 'post_smtp_gmail_connection_check_auth', null, $connection );
				if ( is_array( $gmail ) ) {
					return $gmail;
				}
				if ( ! empty( $connection['refresh_token'] ) || ! empty( $connection['access_token'] ) ) {
					return $this->row(
						'provider_auth',
						__( 'Provider accepted auth', 'post-smtp' ),
						'passed',
						__( 'OAuth token is stored for Gmail.', 'post-smtp' )
					);
				}
				return $this->row(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'warning',
					__( 'Connect Gmail OAuth in Connections to verify provider auth.', 'post-smtp' )
				);

			default:
				/**
				 * Filter provider auth probe for other mailers.
				 *
				 * @param array<string,mixed>|null $check      Check row or null.
				 * @param string                   $slug       Provider slug.
				 * @param array<string,mixed>      $connection Connection row.
				 */
				$custom = apply_filters( 'post_smtp_connection_check_provider_auth', null, $slug, $connection );
				if ( is_array( $custom ) ) {
					return $custom;
				}
				return $this->row(
					'provider_auth',
					__( 'Provider accepted auth', 'post-smtp' ),
					'unavailable',
					__( 'Live auth verification is not available for this mailer yet.', 'post-smtp' )
				);
		}
	}

	/**
	 * @return array{url:string,args?:array<string,mixed>}|null
	 */
	private function providerReachabilityTarget( string $slug ): ?array {
		$map = array(
			'sendgrid_api'    => 'https://api.sendgrid.com/v3/user/profile',
			'postmark_api'    => 'https://api.postmarkapp.com/server',
			'sendinblue_api'  => 'https://api.brevo.com/v3/account',
			'mailgun_api'     => 'https://api.mailgun.net/v3/domains',
			'sparkpost_api'   => 'https://api.sparkpost.com/api/v1/account',
			'elasticemail_api' => 'https://api.elasticemail.com/v2/account/load',
			'mailersend_api'  => 'https://api.mailersend.com/v1/domains',
			'resend_api'      => 'https://api.resend.com/domains',
			'gmail_api'       => 'https://www.googleapis.com/',
		);

		if ( ! isset( $map[ $slug ] ) ) {
			return null;
		}

		return array(
			'url'  => $map[ $slug ],
			'args' => array(
				'method' => 'HEAD',
			),
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $checks Prior checks.
	 * @return array<string,mixed>
	 */
	private function checkReadyToSend( array $checks ): array {
		foreach ( $checks as $check ) {
			$status = (string) ( $check['status'] ?? '' );
			if ( 'failed' === $status ) {
				return $this->row(
					'ready',
					__( 'Ready to send', 'post-smtp' ),
					'failed',
					__( 'Fix failed checks before sending.', 'post-smtp' )
				);
			}
		}

		foreach ( $checks as $check ) {
			if ( 'warning' === ( $check['status'] ?? '' ) ) {
				return $this->row(
					'ready',
					__( 'Ready to send', 'post-smtp' ),
					'warning',
					__( 'Review warnings — delivery may still fail.', 'post-smtp' )
				);
			}
		}

		return $this->row(
			'ready',
			__( 'Ready to send', 'post-smtp' ),
			'passed',
			__( 'No blockers found. Inbox delivery is not guaranteed.', 'post-smtp' )
		);
	}

	/**
	 * @param array<string,mixed> $args wp_remote_request args.
	 */
	private function probeEndpoint(
		string $id,
		string $title,
		string $url,
		array $args = array(),
		string $success_detail = '',
		bool $auth_required = true
	): array {
		$start    = microtime( true );
		$response = wp_safe_remote_request(
			$url,
			array_merge(
				array(
					'timeout'   => 15,
					'method'    => 'GET',
					'sslverify' => true,
				),
				$args
			)
		);
		$elapsed = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			if ( stripos( $message, 'timed out' ) !== false || stripos( $message, 'timeout' ) !== false ) {
				return $this->row(
					$id,
					$title,
					'failed',
					__( 'The provider did not respond in time. Try again.', 'post-smtp' )
				);
			}

			return $this->row( $id, $title, 'failed', sanitize_text_field( $message ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$port = wp_parse_url( $url, PHP_URL_PORT ) ?: ( 0 === strpos( $url, 'https' ) ? 443 : 80 );

		if ( $code >= 200 && $code < 400 ) {
			$detail = $success_detail !== ''
				? $success_detail
				: sprintf(
					/* translators: 1: host, 2: port, 3: milliseconds */
					__( '%1$s:%2$d responded in %3$d ms', 'post-smtp' ),
					(string) $host,
					(int) $port,
					$elapsed
				);

			return $this->row( $id, $title, 'passed', $detail );
		}

		if ( ! $auth_required && ( 401 === $code || 403 === $code || 405 === $code ) ) {
			return $this->row(
				$id,
				$title,
				'passed',
				sprintf(
					/* translators: 1: host, 2: port, 3: milliseconds */
					__( '%1$s:%2$d responded in %3$d ms', 'post-smtp' ),
					(string) $host,
					(int) $port,
					$elapsed
				)
			);
		}

		if ( 401 === $code || 403 === $code ) {
			$body = wp_remote_retrieve_body( $response );
			$hint = $this->sanitizeProviderError( $body, $code );
			$fail_title = 'provider_auth' === $id
				? __( 'Provider refused the connection', 'post-smtp' )
				: $title;

			return $this->row(
				$id,
				$fail_title,
				'warning',
				$hint
			);
		}

		return $this->row(
			$id,
			$title,
			'failed',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'Provider returned HTTP %d.', 'post-smtp' ),
				$code
			)
		);
	}

	private function sanitizeProviderError( string $body, int $code ): string {
		$snippet = wp_strip_all_tags( $body );
		$snippet = preg_replace( '/\s+/', ' ', $snippet ) ?? '';
		$snippet = trim( $snippet );
		if ( strlen( $snippet ) > 160 ) {
			$snippet = substr( $snippet, 0, 157 ) . '…';
		}
		if ( '' === $snippet ) {
			return sprintf(
				/* translators: %d: HTTP status code */
				__( 'HTTP %d — authentication or permission failed.', 'post-smtp' ),
				$code
			);
		}

		return sprintf( '%d — %s', $code, $snippet );
	}

	/**
	 * @param array<string,mixed> $connection Connection row.
	 */
	private function firstApiKey( array $connection ): string {
		$keys = array(
			'sendgrid_api_key',
			'sendinblue_api_key',
			'postmark_api_key',
			'mailgun_api_key',
			'sparkpost_api_key',
			'elasticemail_api_key',
			'smtp2go_api_key',
			'mailersend_api_key',
			'smtp_com_api_key',
			'emailit_api_key',
			'maileroo_api_key',
			'sweego_api_key',
			'resend_api_key',
			'mailtrap_api_key',
			'mandrill_api_key',
			'cloudflare_email_api_key',
			'sendpulse_api_key',
			'api_key',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $connection[ $key ] ) && '••••••••' !== $connection[ $key ] ) {
				return (string) $connection[ $key ];
			}
		}

		return '';
	}

	/**
	 * @return array<string,mixed>
	 */
	private function row( string $id, string $title, string $status, string $detail ): array {
		return array(
			'id'     => $id,
			'title'  => $title,
			'status' => $status,
			'detail' => $detail,
		);
	}
}
