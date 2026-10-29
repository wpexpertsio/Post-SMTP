import { Navigate, Route, Routes } from 'react-router-dom';
import SettingsScreen from './SettingsScreen';
import { DEFAULT_SETTINGS_SLUG } from './settingsNav';

const SettingsRoutes = () => (
	<Routes>
		<Route index element={ <Navigate to={ DEFAULT_SETTINGS_SLUG } replace /> } />
		<Route path=":settingsSlug" element={ <SettingsScreen /> } />
		<Route path="*" element={ <Navigate to={ DEFAULT_SETTINGS_SLUG } replace /> } />
	</Routes>
);

export default SettingsRoutes;
