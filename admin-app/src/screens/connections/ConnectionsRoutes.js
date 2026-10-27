import { Routes, Route, Navigate } from 'react-router-dom';
import ConnectionsManageView from './views/ConnectionsManageView';
import AddConnectionView from './views/AddConnectionView';
import EditConnectionView from './views/EditConnectionView';

const ConnectionsRoutes = () => (
	<Routes>
		<Route index element={ <ConnectionsManageView /> } />
		<Route path="add" element={ <AddConnectionView /> } />
		<Route path=":id/edit" element={ <EditConnectionView /> } />
		<Route path="*" element={ <Navigate to="/connections" replace /> } />
	</Routes>
);

export default ConnectionsRoutes;
