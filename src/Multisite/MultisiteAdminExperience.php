<?php
/**
 * Keep child-site admin cohort aligned with main site (revamp vs legacy).
 *
 * @package PostSMTP\Multisite
 */

namespace PostSMTP\Multisite;

use PostSMTP\Kernel\Data\ConnectionRepositoryFactory;
use PostSMTP\Rollout\CohortResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MultisiteAdminExperience {

	public static function init(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'plugins_loaded', array( self::class, 'alignChildCohortEarly' ), 18 );
		add_action( 'init', array( self::class, 'alignChildCohortEarly' ), 1 );
	}

	/**
	 * After sync (or when storage is modern), child blogs should load the same revamp admin as the main site.
	 */
	public static function alignChildCohortForCurrentBlog(): void {
		if ( ! is_multisite() || MultisitePolicy::isNetworkMainSite() ) {
			return;
		}

		if ( CohortResolver::usesReactAdmin() ) {
			return;
		}

		$main_id     = MultisitePolicy::getMainSiteBlogId();
		$main_cohort = get_blog_option( $main_id, 'post_smtp_cohort', CohortResolver::COHORT_LEGACY );
		if ( ! is_string( $main_cohort ) ) {
			$main_cohort = CohortResolver::COHORT_LEGACY;
		}

		if ( in_array( $main_cohort, array( CohortResolver::COHORT_MIGRATED, CohortResolver::COHORT_NEW_INSTALL ), true ) ) {
			self::applyCohortToCurrentBlog( $main_cohort );
			return;
		}

		if ( self::childHasModernMailerStorage() ) {
			CohortResolver::markMigrated();
		}
	}

	public static function alignChildCohortEarly(): void {
		self::alignChildCohortForCurrentBlog();
		CohortResolver::syncCohortFromStorage();
	}

	private static function childHasModernMailerStorage(): bool {
		return ! ConnectionRepositoryFactory::isLegacyMode();
	}

	private static function applyCohortToCurrentBlog( string $main_cohort ): void {
		if ( CohortResolver::COHORT_NEW_INSTALL === $main_cohort ) {
			update_option( 'post_smtp_cohort', CohortResolver::COHORT_NEW_INSTALL, false );
			return;
		}

		CohortResolver::markMigrated();
	}
}