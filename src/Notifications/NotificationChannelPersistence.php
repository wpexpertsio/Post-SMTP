<?php
/**
 * Extended channel get/save helpers for NotificationSettingsRepository.
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait NotificationChannelPersistence {

	/**
	 * @return array<string,mixed>
	 */
	public function getChannelSettings( string $slug ): array {
		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_ADMIN_EMAIL:
				$settings = $this->getAdminEmailSettings();
				return array(
					'enabled'         => ! empty( $settings['enabled'] ),
					'email'           => (string) ( $settings['email'] ?? '' ),
					'effective_email' => $this->resolveEffectiveAdminEmail( (string) ( $settings['email'] ?? '' ) ),
					'wp_admin_email'  => sanitize_email( (string) get_option( 'admin_email' ) ),
				);
			case NotificationChannelRegistry::SLUG_CHROME:
				return $this->getChromeSettingsForApi();
			case NotificationChannelRegistry::SLUG_SLACK:
				return $this->getSlackSettingsForApi();
			case NotificationChannelRegistry::SLUG_PUSHOVER:
				return $this->getPushoverSettingsForApi();
			case NotificationChannelRegistry::SLUG_WEBHOOK:
				return $this->getWebhookSettingsForApi();
			case NotificationChannelRegistry::SLUG_TEAMS:
				return NotificationTeamsPanelHooks::getPanelSettingsForApi();
			case NotificationChannelRegistry::SLUG_TWILIO:
				return NotificationTwilioPanelHooks::getPanelSettingsForApi();
			default:
				$schema = apply_filters( 'post_smtp_notification_channel_schema', array(), $slug );
				return is_array( $schema ) ? $schema : array();
		}
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	public function saveChannelSettings( string $slug, array $payload ): array {
		if ( NotificationChannelRegistry::SLUG_TEAMS === $slug && NotificationTeamsPanelHooks::isPanelLocked() ) {
			return NotificationTeamsPanelHooks::blockedSaveResponse( $payload );
		}

		if ( NotificationChannelRegistry::SLUG_TWILIO === $slug && NotificationTwilioPanelHooks::isPanelLocked() ) {
			return NotificationTwilioPanelHooks::blockedSaveResponse( $payload );
		}

		/**
		 * Short-circuit channel save (Pro Teams/Twilio).
		 *
		 * @param array|null          $result  Return array to short-circuit.
		 * @param array<string,mixed> $payload Request payload.
		 */
		$pro_result = apply_filters( "post_smtp_notification_save_{$slug}", null, $payload );
		if ( is_array( $pro_result ) && isset( $pro_result['success'] ) ) {
			return $pro_result;
		}

		switch ( $slug ) {
			case NotificationChannelRegistry::SLUG_ADMIN_EMAIL:
				return $this->saveAdminEmailSettings(
					array(
						'enabled' => ! empty( $payload['enabled'] ),
						'email'   => isset( $payload['email'] ) ? (string) $payload['email'] : '',
					)
				);
			case NotificationChannelRegistry::SLUG_CHROME:
				return $this->saveChromeSettings( $payload );
			case NotificationChannelRegistry::SLUG_SLACK:
				return $this->saveSlackSettings( $payload );
			case NotificationChannelRegistry::SLUG_PUSHOVER:
				return $this->savePushoverSettings( $payload );
			case NotificationChannelRegistry::SLUG_WEBHOOK:
				return $this->saveWebhookSettings( $payload );
			default:
				return array(
					'success' => false,
					'message' => __( 'This notification channel cannot be saved yet.', 'post-smtp' ),
				);
		}
	}

	private function persistChannelRow( string $slug, array $row ): void {
		$channels         = $this->getStoredChannels();
		$channels[ $slug ] = array_merge( $channels[ $slug ] ?? array(), $row );
		update_option( NotificationSettingsRepository::OPTION_CHANNELS, $channels, false );
	}

	private function encodeOptionSecret( string $plain ): string {
		return base64_encode( sanitize_text_field( $plain ) );
	}

	private function updateLegacyOptions( callable $mutator ): void {
		$options = $this->getLegacyOptions();
		$mutator( $options );
		update_option( 'postman_options', $options, false );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getChromeSettingsForApi(): array {
		$enabled = $this->isChannelEnabled( NotificationChannelRegistry::SLUG_CHROME );
		$uid     = NotificationLegacyOptions::getChromeUid();
		return array(
			'enabled'   => $enabled,
			'has_uid'   => '' !== $uid,
			'uid_masked' => '' !== $uid ? NotificationSecretMask::mask( $uid ) : '',
			'chrome_extension_url' => 'https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch',
			'doc_url'   => 'https://postmansmtp.com/docs/alerts/chrome-extension/',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	private function saveChromeSettings( array $payload ): array {
		$enabled = ! empty( $payload['enabled'] );
		$uid     = isset( $payload['uid'] ) ? trim( (string) $payload['uid'] ) : null;

		$this->persistChannelRow(
			NotificationChannelRegistry::SLUG_CHROME,
			array( 'enabled' => $enabled )
		);

		$this->updateLegacyOptions(
			function ( array &$options ) use ( $enabled, $uid ) {
				$options['notification_use_chrome'] = $enabled ? '1' : '';
				if ( null !== $uid && ! NotificationSecretMask::isMaskedPlaceholder( $uid ) ) {
					if ( '' === $uid ) {
						unset( $options['notification_chrome_uid'] );
					} else {
						$options['notification_chrome_uid'] = $this->encodeOptionSecret( $uid );
					}
				}
			}
		);

		return $this->successSave( $this->getChromeSettingsForApi() );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getSlackSettingsForApi(): array {
		$token = NotificationLegacyOptions::getSlackWebhookUrl();
		return array(
			'enabled'        => $this->isChannelEnabled( NotificationChannelRegistry::SLUG_SLACK ),
			'has_webhook'    => '' !== $token,
			'webhook_masked' => '' !== $token ? NotificationSecretMask::mask( $token ) : '',
			'webhook_helper_url' => 'https://slack.postmansmtp.com/',
			'doc_url'        => 'https://postmansmtp.com/docs/alerts/slack/',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	private function saveSlackSettings( array $payload ): array {
		$enabled = ! empty( $payload['enabled'] );
		$webhook = isset( $payload['webhook'] ) ? trim( (string) $payload['webhook'] ) : null;

		$this->persistChannelRow(
			NotificationChannelRegistry::SLUG_SLACK,
			array( 'enabled' => $enabled )
		);

		$this->updateLegacyOptions(
			function ( array &$options ) use ( $enabled, $webhook ) {
				if ( $enabled ) {
					$options['notification_service'] = 'slack';
				} elseif ( ( $options['notification_service'] ?? '' ) === 'slack' ) {
					$options['notification_service'] = 'none';
				}
				if ( null !== $webhook && ! NotificationSecretMask::isMaskedPlaceholder( $webhook ) && '' !== $webhook ) {
					$options['slack_token'] = $this->encodeOptionSecret( $webhook );
				}
			}
		);

		return $this->successSave( $this->getSlackSettingsForApi() );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getPushoverSettingsForApi(): array {
		$user  = NotificationLegacyOptions::getPushoverUser();
		$token = NotificationLegacyOptions::getPushoverToken();
		return array(
			'enabled'       => $this->isChannelEnabled( NotificationChannelRegistry::SLUG_PUSHOVER ),
			'has_user'      => '' !== $user,
			'has_token'     => '' !== $token,
			'user_masked'   => '' !== $user ? NotificationSecretMask::mask( $user ) : '',
			'token_masked'  => '' !== $token ? NotificationSecretMask::mask( $token ) : '',
			'doc_url'       => 'https://postmansmtp.com/docs/alerts/pushover/',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	private function savePushoverSettings( array $payload ): array {
		$enabled = ! empty( $payload['enabled'] );
		$user    = isset( $payload['user'] ) ? trim( (string) $payload['user'] ) : null;
		$token   = isset( $payload['token'] ) ? trim( (string) $payload['token'] ) : null;

		$this->persistChannelRow(
			NotificationChannelRegistry::SLUG_PUSHOVER,
			array( 'enabled' => $enabled )
		);

		$this->updateLegacyOptions(
			function ( array &$options ) use ( $enabled, $user, $token ) {
				if ( $enabled ) {
					$options['notification_service'] = 'pushover';
				} elseif ( ( $options['notification_service'] ?? '' ) === 'pushover' ) {
					$options['notification_service'] = 'none';
				}
				if ( null !== $user && ! NotificationSecretMask::isMaskedPlaceholder( $user ) && '' !== $user ) {
					$options['pushover_user'] = $this->encodeOptionSecret( $user );
				}
				if ( null !== $token && ! NotificationSecretMask::isMaskedPlaceholder( $token ) && '' !== $token ) {
					$options['pushover_token'] = $this->encodeOptionSecret( $token );
				}
			}
		);

		return $this->successSave( $this->getPushoverSettingsForApi() );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getWebhookSettingsForApi(): array {
		$urls = array_values( array_filter( array_map( 'esc_url_raw', NotificationLegacyOptions::getWebhookUrls() ) ) );
		if ( empty( $urls ) ) {
			$urls = array( '' );
		}
		return array(
			'enabled' => $this->isChannelEnabled( NotificationChannelRegistry::SLUG_WEBHOOK ),
			'urls'    => $urls,
			'doc_url' => 'https://postmansmtp.com/docs/alerts/webhook/',
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string,settings?:array<string,mixed>}
	 */
	private function saveWebhookSettings( array $payload ): array {
		$enabled = ! empty( $payload['enabled'] );
		$urls    = isset( $payload['urls'] ) && is_array( $payload['urls'] ) ? $payload['urls'] : array();

		$clean = array();
		foreach ( $urls as $url ) {
			$url = esc_url_raw( trim( (string) $url ) );
			if ( '' !== $url ) {
				$clean[] = $url;
			}
		}

		$this->persistChannelRow(
			NotificationChannelRegistry::SLUG_WEBHOOK,
			array( 'enabled' => $enabled )
		);

		update_option( NotificationLegacyOptions::WEBHOOK_URLS_OPTION, $clean, false );

		$this->updateLegacyOptions(
			function ( array &$options ) use ( $enabled ) {
				if ( $enabled ) {
					$options['notification_service'] = 'webhook_alerts';
				} elseif ( ( $options['notification_service'] ?? '' ) === 'webhook_alerts' ) {
					$options['notification_service'] = 'none';
				}
			}
		);

		return $this->successSave( $this->getWebhookSettingsForApi() );
	}

	/**
	 * @param array<string,mixed> $settings
	 * @return array{success:bool,message:string,settings:array<string,mixed>}
	 */
	private function successSave( array $settings ): array {
		return array(
			'success'  => true,
			'message'  => __( 'Notification settings saved.', 'post-smtp' ),
			'settings' => $settings,
		);
	}
}
