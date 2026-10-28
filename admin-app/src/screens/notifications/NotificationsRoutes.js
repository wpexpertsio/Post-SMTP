import { Navigate, Route, Routes } from 'react-router-dom';
import NotificationsScreen from './NotificationsScreen';

const NotificationsRoutes = () => (
	<Routes>
		<Route index element={ <Navigate to="admin_email" replace /> } />
		<Route path=":channelSlug" element={ <NotificationsScreen /> } />
		<Route path="*" element={ <Navigate to="admin_email" replace /> } />
	</Routes>
);

export default NotificationsRoutes;
