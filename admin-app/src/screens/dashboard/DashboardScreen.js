import { useState } from '@wordpress/element';
import { getAdminConfig } from '../../config/admin';
import { useDashboardData } from '../../hooks/useDashboardData';
import ActiveMailersSidebar from './components/v2/ActiveMailersSidebar';
import DomainAuthSidebar from './components/v2/DomainAuthSidebar';
import GmailPromoBar from './components/v2/GmailPromoBar';
import OverviewSection from './components/v2/OverviewSection';
import ProUpgradeBanner from './components/v2/ProUpgradeBanner';
import RecentEmailsTable from './components/v2/RecentEmailsTable';
import SetupWizardBanner from './components/v2/SetupWizardBanner';

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-4911
 */
const DashboardScreen = () => {
	const config = getAdminConfig();
	const [ period, setPeriod ] = useState( 'week' );
	const { stats, logs, connections, queries } = useDashboardData( period );

	return (
		<div className="ps-dashboard-v2">
			<SetupWizardBanner />

			<div className="ps-dashboard-v2__grid">
				<div className="ps-dashboard-v2__main">
					<OverviewSection
						period={ period }
						onPeriodChange={ setPeriod }
						stats={ stats }
						statsQuery={ queries.stats }
						isPro={ Boolean( config.isPro ) }
					/>
					<GmailPromoBar />
					<RecentEmailsTable logs={ logs } logsQuery={ queries.logs } />
					{ ! config.isPro && <ProUpgradeBanner /> }
				</div>

				<aside className="ps-dashboard-v2__sidebar">
					<DomainAuthSidebar />
					<ActiveMailersSidebar connections={ connections } connectionsQuery={ queries.connections } />
				</aside>
			</div>
		</div>
	);
};

export default DashboardScreen;
