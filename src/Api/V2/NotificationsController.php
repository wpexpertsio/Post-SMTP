<?php
/**
 * Notifications REST endpoints.
 *
 * @package PostSMTP\Api\V2
 */

namespace PostSMTP\Api\V2;

use PostSMTP\Notifications\NotificationChannelRegistry;
use PostSMTP\Notifications\NotificationSettingsRepository;
use PostSMTP\Notifications\NotificationTestService;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationsController extends BaseController {

	private const ALLOWED_SLUGS = array(
		NotificationChannelRegistry::SLUG_ADMIN_EMAIL,
		NotificationChannelRegistry::SLUG_CHROME,
		NotificationChannelRegistry::SLUG_SLACK,
		NotificationChannelRegistry::SLUG_PUSHOVER,
		NotificationChannelRegistry::SLUG_WEBHOOK,
		NotificationChannelRegistry::SLUG_TEAMS,
		NotificationChannelRegistry::SLUG_TWILIO,
	);

	private NotificationSettingsRepository $settings;

	private NotificationTestService $tests;

	public function __construct() {
		$this->settings = new NotificationSettingsRepository();
		$this->tests    = new NotificationTestService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/notifications',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getNotifications' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/notifications/channels/(?P<slug>[a-z_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'saveChannel' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/notifications/channels/(?P<slug>[a-z_]+)/test',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'testChannel' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getNotifications() {
		$summaries        = $this->settings->getChannelSummaries();
		$channel_settings = array();
		foreach ( $summaries as $channel ) {
			$slug = (string) ( $channel['slug'] ?? '' );
			if ( '' === $slug ) {
				continue;
			}
			$channel_settings[ $slug ] = $this->settings->getChannelSettings( $slug );
		}

		/**
		 * Pro channel upsell cards shown in the notifications aside (free sites only in the admin UI).
		 *
		 * @param list<array<string,mixed>> $promos Each item: slug, title, description, upgrade_url, upgrade_label.
		 */
		$pro_channel_promos = apply_filters(
			'post_smtp_notification_pro_channel_promos',
			array(
				array(
					'slug'          => NotificationChannelRegistry::SLUG_TEAMS,
					'title'         => __( 'Microsoft Teams', 'post-smtp' ),
					'description'   => __( 'Send failure alerts into a Microsoft Teams channel.', 'post-smtp' ),
					'upgrade_url'   => 'https://postmansmtp.com/pricing/',
					'upgrade_label' => __( 'Upgrade to Pro', 'post-smtp' ),
				),
				array(
					'slug'          => NotificationChannelRegistry::SLUG_TWILIO,
					'title'         => __( 'Twilio (SMS)', 'post-smtp' ),
					'description'   => __( 'Text failure alerts to a mobile number over SMS.', 'post-smtp' ),
					'upgrade_url'   => 'https://postmansmtp.com/pricing/',
					'upgrade_label' => __( 'Upgrade to Pro', 'post-smtp' ),
				),
			)
		);

		return rest_ensure_response(
			array(
				'channels'           => $summaries,
				'channel_settings'   => $channel_settings,
				'admin_email'        => $channel_settings[ NotificationChannelRegistry::SLUG_ADMIN_EMAIL ] ?? array(),
				'is_pro'             => $this->isPro(),
				'default_channel'    => NotificationChannelRegistry::SLUG_ADMIN_EMAIL,
				'pro_channel_promos' => is_array( $pro_channel_promos ) ? $pro_channel_promos : array(),
				'docs'               => array(
					'admin_email' => 'https://postmansmtp.com/docs/alerts/admin-email-failure-alerts/',
					'chrome'      => 'https://postmansmtp.com/docs/alerts/chrome-extension/',
					'slack'       => 'https://postmansmtp.com/docs/alerts/slack/',
					'pushover'    => 'https://postmansmtp.com/docs/alerts/pushover/',
					'webhook'     => 'https://postmansmtp.com/docs/alerts/webhook/',
					'teams'       => 'https://postmansmtp.com/docs/alerts/microsoft-teams/',
					'twilio'      => 'https://postmansmtp.com/documentation/extension/twilio-sms-notification-extension/',
				),
			)
		);
	}

	public function saveChannel( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$slug = sanitize_key( (string) $request->get_param( 'slug' ) );
		if ( ! in_array( $slug, self::ALLOWED_SLUGS, true ) ) {
			return new \WP_Error( 'rest_not_found', __( 'Unknown notification channel.', 'post-smtp' ), array( 'status' => 404 ) );
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$result = $this->settings->saveChannelSettings( $slug, $params );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_notification_save_failed',
				$result['message'] ?? __( 'Could not save notification settings.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response(
			array(
				'success'          => true,
				'message'          => $result['message'],
				'channels'         => $this->settings->getChannelSummaries(),
				'channel_settings' => array(
					$slug => $this->settings->getChannelSettings( $slug ),
				),
			)
		);
	}

	public function testChannel( WP_REST_Request $request ) {
		if ( ! $this->checkNonce( $request ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid nonce.', 'post-smtp' ), array( 'status' => 403 ) );
		}

		$slug = sanitize_key( (string) $request->get_param( 'slug' ) );
		if ( ! in_array( $slug, self::ALLOWED_SLUGS, true ) ) {
			return new \WP_Error( 'rest_not_found', __( 'Unknown notification channel.', 'post-smtp' ), array( 'status' => 404 ) );
		}

		$result = $this->tests->sendTest( $slug );

		if ( empty( $result['success'] ) ) {
			return new \WP_Error(
				'post_smtp_notification_test_failed',
				$result['message'] ?? __( 'Test notification failed.', 'post-smtp' ),
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}

	private function isPro(): bool {
		return defined( 'POST_SMTP_PRO_VERSION' )
			|| ( function_exists( 'pspro_fs' ) && pspro_fs()->is_registered() )
			|| ( function_exists( 'post_smtp_has_pro' ) && post_smtp_has_pro() );
	}
}
