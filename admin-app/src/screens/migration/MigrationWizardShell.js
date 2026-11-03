import TopNav from '../../components/layout/TopNav';
import DashboardScreen from '../dashboard/DashboardScreen';
import MigrationModal from './MigrationModal';
import { useMigrationWizardLock } from './useMigrationWizardLock';

/**
 * Figma 985:15372 — blurred dashboard + full-screen lock overlay + modal (952:15291).
 */
const MigrationWizardShell = () => {
	useMigrationWizardLock( true );

	return (
		<div className="ps-app ps-app--migration-wizard ps-app--migration-locked">
			<div className="ps-migration-wizard__underlay" inert={ true } aria-hidden="true">
				<TopNav />
				<main className="ps-app__main ps-app__main--flush ps-migration-wizard__backdrop">
					<DashboardScreen />
				</main>
			</div>
			<div className="ps-migration-overlay" role="presentation">
				<MigrationModal />
			</div>
		</div>
	);
};

export default MigrationWizardShell;
