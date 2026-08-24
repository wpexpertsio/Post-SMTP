<?php
/**
 * Provider discovery and registration.
 *
 * @package PostSMTP\Kernel\Providers
 */

namespace PostSMTP\Kernel\Providers;

use PostSMTP\Kernel\Providers\Legacy\LegacyProviderAdapter;
use PostSMTP\Kernel\Providers\Modern\BrevoProvider;
use PostSMTP\Kernel\Providers\Modern\SendgridProvider;
use PostSMTP\Kernel\Providers\Modern\SmtpProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProviderRegistry {

	/** @var array<string,class-string<ProviderInterface>> */
	private array $providers = array();

	public function __construct() {
		$this->providers = array(
			SmtpProvider::slug()       => SmtpProvider::class,
			SendgridProvider::slug()   => SendgridProvider::class,
			BrevoProvider::slug()      => BrevoProvider::class,
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
