import { HashRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AppShell from './components/layout/AppShell';
import AdminRouteSync from './components/layout/AdminRouteSync';
import DashboardScreen from './screens/dashboard/DashboardScreen';
import ConnectionsRoutes from './screens/connections/ConnectionsRoutes';
import EmailLogsRoutes from './screens/email-logs/EmailLogsRoutes';
import NotificationsRoutes from './screens/notifications/NotificationsRoutes';
import ToolsRoutes from './screens/tools/ToolsRoutes';
import MobileAppScreen from './screens/mobile-app/MobileAppScreen';
import HelpDocsScreen from './screens/help-docs/HelpDocsScreen';
import ReportsScreen from './screens/reports/ReportsScreen';
import SettingsRoutes from './screens/settings/SettingsRoutes';
import MigrationWizardShell from './screens/migration/MigrationWizardShell';
import { HeaderNotificationsProvider } from './context/HeaderNotificationsContext';
import './styles.css';

const queryClient = new QueryClient();
const config = window.postSmtpAdmin || {};
const dashboardUrl = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';

const FullApp = () => (
	<>
		<AdminRouteSync />
		<Routes>
		<Route
			path="/"
			element={
				<AppShell title="Dashboard" showEyebrow={ false } hideTitle>
					<DashboardScreen />
				</AppShell>
			}
		/>
		<Route
			path="/connections/*"
			element={
				<AppShell title="Connections" hideTitle mainClassName="ps-app__main--connections">
					<ConnectionsRoutes />
				</AppShell>
			}
		/>
		<Route
			path="/email-logs"
			element={
				<AppShell title="Email Log" hideTitle mainClassName="ps-app__main--email-logs">
					<EmailLogsRoutes />
				</AppShell>
			}
		/>
		<Route
			path="/notifications/*"
			element={
				<AppShell title="Notifications" hideTitle mainClassName="ps-app__main--notifications">
					<NotificationsRoutes />
				</AppShell>
			}
		/>
		<Route
			path="/tools/*"
			element={
				<AppShell title="Tools" hideTitle mainClassName="ps-app__main--tools">
					<ToolsRoutes />
				</AppShell>
			}
		/>
		<Route
			path="/mobile-app"
			element={
				<AppShell title="Mobile App" hideTitle mainClassName="ps-app__main--mobile-app">
					<MobileAppScreen />
				</AppShell>
			}
		/>
		<Route
			path="/help-docs"
			element={
				<AppShell title="Help and Docs" hideTitle mainClassName="ps-app__main--help-docs">
					<HelpDocsScreen />
				</AppShell>
			}
		/>
		<Route
			path="/reports"
			element={
				<AppShell title="Reports" hideTitle mainClassName="ps-app__main--reports">
					<ReportsScreen />
				</AppShell>
			}
		/>
		<Route
			path="/settings/*"
			element={
				<AppShell title="Settings" hideTitle mainClassName="ps-app__main--settings">
					<SettingsRoutes />
				</AppShell>
			}
		/>
		<Route path="/migration" element={ <Navigate to="/" replace /> } />
		<Route path="*" element={ <Navigate to="/" replace /> } />
		</Routes>
	</>
);

const App = () => (
	<QueryClientProvider client={ queryClient }>
		<HeaderNotificationsProvider>
			<Router>
				{ config.migrationWizard ? <MigrationWizardShell /> : <FullApp /> }
			</Router>
		</HeaderNotificationsProvider>
	</QueryClientProvider>
);

export { dashboardUrl };
export default App;
