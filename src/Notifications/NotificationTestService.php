<?php
/**
 * Sends channel test notifications for the revamp UI.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationTestService {

	private NotificationDeliveryService $delivery;

	public function __construct( ?NotificationDeliveryService $delivery = null ) {
		$this->delivery = $delivery ?? new NotificationDeliveryService();
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	public function sendTest( string $slug ): array {
		return $this->delivery->sendTest( $slug );
	}

	/**
	 * @deprecated Use sendTest()
	 * @return array{success:bool,message:string}
	 */
	public function sendAdminEmailTest(): array {
		return $this->sendTest( NotificationChannelRegistry::SLUG_ADMIN_EMAIL );
	}
}
