import { NavLink } from 'react-router-dom';

const ConnectionsSubnav = () => (
	<nav className="ps-v2-conn-subnav" aria-label="Connections sections">
		<NavLink to="/connections" end className="ps-v2-conn-subnav__link">
			Connections
		</NavLink>
		<NavLink to="/connections/add" className="ps-v2-conn-subnav__link">
			Add Connection
		</NavLink>
	</nav>
);

export default ConnectionsSubnav;
