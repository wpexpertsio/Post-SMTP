<?php
/**
 * Diagnostic report collector for Tools → Diagnostic Report.
 *
 * @package PostSMTP\Tools
 */

namespace PostSMTP\Tools;

use PostSMTP\Connections\ConnectionService;
use PostSMTP\Connections\MailerCatalog;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DiagnosticReportCollector {

	/** @var array<int,array{label:string,value:string}> */
	private array $rows = array();

	/** @var array<string,mixed> */
	private $options;

	public function __construct() {
		if ( class_exists( '\PostmanOptions' ) ) {
			$this->options = \PostmanOptions::getInstance();
		}
	}

	/**
	 * @return array{rows:array<int,array{label:string,value:string}>,plain_text:string,generated_at:string}
	 */
	public function collect(): array {
		$this->rows = array();

		$connection_meta = $this->buildConnectionMeta();

		$this->add( __( 'Primary Connection (Mailer)', 'post-smtp' ), $connection_meta['primary'] );
		$this->add( __( 'Fallback Connection', 'post-smtp' ), $connection_meta['fallback'] );

		$this->addEnvironmentRows();
		$this->addWordPressRows();
		$this->addPostSmtpRows( $connection_meta );

		/**
		 * Filter diagnostic report rows before persistence/API response.
		 *
		 * @param array<int,array{label:string,value:string}> $rows Report rows.
		 */
		$this->rows = apply_filters( 'post_smtp_diagnostic_report_rows', $this->rows );

		$plain = $this->buildPlainText( $this->rows );
		$generated_at = gmdate( 'c' );

		update_option( 'post_smtp_diagnostic_report_v2', array( 'rows' => $this->rows, 'generated_at' => $generated_at ), false );
		update_option( 'post_smtp_clean_diagnostic_report_data', $plain, false );

		return array(
			'rows'         => $this->rows,
			'plain_text'   => $plain,
			'generated_at' => $generated_at,
		);
	}

	/**
	 * @return array{rows:array<int,array{label:string,value:string}>,plain_text:string,generated_at:string}|null
	 */
	public function getCached(): ?array {
		$cached = get_option( 'post_smtp_diagnostic_report_v2', null );
		if ( ! is_array( $cached ) || empty( $cached['rows'] ) ) {
			$legacy = get_option( 'post_smtp_clean_diagnostic_report_data', '' );
			if ( is_string( $legacy ) && '' !== trim( $legacy ) ) {
				return array(
					'rows'         => $this->parseLegacyPlainText( $legacy ),
					'plain_text'   => $legacy,
					'generated_at' => '',
				);
			}
			return null;
		}

		$rows = $cached['rows'];
		return array(
			'rows'         => is_array( $rows ) ? $rows : array(),
			'plain_text'   => $this->buildPlainText( is_array( $rows ) ? $rows : array() ),
			'generated_at' => (string) ( $cached['generated_at'] ?? '' ),
		);
	}

	private function buildConnectionMeta(): array {
		if ( ConnectionRepositoryFactory::isLegacyMode() || ! class_exists( ConnectionService::class ) ) {
			$mailer = class_exists( '\PostmanOptions' )
				? (string) \PostmanOptions::getInstance()->getSmtpMailer()
				: __( 'Not configured', 'post-smtp' );

			return array(
				'primary'         => $mailer,
				'fallback'        => __( 'None', 'post-smtp' ),
				'active'          => $mailer,
				'ready_connected' => 'n/a',
				'api_status'      => __( 'Not checked', 'post-smtp' ),
			);
		}

		$service     = new ConnectionService();
		$repo        = ConnectionRepositoryFactory::make();
		$connections = $service->normalizeOnRead( $repo->getConnections(), $repo->getOptions() );
		$labels      = $this->mailerLabels();

		$meta = array(
			'primary'         => __( 'Not configured', 'post-smtp' ),
			'fallback'        => __( 'None', 'post-smtp' ),
			'active'          => '',
			'ready_connected' => '',
			'api_status'      => __( 'Not checked — run Check Connection', 'post-smtp' ),
		);

		foreach ( $connections as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$role    = ConnectionService::deriveRole( (int) $index, $connections );
			$summary = $this->connectionSummary( $row, $labels );

			if ( 'primary' === ( $role['role'] ?? '' ) ) {
				$meta['primary']         = $summary;
				$meta['active']          = $this->activeConnectionLabel( $row, $labels );
				$meta['ready_connected'] = $this->connectionReadyStatus( (int) $index, $row );
				$meta['api_status']      = $this->apiProviderStatus( (int) $index );
			} elseif ( 'fallback' === ( $role['role'] ?? '' ) && ! empty( $row['fallback_enabled'] ) ) {
				$meta['fallback'] = $summary;
			}
		}

		if ( '' === $meta['active'] ) {
			$meta['active'] = $meta['primary'];
		}
		if ( '' === $meta['ready_connected'] ) {
			$meta['ready_connected'] = 'No | Not checked';
		}

		return $meta;
	}

	/**
	 * @param array<string,string> $connection_meta From buildConnectionMeta().
	 */
	private function addEnvironmentRows(): void {
		$curl = function_exists( 'curl_version' ) ? curl_version() : array();
		$this->add( 'HostName', class_exists( '\PostmanUtils' ) ? \PostmanUtils::getServerName() : wp_parse_url( home_url(), PHP_URL_HOST ) );
		$this->add( 'cURL Version', (string) ( $curl['version'] ?? 'n/a' ) );
		$this->add( 'OpenSSL Version', (string) ( $curl['ssl_version'] ?? 'n/a' ) );
		$this->add( 'OS', php_uname() );

		$memory = ini_get( 'memory_limit' );
		$max_ex = ini_get( 'max_execution_time' );
		$php    = sprintf(
			'%s %s [fpm-fcgi] memory_limit=%s max_execution_time=%s',
			PHP_OS,
			PHP_VERSION,
			$memory ?: 'n/a',
			$max_ex !== false && $max_ex !== '' ? (string) $max_ex : 'n/a'
		);
		$this->add( 'PHP', $php );
		$this->add( 'PHP Dependencies', $this->getPhpDependencies() );
	}

	private function addWordPressRows(): void {
		$wp = ( is_multisite() ? 'Multisite ' : '' )
			. get_bloginfo( 'version' ) . ' ' . get_locale() . ' ' . get_bloginfo( 'charset', 'display' )
			. ' [multisite: ' . ( is_multisite() ? 'Yes' : 'No' ) . ']';
		$this->add( 'WordPress', $wp );
		$this->add( 'WordPress Theme', (string) wp_get_theme() );
		$this->add( 'WordPress Plugins', $this->getActivePlugins() );

		$wp_mail_file = 'n/a';
		if ( class_exists( 'ReflectionFunction' ) ) {
			$wp_mail = new \ReflectionFunction( 'wp_mail' );
			$wp_mail_file = (string) realpath( $wp_mail->getFileName() );
		}
		$this->add( 'WordPress wp_mail Owner', $wp_mail_file );
		$this->add( 'WordPress wp_mail Filter(s)', $this->getFilters( 'wp_mail' ) ?: 'none' );
		$this->add( 'WordPress wp_mail_from Filter(s)', $this->getFilters( 'wp_mail_from' ) ?: 'none' );
		$this->add( 'WordPress wp_mail_from_name Filter(s)', $this->getFilters( 'wp_mail_from_name' ) ?: 'none' );
		$this->add( 'WordPress wp_mail_content_type Filter(s)', $this->getFilters( 'wp_mail_content_type' ) ?: 'none' );
		$this->add( 'WordPress wp_mail_charset Filter(s)', $this->getFilters( 'wp_mail_charset' ) ?: 'none' );
		$this->add( 'WordPress phpmailer_init Action(s)', $this->getFilters( 'phpmailer_init' ) ?: 'none' );
	}

	private function addPostSmtpRows( array $connection_meta ): void {
		if ( ! $this->options ) {
			return;
		}

		$plugin_data = apply_filters( 'postman_get_plugin_metadata', null );
		$version     = is_array( $plugin_data ) ? (string) ( $plugin_data['version'] ?? '' ) : '';
		$this->add( __( 'Post SMTP Version', 'post-smtp' ), $version );

		$s1 = $this->options->getEnvelopeSender();
		$s2 = $this->options->getMessageSenderEmail();
		if ( ! empty( $s1 ) || ! empty( $s2 ) ) {
			$env_domain = substr( strrchr( (string) $s1, '@' ), 1 );
			$msg_domain = substr( strrchr( (string) $s2, '@' ), 1 );
			$this->add(
				__( 'Sender Domain (Envelope|Message)', 'post-smtp' ),
				$env_domain . ' | ' . $msg_domain
			);
		}

		$this->add(
			__( 'Force From Email|Name', 'post-smtp' ),
			( $this->options->isSenderEmailOverridePrevented() ? 'Yes' : 'No' )
			. ' | '
			. ( $this->options->isSenderNameOverridePrevented() ? 'Yes' : 'No' )
		);

		$this->add( __( 'Active Connection', 'post-smtp' ), $connection_meta['active'] );
		$this->add( __( 'Connection Status (Ready|Connected)', 'post-smtp' ), $connection_meta['ready_connected'] );
		$this->add( __( 'API Provider Status', 'post-smtp' ), $connection_meta['api_status'] );

		if ( class_exists( '\PostmanState' ) ) {
			$this->add(
				__( 'Deliveries (Success|Fail)', 'post-smtp' ),
				\PostmanState::getInstance()->getSuccessfulDeliveries()
				. ' | '
				. \PostmanState::getInstance()->getFailedDeliveries()
			);
		}

		$connect = (int) $this->options->getConnectionTimeout();
		$read    = (int) $this->options->getReadTimeout();
		if ( class_exists( '\PostmanOptions' ) ) {
			$this->add(
				__( 'API Timeout (Connect|Read)', 'post-smtp' ),
				$connect . ' | ' . $read
			);
		}

		$this->add(
			__( 'Email Log (Enabled|Limit|Transcript Size)', 'post-smtp' ),
			( $this->options->isMailLoggingEnabled() ? 'Yes' : 'No' )
			. ' | '
			. $this->options->getMailLoggingMaxEntries()
			. ' | '
			. $this->options->getTranscriptSize()
		);

		$run_mode = $this->options->getRunMode();
		if ( class_exists( '\PostmanOptions' ) && $run_mode !== \PostmanOptions::RUN_MODE_PRODUCTION ) {
			$this->add( __( 'Run Mode', 'post-smtp' ), (string) $run_mode );
		} else {
			$this->add( __( 'Run Mode', 'post-smtp' ), 'production' );
		}

		$this->add(
			__( 'Stealth Mode', 'post-smtp' ),
			$this->options->isStealthModeEnabled() ? 'Yes' : 'No'
		);
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @param array<string,string> $labels Mailer labels.
	 */
	private function connectionSummary( array $row, array $labels ): string {
		$provider = (string) ( $row['provider'] ?? '' );
		$name     = $this->displayMailerName( $provider, $labels );
		$transport = ( new ConnectionCheckRunner() )->transportLabel( $provider, $row );

		return $name . ' — ' . $transport;
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 * @param array<string,string> $labels Mailer labels.
	 */
	private function activeConnectionLabel( array $row, array $labels ): string {
		$provider = (string) ( $row['provider'] ?? '' );
		$name     = $labels[ $provider ] ?? ucwords( str_replace( '_', ' ', $provider ) );
		$uri      = $this->providerEndpointHint( $provider );

		return $uri ? sprintf( '%s [%s]', $name, $uri ) : $name;
	}

	private function providerEndpointHint( string $provider ): string {
		$map = array(
			'gmail_api'      => 'https://www.googleapis.com:443',
			'sendgrid_api'   => 'https://api.sendgrid.com:443',
			'postmark_api'   => 'https://api.postmarkapp.com:443',
			'mailgun_api'    => 'https://api.mailgun.net:443',
			'sendinblue_api' => 'https://api.brevo.com:443',
		);

		return $map[ $provider ] ?? '';
	}

	/**
	 * @param array<string,mixed> $row Connection row.
	 */
	private function connectionReadyStatus( int $connection_id, array $row ): string {
		$registry = new \PostSMTP\Kernel\Providers\ProviderRegistry();
		$slug     = (string) ( $row['provider'] ?? '' );
		$provider = $registry->create( $slug );
		$auth     = $provider->authenticate( $row );
		$ready    = ! empty( $auth['success'] ) ? 'Yes' : 'No';

		$history = get_option( 'post_smtp_connection_check_history', array() );
		$last    = is_array( $history ) ? ( $history[ (string) $connection_id ] ?? null ) : null;
		$connected = 'Not checked';
		if ( is_array( $last ) ) {
			$status = (string) ( $last['overall_status'] ?? '' );
			if ( 'passed' === $status ) {
				$connected = 'Yes';
			} elseif ( in_array( $status, array( 'failed', 'warning' ), true ) ) {
				$connected = 'No';
			}
		}

		return $ready . ' | ' . $connected;
	}

	private function apiProviderStatus( int $connection_id ): string {
		$history = get_option( 'post_smtp_connection_check_history', array() );
		if ( ! is_array( $history ) || empty( $history[ (string) $connection_id ] ) ) {
			return __( 'Not checked — run Check Connection', 'post-smtp' );
		}

		$last   = $history[ (string) $connection_id ];
		$status = (string) ( $last['overall_status'] ?? 'none' );
		$checked = isset( $last['checked_at'] ) ? strtotime( (string) $last['checked_at'] ) : false;
		$when    = $checked ? wp_date( __( 'M j, Y g:i a', 'post-smtp' ), $checked, wp_timezone() ) : '';

		$label = 'passed' === $status
			? __( 'operational', 'post-smtp' )
			: ( 'warning' === $status ? __( 'warning', 'post-smtp' ) : __( 'issue detected', 'post-smtp' ) );

		return $when
			? sprintf( '%s [%s — last check %s]', $label, ucfirst( $status ), $when )
			: $label;
	}

	/**
	 * @return array<string,string>
	 */
	private function mailerLabels(): array {
		$labels = array();
		foreach ( MailerCatalog::all() as $item ) {
			if ( ! empty( $item['slug'] ) ) {
				$labels[ (string) $item['slug'] ] = (string) ( $item['label'] ?? $item['slug'] );
			}
		}
		return $labels;
	}

	private function displayMailerName( string $provider, array $labels ): string {
		$map = array(
			'gmail_api' => 'Gmail / Google Workspace',
		);
		if ( isset( $map[ $provider ] ) ) {
			return $map[ $provider ];
		}

		return $labels[ $provider ] ?? ucwords( str_replace( '_', ' ', $provider ) );
	}

	private function add( string $label, ?string $value ): void {
		if ( null === $value || '' === trim( $value ) ) {
			return;
		}
		$this->rows[] = array(
			'label' => $label,
			'value' => $value,
		);
	}

	/**
	 * @param array<int,array{label:string,value:string}> $rows Rows.
	 */
	private function buildPlainText( array $rows ): string {
		$lines = array();
		foreach ( $rows as $row ) {
			$lines[] = sprintf( '%s: %s', $row['label'], $row['value'] );
		}
		return implode( PHP_EOL, $lines ) . PHP_EOL;
	}

	/**
	 * @return array<int,array{label:string,value:string}>
	 */
	private function parseLegacyPlainText( string $plain ): array {
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $plain ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, ':' ) ) {
				continue;
			}
			$parts = explode( ':', $line, 2 );
			$rows[] = array(
				'label' => trim( $parts[0] ),
				'value' => trim( $parts[1] ?? '' ),
			);
		}
		return $rows;
	}

	private function getActivePlugins(): string {
		$apl     = get_option( 'active_plugins', array() );
		$plugins = get_plugins();
		$names   = array();
		if ( is_array( $apl ) ) {
			foreach ( $apl as $p ) {
				if ( isset( $plugins[ $p ] ) ) {
					$names[] = $plugins[ $p ]['Name'] ?? $p;
				}
			}
		}
		return implode( ', ', $names );
	}

	private function getPhpDependencies(): string {
		if ( ! class_exists( '\PostmanPreRequisitesCheck' ) ) {
			return 'n/a';
		}
		$apl  = \PostmanPreRequisitesCheck::getState();
		$text = array();
		foreach ( $apl as $p ) {
			$text[] = ( $p['name'] ?? '' ) . '=' . ( ! empty( $p['ready'] ) ? 'Yes' : 'No' );
		}
		return implode( ', ', $text );
	}

	private function getFilters( string $hook ): ?string {
		global $wp_filter;
		if ( empty( $hook ) || ! isset( $wp_filter[ $hook ] ) ) {
			return null;
		}
		$function_array = array();
		foreach ( $wp_filter[ $hook ] as $functions ) {
			foreach ( $functions as $function ) {
				$thing = $function['function'];
				if ( is_array( $thing ) ) {
					$function_array[] = get_class( $thing[0] ) . '->' . $thing[1];
				} elseif ( is_object( $thing ) ) {
					$function_array[] = 'Anonymous Function';
				} else {
					$function_array[] = (string) $thing;
				}
			}
		}

		return implode( ', ', $function_array );
	}
}
