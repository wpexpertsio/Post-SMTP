<?php
/**
 * Provider discovery and registration.
 *
 * @package PostSMTP\Kernel\Providers
 */

namespace PostSMTP\Kernel\Providers;

use PostSMTP\Kernel\Providers\Legacy\LegacyProviderAdapter;
use PostSMTP\Kernel\Providers\Modern\BrevoProvider;
use PostSMTP\Kernel\Providers\Modern\CloudflareEmailProvider;
use PostSMTP\Kernel\Providers\Modern\ElasticemailProvider;
use PostSMTP\Kernel\Providers\Modern\EmailitProvider;
use PostSMTP\Kernel\Providers\Modern\GmailProvider;
use PostSMTP\Kernel\Providers\Modern\MailersendProvider;
use PostSMTP\Kernel\Providers\Modern\MailgunProvider;
use PostSMTP\Kernel\Providers\Modern\MailerooProvider;
use PostSMTP\Kernel\Providers\Modern\MailtrapProvider;
use PostSMTP\Kernel\Providers\Modern\PostmarkProvider;
use PostSMTP\Kernel\Providers\Modern\SendgridProvider;
use PostSMTP\Kernel\Providers\Modern\SendpulseProvider;
use PostSMTP\Kernel\Providers\Modern\SparkpostProvider;
use PostSMTP\Kernel\Providers\Modern\SmtpComProvider;
use PostSMTP\Kernel\Providers\Modern\SmtpProvider;
use PostSMTP\Kernel\Providers\Modern\Smtp2goProvider;
use PostSMTP\Kernel\Providers\Modern\MandrillProvider;
use PostSMTP\Kernel\Providers\Modern\ResendProvider;
use PostSMTP\Kernel\Providers\Modern\SweegoProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProviderRegistry {

	/** @var array<string,class-string<ProviderInterface>> */
	private array $providers = array();

	public function __construct() {
		$this->providers = array(
			GmailProvider::slug()      => GmailProvider::class,
			SmtpProvider::slug()       => SmtpProvider::class,
			SendgridProvider::slug()   => SendgridProvider::class,
			PostmarkProvider::slug()   => PostmarkProvider::class,
			MailgunProvider::slug()    => MailgunProvider::class,
			CloudflareEmailProvider::slug() => CloudflareEmailProvider::class,
			BrevoProvider::slug()      => BrevoProvider::class,
			SendpulseProvider::slug()  => SendpulseProvider::class,
			MailersendProvider::slug() => MailersendProvider::class,
			Smtp2goProvider::slug()    => Smtp2goProvider::class,
			ElasticemailProvider::slug() => ElasticemailProvider::class,
			SparkpostProvider::slug()  => SparkpostProvider::class,
			SmtpComProvider::slug()    => SmtpComProvider::class,
			MailerooProvider::slug()   => MailerooProvider::class,
			EmailitProvider::slug()    => EmailitProvider::class,
			MailtrapProvider::slug()   => MailtrapProvider::class,
			SweegoProvider::slug()     => SweegoProvider::class,
			ResendProvider::slug()     => ResendProvider::class,
			MandrillProvider::slug()   => MandrillProvider::class,
		);

		$this->providers = apply_filters( 'post_smtp_register_providers', $this->providers );
	}

	/**
	 * @return array<string,class-string<ProviderInterface>>
	 */
	public function all(): array {
		return $this->providers;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public function schemas(): array {
		$schemas = array();
		foreach ( $this->providers as $slug => $class ) {
			if ( is_string( $class ) && method_exists( $class, 'getOptions' ) ) {
				$schemas[ $slug ] = $class::getOptions();
			}
		}
		return $schemas;
	}

	public function create( string $slug ): ProviderInterface {
		if ( isset( $this->providers[ $slug ] ) && class_exists( $this->providers[ $slug ] ) ) {
			$class = $this->providers[ $slug ];
			return new $class();
		}
		return new LegacyProviderAdapter( $slug );
	}
}
