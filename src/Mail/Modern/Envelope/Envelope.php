<?php
/**
 * Normalized wp_mail payload for modern providers.
 *
 * @package PostSMTP\Mail\Modern\Envelope
 */

namespace PostSMTP\Mail\Modern\Envelope;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Envelope {

	/** @var array<string,mixed> */
	public array $atts;

	/** @var array<string,mixed> */
	public array $processed;

	/**
	 * @param array<string,mixed> $atts      Raw wp_mail arguments (after core filters).
	 * @param array<string,mixed> $processed Provider-facing normalized data.
	 */
	public function __construct( array $atts, array $processed ) {
		$this->atts      = $atts;
		$this->processed = $processed;
	}
}
