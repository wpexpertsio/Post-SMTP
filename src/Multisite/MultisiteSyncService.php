<?php
/**
 * Copy main-site mailer configuration to child blogs (multisite sync).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

use PostSMTP\Connections\ConnectionLegacySendBridge;
use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Kernel\Data\ConnectionSchema;
use PostSMTP\Kernel\Data\SecretCodec;
use PostSMTP\Kernel\Providers\ProviderRegistry;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteSyncService {

	private const OAUTH_OPTION_KEYS = array(
		'postman_auth_token',
		'postman_office365_oauth',
	);

	/**
	 * @return array<string,mixed>
	 */
	public function getStatus(): array {
		$main_id = MultisitePolicy::getMainSiteBlogId();
		$sites   = array();
		$main_ok = $this->isSiteMailerConfigured( $main_id );

		foreach ( get_sites( array( 'number' => 500 ) ) as $site ) {
			$blog_id = (int) $site->blog_id;
			if ( $blog_id === $main_id ) {
				continue;
			}

			$sites[] = array(
				'blog_id'   => $blog_id,
				'name'      => get_blog_option( $blog_id, 'blogname' ),
				'url'       => get_site_url( $blog_id ),
				'synced'    => MultisitePolicy::isChildSyncedWithMain( $blog_id ),
				'synced_at' => (int) get_blog_option( $blog_id, MultisitePolicy::OPTION_SYNCED_AT, 0 ),
				'configured'=> $this->isSiteMailerConfigured( $blog_id ),
			);
		}

		return array(
			'main_configured' => $main_ok,
			'main_blog_id'    => $main_id,
			'global_enabled'  => MultisitePolicy::isMultisiteGlobalSettingsEnabled(),
			'sites'           => $sites,
		);
	}

	/**
	 * @return array{success:bool,message?:string,success_count?:int,failed_count?:int,sites?:array}
	 */
	public function syncAllChildSites(): array {
		if ( ! is_multisite() ) {
			return array(
				'success' => false,
				'message' => __( 'Multisite is not enabled.', 'post-smtp' ),
			);
		}

		if ( ! MultisitePolicy::isMultisiteGlobalSettingsEnabled() ) {
			return array(
				'success' => false,
				'message' => __( 'Enable global settings on the network before syncing.', 'post-smtp' ),
			);
		}

		$main_id = MultisitePolicy::getMainSiteBlogId();
		if ( ! $this->isSiteMailerConfigured( $main_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Configure mail on the main site before syncing child sites.', 'post-smtp' ),
			);
		}

		$snapshot = $this->readSnapshotFromBlog( $main_id );
		if ( empty( $snapshot['success'] ) ) {
			return array(
				'success' => false,
				'message' => $snapshot['message'] ?? __( 'Could not read main site configuration.', 'post-smtp' ),
			);
		}

		$success_count = 0;
		$failed_count  = 0;
		$site_results  = array();

		add_filter( 'post_smtp_multisite_sync_in_progress', '__return_true', 100 );

		try {
			foreach ( get_sites( array( 'number' => 500 ) ) as $site ) {
				$blog_id = (int) $site->blog_id;
				if ( $blog_id === $main_id ) {
					continue;
				}

				$result = $this->copySnapshotToBlog( $blog_id, $snapshot );
				$site_results[] = array(
					'blog_id' => $blog_id,
					'success' => ! empty( $result['success'] ),
					'message' => $result['message'] ?? '',
				);

				if ( ! empty( $result['success'] ) ) {
					++$success_count;
				} else {
					++$failed_count;
				}
			}
		} finally {
			remove_filter( 'post_smtp_multisite_sync_in_progress', '__return_true', 100 );
		}

		return array(
			'success'       => $failed_count === 0,
			'message'       => sprintf(
				/* translators: 1: success count, 2: failed count */
				__( 'Sync finished. %1$d succeeded, %2$d failed.', 'post-smtp' ),
				$success_count,
				$failed_count
			),
			'success_count' => $success_count,
			'failed_count'  => $failed_count,
			'sites'         => $site_results,
		);
	}

	public static function isSiteMailerConfigured( int $blog_id ): bool {
		if ( $blog_id <= 0 ) {
			return false;
		}

		$switched = false;
		if ( get_current_blog_id() !== $blog_id ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		try {
			if ( ConnectionRepositoryFactory::isLegacyMode() ) {
				if ( class_exists( '\PostmanTransportRegistry' ) && class_exists( '\PostmanOptions' ) ) {
					$registry = \PostmanTransportRegistry::getInstance();
					$active   = $registry->getActiveTransport();
					return $active && $active->isConfiguredAndReady();
				}
				return false;
			}

			$repo        = ConnectionRepositoryFactory::make();
			$connections = $repo->getConnections();
			if ( empty( $connections ) || ! is_array( $connections ) ) {
				return false;
			}

			$registry = new ProviderRegistry();

			foreach ( $connections as $stored ) {
				if ( ! is_array( $stored ) ) {
					continue;
				}

				$row = SecretCodec::decodeConnectionRow( $stored );
				$slug = (string) ( $row['provider'] ?? $row['transport_type'] ?? '' );
				if ( '' === trim( $slug ) ) {
					continue;
				}

				$row = ConnectionSchema::sanitizeRow( $row );
				$provider = $registry->create( $slug );
				$auth     = $provider->authenticate( $row );
				if ( ! empty( $auth['success'] ) ) {
					return true;
				}
			}

			return false;
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * @return array{success:bool,message?:string,connections?:array,repo_options?:array,legacy_options?:array,oauth_options?:array,db_version?:string}
	 */
	private function readSnapshotFromBlog( int $blog_id ): array {
		$switched = false;
		if ( get_current_blog_id() !== $blog_id ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		try {
			$repo           = ConnectionRepositoryFactory::make();
			$postman_options = get_option( \PostmanOptions::POSTMAN_OPTIONS, array() );
			if ( ! is_array( $postman_options ) ) {
				$postman_options = array();
			}

			return array(
				'success'          => true,
				'connections'      => $repo->getConnections(),
				'repo_options'     => $repo->getOptions(),
				'legacy_options'   => MailerOptionKeys::extractMailerValues( $postman_options ),
				'logging_options'  => EmailLogOptionKeys::extractValues( $postman_options ),
				'db_version'       => (string) get_option( ConnectionSchema::OPTION_DB_VERSION, '' ),
				'oauth_options'    => $this->readOAuthOptions(),
			);
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * @param array<string,mixed> $snapshot
	 * @return array{success:bool,message?:string}
	 */
	private function copySnapshotToBlog( int $blog_id, array $snapshot ): array {
		$connections_ok = false;
		$legacy_ok      = false;

		switch_to_blog( $blog_id );

		try {
			if ( '' !== (string) ( $snapshot['db_version'] ?? '' ) ) {
				update_option( ConnectionSchema::OPTION_DB_VERSION, $snapshot['db_version'], false );
			}

			$repo = ConnectionRepositoryFactory::make();

			$connections = $snapshot['connections'] ?? array();
			if ( ! is_array( $connections ) ) {
				$connections = array();
			}

			$repo->saveConnections( $connections );
			$connections_ok = $this->arraysMatch( $repo->getConnections(), $connections );

			$child_options = $repo->getOptions();
			if ( ! is_array( $child_options ) ) {
				$child_options = array();
			}

			$mailer_patch = is_array( $snapshot['legacy_options'] ?? null )
				? $snapshot['legacy_options']
				: array();

			$repo_options = is_array( $snapshot['repo_options'] ?? null )
				? $snapshot['repo_options']
				: array();

			foreach ( array( 'selected_primary', 'primary_connection' ) as $control_key ) {
				if ( array_key_exists( $control_key, $repo_options ) ) {
					$child_options[ $control_key ] = $repo_options[ $control_key ];
				}
			}

			$logging_patch = is_array( $snapshot['logging_options'] ?? null )
				? $snapshot['logging_options']
				: array();

			$merged_options = array_merge( $child_options, $mailer_patch, $logging_patch );
			$repo->saveOptions( $merged_options );
			$legacy_ok = $this->optionsMailerLayerMatch( $repo->getOptions(), $merged_options );

			foreach ( self::OAUTH_OPTION_KEYS as $option_name ) {
				if ( empty( $snapshot['oauth_options'][ $option_name ] ) ) {
					continue;
				}
				update_option( $option_name, $snapshot['oauth_options'][ $option_name ], false );
			}

			if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
				ConnectionLegacySendBridge::sync_primary_from_connection_index( 0 );
			}

			if ( class_exists( '\PostmanOptions' ) ) {
				\PostmanOptions::getInstance()->reload();
			}

			if ( ! $connections_ok || ! $legacy_ok ) {
				return array(
					'success' => false,
					'message' => __( 'Could not save mailer data on this site.', 'post-smtp' ),
				);
			}

			MultisiteEmailLogSupport::ensureTablesForCurrentBlog();

			update_option( MultisitePolicy::OPTION_SYNCED, '1', false );
			update_option( MultisitePolicy::OPTION_SYNCED_AT, time(), false );

			MultisiteCapabilities::grantIfNeeded();
			MultisiteAdminExperience::alignChildCohortForCurrentBlog();
			CohortResolver::syncCohortFromStorage();

			return array( 'success' => true );
		} catch ( \Throwable $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @param array<mixed> $a
	 * @param array<mixed> $b
	 */
	private function arraysMatch( array $a, array $b ): bool {
		return $a == $b;
	}

	/**
	 * @param array<string,mixed> $stored
	 * @param array<string,mixed> $expected
	 */
	private function optionsMailerLayerMatch( array $stored, array $expected ): bool {
		foreach ( MailerOptionKeys::keys() as $key ) {
			if ( ! array_key_exists( $key, $expected ) ) {
				continue;
			}
			$have = $stored[ $key ] ?? null;
			$want = $expected[ $key ];
			if ( $have !== $want ) {
				return false;
			}
		}

		foreach ( array( 'selected_primary', 'primary_connection' ) as $key ) {
			if ( array_key_exists( $key, $expected ) ) {
				$have = $stored[ $key ] ?? null;
				$want = $expected[ $key ];
				if ( $have !== $want ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @return array<string,array>
	 */
	private function readOAuthOptions(): array {
		$out = array();
		foreach ( self::OAUTH_OPTION_KEYS as $name ) {
			$value = get_option( $name, array() );
			if ( is_array( $value ) && ! empty( $value ) ) {
				$out[ $name ] = $value;
			}
		}

		return $out;
	}
}