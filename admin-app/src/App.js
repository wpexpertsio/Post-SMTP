import { HashRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AppShell from './components/layout/AppShell';
import DashboardScreen from './screens/dashboard/DashboardScreen';
import ConnectionsRoutes from './screens/connections/ConnectionsRoutes';
import EmailLogsRoutes from './screens/email-logs/EmailLogsRoutes';
import NotificationsRoutes from './screens/notifications/NotificationsRoutes';
import ToolsRoutes from './screens/tools/ToolsRoutes';
import MigrationScreen from './screens/migration/MigrationScreen';
import './styles.css';

const queryClient = new QueryClient();
const config = window.postSmtpAdmin || {};
const dashboardUrl = config.adminUrls?.dashboard || '/wp-admin/admin.php?page=postman';

const MigrationOnlyApp = () => (
	<div className="ps-app ps-app--migration-only">
		<main className="ps-app__main">
			<div className="ps-page-title">
				<span className="ps-page-title__eyebrow">Post SMTP</span>
				<h1>Guided migration</h1>
			</div>
			<MigrationScreen />
		</main>
	</div>
);

const FullApp = () => (
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
		<Route path="/migration" element={ <Navigate to="/" replace /> } />
		<Route path="*" element={ <Navigate to="/" replace /> } />
	</Routes>
);

const App = () => (
	<QueryClientProvider client={ queryClient }>
		<Router>
			{ config.migrationOnly ? <MigrationOnlyApp /> : <FullApp /> }
		</Router>
	</QueryClientProvider>
);

export { dashboardUrl };
export default App;
