<?php
/**
 * Routes admin UI, storage, and mail pipeline by cohort.
 *
 * @package PostSMTP\Bootstrap
 */

namespace PostSMTP\Bootstrap;

use PostSMTP\Admin\LegacyAdminLoader;
use PostSMTP\Admin\LegacyRevertPage;
use PostSMTP\Admin\MigrationNotice;
use PostSMTP\Admin\ReactAdminLoader;
use PostSMTP\Mail\MailPipeline;
use PostSMTP\Rollout\CohortResolver;
use PostSMTP\Rollout\FeatureFlags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Router {

	public static function boot(): void {
		if ( CohortResolver::usesReactAdmin() ) {
			ReactAdminLoader::init();
			LegacyRevertPage::init();
		} else {
			LegacyAdminLoader::init();
			MigrationNotice::init();
			ReactAdminLoader::initMigrationWizard();
		}

		add_filter( 'postman_wp_mail_bind_status', array( self::class, 'mailBindStatus' ) );
	}

	public static function usesLegacyStorage(): bool {
		return \PostSMTP\Kernel\Data\ConnectionRepositoryFactory::isLegacyMode();
	}

	public static function usesLegacyAdmin(): bool {
		return CohortResolver::usesLegacyAdmin();
	}

	public static function usesLegacyMailPipeline(): bool {
		return ! FeatureFlags::enabled( 'new_mail_pipeline' );
	}

	/**
	 * @param mixed $status Existing bind status.
	 * @return mixed
	 */
	public static function mailBindStatus( $status ) {
		if ( self::usesLegacyMailPipeline() ) {
			return $status;
		}

		return MailPipeline::class;
	}
}
