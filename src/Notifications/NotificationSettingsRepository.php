<?php
/**
 * Reads/writes revamp notification channel settings with legacy migration.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NotificationSettingsRepository {

	use NotificationChannelPersistence;

	public const OPTION_CHANNELS = 'post_smtp_notification_channels';

	/**
	 * @return array<string,mixed>
	 */
	public function getStoredChannels(): array {
		$stored = get_option( self::OPTION_CHANNELS, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getAdminEmailSettings(): array {
		$stored   = $this->getStoredChannels();
		$legacy   = $this->getLegacyOptions();
		$has_row  = isset( $stored[ NotificationChannelRegistry::SLUG_ADMIN_EMAIL ] )
			&& is_array( $stored[ NotificationChannelRegistry::SLUG_ADMIN_EMAIL ] );

		if ( $has_row ) {
			$row = $stored[ NotificationChannelRegistry::SLUG_ADMIN_EMAIL ];
			return array(
				'enabled' => ! empty( $row['enabled'] ),
				'email'   => isset( $row['email'] ) ? sanitize_text_field( (string) $row['email'] ) : '',
			);
		}

		return array(
			'enabled' => $this->legacyAdminEmailEnabled( $legacy ),
			'email'   => $this->legacyStoredAdminEmail( $legacy ),
		);
	}

	/**
	 * @param array{enabled:bool,email:string} $settings
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	public function saveAdminEmailSettings( array $settings ): array {
		$enabled = ! empty( $settings['enabled'] );
		$email   = isset( $settings['email'] ) ? trim( (string) $settings['email'] ) : '';

		if ( '' !== $email && ! is_email( $email ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid admin email address.', 'post-smtp' ),
			);
		}

		$effective = $this->resolveEffectiveAdminEmail( $email );

		if ( ! is_email( $effective ) ) {
			return array(
				'success' => false,
				'message' => __( 'Could not resolve a valid administrator email address.', 'post-smtp' ),
			);
		}

		$channels = $this->getStoredChannels();
		$channels[ NotificationChannelRegistry::SLUG_ADMIN_EMAIL ] = array(
			'enabled' => $enabled,
			'email'   => $email,
		);
		update_option( self::OPTION_CHANNELS, $channels, false );

		$this->syncLegacyAdminEmail( $enabled, $email );

		return array(
			'success'  => true,
			'message'  => __( 'Notification settings saved.', 'post-smtp' ),
			'settings' => array(
				'enabled'          => $enabled,
				'email'            => $email,
				'effective_email'  => $effective,
			),
		);
	}

	public function resolveEffectiveAdminEmail( string $configured_email = '' ): string {
		$configured_email = trim( $configured_email );
		if ( '' !== $configured_email && is_email( $configured_email ) ) {
			return sanitize_email( $configured_email );
		}

		return sanitize_email( (string) get_option( 'admin_email' ) );
	}

	/**
	 * @return list<array<string,mixed>>
	 */
	public function getChannelSummaries(): array {
		$summaries = array();
		foreach ( NotificationChannelRegistry::all() as $channel ) {
			$slug    = (string) $channel['slug'];
			$enabled = $this->isChannelEnabled( $slug );
			$valid   = $this->isChannelConfigured( $slug );

			$summaries[] = array_merge(
				$channel,
				array(
					'enabled'       => $enabled,
					'status_active' => $enabled && $valid && empty( $channel['pro_locked'] ),
				)
			);
		}

		return $summaries;
	}

	public function isChannelEnabled( string $slug ): bool {
		$stored = $this->getStoredChannels();
		if ( isset( $stored[ $slug ]['enabled'] ) ) {
			return (bool) $stored[ $slug ]['enabled'];
		}

		return $this->legacyChannelEnabled( $slug );
	}

	public function isChannelConfigured( string $slug ): bool {
		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_ADMIN_EMAIL:
				$settings = $this->getAdminEmailSettings();
				if ( empty( $settings['enabled'] ) ) {
					return false;
				}
				return (bool) is_email( $this->resolveEffectiveAdminEmail( (string) $settings['email'] ) );
			case NotificationChannelRegistry::SLUG_CHROME:
				if ( ! $this->isChannelEnabled( $slug ) ) {
					return false;
				}
				return ! empty( PostmanNotifyOptions::getInstance()->getNotificationChromeUid() );
			case NotificationChannelRegistry::SLUG_SLACK:
				if ( ! $this->isChannelEnabled( $slug ) ) {
					return false;
				}
				return ! empty( PostmanNotifyOptions::getInstance()->getSlackToken() );
			case NotificationChannelRegistry::SLUG_PUSHOVER:
				if ( ! $this->isChannelEnabled( $slug ) ) {
					return false;
				}
				$opts = PostmanNotifyOptions::getInstance();
				return ! empty( $opts->getPushoverUser() ) && ! empty( $opts->getPushoverToken() );
			case NotificationChannelRegistry::SLUG_WEBHOOK:
				if ( ! $this->isChannelEnabled( $slug ) ) {
					return false;
				}
				$urls = get_option( PostmanWebhookAlertsNotify::WEBHOOK_OPTION, array() );
				return is_array( $urls ) && count( array_filter( $urls ) ) > 0;
			case NotificationChannelRegistry::SLUG_TEAMS:
				if ( ! $this->isChannelEnabled( $slug ) || ! NotificationChannelRegistry::isTeamsAvailable() ) {
					return false;
				}
				if ( class_exists( 'PSP_MSTeams_Settings' ) ) {
					$settings = new \PSP_MSTeams_Settings();
					$url      = $settings->webhook_url();
					return '' !== $url && filter_var( $url, FILTER_VALIDATE_URL );
				}
				return false;
			case NotificationChannelRegistry::SLUG_TWILIO:
				if ( ! $this->isChannelEnabled( $slug ) || ! NotificationChannelRegistry::isTwilioAvailable() ) {
					return false;
				}
				if ( class_exists( '\PostSMTPTwilio\Settings' ) ) {
					$settings = new \PostSMTPTwilio\Settings();
					return '' !== $settings->get_sid()
						&& '' !== $settings->get_token()
						&& '' !== $settings->get_send_to()
						&& '' !== $settings->get_msg_service_sid();
				}
				return false;
			default:
				return false;
		}
	}

	private function legacyChannelEnabled( string $slug ): bool {
		$legacy = $this->getLegacyOptions();
		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_ADMIN_EMAIL:
				return $this->legacyAdminEmailEnabled( $legacy );
			case NotificationChannelRegistry::SLUG_CHROME:
				return ! empty( $legacy['notification_use_chrome'] );
			case NotificationChannelRegistry::SLUG_SLACK:
				return ( $legacy['notification_service'] ?? '' ) === 'slack';
			case NotificationChannelRegistry::SLUG_PUSHOVER:
				return ( $legacy['notification_service'] ?? '' ) === 'pushover';
			case NotificationChannelRegistry::SLUG_WEBHOOK:
				return ( $legacy['notification_service'] ?? '' ) === 'webhook_alerts';
			case NotificationChannelRegistry::SLUG_TEAMS:
				return ( $legacy['notification_service'] ?? '' ) === 'microsoft-teams';
			case NotificationChannelRegistry::SLUG_TWILIO:
				return ( $legacy['notification_service'] ?? '' ) === 'twilio';
			default:
				return false;
		}
	}

	/**
	 * @param array<string,mixed> $legacy
	 */
	private function legacyAdminEmailEnabled( array $legacy ): bool {
		if ( CohortResolver::COHORT_NEW_INSTALL === CohortResolver::current() ) {
			return false;
		}

		$service = isset( $legacy['notification_service'] )
			? (string) $legacy['notification_service']
			: PostmanNotifyOptions::DEFAULT_NOTIFICATION_SERVICE;

		if ( 'none' === $service ) {
			return false;
		}

		return 'default' === $service;
	}

	/**
	 * @param array<string,mixed> $legacy
	 */
	private function legacyStoredAdminEmail( array $legacy ): string {
		if ( isset( $legacy['notification_email'] ) ) {
			return sanitize_text_field( (string) $legacy['notification_email'] );
		}
		return '';
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getLegacyOptions(): array {
		$options = get_option( 'postman_options', array() );
		return is_array( $options ) ? $options : array();
	}

	private function syncLegacyAdminEmail( bool $enabled, string $email ): void {
		$options = $this->getLegacyOptions();

		if ( $enabled ) {
			$options['notification_service'] = 'default';
		} elseif ( ( $options['notification_service'] ?? '' ) === 'default' ) {
			$options['notification_service'] = 'none';
		}

		$options['notification_email'] = $email;
		update_option( 'postman_options', $options, false );
	}
}
