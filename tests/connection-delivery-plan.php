<?php
/**
 * Unit-style checks for primary/fallback resolution (run with php tests/connection-delivery-plan.php).
 *
 * @package PostSMTP\Tests
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

use PostSMTP\Mail\Modern\Delivery\Plan;

if ( ! class_exists( Plan::class ) ) {
	require_once dirname( __DIR__ ) . '/src/Mail/Modern/Delivery/Plan.php';
	require_once dirname( __DIR__ ) . '/src/Connections/ConnectionService.php';
}

final class InMemoryConnectionRepository implements \PostSMTP\Kernel\Data\ConnectionRepositoryInterface {

	private array $connections;
	private array $options;

	public function __construct( array $connections, array $options ) {
		$this->connections = $connections;
		$this->options     = $options;
	}

	public function getConnections(): array {
		return $this->connections;
	}

	public function getOptions(): array {
		return $this->options;
	}

	public function getConnection( $id ): array {
		return $this->connections[ (int) $id ] ?? array();
	}

	public function saveConnections( array $connections ): void {
		$this->connections = $connections;
	}

	public function saveOptions( array $options ): void {
		$this->options = $options;
	}
}

$connections = array(
	0 => array(
		'provider'         => 'amazon_ses_api',
		'connection_title' => 'Primary SES',
		'from_email'       => 'a@example.com',
	),
	1 => array(
		'provider'         => 'smtp',
		'connection_title' => 'Backup SMTP',
		'from_email'       => 'a@example.com',
		'fallback_enabled' => true,
	),
);

$options = array(
	'selected_primary' => 0,
);

$repo = new InMemoryConnectionRepository( $connections, $options );
$plan = Plan::resolve( $repo );

assert( $plan['primary']['provider'] === 'amazon_ses_api' );
assert( count( $plan['fallbacks'] ) === 1 );
assert( $plan['fallbacks'][0]['connection']['provider'] === 'smtp' );

echo "connection-delivery-plan: OK\n";
