import { Navigate, Route, Routes } from 'react-router-dom';
import ToolsScreen from './ToolsScreen';
import { DEFAULT_TOOL_SLUG } from './toolsNav';

const ToolsRoutes = () => (
	<Routes>
		<Route index element={ <Navigate to={ DEFAULT_TOOL_SLUG } replace /> } />
		<Route path=":toolSlug" element={ <ToolsScreen /> } />
		<Route path="*" element={ <Navigate to={ DEFAULT_TOOL_SLUG } replace /> } />
	</Routes>
);

export default ToolsRoutes;
