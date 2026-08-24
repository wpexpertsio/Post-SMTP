<?php
/**
 * Resolves which customer cohort the current site belongs to.
 *
 * @package PostSMTP\Rollout
 */

namespace PostSMTP\Rollout;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CohortResolver {

	public const COHORT_LEGACY      = 'legacy';
	public const COHORT_NEW_INSTALL = 'new_install';
	public const COHORT_MIGRATED    = 'migrated';

	/**
	 * Detect cohort on first bootstrap.
	 */
	public static function detectOnBootstrap(): void {
		if ( false === get_option( 'post_smtp_cohort', false ) ) {
			if ( false === get_option( 'postman_db_version', false ) ) {
				ConnectionRepositoryFactory::bootstrapNewInstall();
				update_option( 'post_smtp_cohort', self::COHORT_NEW_INSTALL );
				return;
			}

			update_option( 'post_smtp_cohort', self::COHORT_LEGACY );
		}

		self::syncCohortFromStorage();
	}

	/**
	 * Sites already on modern storage should use the new admin experience.
	 */
	public static function syncCohortFromStorage(): void {
		if ( self::current() !== self::COHORT_LEGACY ) {
			return;
		}

		if ( ! ConnectionRepositoryFactory::isLegacyMode() ) {
			self::markMigrated();
		}
	}

	public static function markMigrated(): void {
		update_option( 'post_smtp_cohort', self::COHORT_MIGRATED );
	}

	public static function current(): string {
		$cohort = get_option( 'post_smtp_cohort', self::COHORT_LEGACY );
		return is_string( $cohort ) ? $cohort : self::COHORT_LEGACY;
	}

	public static function usesReactAdmin(): bool {
		return in_array(
			self::current(),
			array( self::COHORT_NEW_INSTALL, self::COHORT_MIGRATED ),
			true
		);
	}

	/**
	 * Legacy cohort sites that still need schema migration.
	 */
	public static function needsMigration(): bool {
		return self::current() === self::COHORT_LEGACY && ConnectionRepositoryFactory::isLegacyMode();
	}

	public static function usesLegacyAdmin(): bool {
		return ! self::usesReactAdmin();
	}
}
