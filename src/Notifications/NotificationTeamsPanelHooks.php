<?php
/**
 * WordPress hooks for the Microsoft Teams notification panel (free vs Pro).
 *
 * @package PostSMTP\Notifications
 */

namespace PostSMTP\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers default Teams panel behavior and documents extension points for Post SMTP Pro.
 *
 * Filters:
 * - post_smtp_notification_teams_panel_locked — bool, default ! class_exists( 'PSP_MSTeams_Settings' )
 * - post_smtp_notification_teams_panel_config — array upgrade_url, upgrade_label (second arg: locked)
 * - post_smtp_notification_teams_panel_settings — full REST settings array (second arg: locked)
 * - post_smtp_notification_teams_panel_locked_save_result — blocked save response (second arg: payload)
 *
 * Actions:
 * - post_smtp_notification_teams_panel_locked_save_attempt — payload when save blocked on locked panel
 */
final class NotificationTeamsPanelHooks {

	public static function init(): void {
		add_filter( 'post_smtp_notification_teams_panel_config', array( self::class, 'defaultPanelConfig' ), 5, 2 );
	}

	/**
	 * @param array<string,mixed> $config
	 * @return array<string,mixed>
	 */
	public static function defaultPanelConfig( array $config, bool $locked ): array {
		unset( $locked );
		return array_merge(
			array(
				'upgrade_url'   => 'https://postmansmtp.com/pricing/',
				'upgrade_label' => __( 'Upgrade to Pro', 'post-smtp' ),
			),
			$config
		);
	}

	public static function isPanelLocked(): bool {
		/**
		 * Whether the Microsoft Teams notification panel is locked (read-only) in the revamp UI.
		 *
		 * @param bool $locked Default true when the Teams extension settings class is unavailable.
		 */
		return (bool) apply_filters(
			'post_smtp_notification_teams_panel_locked',
			! class_exists( 'PSP_MSTeams_Settings' )
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function getPanelSettingsForApi(): array {
		$locked = self::isPanelLocked();

		$defaults = array(
			'enabled'      => false,
			'panel_locked' => $locked,
			'doc_url'      => 'https://postmansmtp.com/docs/alerts/microsoft-teams/',
		);

		/**
		 * Teams panel UI config (upgrade CTA, etc.).
		 *
		 * @param array<string,mixed> $config Config fragments.
		 * @param bool                $locked Whether the panel is locked.
		 */
		$config = apply_filters( 'post_smtp_notification_teams_panel_config', array(), $locked );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$payload = array_merge( $defaults, $config );

		if ( ! $locked ) {
			$schema = apply_filters( 'post_smtp_notification_channel_schema', array(), NotificationChannelRegistry::SLUG_TEAMS );
			if ( is_array( $schema ) ) {
				$payload = array_merge( $payload, $schema, array( 'panel_locked' => false ) );
			}
		}

		/**
		 * Final Teams channel settings for GET /notifications (channel_settings.teams).
		 *
		 * @param array<string,mixed> $settings Merged settings.
		 * @param bool                $locked   Whether the panel is locked.
		 */
		return apply_filters( 'post_smtp_notification_teams_panel_settings', $payload, $locked );
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array{success:bool,message:string}
	 */
	public static function blockedSaveResponse( array $payload ): array {
		/**
		 * Fires when a client attempts to save Teams settings while the panel is locked.
		 *
		 * @param array<string,mixed> $payload Save payload.
		 */
		do_action( 'post_smtp_notification_teams_panel_locked_save_attempt', $payload );

		$blocked = array(
			'success' => false,
			'message' => __( 'Upgrade to Post SMTP Pro to configure Microsoft Teams alerts.', 'post-smtp' ),
		);

		/**
		 * Filter the REST error when saving a locked Teams panel.
		 *
		 * @param array{success:bool,message:string} $blocked Blocked response.
		 * @param array<string,mixed>                $payload Save payload.
		 */
		$filtered = apply_filters( 'post_smtp_notification_teams_panel_locked_save_result', $blocked, $payload );

		return is_array( $filtered ) ? $filtered : $blocked;
	}
}
